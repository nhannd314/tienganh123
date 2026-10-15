/*
 * Galaxy Defender, played with the words of a lesson. Loaded only by
 * resources/views/lessons/games/galaxy-defender.blade.php, which holds the config (words, saveUrl, assetsUrl).
 *
 * Small glowing stars fall from the top, each labelled with the meaning of a word. The player types the English word of one: the
 * first letters pick the meteor (the lowest one whose word starts that way) and every right letter makes the
 * spaceship shoot a bullet at it. The last letter brings the meteor down once its bullets have landed. A wrong letter
 * is not counted (Backspace takes back a right one). There is no time limit and nothing to lose: a meteor that
 * reaches the bottom edge explodes, shaking the screen, and its word is queued again. The game is won when every word has been typed, and the time it took is sent to the server to be ranked.
 *
 * The lasers and explosions are synthesised with the Web Audio API; the background music and the sound of the win are
 * public/games/galaxy-defender/bg-sound.mp3 and win.wav.
 *
 * The scene is drawn on a 1200 × 700 canvas, scaled with the page; the spaceship is an image (it drifts around gently
 * while waiting), the rest is painted. The label of a meteor, HTML, is set over it.
 */
import { playWrong } from '../sound-effects';

const WIDTH = 1200;
const HEIGHT = 700;

// The spaceship image is 200 × 189; its nose is the middle of the top edge.
const SHIP = { x: WIDTH / 2, y: HEIGHT - 70, width: 130, height: 123, noseLength: 62 };
const MAX_AIM = 0.85;
const AIM_PER_SECOND = 14;

const LANES = [150, 450, 750, 1050];
const MAX_ACTIVE = 3;
const SPAWN_MS = 3000;
const FALL_SPEED = 34;
const ORB_RADIUS = 20;
// Where a star appears, and the least distance the one before it must have fallen for the next to appear.
const ORB_START_Y = 100;
const SPAWN_CLEARANCE = 115;
const FLAME_COLORS = ['#fff2a8', '#ffc233', '#ff8a1f', '#ff4a1c'];
// How far the spaceship drifts from its place while it waits.
const DRIFT_X = 46;
const DRIFT_Y = 9;
const SHAKE_MS = 450;
const SHAKE_SIZE = 14;

const MUSIC_VOLUME = 0.3;

const BULLET_SPEED = 1700;
const RESULT_DELAY_MS = 1400;
const WRONG_MS = 350;
const MAX_FRAME_MS = 100;

const root = document.querySelector('[data-galaxy-game]');

if (root) {
    startGame(root);
}

function startGame(root) {
    const config = JSON.parse(root.querySelector('[data-game-config]').textContent);

    const canvas = root.querySelector('[data-canvas]');
    const context = canvas.getContext('2d');
    const ratio = Math.min(window.devicePixelRatio || 1, 2);

    canvas.width = WIDTH * ratio;
    canvas.height = HEIGHT * ratio;
    context.scale(ratio, ratio);

    const hud = root.querySelector('[data-hud]');
    const wordsDoneText = root.querySelector('[data-words-done]');
    const timeText = root.querySelector('[data-time-text]');
    const typing = root.querySelector('[data-typing]');
    const startScreen = root.querySelector('[data-start]');
    const startButton = root.querySelector('[data-start-button]');
    const resultScreen = root.querySelector('[data-result]');
    const muteButton = root.querySelector('[data-mute]');

    const template = (name) => root.querySelector(`template[data-tpl="${name}"]`).content.firstElementChild.cloneNode(true);
    const isLetter = (character) => /[\p{L}\p{N}]/u.test(character);
    // The letter without its accents, so a Vietnamese keyboard (Telex turns "ee" into "ê") still types the right letter.
    const plainLetter = (character) => character.normalize('NFD').replace(/\p{M}/gu, '').replace(/đ/giu, 'd').toLowerCase();
    const lettersOf = (word) => [...word].filter(isLetter);

    let isMuted = false;
    let state = null;
    let lastFrameAt = null;

    // Sounds: a laser and an explosion, synthesised so no file is needed. The audio context is made and woken up by the
    // click on Start, because shots fired later from timers would stay muted in a browser otherwise.

    let audio = null;

    const wakeAudio = () => {
        const AudioContext = window.AudioContext ?? window.webkitAudioContext;

        if (!AudioContext) {
            return;
        }

        audio ??= new AudioContext();

        if (audio.state === 'suspended') {
            audio.resume();
        }
    };

    const playShot = () => {
        if (isMuted || !audio) {
            return;
        }

        try {
            const at = audio.currentTime;
            const source = audio.createOscillator();
            const gain = audio.createGain();

            source.type = 'sawtooth';
            source.frequency.setValueAtTime(1400, at);
            source.frequency.exponentialRampToValueAtTime(260, at + 0.1);
            gain.gain.setValueAtTime(0.08, at);
            gain.gain.exponentialRampToValueAtTime(0.001, at + 0.1);
            source.connect(gain).connect(audio.destination);
            source.start(at);
            source.stop(at + 0.1);
        } catch {
            // A missing sound must never break the game.
        }
    };

    // White noise, made once and played from a random point each time.
    let noiseBuffer = null;
    let softClip = null;

    const noiseSource = () => {
        if (!noiseBuffer) {
            noiseBuffer = audio.createBuffer(1, audio.sampleRate * 2, audio.sampleRate);

            const data = noiseBuffer.getChannelData(0);

            for (let index = 0; index < data.length; index += 1) {
                data[index] = Math.random() * 2 - 1;
            }
        }

        const source = audio.createBufferSource();

        source.buffer = noiseBuffer;

        return source;
    };

    // An explosion in layers: a hard crack, a body of noise that darkens as it dies, a deep thump and a few late crackles,
    // all through a soft clipper for some grit. A crash on the ground (`isBig`) is longer and deeper.
    const playBoom = (isBig = false) => {
        if (isMuted || !audio) {
            return;
        }

        try {
            const at = audio.currentTime;
            const length = isBig ? 1.2 : 0.75;

            if (!softClip) {
                const curve = new Float32Array(1024);

                curve.forEach((_, index) => {
                    curve[index] = Math.tanh(((index / 1023) * 2 - 1) * 2.5);
                });
                softClip = audio.createWaveShaper();
                softClip.curve = curve;
                const master = audio.createGain();

                master.gain.value = 0.45;
                softClip.connect(master).connect(audio.destination);
            }

            const layer = (source, filter, level, duration, start = at) => {
                const gain = audio.createGain();

                gain.gain.setValueAtTime(level, start);
                gain.gain.exponentialRampToValueAtTime(0.001, start + duration);
                source.connect(filter);
                filter.connect(gain).connect(softClip);
                source.start(start, Math.random() * 0.7);
                source.stop(start + duration + 0.05);
            };

            const filterOf = (type, frequency, endFrequency = null, duration = 0, start = at) => {
                const filter = audio.createBiquadFilter();

                filter.type = type;
                filter.frequency.setValueAtTime(frequency, start);

                if (endFrequency) {
                    filter.frequency.exponentialRampToValueAtTime(endFrequency, start + duration);
                }

                return filter;
            };

            // The crack at the start
            layer(noiseSource(), filterOf('highpass', 2500), 0.55, 0.07);

            // The body
            layer(noiseSource(), filterOf('lowpass', 3500, 90, length, at), isBig ? 0.9 : 0.65, length);

            // The thump
            const thump = audio.createOscillator();

            thump.frequency.setValueAtTime(isBig ? 120 : 150, at);
            thump.frequency.exponentialRampToValueAtTime(30, at + length * 0.55);
            layer(thump, filterOf('lowpass', 400), isBig ? 1.1 : 0.8, length * 0.6);

            // Crackles of the falling debris
            for (let index = 0; index < (isBig ? 9 : 5); index += 1) {
                const start = at + 0.12 + Math.random() * length * 0.8;

                layer(noiseSource(), filterOf('bandpass', 1500 + Math.random() * 3000), 0.12 + Math.random() * 0.12, 0.04 + Math.random() * 0.05, start);
            }
        } catch {
            // A missing sound must never break the game.
        }
    };

    // The background music, a file looped while playing; it fades out when the game is won.

    const music = new Audio(`${config.assetsUrl}/bg-sound.mp3`);
    const winSound = new Audio(`${config.assetsUrl}/win.wav`);
    let fadeTimer = null;

    music.loop = true;

    const startMusic = (restart = true) => {
        clearInterval(fadeTimer);

        if (isMuted) {
            return;
        }

        music.volume = MUSIC_VOLUME;

        if (restart) {
            music.currentTime = 0;
        }

        music.play().catch(() => {});
    };

    const playWin = () => {
        if (isMuted) {
            return;
        }

        winSound.currentTime = 0;
        winSound.play().catch(() => {});
    };

    const stopMusic = (fadeMs = 0) => {
        clearInterval(fadeTimer);

        if (fadeMs === 0 || music.paused) {
            music.pause();

            return;
        }

        const fadedAt = performance.now();

        fadeTimer = setInterval(() => {
            const share = Math.min(1, (performance.now() - fadedAt) / fadeMs);

            music.volume = MUSIC_VOLUME * (1 - share);

            if (share === 1) {
                clearInterval(fadeTimer);
                music.pause();
            }
        }, 50);
    };

    // Scene

    const randomBetween = (low, high) => low + Math.random() * (high - low);

    // The milky way and the nebulae, painted once.
    const makeBackground = () => {
        const layer = document.createElement('canvas');

        layer.width = WIDTH * ratio;
        layer.height = HEIGHT * ratio;

        const paint = layer.getContext('2d');

        paint.scale(ratio, ratio);

        const sky = paint.createLinearGradient(0, 0, 0, HEIGHT);

        sky.addColorStop(0, '#02040f');
        sky.addColorStop(0.6, '#070a22');
        sky.addColorStop(1, '#120a2e');
        paint.fillStyle = sky;
        paint.fillRect(0, 0, WIDTH, HEIGHT);

        [
            [220, 200, 360, 'rgba(120, 80, 255, 0.16)'],
            [980, 420, 420, 'rgba(255, 90, 170, 0.1)'],
            [640, 80, 300, 'rgba(60, 170, 255, 0.09)'],
        ].forEach(([x, y, radius, color]) => {
            const glow = paint.createRadialGradient(x, y, 0, x, y, radius);

            glow.addColorStop(0, color);
            glow.addColorStop(1, 'rgba(0, 0, 0, 0)');
            paint.fillStyle = glow;
            paint.fillRect(0, 0, WIDTH, HEIGHT);
        });

        // A diagonal band of dust and tiny stars.
        paint.save();
        paint.translate(WIDTH / 2, HEIGHT / 2);
        paint.rotate(-0.38);

        const band = paint.createLinearGradient(0, -170, 0, 170);

        band.addColorStop(0, 'rgba(190, 170, 255, 0)');
        band.addColorStop(0.5, 'rgba(190, 170, 255, 0.12)');
        band.addColorStop(1, 'rgba(190, 170, 255, 0)');
        paint.fillStyle = band;
        paint.fillRect(-WIDTH, -170, WIDTH * 2, 340);

        for (let index = 0; index < 700; index += 1) {
            const spread = (Math.random() + Math.random() + Math.random() - 1.5) / 1.5;

            paint.fillStyle = `rgba(255, 255, 255, ${randomBetween(0.15, 0.7)})`;
            paint.fillRect(randomBetween(-WIDTH * 0.8, WIDTH * 0.8), spread * 150, randomBetween(0.6, 1.6), randomBetween(0.6, 1.6));
        }

        paint.restore();

        return layer;
    };

    const background = makeBackground();

    const makeStars = () =>
        Array.from({ length: 90 }, () => ({
            x: Math.random() * WIDTH,
            y: Math.random() * HEIGHT,
            radius: randomBetween(0.6, 2),
            speed: randomBetween(4, 22),
            phase: Math.random() * Math.PI * 2,
        }));

    const stars = makeStars();

    const shipImage = new Image();

    shipImage.src = `${config.assetsUrl}/space-ship.png`;

    // Drawing

    const drawStars = (now) => {
        stars.forEach((star) => {
            context.globalAlpha = 0.45 + 0.45 * Math.sin(now / 600 + star.phase);
            context.fillStyle = '#ffffff';
            context.beginPath();
            context.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
            context.fill();
        });

        context.globalAlpha = 1;
    };

    // A small glowing star.
    const drawMeteor = (meteor, now) => {
        const shake = now < meteor.hitUntil ? Math.sin(now / 10) * 3 : 0;
        const x = meteor.x + shake;
        const { y } = meteor;
        const radius = ORB_RADIUS * (1 + Math.sin(now / 160 + meteor.phase) * 0.08);

        context.save();
        context.globalCompositeOperation = 'lighter';

        const glow = context.createRadialGradient(x, y, 0, x, y, radius * 2.8);

        glow.addColorStop(0, 'rgba(255, 255, 255, 1)');
        glow.addColorStop(0.25, 'rgba(255, 235, 170, 0.85)');
        glow.addColorStop(0.5, 'rgba(255, 150, 60, 0.35)');
        glow.addColorStop(1, 'rgba(255, 90, 30, 0)');
        context.fillStyle = glow;
        context.beginPath();
        context.arc(x, y, radius * 2.8, 0, Math.PI * 2);
        context.fill();
        context.restore();

        if (now < meteor.hitUntil) {
            context.fillStyle = 'rgba(255, 255, 255, 0.7)';
            context.beginPath();
            context.arc(x, y, radius, 0, Math.PI * 2);
            context.fill();
        }
    };

    // The spaceship sways to and fro, bobs and tilts a little, so it looks alive while the player thinks.
    const shipPlace = (now) => ({
        x: SHIP.x + Math.sin(now / 800) * DRIFT_X + Math.sin(now / 330) * 6,
        y: SHIP.y + Math.sin(now / 520) * DRIFT_Y,
        tilt: Math.cos(now / 800) * 0.07,
    });

    const nose = () => {
        const { x, y } = state.ship;
        const angle = state.aim + state.ship.tilt;

        return { x: x + Math.sin(angle) * SHIP.noseLength, y: y - Math.cos(angle) * SHIP.noseLength };
    };

    const drawShip = () => {
        if (!shipImage.complete || shipImage.naturalWidth === 0) {
            return;
        }

        context.save();
        context.translate(state.ship.x, state.ship.y);
        context.rotate(state.aim + state.ship.tilt);
        context.drawImage(shipImage, -SHIP.width / 2, -SHIP.height / 2, SHIP.width, SHIP.height);
        context.restore();
    };

    const drawBullets = () => {
        context.save();
        context.lineCap = 'round';
        context.lineWidth = 5;
        context.strokeStyle = '#7df9ff';
        context.shadowColor = '#7df9ff';
        context.shadowBlur = 12;
        state.bullets.forEach((bullet) => {
            context.beginPath();
            context.moveTo(bullet.x, bullet.y);
            context.lineTo(bullet.x - bullet.directionX * 24, bullet.y - bullet.directionY * 24);
            context.stroke();
        });
        context.restore();
    };

    const drawEffects = (now) => {
        state.rings.forEach((ring) => {
            const share = (now - ring.startedAt) / ring.duration;

            context.globalAlpha = Math.max(0, 1 - share);
            context.strokeStyle = '#ffe9a8';
            context.lineWidth = 4 * (1 - share) + 1;
            context.beginPath();
            context.arc(ring.x, ring.y, ring.radius * share, 0, Math.PI * 2);
            context.stroke();
        });

        state.particles.forEach((particle) => {
            const share = particle.life / particle.duration;

            context.globalAlpha = Math.max(0, share);
            context.fillStyle = particle.color;
            context.beginPath();
            context.arc(particle.x, particle.y, particle.radius * (0.4 + share * 0.6), 0, Math.PI * 2);
            context.fill();
        });

        context.globalAlpha = 1;
    };

    const draw = (now) => {
        const shakeLeft = Math.max(0, state.shakeUntil - now) / SHAKE_MS;

        context.save();

        if (shakeLeft > 0) {
            context.translate((Math.random() * 2 - 1) * SHAKE_SIZE * shakeLeft, (Math.random() * 2 - 1) * SHAKE_SIZE * shakeLeft);
        }

        context.drawImage(background, -SHAKE_SIZE, -SHAKE_SIZE, WIDTH + SHAKE_SIZE * 2, HEIGHT + SHAKE_SIZE * 2);
        drawStars(now);
        state.meteors.forEach((meteor) => drawMeteor(meteor, now));
        drawShip();
        drawBullets();
        drawEffects(now);
        context.restore();

        // A red flash as the impact hits.
        if (shakeLeft > 0) {
            context.fillStyle = `rgba(255, 70, 50, ${0.28 * shakeLeft})`;
            context.fillRect(0, 0, WIDTH, HEIGHT);
        }
    };

    // Game state

    const shuffle = (items) => {
        const shuffled = [...items];

        for (let index = shuffled.length - 1; index > 0; index -= 1) {
            const other = Math.floor(Math.random() * (index + 1));

            [shuffled[index], shuffled[other]] = [shuffled[other], shuffled[index]];
        }

        return shuffled;
    };

    const newState = () => ({
        phase: 'ready',
        queue: shuffle(config.words),
        meteors: [],
        target: null,
        typed: [],
        aim: 0,
        bullets: [],
        particles: [],
        rings: [],
        shakeUntil: 0,
        ship: { x: SHIP.x, y: SHIP.y, tilt: 0 },
        spawnIn: 0,
        wordsDone: 0,
        elapsed: 0,
        rightLetters: 0,
        wrongLetters: 0,
    });

    // Runs `callback` after `ms` unless another game has started since, so a restart never inherits an old timer.
    const later = (callback, ms) => {
        const game = state;

        setTimeout(() => {
            if (state === game) {
                callback();
            }
        }, ms);
    };

    const burst = (x, y, count, speed, colors, duration) => {
        for (let index = 0; index < count; index += 1) {
            const angle = Math.random() * Math.PI * 2;
            const velocity = randomBetween(speed * 0.3, speed);

            state.particles.push({
                x,
                y,
                velocityX: Math.cos(angle) * velocity,
                velocityY: Math.sin(angle) * velocity,
                radius: randomBetween(2, 5),
                color: colors[Math.floor(Math.random() * colors.length)],
                life: duration,
                duration,
            });
        }
    };

    // A meteor, in a lane no other meteor is falling in, so the ones on the screen are spread out.
    const spawnMeteor = () => {
        const { word, meaning } = state.queue.shift();
        const freeLanes = LANES.filter((lane) => !state.meteors.some((meteor) => meteor.lane === lane));
        const lanes = freeLanes.length > 0 ? freeLanes : LANES;
        const lane = lanes[Math.floor(Math.random() * lanes.length)];
        const label = template('label');
        const slots = label.querySelector('[data-slots]');

        label.querySelector('[data-meaning]').textContent = meaning;

        word.split(/\s+/).forEach((part) => {
            const group = document.createElement('span');

            group.className = 'flex flex-wrap justify-center gap-[0.3cqw]';
            [...part].forEach((character) => {
                const box = template(isLetter(character) ? 'letter' : 'letter-fixed');

                if (!isLetter(character)) {
                    box.textContent = character;
                }

                group.append(box);
            });
            slots.append(group);
        });

        root.append(label);

        state.meteors.push({
            word,
            meaning,
            plain: lettersOf(word).map(plainLetter),
            letters: lettersOf(word),
            lane,
            x: lane + randomBetween(-30, 30),
            y: ORB_START_Y,
            speed: FALL_SPEED * randomBetween(0.96, 1.04),
            phase: Math.random() * Math.PI * 2,
            label,
            boxes: [...label.querySelectorAll('[data-letter]')],
            pending: 0,
            hitUntil: 0,
            isCompleted: false,
        });

        burst(lane, ORB_START_Y, 14, 160, FLAME_COLORS, 450);
    };

    const removeMeteor = (meteor) => {
        meteor.label.remove();
        state.meteors = state.meteors.filter((other) => other !== meteor);
        state.bullets = state.bullets.filter((bullet) => bullet.meteor !== meteor);
    };

    // The boxes of a meteor show the letters typed so far; the locked one has its label outlined.
    const renderMeteor = (meteor) => {
        const progress = meteor === state.target ? state.typed.length : 0;

        meteor.boxes.forEach((box, index) => {
            if (index < progress || meteor.isCompleted) {
                box.textContent = meteor.letters[index];
                box.dataset.state = 'correct';
            } else {
                box.textContent = '';
                delete box.dataset.state;
            }
        });

        if (meteor === state.target) {
            meteor.label.dataset.state = 'locked';
        } else {
            delete meteor.label.dataset.state;
        }
    };

    const renderMeteors = () => state.meteors.forEach(renderMeteor);

    const renderWordsDone = () => {
        wordsDoneText.textContent = state.wordsDone;
    };

    // The time the player has taken so far, as m:ss; it is what ranks them.
    const renderTime = () => {
        const seconds = Math.floor(state.elapsed / 1000);
        const text = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;

        if (timeText.textContent !== text) {
            timeText.textContent = text;
        }
    };

    // The label is centred on the meteor, over it.
    const placeLabel = (meteor) => {
        meteor.label.style.left = `${(meteor.x / WIDTH) * 100}%`;
        meteor.label.style.top = `${(meteor.y / HEIGHT) * 100}%`;
    };

    const shoot = (meteor) => {
        const { x, y } = nose();

        state.bullets.push({ x, y, meteor, directionX: 0, directionY: -1 });
        meteor.pending += 1;
        playShot();
    };

    const destroyMeteor = (meteor) => {
        burst(meteor.x, meteor.y, 46, 420, ['#fff2a8', '#ffb13b', '#ff6b3d', '#c9c9ff'], 700);
        state.rings.push({ x: meteor.x, y: meteor.y, radius: 110, duration: 450, startedAt: performance.now() });
        playBoom();
        removeMeteor(meteor);
        state.wordsDone += 1;
        renderWordsDone();

        if (state.wordsDone === config.words.length) {
            win();
        }
    };

    // A meteor that got to the bottom edge explodes and shakes the screen; its word is asked again later.
    const crash = (meteor, now) => {
        const y = HEIGHT - ORB_RADIUS;

        burst(meteor.x, y, 60, 520, ['#ffffff', ...FLAME_COLORS], 800);
        state.rings.push({ x: meteor.x, y, radius: 170, duration: 550, startedAt: now });
        state.shakeUntil = now + SHAKE_MS;
        playBoom(true);

        if (state.target === meteor) {
            state.target = null;
            state.typed = [];
        }

        state.queue.push({ word: meteor.word, meaning: meteor.meaning });
        removeMeteor(meteor);
        renderMeteors();
    };

    const update = (now, elapsedMs) => {
        const seconds = elapsedMs / 1000;

        state.ship = shipPlace(now);

        stars.forEach((star) => {
            star.y += star.speed * seconds;

            if (star.y > HEIGHT) {
                star.y = 0;
                star.x = Math.random() * WIDTH;
            }
        });

        if (state.phase === 'playing') {
            state.elapsed += elapsedMs;
            renderTime();
            state.spawnIn = Math.max(0, state.spawnIn - elapsedMs);

            const active = state.meteors.filter((meteor) => !meteor.isCompleted).length;

            const isStartClear = state.meteors.every((meteor) => meteor.y > ORB_START_Y + SPAWN_CLEARANCE);

            if (state.queue.length > 0 && active < MAX_ACTIVE && isStartClear && (active === 0 || state.spawnIn === 0)) {
                spawnMeteor();
                state.spawnIn = SPAWN_MS;
                renderMeteors();
            }
        }

        [...state.meteors].forEach((meteor) => {
            meteor.y += meteor.speed * seconds;

            if (meteor.y >= HEIGHT - ORB_RADIUS && !meteor.isCompleted) {
                crash(meteor, now);

                return;
            }

            placeLabel(meteor);
        });

        const desiredAim = state.target ? Math.max(-MAX_AIM, Math.min(MAX_AIM, Math.atan2(state.target.x - state.ship.x, state.ship.y - state.target.y))) : 0;

        state.aim += (desiredAim - state.aim) * Math.min(1, AIM_PER_SECOND * seconds);

        state.bullets = state.bullets.filter((bullet) => {
            const { meteor } = bullet;
            const distanceX = meteor.x - bullet.x;
            const distanceY = meteor.y - bullet.y;
            const distance = Math.hypot(distanceX, distanceY);
            const step = BULLET_SPEED * seconds;

            if (distance > step + ORB_RADIUS * 0.6) {
                bullet.directionX = distanceX / distance;
                bullet.directionY = distanceY / distance;
                bullet.x += bullet.directionX * step;
                bullet.y += bullet.directionY * step;

                return true;
            }

            meteor.pending -= 1;
            meteor.hitUntil = now + 120;
            burst(bullet.x, bullet.y, 6, 220, ['#ffffff', '#7df9ff'], 260);

            if (meteor.isCompleted && meteor.pending === 0) {
                destroyMeteor(meteor);
            }

            return false;
        });

        state.particles = state.particles.filter((particle) => {
            particle.x += particle.velocityX * seconds;
            particle.y += particle.velocityY * seconds;
            particle.life -= elapsedMs;

            return particle.life > 0;
        });
        state.rings = state.rings.filter((ring) => now - ring.startedAt < ring.duration);
    };

    const loop = (now) => {
        const elapsedMs = lastFrameAt === null ? 0 : Math.min(now - lastFrameAt, MAX_FRAME_MS);

        lastFrameAt = now;
        // Asked for first, so one failed frame can never stop the game.
        requestAnimationFrame(loop);
        update(now, elapsedMs);
        draw(now);
    };

    // Typing

    const focusTyping = () => typing.focus({ preventScroll: true });

    const typeLetter = (letter) => {
        if (state?.phase !== 'playing') {
            return;
        }

        const next = [...state.typed, plainLetter(letter)];
        const candidates = state.meteors.filter((meteor) => !meteor.isCompleted && next.every((character, index) => meteor.plain[index] === character));

        if (candidates.length === 0) {
            state.wrongLetters += 1;
            playWrongLetter();

            return;
        }

        if (!candidates.includes(state.target)) {
            state.target = candidates.reduce((lowest, meteor) => (meteor.y > lowest.y ? meteor : lowest));
        }

        const meteor = state.target;

        state.typed = next;
        state.rightLetters += 1;
        shoot(meteor);

        if (next.length === meteor.plain.length) {
            meteor.isCompleted = true;
            state.target = null;
            state.typed = [];
        }

        renderMeteors();
    };

    const eraseLetter = () => {
        if (state?.phase !== 'playing' || state.typed.length === 0) {
            return;
        }

        state.typed.pop();

        if (state.typed.length === 0) {
            state.target = null;
        }

        renderMeteors();
    };

    // The meteor being typed flashes red for a moment.
    const playWrongLetter = () => {
        if (!isMuted) {
            playWrong();
        }

        const meteor = state.target;

        if (!meteor) {
            return;
        }

        meteor.label.dataset.state = 'wrong';
        later(() => renderMeteor(meteor), WRONG_MS);
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
            eraseLetter();
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
            eraseLetter();
        }
    });

    // Game flow

    const begin = () => {
        state?.meteors.forEach((meteor) => meteor.label.remove());
        wakeAudio();
        stopMusic();
        winSound.pause();
        state = newState();
        state.phase = 'playing';
        startScreen.hidden = true;
        resultScreen.hidden = true;
        hud.hidden = false;
        renderWordsDone();
        renderTime();
        startMusic();
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

    const win = () => {
        state.phase = 'won';
        typing.blur();
        stopMusic(1200);
        playWin();
        const game = state;
        const saved = saveTime(Math.round(state.elapsed));

        later(() => {
            const seconds = Math.round(state.elapsed / 1000);
            const typedLetters = state.rightLetters + state.wrongLetters;
            const accuracy = Math.round((state.rightLetters / typedLetters) * 100);

            resultScreen.querySelector('[data-result-title]').textContent = 'Chiến thắng!';
            resultScreen.querySelector('[data-result-text]').textContent = `Bạn đã bảo vệ dải ngân hà, gõ đúng cả ${config.words.length} từ sau ${seconds} giây.`;
            resultNote.textContent = `Độ chính xác: ${accuracy}%`;
            resultScreen.hidden = false;

            saved.then((message) => {
                if (state === game) {
                    resultNote.textContent = `Độ chính xác: ${accuracy}%. ${message}`;
                }
            });
        }, RESULT_DELAY_MS);
    };

    root.querySelector('[data-restart]').addEventListener('click', begin);
    startButton.addEventListener('click', begin);

    muteButton.addEventListener('click', () => {
        isMuted = !isMuted;
        muteButton.setAttribute('aria-pressed', String(isMuted));
        muteButton.setAttribute('aria-label', isMuted ? 'Bật âm thanh' : 'Tắt âm thanh');
        muteButton.querySelector('[data-mute-on]').hidden = isMuted;
        muteButton.querySelector('[data-mute-off]').hidden = !isMuted;

        if (isMuted) {
            stopMusic();
        } else if (state?.phase === 'playing') {
            startMusic(false);
        }

        focusTyping();
    });

    state = newState();
    requestAnimationFrame(loop);
}
