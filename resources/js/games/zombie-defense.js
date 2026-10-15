/*
 * Zombie Defense, played with the words of a lesson. Loaded only by
 * resources/views/lessons/games/zombie-defense.blade.php, which holds the config (words, secondsPerWord, saveUrl).
 *
 * Zombies come out on the left, close behind each other, and walk along the road to the house of the player,
 * on the right; a gun fixed to the wall of the house shoots at them. The meaning of a word is shown over the ground at the bottom of the
 * scene, with one box per letter.
 * Every letter typed makes the gun fire. A right letter shows in its box and takes one point off the health of the zombie
 * the word belongs to; a wrong letter is not accepted: nothing shows, the bullet does no harm and the same letter is asked
 * again. So a word is always typed right in the end, and the zombie dies.
 *
 * One zombie for each word, with as much health as the word has letters, so every zombie takes exactly as many bullets as its
 * word has letters. They come out a little more than a word's time apart and take a few words' time to cross. A zombie that
 * gets to the house before its word is typed attacks the house and is gone; the game moves on to the next word, and the
 * word comes back with a new zombie at the end of the queue. There is no way to lose: the game is won when every zombie is dead.
 * The time of a win is sent to the server to be ranked.
 *
 * The scene is drawn on a 1400 × 693 canvas, scaled with the page, from pictures (the background and the house with its gun, see `config.images`); the word to type, HUD and
 * start/result screens are HTML on top of it.
 */
import { playWrong } from '../sound-effects';

// The size of the background picture (public/games/zombie-defense/bg.jpg), whole and not cropped.
const WIDTH = 1400;
const HEIGHT = 693;
// Where the zombies' feet are, on the road of the picture, and where a bullet hits them.
const GROUND_Y = 554;
const CHEST_Y = GROUND_Y - 80;

// The house and its gun are one picture (public/games/zombie-defense/gun-home.webp), standing on the right with the muzzle of
// the gun at the height of the zombies' chests, which is where the bullets start.
const HOUSE_IMAGE = { x: 1139, y: 187, width: 261, height: 375 };
const MUZZLE = { x: HOUSE_IMAGE.x + 3, y: HOUSE_IMAGE.y + 265 };
// Where a zombie gets to the house: it attacks it, then walks on behind it and is gone once it is out of sight.
const EXIT_X = WIDTH + 40;
const HOUSE_X = 1150;
const START_X = -20;

// A zombie comes out after the one before it by a random time between these two, counted in the seconds allowed for a word:
// never so soon that the word of the one before cannot be typed.
const SPAWN_GAP_WORDS = [0.75, 1.1];
// A zombie walks from the left edge to the house in this many words' time.
const WALK_WORDS = 4.25 / 1.5;

const BULLET_SPEED = 2600;
const NEXT_WORD_MS = 300;
const WRONG_FLASH_MS = 250;
const DEATH_MS = 700;
const RESULT_DELAY_MS = 1300;
const SHAKE_MS = 450;
const SHAKE_SIZE = 10;
const MAX_FRAME_MS = 100;

// The four zombies (public/games/zombie-defense/zombies/zombie-{n}-{part}.webp), each cut into a back leg, a front leg, a body and a head. Their size,
// and where each part sits in it (x, y), as parts.json of that folder said when they were cut.
const ZOMBIE_SIZE = [[188, 286], [188, 286], [187, 287], [187, 284]];
const ZOMBIE_PARTS = [
    { legback: [0, 167], legfront: [73, 167], body: [32, 85], head: [37, 0] },
    { legback: [0, 167], legfront: [74, 167], body: [32, 85], head: [35, 0] },
    { legback: [0, 167], legfront: [74, 167], body: [32, 85], head: [36, 0] },
    { legback: [0, 167], legfront: [75, 167], body: [33, 85], head: [36, 0] },
];
// How tall a zombie is on the scene, and how far along its picture (0 to 1) the point it stands on is.
const ZOMBIE_HEIGHT = 150;
const ZOMBIE_ANCHOR = 0.42;
// The share of the zombies that wear the face of a player or learner (see config.avatars).
const AVATAR_SHARE = 0.35;

// The recorded growls (public/games/zombie-defense/sounds), how loud they are, how many can sound at once, and the seconds a
// zombie waits between one growl and the next.
const GROWL_FILES = ['zombie-growling-1.mp3', 'zombie-growling-2.mp3', 'zombie-growling-3.mp3', 'zombie-growling-4.mp3'];
const GROWL_LEVEL = 0.45;
const MAX_GROWLS = 8;
const GROWL_EVERY = [2.5, 6];
// The machine gun (sounds/machine-gun.mp3, a burst of fire): it starts and then its steady part (between these two seconds of the
// recording) is looped while letters are being typed, and fades out this long after the last one.
const GUN_FILE = 'machine-gun.mp3';
const GUN_LOOP = [0.25, 1];
const GUN_LEVEL = 0.5;
const GUN_HOLD_MS = 260;
// The background music (sounds/bg.mp3), looped while playing, and how loud it is.
const MUSIC_VOLUME = 0.6;
// The music of the win (sounds/win.mp3), played once.
const WIN_VOLUME = 0.9;

// How the zombie walks, in radians. It stands leaning forward by LEAN. A walk is two steps: in the first it leans back (to
// LEAN_BACK) and its front leg steps forward; in the second it leans forward again and its back leg kicks out.
const LEAN = (2 * Math.PI) / 180;
const LEAN_BACK = (-4 * Math.PI) / 180;
const FRONT_STEP = 0.3;
const BACK_PUSH = 0.05;
const BACK_KICK = 0.35;
// The zombie finishes a walk of two steps each time it has gone this far.
const WALK_LENGTH = 95;
// How high (in pixels) the zombie rises in the middle of each step.
const BOB = 5;
// How high a leg is lifted for its step, as a share of the height of the zombie.
const LEG_LIFT = 0.035;

const shuffled = (list) => {
    const copy = [...list];

    for (let index = copy.length - 1; index > 0; index -= 1) {
        const other = Math.floor(Math.random() * (index + 1));

        [copy[index], copy[other]] = [copy[other], copy[index]];
    }

    return copy;
};

const isLetter = (character) => /[\p{L}\p{N}]/u.test(character);
// The letter without its accents, so a Vietnamese keyboard (Telex turns "ee" into "ê") still types the right letter.
const plainLetter = (character) => character.normalize('NFD').replace(/\p{M}/gu, '').replace(/đ/giu, 'd').toLowerCase();
const lettersOf = (word) => [...word].filter(isLetter);

function startGame(root) {
    const config = JSON.parse(root.querySelector('[data-game-config]').textContent);
    const words = config.words.filter((entry) => lettersOf(entry.word).length > 0);

    // The numbers of the game, worked out from the seconds allowed for a word.
    const plan = {
        zombies: words.length,
        spawnMs: SPAWN_GAP_WORDS.map((share) => share * config.secondsPerWord * 1000),
        speed: (HOUSE_X - START_X) / (WALK_WORDS * config.secondsPerWord),
    };

    const canvas = root.querySelector('[data-canvas]');
    const context = canvas.getContext('2d');
    const ratio = Math.min(window.devicePixelRatio || 1, 2);

    canvas.width = WIDTH * ratio;
    canvas.height = HEIGHT * ratio;

    const hud = root.querySelector('[data-hud]');
    const passedText = root.querySelector('[data-passed]');
    const killedText = root.querySelector('[data-killed]');
    const timeText = root.querySelector('[data-time-text]');
    const typing = root.querySelector('[data-typing]');
    const panel = root.querySelector('[data-word-panel]');
    const meaningText = root.querySelector('[data-meaning]');
    const slotsBox = root.querySelector('[data-slots]');
    const startScreen = root.querySelector('[data-start]');
    const startButton = root.querySelector('[data-start-button]');
    const resultScreen = root.querySelector('[data-result]');
    const muteButton = root.querySelector('[data-mute]');

    const template = (name) => root.querySelector(`template[data-tpl="${name}"]`).content.firstElementChild.cloneNode(true);

    root.querySelector('[data-zombie-total]').textContent = plan.zombies;

    let isMuted = false;
    let state = null;
    let frameAt = performance.now();

    // Sounds: the gun, the growls of the zombies and the music are recorded (see fireGun, growl and music), the rest is synthesised
    // so no file is needed. The audio context is made and woken up by the click on Start, because sounds played later from timers would stay muted in a browser otherwise.

    let audio = null;
    let noiseBuffer = null;
    let growls = null;
    let growlBus = null;
    let growlVoices = 0;
    let gunBuffer = null;
    let gunVoice = null;
    let gunTimer = 0;

    const wakeAudio = () => {
        const AudioContext = window.AudioContext ?? window.webkitAudioContext;

        if (!AudioContext) {
            return;
        }

        audio ??= new AudioContext();

        if (audio.state === 'suspended') {
            audio.resume();
        }

        loadGrowls();
    };

    // The background music, a file looped while the game is played.
    const music = new Audio(`${config.sounds}/bg.mp3`);

    music.loop = true;
    music.volume = MUSIC_VOLUME;

    // The music of the win, played when the last zombie is dead.
    const winMusic = new Audio(`${config.sounds}/win.mp3`);

    winMusic.volume = WIN_VOLUME;

    // The recorded growls, fetched and decoded once. Until they are ready the zombies are quiet.
    const loadGrowls = () => {
        if (growls !== null) {
            return;
        }

        growls = [];
        growlBus = audio.createDynamicsCompressor();
        growlBus.connect(audio.destination);

        const decode = async (file) => {
            try {
                const response = await fetch(`${config.sounds}/${file}`);

                return await audio.decodeAudioData(await response.arrayBuffer());
            } catch {
                // A missing sound must never break the game.
                return null;
            }
        };

        GROWL_FILES.forEach(async (file) => {
            const buffer = await decode(file);

            if (buffer) {
                growls.push(buffer);
            }
        });
        decode(GUN_FILE).then((buffer) => {
            gunBuffer = buffer;
        });
    };

    // A letter typed fires the machine gun: the burst starts, goes on while letters keep coming and fades out soon after the last.
    const fireGun = () => {
        if (isMuted || !audio || !gunBuffer) {
            return;
        }

        if (!gunVoice) {
            const source = audio.createBufferSource();
            const gain = audio.createGain();

            source.buffer = gunBuffer;
            source.loop = true;
            [source.loopStart, source.loopEnd] = GUN_LOOP;
            gain.gain.value = GUN_LEVEL;
            source.connect(gain).connect(audio.destination);
            source.start();
            gunVoice = { source, gain };
        }

        clearTimeout(gunTimer);
        gunTimer = setTimeout(stopGun, GUN_HOLD_MS);
    };

    const stopGun = () => {
        clearTimeout(gunTimer);

        if (!gunVoice) {
            return;
        }

        const { source, gain } = gunVoice;
        const at = audio.currentTime;

        gunVoice = null;
        gain.gain.setValueAtTime(GUN_LEVEL, at);
        gain.gain.linearRampToValueAtTime(0, at + 0.08);
        source.stop(at + 0.1);
    };

    // One zombie growling: a piece of a random recording, higher or lower, louder the closer the zombie is to the house and
    // coming from the side of the scene it is on. Many zombies growling at their own times sound like a crowd.
    const growl = (zombie, { rate = 0.85 + Math.random() * 0.4, loudness = 1 } = {}) => {
        if (isMuted || !audio || !growls?.length || growlVoices >= MAX_GROWLS) {
            return;
        }

        const buffer = growls[Math.floor(Math.random() * growls.length)];
        const length = Math.min(buffer.duration, 1.4 + Math.random() * 1.8);
        const offset = Math.random() * (buffer.duration - length);
        const closeness = 0.45 + 0.55 * Math.max(0, Math.min(1, zombie.x / HOUSE_X));
        const level = GROWL_LEVEL * loudness * closeness;
        const at = audio.currentTime;
        const source = audio.createBufferSource();
        const gain = audio.createGain();
        const pan = audio.createStereoPanner();

        source.buffer = buffer;
        source.playbackRate.value = rate;
        pan.pan.value = Math.max(-0.8, Math.min(0.8, (zombie.x / WIDTH) * 2 - 1));
        gain.gain.setValueAtTime(0.001, at);
        gain.gain.exponentialRampToValueAtTime(level, at + 0.12);
        gain.gain.setValueAtTime(level, at + Math.max(0.12, length / rate - 0.5));
        gain.gain.exponentialRampToValueAtTime(0.001, at + length / rate);
        source.connect(gain).connect(pan).connect(growlBus);
        growlVoices += 1;
        source.addEventListener('ended', () => {
            growlVoices -= 1;
        });
        source.start(at, offset, length);
    };

    const noiseSource = () => {
        if (!noiseBuffer) {
            noiseBuffer = audio.createBuffer(1, audio.sampleRate, audio.sampleRate);

            const data = noiseBuffer.getChannelData(0);

            for (let index = 0; index < data.length; index += 1) {
                data[index] = Math.random() * 2 - 1;
            }
        }

        const source = audio.createBufferSource();

        source.buffer = noiseBuffer;

        return source;
    };

    // One sound: an oscillator gliding down from `from` to `to`, and/or a burst of filtered noise.
    const sound = ({ from = 0, to = 0, type = 'sine', level = 0.3, duration = 0.15, noise = null }) => {
        if (isMuted || !audio) {
            return;
        }

        try {
            const at = audio.currentTime;

            if (from) {
                const oscillator = audio.createOscillator();
                const gain = audio.createGain();

                oscillator.type = type;
                oscillator.frequency.setValueAtTime(from, at);
                oscillator.frequency.exponentialRampToValueAtTime(to, at + duration);
                gain.gain.setValueAtTime(level, at);
                gain.gain.exponentialRampToValueAtTime(0.001, at + duration);
                oscillator.connect(gain).connect(audio.destination);
                oscillator.start(at);
                oscillator.stop(at + duration + 0.02);
            }

            if (noise) {
                const source = noiseSource();
                const filter = audio.createBiquadFilter();
                const gain = audio.createGain();

                filter.type = noise.type;
                filter.frequency.setValueAtTime(noise.frequency, at);
                gain.gain.setValueAtTime(noise.level, at);
                gain.gain.exponentialRampToValueAtTime(0.001, at + duration);
                source.connect(filter).connect(gain).connect(audio.destination);
                source.start(at, Math.random() * 0.5);
                source.stop(at + duration + 0.02);
            }
        } catch {
            // A missing sound must never break the game.
        }
    };


    // Game state ------------------------------------------------------------------------------------------------

    const newState = () => ({
        phase: 'idle',
        zombies: [],
        bullets: [],
        particles: [],
        texts: [],
        timers: [],
        spawned: 0,
        spawnTimer: 0,
        killed: 0,
        passed: 0,
        order: shuffled(words),
        queue: [],
        current: null,
        letters: [],
        typed: [],
        pendingLetters: [],
        isLeaving: false,
        rightLetters: 0,
        wrongLetters: 0,
        elapsed: 0,
        flash: 0,
        houseFlash: 0,
        shakeUntil: 0,
        muzzle: 0,
        recoil: 0,
        endedAt: 0,
        slots: [],
    });

    // Runs `callback` after `ms` of game time.
    const later = (ms, callback) => {
        state.timers.push({ left: ms, callback });
    };

    // The word to type ------------------------------------------------------------------------------------------

    const focusTyping = () => typing.focus({ preventScroll: true });

    const markActive = () => {
        state.slots.forEach((box, index) => {
            box.dataset.active = String(index === state.typed.length);
        });
    };

    // The word of the zombie being shot at (`current` is that zombie), one box per letter.
    const renderWord = () => {
        const { word, meaning } = state.current.entry;

        meaningText.textContent = meaning;
        state.letters = lettersOf(word);
        state.slots = [];
        slotsBox.replaceChildren(...[...word].map((character) => {
            if (!isLetter(character)) {
                const fixed = template('letter-fixed');

                fixed.textContent = character === ' ' ? '' : character;
                fixed.classList.toggle('w-[1.2cqw]', character === ' ');

                return fixed;
            }

            const box = template('letter');

            state.slots.push(box);

            return box;
        }));
        markActive();
    };

    const clearWord = () => {
        meaningText.textContent = '';
        state.letters = [];
        state.slots = [];
        slotsBox.replaceChildren();
    };

    const nextWord = () => {
        if (state.phase !== 'playing') {
            return;
        }

        state.current = null;
        state.queue = state.queue.filter(isShootable);
        state.typed = [];
        state.isLeaving = false;

        // With no zombie waiting for its word, the next one that comes out takes it (see spawnZombie).
        if (state.queue.length === 0) {
            clearWord();

            return;
        }

        state.current = state.queue.shift();
        renderWord();
        focusTyping();

        // Letters typed while the finished word was still on screen belong to this one.
        state.pendingLetters.splice(0).forEach(typeLetter);
    };

    // Moves on to the next word after `delay`, once.
    const leaveWord = (delay) => {
        if (state.isLeaving) {
            return;
        }

        state.isLeaving = true;
        later(delay, nextWord);
    };

    const canType = () => state?.phase === 'playing' && state.current !== null;

    const typeLetter = (letter) => {
        if (!canType()) {
            return;
        }

        if (state.isLeaving) {
            state.pendingLetters.push(letter);

            return;
        }

        const index = state.typed.length;
        const expected = state.letters[index];
        const isRight = plainLetter(letter) === plainLetter(expected);

        shoot(isRight);

        // A wrong letter is not accepted: nothing shows and the same box waits for the right one.
        if (!isRight) {
            state.wrongLetters += 1;
            flashWrong(state.slots[index]);

            return;
        }

        state.rightLetters += 1;
        state.typed.push(letter);
        state.slots[index].textContent = expected;
        markActive();
        hurtZombie(state.current);

        if (state.typed.length === state.slots.length) {
            state.slots.forEach((box) => {
                box.dataset.active = 'false';
                box.dataset.state = 'correct';
            });
            leaveWord(NEXT_WORD_MS);
        }
    };

    // The box of a letter that was typed wrong turns red for a moment.
    const flashWrong = (box) => {
        box.dataset.state = 'wrong';
        later(WRONG_FLASH_MS, () => {
            if (box.dataset.state === 'wrong') {
                delete box.dataset.state;
            }
        });

        if (!isMuted) {
            playWrong();
        }
    };

    // Everything typed goes through the hidden input, which is emptied each time. While a keyboard is still composing a
    // letter (an IME), the input is left alone until the letter is done.
    const takeTyped = () => {
        const letters = [...typing.value].filter(isLetter);

        typing.value = '';
        letters.forEach(typeLetter);
    };

    typing.addEventListener('input', (event) => {
        if (!event.isComposing) {
            takeTyped();
        }
    });
    typing.addEventListener('compositionend', takeTyped);
    typing.addEventListener('keydown', (event) => {
        if (event.key === 'Backspace') {
            event.preventDefault();
        }
    });

    // A click anywhere on the scene gives the typing back its focus.
    root.addEventListener('click', (event) => {
        if (state?.phase === 'playing' && !event.target.closest('button, a')) {
            focusTyping();
        }
    });

    // Keys pressed while the focus is elsewhere on the page still play.
    document.addEventListener('keydown', (event) => {
        const isTypingElsewhere = document.activeElement?.matches('input, textarea, select, [contenteditable]') && document.activeElement !== typing;

        if (state?.phase !== 'playing' || document.activeElement === typing || isTypingElsewhere || event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        if (event.key.length === 1 && isLetter(event.key)) {
            event.preventDefault();
            focusTyping();
            typeLetter(event.key);
        } else if (event.key === 'Backspace') {
            event.preventDefault();
            focusTyping();
        }
    });

    // Zombies and shooting --------------------------------------------------------------------------------------

    const spawnZombie = () => {
        const index = state.spawned;
        const entry = state.order.shift();

        state.zombies.push({
            x: START_X,
            speed: plan.speed,
            entry,
            health: lettersOf(entry.word).length,
            maxHealth: lettersOf(entry.word).length,
            look: index % ZOMBIE_SIZE.length,
            avatar: avatarFaces.length > 0 && Math.random() < AVATAR_SHARE ? avatarFaces[Math.floor(Math.random() * avatarFaces.length)] : null,
            phase: Math.random() * Math.PI * 2,
            growlIn: Math.random() * 0.8,
            flash: 0,
            isAttacking: false,
            isDying: false,
            dyingLeft: 0,
            isGone: false,
        });
        state.spawned += 1;
        state.queue.push(state.zombies[state.zombies.length - 1]);

        if (state.current === null) {
            nextWord();
        }
    };

    // A zombie that is still walking and can be shot at.
    const isShootable = (zombie) => !zombie.isDying && !zombie.isAttacking && !zombie.isGone;

    const muzzlePoint = () => ({ x: MUZZLE.x + state.recoil * 4, y: MUZZLE.y });

    const burst = (x, y, colors, count, speed = 140, life = 500) => {
        for (let index = 0; index < count; index += 1) {
            const angle = Math.random() * Math.PI * 2;
            const power = speed * (0.35 + Math.random() * 0.65);

            state.particles.push({
                x,
                y,
                vx: Math.cos(angle) * power,
                vy: Math.sin(angle) * power - 40,
                life,
                maxLife: life * (0.6 + Math.random() * 0.4),
                size: 2 + Math.random() * 3.5,
                color: colors[index % colors.length],
            });
        }
    };

    const floatText = (x, y, text, color) => {
        state.texts.push({ x, y, text, color, life: 1100 });
    };

    // Every letter fires at the zombie of the word being typed; only the bullet of a right letter does harm.
    const shoot = (isHarmful) => {
        const target = state.current;
        const from = muzzlePoint();

        state.muzzle = 1;
        state.recoil = 1;
        fireGun();
        state.particles.push({ x: MUZZLE.x + 90, y: MUZZLE.y - 14, vx: 60 + Math.random() * 40, vy: -120, life: 450, maxLife: 450, size: 2.5, color: '#f2b800' });

        state.bullets.push({ x: from.x, y: from.y, dirX: -1, dirY: 0, target, isHarmful });
    };

    // A right letter takes one point off the health of the zombie, which dies at none.
    const hurtZombie = (zombie) => {
        zombie.health = Math.max(0, zombie.health - 1);
        zombie.flash = 1;
        burst(zombie.x + 6, CHEST_Y, ['#a02525', '#e0453a', '#ffe38a'], 6, 120, 350);

        if (zombie.health === 0) {
            killZombie(zombie);
        }
    };

    const killZombie = (zombie) => {
        zombie.isDying = true;
        zombie.dyingLeft = DEATH_MS;
        state.killed += 1;
        state.queue = state.queue.filter((queued) => queued !== zombie);

        if (state.current === zombie) {
            leaveWord(NEXT_WORD_MS);
        }

        burst(zombie.x, CHEST_Y, ['#7fae62', '#4d7a3a', '#a02525', '#e0453a'], 22, 190, 700);
        growl(zombie, { rate: 0.7 + Math.random() * 0.1, loudness: 1.4 });
        renderHud();
        checkEnd();
    };

    const passZombie = (zombie) => {
        state.passed += 1;
        state.houseFlash = 1;
        state.flash = 0.35;
        state.shakeUntil = performance.now() + SHAKE_MS;
        state.order.push(zombie.entry);
        floatText(HOUSE_X + 30, GROUND_Y - 160, 'Nhà bị tấn công!', '#ff8a8a');
        renderHud();
        checkEnd();
    };

    const checkEnd = () => {
        if (state.phase !== 'playing') {
            return;
        }

        if (state.killed === plan.zombies) {
            finish();
        }
    };

    const update = (ms) => {
        const seconds = ms / 1000;

        if (state.phase === 'playing') {
            state.elapsed += ms;
            renderTime();
        }

        if (state.phase === 'playing' && state.order.length > 0) {
            state.spawnTimer -= ms;

            if (state.spawnTimer <= 0) {
                spawnZombie();
                state.spawnTimer = plan.spawnMs[0] + Math.random() * (plan.spawnMs[1] - plan.spawnMs[0]);
            }
        }

        state.zombies.forEach((zombie) => {
            if (zombie.isDying) {
                zombie.dyingLeft -= ms;
                zombie.flash = 0;

                return;
            }

            zombie.flash = Math.max(0, zombie.flash - ms / 100);

            if (state.phase !== 'playing') {
                return;
            }

            zombie.x += zombie.speed * seconds;
            zombie.phase += ((zombie.speed * seconds) / WALK_LENGTH) * Math.PI * 2;
            zombie.growlIn -= seconds;

            if (zombie.growlIn <= 0) {
                growl(zombie);
                zombie.growlIn = GROWL_EVERY[0] + Math.random() * (GROWL_EVERY[1] - GROWL_EVERY[0]);
            }

            if (zombie.isAttacking) {
                zombie.isGone = zombie.x >= EXIT_X;

                return;
            }

            if (zombie.x >= HOUSE_X) {
                zombie.isAttacking = true;
                passZombie(zombie);
                state.queue = state.queue.filter((queued) => queued !== zombie);

                // Too late for its word: the game moves on to the next one.
                if (state.current === zombie) {
                    leaveWord(NEXT_WORD_MS);
                }
            }
        });

        state.zombies = state.zombies.filter((zombie) => !zombie.isGone && !(zombie.isDying && zombie.dyingLeft <= -300));

        state.bullets = state.bullets.filter((bullet) => {
            const { target } = bullet;

            if (!target) {
                bullet.x += bullet.dirX * BULLET_SPEED * seconds;
                bullet.y += bullet.dirY * BULLET_SPEED * seconds;

                return bullet.x > -40 && bullet.y < HEIGHT;
            }

            if (!isShootable(target) && !target.isDying) {
                return false;
            }

            const dx = target.x + 6 - bullet.x;
            const dy = CHEST_Y - bullet.y;
            const distance = Math.hypot(dx, dy);
            const step = BULLET_SPEED * seconds;

            bullet.dirX = dx / distance;
            bullet.dirY = dy / distance;

            if (distance <= step + 8) {
                burst(target.x + 6, CHEST_Y, bullet.isHarmful ? ['#a02525', '#e0453a', '#4d7a3a'] : ['#6b6f80', '#9aa0b4'], 4, 100, 320);

                return false;
            }

            bullet.x += (dx / distance) * step;
            bullet.y += (dy / distance) * step;

            return true;
        });

        state.particles = state.particles.filter((particle) => {
            particle.life -= ms;
            particle.x += particle.vx * seconds;
            particle.y += particle.vy * seconds;
            particle.vy += 380 * seconds;

            return particle.life > 0;
        });

        state.texts = state.texts.filter((text) => {
            text.life -= ms;
            text.y -= 30 * seconds;

            return text.life > 0;
        });

        state.timers = state.timers.filter((timer) => {
            timer.left -= ms;

            if (timer.left <= 0) {
                timer.callback();

                return false;
            }

            return true;
        });

        state.muzzle = Math.max(0, state.muzzle - ms / 70);
        state.recoil = Math.max(0, state.recoil - ms / 110);
        state.flash = Math.max(0, state.flash - seconds * 1.2);
        state.houseFlash = Math.max(0, state.houseFlash - seconds * 2.2);

        if (state.phase === 'ending' && performance.now() - state.endedAt >= RESULT_DELAY_MS) {
            showResult();
        }
    };

    // HUD, start and end ----------------------------------------------------------------------------------------

    const renderHud = () => {
        passedText.textContent = state.passed;
        killedText.textContent = state.killed;
    };

    const renderTime = () => {
        const seconds = Math.floor(state.elapsed / 1000);
        const text = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;

        if (timeText.textContent !== text) {
            timeText.textContent = text;
        }
    };

    const begin = () => {
        wakeAudio();
        music.currentTime = 0;
        winMusic.pause();
        music.muted = isMuted;
        music.play().catch(() => {});
        state = newState();
        state.phase = 'playing';
        startScreen.hidden = true;
        resultScreen.hidden = true;
        hud.hidden = false;
        panel.hidden = false;
        renderHud();
        renderTime();
        nextWord();
        focusTyping();
    };

    const resultNote = resultScreen.querySelector('[data-result-note]');
    const bestTimes = document.querySelector('[data-best-times]');

    // The time goes to the server, which keeps it when it is the best of the player, and the list of the fastest players
    // on the page is replaced by the one it answers with. A guest is told to sign in to be ranked.
    const saveTime = async (milliseconds) => {
        if (!config.saveUrl) {
            return 'Đăng nhập để lưu vào bảng xếp hạng.';
        }

        try {
            const response = await fetch(config.saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({ milliseconds }),
            });

            if (!response.ok) {
                throw new Error(response.status);
            }

            const { html } = await response.json();

            if (bestTimes) {
                bestTimes.innerHTML = html;
            }

            return 'Đã lưu thời gian của bạn.';
        } catch {
            return 'Không lưu được thời gian, bạn thử lại sau nhé.';
        }
    };

    const finish = () => {
        state.phase = 'ending';
        state.endedAt = performance.now();
        music.pause();
        stopGun();
        winMusic.currentTime = 0;
        winMusic.muted = isMuted;
        winMusic.play().catch(() => {});
        state.saved = saveTime(Math.round(state.elapsed));
        typing.blur();
    };

    const showResult = () => {
        const attempts = state.rightLetters + state.wrongLetters;
        const accuracy = attempts === 0 ? 0 : Math.round((state.rightLetters / attempts) * 100);
        const seconds = Math.round(state.elapsed / 1000);
        const game = state;
        const note = `Gõ đúng ${state.rightLetters}/${attempts} chữ cái (${accuracy}%).`;

        state.phase = 'won';
        panel.hidden = true;

        resultScreen.querySelector('[data-result-title]').textContent = 'Chiến thắng!';
        resultScreen.querySelector('[data-result-text]').textContent = `Bạn đã hạ ${plan.zombies} zombie sau ${seconds} giây, ngôi nhà bị tấn công ${state.passed} lần.`;
        resultNote.textContent = note;
        resultScreen.hidden = false;

        state.saved?.then((message) => {
            if (state === game) {
                resultNote.textContent = `${note} ${message}`;
            }
        });
    };

    startButton.addEventListener('click', begin);
    resultScreen.querySelector('[data-restart]').addEventListener('click', begin);

    muteButton.addEventListener('click', () => {
        isMuted = !isMuted;
        music.muted = isMuted;
        winMusic.muted = isMuted;

        if (isMuted) {
            stopGun();
        }

        muteButton.setAttribute('aria-pressed', String(isMuted));
        muteButton.setAttribute('aria-label', isMuted ? 'Bật âm thanh' : 'Tắt âm thanh');
        muteButton.querySelector('[data-mute-on]').hidden = isMuted;
        muteButton.querySelector('[data-mute-off]').hidden = !isMuted;

        if (state?.phase === 'playing') {
            focusTyping();
        }
    });

    // Painting --------------------------------------------------------------------------------------------------

    const loadImage = (url) => {
        const image = new Image();

        image.src = url;

        return image;
    };

    const backgroundImage = loadImage(config.images.background);
    const houseImage = loadImage(config.images.house);

    // The house in red, to flash over the house when a zombie attacks it, made once the picture is loaded.
    let houseRed = null;

    houseImage.addEventListener('load', () => {
        houseRed = document.createElement('canvas');
        houseRed.width = houseImage.naturalWidth;
        houseRed.height = houseImage.naturalHeight;

        const paintRed = houseRed.getContext('2d');

        paintRed.drawImage(houseImage, 0, 0);
        paintRed.globalCompositeOperation = 'source-atop';
        paintRed.fillStyle = '#ff2a2a';
        paintRed.fillRect(0, 0, houseRed.width, houseRed.height);
    });

    const paintBackground = () => {
        context.fillStyle = '#0e1330';
        context.fillRect(0, 0, WIDTH, HEIGHT);

        if (backgroundImage.complete && backgroundImage.naturalWidth > 0) {
            context.drawImage(backgroundImage, 0, 0, WIDTH, HEIGHT);
        }
    };

    const paintHouse = () => {
        if (!houseImage.complete || houseImage.naturalWidth === 0) {
            return;
        }

        const kick = state ? state.recoil * 3 : 0;
        const { x, y, width, height } = HOUSE_IMAGE;

        context.drawImage(houseImage, x + kick, y, width, height);

        if (state && state.houseFlash > 0 && houseRed) {
            context.globalAlpha = state.houseFlash * 0.6;
            context.drawImage(houseRed, x + kick, y, width, height);
            context.globalAlpha = 1;
        }

        if (state && state.muzzle > 0) {
            const flashX = MUZZLE.x - 6;
            const size = 18 + state.muzzle * 20;

            context.fillStyle = `rgba(255,220,110,${state.muzzle})`;
            context.beginPath();

            for (let point = 0; point < 8; point += 1) {
                const angle = (point / 8) * Math.PI * 2;
                const radius = point % 2 === 0 ? size : size * 0.4;

                context.lineTo(flashX + Math.cos(angle) * radius, MUZZLE.y + Math.sin(angle) * radius);
            }

            context.fill();
            context.fillStyle = `rgba(255,255,255,${state.muzzle})`;
            context.beginPath();
            context.arc(flashX, MUZZLE.y, size * 0.3, 0, Math.PI * 2);
            context.fill();
        }
    };

    // The avatars of the player and of other learners of the course: some zombies wear one as their face.
    const avatarFaces = config.avatars.map(({ url, name }) => ({ image: loadImage(url), name }));

    const zombieImages = ZOMBIE_SIZE.map((size, look) => Object.fromEntries(['legback', 'legfront', 'body', 'head'].map((part) => [part, loadImage(`${config.images.zombies}/zombie-${look + 1}-${part}.webp`)])));

    // The avatar in a round face, with the outline of the zombies, over the whole of the head: it takes the place of the head.
    const paintAvatarFace = (image, x, y, radius) => {
        context.save();
        context.beginPath();
        context.arc(x, y, radius, 0, Math.PI * 2);
        context.clip();
        context.drawImage(image, x - radius, y - radius, radius * 2, radius * 2);
        context.restore();
        context.beginPath();
        context.arc(x, y, radius, 0, Math.PI * 2);
        context.lineWidth = 2;
        context.strokeStyle = '#1b2210';
        context.stroke();
    };

    // A zombie is drawn from its four parts, moved through the two steps of its walk (see LEAN): the whole of it leans, each leg swings
    // from the hip and the head nods on the neck.
    const paintZombie = (zombie) => {
        const [naturalWidth, naturalHeight] = ZOMBIE_SIZE[zombie.look];
        const images = zombieImages[zombie.look];
        const positions = ZOMBIE_PARTS[zombie.look];
        const scale = ZOMBIE_HEIGHT / naturalHeight;
        const width = naturalWidth * scale;
        const height = ZOMBIE_HEIGHT;
        const cycle = zombie.isDying ? 0 : (zombie.phase / (Math.PI * 2)) % 1;
        const inFirstStep = cycle < 0.5;
        const t = (inFirstStep ? cycle : cycle - 0.5) * 2;
        const progress = t * t * (3 - 2 * t);
        const arch = Math.sin(Math.PI * progress);
        const lean = inFirstStep ? LEAN + (LEAN_BACK - LEAN) * progress : LEAN_BACK + (LEAN - LEAN_BACK) * progress;
        const frontAngle = inFirstStep ? -FRONT_STEP * progress : -FRONT_STEP * (1 - progress);
        const backAngle = inFirstStep ? BACK_PUSH * progress : BACK_PUSH * (1 - progress) - BACK_KICK * arch;
        const frontLift = inFirstStep ? arch * LEG_LIFT * ZOMBIE_HEIGHT : 0;
        const backLift = inFirstStep ? 0 : arch * LEG_LIFT * ZOMBIE_HEIGHT;
        const fall = zombie.isDying ? Math.min(1, (DEATH_MS - zombie.dyingLeft) / 400) : 0;
        const fade = zombie.isDying ? Math.max(0, Math.min(1, (zombie.dyingLeft + 300) / 500)) : 1;
        // Each step lifts the zombie and lets it down again, gently.
        const bob = -arch * BOB;

        if (!Object.values(images).every((image) => image.complete && image.naturalWidth > 0)) {
            return;
        }

        context.save();
        context.globalAlpha = fade;

        // The shadow: darker in the middle and fading softly to its edge.
        const shadowWidth = width * 0.4 * (1 - arch * 0.05);
        const shadowFade = context.createRadialGradient(0, 0, 0, 0, 0, shadowWidth);

        shadowFade.addColorStop(0, 'rgba(0,0,0,0.55)');
        shadowFade.addColorStop(0.55, 'rgba(0,0,0,0.4)');
        shadowFade.addColorStop(1, 'rgba(0,0,0,0)');
        context.save();
        context.translate(zombie.x, GROUND_Y - 4 - arch);
        context.scale(1, 9 / shadowWidth);
        context.fillStyle = shadowFade;
        context.beginPath();
        context.arc(0, 0, shadowWidth, 0, Math.PI * 2);
        context.fill();
        context.restore();

        context.translate(zombie.x, GROUND_Y);
        // The whole zombie leans on its feet: forward at rest, back as it takes the first step.
        context.rotate(-fall * (Math.PI / 2) + (zombie.isDying ? 0 : lean));

        if (zombie.flash > 0) {
            context.filter = 'brightness(2.2) saturate(0.3)';
        }

        // The picture's own pixels are drawn from its top left corner, at the scale of the scene.
        context.translate(-width * ZOMBIE_ANCHOR, -height + bob);

        const part = (name, transform, paintOver) => {
            const [x, y] = positions[name];
            const image = images[name];

            context.save();
            context.translate(x * scale, y * scale);
            transform?.(image.naturalWidth * scale, image.naturalHeight * scale);
            context.drawImage(image, 0, 0, image.naturalWidth * scale, image.naturalHeight * scale);
            paintOver?.(image.naturalWidth * scale, image.naturalHeight * scale);
            context.restore();
        };

        // Legs: swung from the hip, and lifted for a step. The back leg is drawn first.
        part('legback', (partWidth, partHeight) => {
            context.translate(partWidth * 0.85, partHeight * 0.1);
            context.rotate(backAngle);
            context.translate(0, -backLift);
            context.translate(-partWidth * 0.85, -partHeight * 0.1);
        });
        part('legfront', (partWidth, partHeight) => {
            context.translate(partWidth * 0.2, partHeight * 0.1);
            context.rotate(frontAngle);
            context.translate(0, -frontLift);
            context.translate(-partWidth * 0.2, -partHeight * 0.1);
        });

        part('body');

        // Head: it does not keep up with the lean, and lolls from side to side on the neck.
        part('head', (partWidth, partHeight) => {
            context.translate(partWidth * 0.4, partHeight * 0.9);
            context.rotate(-(lean - LEAN) + (zombie.isDying ? 0 : Math.sin(cycle * Math.PI * 2 - 0.9) * 0.09));
            context.translate(-partWidth * 0.4, -partHeight * 0.9);
        }, (partWidth, partHeight) => {
            if (zombie.avatar?.image.complete && zombie.avatar.image.naturalWidth > 0) {
                paintAvatarFace(zombie.avatar.image, partWidth * 0.5 + 3, partHeight * 0.5 + 1, Math.max(partWidth, partHeight) * 0.46 + 1);
            }
        });

        context.restore();

        // Health bar
        if (!zombie.isDying) {
            const barWidth = 52;
            const share = Math.max(0, zombie.health / zombie.maxHealth);

            // A zombie with a name has the name right under its bar, so the bar is higher.
            const barY = GROUND_Y - ZOMBIE_HEIGHT - (zombie.avatar ? 30 : 20);

            context.fillStyle = 'rgba(0,0,0,0.55)';
            context.fillRect(zombie.x - barWidth / 2 - 1, barY, barWidth + 2, 8);
            context.fillStyle = share > 0.4 ? '#25c559' : '#fb3f40';
            context.fillRect(zombie.x - barWidth / 2, barY + 1, barWidth * share, 6);

            if (zombie.avatar) {
                context.save();
                context.font = '700 14px "Be Vietnam Pro", system-ui, sans-serif';
                context.textAlign = 'center';
                context.textBaseline = 'top';
                context.lineJoin = 'round';
                context.lineWidth = 4;
                context.strokeStyle = 'rgba(27,31,59,0.85)';
                context.strokeText(zombie.avatar.name, zombie.x, barY + 10);
                context.fillStyle = '#ffffff';
                context.fillText(zombie.avatar.name, zombie.x, barY + 10);
                context.restore();
            }
        }
    };

    const paint = (now) => {
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.clearRect(0, 0, WIDTH, HEIGHT);

        if (state && now < state.shakeUntil) {
            const power = SHAKE_SIZE * ((state.shakeUntil - now) / SHAKE_MS);

            context.translate((Math.random() - 0.5) * 2 * power, (Math.random() - 0.5) * 2 * power);
        }

        paintBackground();

        if (!state) {
            paintHouse();

            return;
        }

        state.zombies.forEach((zombie) => paintZombie(zombie));
        paintHouse();

        state.bullets.forEach((bullet) => {
            context.strokeStyle = 'rgba(255,230,140,0.9)';
            context.lineWidth = 3;
            context.lineCap = 'round';
            context.beginPath();
            context.moveTo(bullet.x, bullet.y);
            context.lineTo(bullet.x - bullet.dirX * 34, bullet.y - bullet.dirY * 34);
            context.stroke();
        });

        state.particles.forEach((particle) => {
            context.globalAlpha = Math.max(0, Math.min(1, particle.life / particle.maxLife));
            context.fillStyle = particle.color;
            context.beginPath();
            context.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            context.fill();
        });
        context.globalAlpha = 1;

        context.font = '700 24px "Be Vietnam Pro", system-ui, sans-serif';
        context.textAlign = 'center';
        context.lineJoin = 'round';
        state.texts.forEach((text) => {
            context.globalAlpha = Math.min(1, text.life / 400);
            context.strokeStyle = 'rgba(27,31,59,0.85)';
            context.lineWidth = 5;
            context.strokeText(text.text, text.x, text.y);
            context.fillStyle = text.color;
            context.fillText(text.text, text.x, text.y);
        });
        context.globalAlpha = 1;

        if (state.flash > 0) {
            context.fillStyle = `rgba(255,40,40,${state.flash * 0.5})`;
            context.fillRect(-20, -20, WIDTH + 40, HEIGHT + 40);
        }
    };

    const frame = (now) => {
        const ms = Math.min(now - frameAt, MAX_FRAME_MS);

        frameAt = now;

        if (state && (state.phase === 'playing' || state.phase === 'ending')) {
            update(ms);
        }

        paint(now);
        requestAnimationFrame(frame);
    };

    requestAnimationFrame(frame);
}

const root = document.querySelector('[data-zombie-game]');

if (root) {
    startGame(root);
}
