/*
 * The brick-throwing game of a challenge (challenges/show, App\Services\ChallengeGame). The server is the referee: this
 * page sends Start and every right letter, and draws the game from the state it gets back, from its own requests and
 * from the private channel of the challenge (`.game`, see ChallengeGameUpdated), where it also learns what the other
 * player does. A right letter shows at once (the server confirms it); a wrong one does nothing.
 *
 * Every right letter throws a brick (`throwBrick`): mine at once when I type it, the other player's when the channel says so.
 */
import '../echo';

const ROUND_MESSAGE_MS = 1800;
// How long the box of a wrong letter stays red.
const WRONG_LETTER_MS = 450;
// The throw: how long the arm takes, how far it winds back (per character) and swings forward (degrees), and the share
// of its move at which the brick leaves the hand; then the flight of the brick (in steps of its arc), how high the arc
// goes (share of the stage height), how many turns the brick makes, its fall after it hits, and the shake of the
// character that is hit.
const ARM_MS = 420;
const ARM_WIND_UP = { challenger: 25, opponent: 15 };
const ARM_SWING = 45;
const RELEASE_SHARE = 0.6;
const FLIGHT_MS = 520;
const FLIGHT_STEPS = 16;
const ARC_SHARE = 0.18;
const BRICK_TURNS = 1.5;
const FALL_MS = 380;
const HIT_MS = 320;
// The sounds: how loud the music, the yell of a lost word and the win / lose sound are (0 to 1), and how long the yell
// and the end sound wait, so that they come after the last brick lands.
const MUSIC_VOLUME = 0.35;
const YELL_VOLUME = 0.9;
const ENDING_VOLUME = 0.8;
const YELL_DELAY_MS = 450;
const ENDING_DELAY_MS = 1100;
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const isLetter = (character) => /[\p{L}\p{N}]/u.test(character);
// The letter without its accents, so a Vietnamese keyboard (Telex turns "ee" into "ê") still types the right letter.
const plainLetter = (character) => character.normalize('NFD').replace(/\p{M}/gu, '').replace(/đ/giu, 'd').toLowerCase();
const lettersOf = (word) => [...word].filter(isLetter);

const root = document.querySelector('[data-challenge-game]');

if (root) {
    const config = JSON.parse(root.querySelector('[data-game-config]').textContent);
    const me = config.side;
    const other = me === 'challenger' ? 'opponent' : 'challenger';
    const stage = root.querySelector('[data-stage]');
    const overlay = root.querySelector('[data-overlay]');
    const typing = root.querySelector('[data-typing]');
    const input = root.querySelector('[data-letter-input]');
    const lettersBox = root.querySelector('[data-letters]');
    const roundMessage = root.querySelector('[data-round-message]');
    let state = config.state;
    // My letters of the current word that the server has not answered yet, so its older states do not take them back.
    let pending = 0;
    let localProgress = state.progress[me];
    let queue = Promise.resolve();
    let roundTimer = null;
    let wrongTimer = null;

    const post = (url, body = {}) => fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf },
        body: JSON.stringify(body),
    }).then((response) => (response.ok ? response.json() : Promise.reject(response)));

    // Sounds ------------------------------------------------------------------------------------------------------
    // The music and the yell, win and lose sounds are files (public/games/challenge/sound); the whoosh of a thrown brick and
    // the thud of a hit are synthesised, so no file is needed. Browsers only play sound after a gesture of the visitor:
    // the click on Start, or a key typed on a page reloaded mid-game, wakes it up.

    const music = new Audio(`${config.sounds}/bg.mp3`);
    const yell = new Audio(`${config.sounds}/yell.mp3`);
    const endings = { win: new Audio(`${config.sounds}/win.mp3`), lose: new Audio(`${config.sounds}/lose.mp3`) };
    const muteButton = root.querySelector('[data-mute]');
    let audio = null;
    let isMuted = false;

    music.loop = true;
    music.volume = MUSIC_VOLUME;
    yell.volume = YELL_VOLUME;
    Object.values(endings).forEach((sound) => {
        sound.volume = ENDING_VOLUME;
    });

    const wakeAudio = () => {
        const AudioContext = window.AudioContext ?? window.webkitAudioContext;

        if (AudioContext) {
            audio ??= new AudioContext();

            if (audio.state === 'suspended') {
                audio.resume();
            }
        }

        if (state.status === 'playing' && music.paused && !isMuted) {
            music.play().catch(() => {});
        }
    };

    const playFile = (sound) => {
        if (isMuted) {
            return;
        }

        sound.currentTime = 0;
        sound.play().catch(() => {});
    };

    // A burst of white noise through a filter, `duration` seconds long: the base of the whoosh and of the thud.
    const noise = (duration, { type, from, to, level }) => {
        if (!audio || isMuted) {
            return;
        }

        const length = Math.ceil(audio.sampleRate * duration);
        const buffer = audio.createBuffer(1, length, audio.sampleRate);
        const data = buffer.getChannelData(0);

        for (let index = 0; index < length; index += 1) {
            data[index] = Math.random() * 2 - 1;
        }

        const source = audio.createBufferSource();
        const filter = audio.createBiquadFilter();
        const gain = audio.createGain();
        const at = audio.currentTime;

        source.buffer = buffer;
        filter.type = type;
        filter.frequency.setValueAtTime(from, at);
        filter.frequency.exponentialRampToValueAtTime(to, at + duration);
        gain.gain.setValueAtTime(0.0001, at);
        gain.gain.exponentialRampToValueAtTime(level, at + duration * 0.25);
        gain.gain.exponentialRampToValueAtTime(0.0001, at + duration);
        source.connect(filter).connect(gain).connect(audio.destination);
        source.start(at);
        source.stop(at + duration);
    };

    // The brick leaves the hand: a rising and falling swish.
    const playWhoosh = () => noise(0.28, { type: 'bandpass', from: 500, to: 2400, level: 0.35 });

    // The brick hits: a low knock with a short crunch on top.
    const playThud = () => {
        if (!audio || isMuted) {
            return;
        }

        const at = audio.currentTime;
        const knock = audio.createOscillator();
        const gain = audio.createGain();

        knock.type = 'sine';
        knock.frequency.setValueAtTime(160, at);
        knock.frequency.exponentialRampToValueAtTime(55, at + 0.18);
        gain.gain.setValueAtTime(0.7, at);
        gain.gain.exponentialRampToValueAtTime(0.0001, at + 0.22);
        knock.connect(gain).connect(audio.destination);
        knock.start(at);
        knock.stop(at + 0.24);
        noise(0.12, { type: 'lowpass', from: 2200, to: 300, level: 0.4 });
    };

    // The music plays while the game does; at the end, my page plays the win or the lose sound (nothing for a draw).
    const followGameSound = (previousStatus) => {
        if (state.status === 'playing' && previousStatus !== 'playing' && !isMuted) {
            music.play().catch(() => {});
        }

        if (state.status === 'finished' && previousStatus !== 'finished') {
            music.pause();

            if (state.winner !== null && previousStatus === 'playing') {
                setTimeout(() => playFile(endings[state.winner === me ? 'win' : 'lose']), ENDING_DELAY_MS);
            }
        }
    };

    muteButton.addEventListener('click', () => {
        isMuted = !isMuted;
        music.muted = isMuted;
        yell.muted = isMuted;
        Object.values(endings).forEach((sound) => {
            sound.muted = isMuted;
        });

        if (!isMuted) {
            wakeAudio();
        }

        muteButton.setAttribute('aria-pressed', String(isMuted));
        muteButton.setAttribute('aria-label', isMuted ? 'Bật âm thanh' : 'Tắt âm thanh');
        muteButton.querySelector('[data-mute-on]').hidden = isMuted;
        muteButton.querySelector('[data-mute-off]').hidden = !isMuted;

        if (state.status === 'playing') {
            input.focus({ preventScroll: true });
        }
    });

    // Where a point of an element is on the stage, in pixels from its top left corner.
    const pointOn = (element, xShare, yShare) => {
        const stageBox = stage.getBoundingClientRect();
        const box = element.getBoundingClientRect();

        return { x: box.left - stageBox.left + box.width * xShare, y: box.top - stageBox.top + box.height * yShare, width: box.width, height: box.height };
    };

    // The character is hit: it is pushed back, shakes and flashes.
    const hit = (character, direction) => {
        playThud();
        character.animate([
            { transform: 'translateX(0)', filter: 'none' },
            { transform: `translateX(${direction * 12}px) rotate(${direction * 4}deg)`, filter: 'brightness(1.6) drop-shadow(0 0 8px rgba(255, 70, 50, 0.9))', offset: 0.25 },
            { transform: `translateX(${direction * -6}px)`, offset: 0.55 },
            { transform: `translateX(${direction * 3}px)`, offset: 0.8 },
            { transform: 'translateX(0)', filter: 'none' },
        ], { duration: HIT_MS, easing: 'ease-out' });
    };

    // The character of `side` throws: its arm (the layer cut out of its picture, `data-arm`) winds back, swings over its
    // head and, partway through, lets go of the brick of its hand, which flies in an arc at the other character, hits it,
    // bounces off and falls. The hand holds a new brick once the arm is back. With reduced motion, only the hit.
    const throwBrick = (side) => {
        const target = side === 'challenger' ? 'opponent' : 'challenger';
        const thrower = root.querySelector(`[data-character="${side}"]`);
        const victim = root.querySelector(`[data-character="${target}"]`);
        const arm = thrower.querySelector('[data-arm]');
        const handBrick = arm.querySelector('[data-hand-brick]');
        // 1 when the brick flies to the right (the challenger on the left throws), -1 to the left.
        const direction = side === 'challenger' ? 1 : -1;

        root.dispatchEvent(new CustomEvent('challenge:throw', { detail: { side } }));

        if (prefersReducedMotion) {
            hit(victim, direction);

            return;
        }

        const swing = arm.animate([
            { transform: 'rotate(0)' },
            { transform: `rotate(${-direction * ARM_WIND_UP[side]}deg)`, offset: 0.3, easing: 'ease-in' },
            { transform: `rotate(${direction * ARM_SWING}deg)`, offset: 0.7, easing: 'ease-out' },
            { transform: 'rotate(0)' },
        ], { duration: ARM_MS });

        setTimeout(() => launch(handBrick, victim, direction), ARM_MS * RELEASE_SHARE);
        swing.finished.then(() => {
            handBrick.style.visibility = '';
        }).catch(() => {});
    };

    // The brick leaves the hand where it is now (the arm is moving) and flies straight across while rising and falling (a
    // parabola), spinning on the way; it hits the victim, then bounces off and falls out of sight.
    const launch = (handBrick, victim, direction) => {
        const from = pointOn(handBrick, 0.5, 0.5);
        const to = pointOn(victim, 0.5, 0.35);
        const width = handBrick.offsetWidth;
        const height = handBrick.offsetHeight;
        const arcHeight = stage.clientHeight * ARC_SHARE;
        const brick = document.createElement('img');

        brick.src = handBrick.src;
        brick.alt = '';
        brick.className = 'pointer-events-none absolute top-0 left-0 z-10';
        brick.style.width = `${width}px`;
        stage.append(brick);
        playWhoosh();
        handBrick.style.visibility = 'hidden';

        const at = (progress) => {
            const x = from.x + (to.x - from.x) * progress - width / 2;
            const y = from.y + (to.y - from.y) * progress - arcHeight * 4 * progress * (1 - progress) - height / 2;

            return `translate(${x}px, ${y}px) rotate(${direction * BRICK_TURNS * 360 * progress}deg) scaleX(${direction})`;
        };
        const flight = Array.from({ length: FLIGHT_STEPS + 1 }, (_, step) => ({ transform: at(step / FLIGHT_STEPS) }));
        const landed = `translate(${to.x - width / 2 - direction * stage.clientWidth * 0.06}px, ${to.y + stage.clientHeight * 0.3}px) rotate(${direction * (BRICK_TURNS * 360 + 300)}deg) scaleX(${direction})`;

        brick.animate(flight, { duration: FLIGHT_MS, easing: 'linear' }).finished
            .then(() => {
                hit(victim, direction);

                return brick.animate([
                    { transform: at(1), opacity: 1 },
                    { transform: landed, opacity: 0 },
                ], { duration: FALL_MS, easing: 'cubic-bezier(0.5, 0, 1, 1)', fill: 'forwards' }).finished;
            })
            .catch(() => {})
            .finally(() => brick.remove());
    };

    const showPanel = (name) => {
        overlay.hidden = name === null;
        overlay.querySelectorAll('[data-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.panel !== name;
        });
    };

    const renderHealth = () => {
        ['challenger', 'opponent'].forEach((side) => {
            const health = state.health[side] ?? state.maxHealth;
            const share = state.maxHealth ? health / state.maxHealth : 1;
            const bar = root.querySelector(`[data-health-bar="${side}"]`);

            bar.style.width = `${share * 100}%`;
            bar.classList.toggle('bg-success', share > 0.4);
            bar.classList.toggle('bg-danger', share <= 0.4);
            // No number on the scene: the bar only, with the health for a hover and for screen readers.
            bar.parentElement.title = state.maxHealth ? `${health}/${state.maxHealth} máu` : '';
            bar.parentElement.ariaLabel = bar.parentElement.title || null;
        });

        root.querySelector('[data-word-counter]').textContent = state.wordCount
            ? `${Math.min(state.wordIndex + 1, state.wordCount)}/${state.wordCount}`
            : 'Sẵn sàng?';
    };

    // A box per letter of the word; spaces, hyphens and the like show as they are, between the boxes.
    const renderWord = () => {
        if (!state.word) {
            return;
        }

        root.querySelector('[data-meaning]').textContent = state.word.meaning;

        let letterIndex = 0;

        lettersBox.replaceChildren(...[...state.word.word].map((character) => {
            const box = document.createElement('span');

            if (!isLetter(character)) {
                box.className = 'flex h-8 min-w-3 items-end justify-center pb-1 text-lg font-bold text-fg-muted sm:h-10 sm:text-xl';
                box.textContent = character === ' ' ? '' : character;

                return box;
            }

            const isTyped = letterIndex < localProgress;
            const isNext = letterIndex === localProgress;

            box.className = [
                'flex size-8 items-center justify-center rounded-md border-2 text-lg font-bold sm:size-10 sm:text-xl',
                isTyped ? 'border-success-border bg-success-bg text-success-text' : 'border-line-default bg-surface-card text-fg-strong',
                isNext ? 'border-primary shadow-focus' : '',
            ].join(' ');
            box.dataset.letterBox = String(letterIndex);
            box.textContent = isTyped ? character : '';
            letterIndex += 1;

            return box;
        }));
    };

    const renderResult = () => {
        const result = root.querySelector('[data-result]');

        if (state.winner === null) {
            result.textContent = 'Hòa!';
        } else {
            result.textContent = state.winner === me ? 'Bạn thắng!' : 'Bạn thua!';
        }

        root.querySelector('[data-result-detail]').textContent = state.maxHealth
            ? `${config.names.challenger}: ${state.health.challenger} máu · ${config.names.opponent}: ${state.health.opponent} máu`
            : 'Bài học này chưa có từ vựng để thi đấu.';
    };

    const render = () => {
        renderHealth();

        if (state.status === 'waiting') {
            showPanel(state.ready[me] ? 'waiting' : 'start');
            root.querySelector('[data-opponent-ready]').hidden = !state.ready[other];
            typing.hidden = true;

            return;
        }

        if (state.status === 'finished') {
            showPanel('finished');
            renderResult();
            typing.hidden = true;
            input.blur();

            return;
        }

        showPanel(null);
        typing.hidden = false;
        renderWord();
    };

    // Take a state from the server. The letters of mine it has not seen yet stay shown.
    const apply = (next) => {
        const isNewWord = next.wordIndex !== state.wordIndex || next.status !== state.status;
        const previousStatus = state.status;

        state = next;
        followGameSound(previousStatus);

        if (isNewWord) {
            pending = 0;
            localProgress = state.progress[me];
        } else {
            localProgress = pending > 0 ? Math.max(localProgress, state.progress[me]) : state.progress[me];
        }

        render();

        if (state.status === 'playing' && document.activeElement !== input) {
            input.focus({ preventScroll: true });
        }
    };

    const flashRound = (text) => {
        roundMessage.textContent = text;
        roundMessage.hidden = false;
        clearTimeout(roundTimer);
        roundTimer = setTimeout(() => {
            roundMessage.hidden = true;
        }, ROUND_MESSAGE_MS);
    };

    // What the other page (or this one, through the channel) learns happened.
    const announce = (event, by, finishedWord, { throws = true } = {}) => {
        if (throws && (event === 'letter' || event === 'round' || (event === 'finish' && finishedWord))) {
            throwBrick(by);
        }

        if ((event === 'round' || event === 'finish') && finishedWord) {
            // The player who lost the word yells, once the last brick has landed.
            setTimeout(() => playFile(yell), YELL_DELAY_MS);
            flashRound(by === me ? `Bạn thắng lượt này: ${finishedWord}` : `${config.names[by]} thắng lượt này: ${finishedWord}`);
        }
    };

    // A wrong letter: the box of the letter to type turns red with the wrong letter in it and shakes, then is drawn again.
    const showWrongLetter = (box, character) => {
        if (!box) {
            return;
        }

        clearTimeout(wrongTimer);
        box.classList.remove('border-line-default', 'bg-surface-card', 'text-fg-strong', 'border-primary', 'shadow-focus');
        box.classList.add('border-danger', 'bg-danger-bg', 'text-danger');
        box.textContent = character;

        if (!prefersReducedMotion) {
            box.animate([
                { transform: 'translateX(0)' },
                { transform: 'translateX(-4px)' },
                { transform: 'translateX(4px)' },
                { transform: 'translateX(-3px)' },
                { transform: 'translateX(0)' },
            ], { duration: 260 });
        }

        wrongTimer = setTimeout(renderWord, WRONG_LETTER_MS);
    };

    // A letter typed: a right one shows at once and goes to the server, in order; a wrong one marks the box for a moment.
    const typeLetter = (character) => {
        if (state.status !== 'playing' || !state.word) {
            return;
        }

        const letters = lettersOf(state.word.word);

        if (!isLetter(character) || localProgress >= letters.length) {
            return;
        }

        if (plainLetter(character) !== plainLetter(letters[localProgress])) {
            showWrongLetter(lettersBox.querySelector(`[data-letter-box="${localProgress}"]`), character);

            return;
        }

        const wordIndex = state.wordIndex;
        const word = state.word.word;

        localProgress += 1;
        pending += 1;
        renderWord();
        throwBrick(me);

        queue = queue
            .then(() => post(config.urls.type, { word_index: wordIndex, letter: character }))
            .then((response) => {
                pending = Math.max(0, pending - 1);

                if (response.event) {
                    // The brick of this letter was thrown when it was typed.
                    announce(response.event, me, response.event === 'letter' ? null : word, { throws: false });
                }

                apply(response.state);
            })
            .catch(() => {
                pending = Math.max(0, pending - 1);
            });
    };

    input.addEventListener('input', () => {
        const typed = input.value;

        wakeAudio();

        input.value = '';
        [...typed].forEach(typeLetter);
    });

    root.querySelector('[data-start]').addEventListener('click', async (event) => {
        const button = event.currentTarget;

        wakeAudio();
        button.disabled = true;

        try {
            apply((await post(config.urls.ready)).state);
        } catch {
            button.disabled = false;
        }
    });

    const catchUp = () => fetch(config.urls.state, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((data) => apply(data.state))
        .catch(() => {});

    const channel = window.Echo.private(config.channel);

    channel.listen('.game', ({ event, by, state: next }) => {
        // This page already drew its own moves from the answers to its requests.
        if (by !== me) {
            const finishedWord = (event === 'round' || event === 'finish') && state.word && next.wordIndex !== state.wordIndex ? state.word.word : null;

            announce(event, by, finishedWord);
            apply(next);
        } else if (event === 'ready' || event === 'start') {
            apply(next);
        }
    });

    // After a lost connection, what happened meanwhile is fetched again.
    window.Echo.connector.pusher?.connection.bind('connected', catchUp);

    render();
}
