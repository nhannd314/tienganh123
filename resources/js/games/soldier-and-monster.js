/*
 * Soldier and Monster, played with the words of a lesson. Loaded only by
 * resources/views/lessons/games/soldier-and-monster.blade.php, which holds the config (words, secondsPerWord, assetsUrl).
 *
 * The meaning of a word is shown with one box per letter. Every letter typed makes the soldier shoot, just for show.
 * The whole word is typed (Backspace fixes a letter) and then graded: only a right word takes its letters off the
 * monster, whose health is the number of letters of all the words. A wrong word is marked wrong, without the answer, and goes to the
 * back of the queue.
 * The monster walks from the right edge to the soldier in secondsPerWord × words seconds; it dies when the queue is
 * empty, and if it gets there first it attacks and the soldier falls.
 *
 * The scene is drawn on a 1200 × 700 canvas (the size of the background) and scaled with the page. Characters are 6 × 6
 * sprite sheets; the muzzle flash and the explosion are TexturePacker atlases, some frames stored rotated.
 */
import { playWrong } from '../sound-effects';

const WIDTH = 1200;
const HEIGHT = 700;
const GROUND_Y = 628;

const SOLDIER_CENTER_X = 205;
const SOLDIER_SCALE = 0.64;
// The tip of the gun in a frame of the soldier sheet (356 × 328).
const MUZZLE = { x: 350, y: 157 };

const MONSTER_SCALE = 0.9;
const MONSTER_FRAME_WIDTH = 421;
const MONSTER_START_X = WIDTH - (MONSTER_FRAME_WIDTH * MONSTER_SCALE) / 2 - 10;
const MONSTER_END_X = 470;
// How far in front of its centre the monster is hit, in scene pixels.
const MONSTER_HIT_OFFSET = 120;
// How far lower, in scene pixels, the dying monster is drawn: its fall frames sit higher than the others and would float.
const MONSTER_DIE_DROP = 20;

const BULLET_SPEED = 1700;
const BULLET_SCALE = 0.4;
const FLASH_SCALE = 0.55;
const EXPLOSION_SCALE = 0.24;

const NEXT_WORD_MS = 350;
// Health points the bar drops per second, so a right word drains it visibly.
const HEALTH_DRAIN_PER_SECOND = 30;
const REVEAL_MS = 900;
const ATTACK_MS = 1100;
const RESULT_DELAY_MS = 2200;
const MAX_FRAME_MS = 100;

const root = document.querySelector('[data-soldier-game]');

if (root) {
    startGame(root);
}

function startGame(root) {
    const config = JSON.parse(root.querySelector('[data-game-config]').textContent);
    const base = config.assetsUrl;
    const totalMs = config.words.length * config.secondsPerWord * 1000;

    const canvas = root.querySelector('[data-canvas]');
    const context = canvas.getContext('2d');
    const ratio = Math.min(window.devicePixelRatio || 1, 2);

    canvas.width = WIDTH * ratio;
    canvas.height = HEIGHT * ratio;
    context.scale(ratio, ratio);

    const hud = root.querySelector('[data-hud]');
    const timeBar = root.querySelector('[data-time-bar]');
    const timeText = root.querySelector('[data-time-text]');
    const wordsDoneText = root.querySelector('[data-words-done]');
    const panel = root.querySelector('[data-word-panel]');
    const meaningText = root.querySelector('[data-meaning]');
    const slotsBox = root.querySelector('[data-slots]');
    const typing = root.querySelector('[data-typing]');
    const startScreen = root.querySelector('[data-start]');
    const startButton = root.querySelector('[data-start-button]');
    const resultScreen = root.querySelector('[data-result]');
    const muteButton = root.querySelector('[data-mute]');

    const template = (name) => root.querySelector(`template[data-tpl="${name}"]`).content.firstElementChild.cloneNode(true);
    const isLetter = (character) => /[\p{L}\p{N}]/u.test(character);
    // The letter without its accents, so a Vietnamese keyboard (Telex turns "ee" into "ê") still types the right letter.
    const plainLetter = (character) => character.normalize('NFD').replace(/\p{M}/gu, '').replace(/đ/giu, 'd').toLowerCase();

    const sounds = {
        music: new Audio(`${base}/audio/background.mp3`),
        win: new Audio(`${base}/audio/win.mp3`),
        lose: new Audio(`${base}/audio/lose.mp3`),
        roar: new Audio(`${base}/audio/monster-roar.mp3`),
    };

    sounds.music.loop = true;
    sounds.music.volume = 0.15;
    sounds.roar.volume = 0.4;

    let isMuted = false;

    const playSound = (name) => {
        if (isMuted) {
            return;
        }

        const sound = sounds[name];

        sound.currentTime = 0;
        sound.play().catch(() => {});
    };

    const stopSounds = () => Object.values(sounds).forEach((sound) => sound.pause());

    let sprites = null;
    let state = null;
    let lastFrameAt = null;

    // Loading

    const loadImage = (path) =>
        new Promise((resolve, reject) => {
            const image = new Image();

            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = `${base}/${path}`;
        });

    const loadJson = (path) => fetch(`${base}/${path}`).then((response) => response.json());

    // A 6 × 6 sheet: every frame the same size, the image cut evenly.
    const gridSheet = (image, frameMs, columns = 6, rows = 6) => {
        const width = Math.floor(image.naturalWidth / columns);
        const height = Math.floor(image.naturalHeight / rows);

        return {
            image,
            frameMs,
            frames: Array.from({ length: columns * rows }, (_, index) => ({
                x: (index % columns) * width,
                y: Math.floor(index / columns) * height,
                width,
                height,
                rotated: false,
                offsetX: 0,
                offsetY: 0,
                sourceWidth: width,
                sourceHeight: height,
            })),
        };
    };

    // A TexturePacker atlas (JSON array), frames in the order of their numbered file names.
    const atlasSheet = (image, json, frameMs) => ({
        image,
        frameMs,
        frames: [...json.frames]
            .sort((first, second) => parseInt(first.filename, 10) - parseInt(second.filename, 10))
            .map((frame) => ({
                x: frame.frame.x,
                y: frame.frame.y,
                width: frame.frame.w,
                height: frame.frame.h,
                rotated: frame.rotated,
                offsetX: frame.spriteSourceSize.x,
                offsetY: frame.spriteSourceSize.y,
                sourceWidth: frame.sourceSize.w,
                sourceHeight: frame.sourceSize.h,
            })),
    });

    const loadSprites = async () => {
        const [background, soldier, soldierDie, monster, monsterAttack, monsterDie, bullet1, bullet2, bullet3, flash, flashJson, explosion, explosionJson] =
            await Promise.all([
                loadImage('bg/1.png'),
                loadImage('soldiers/sprite.png'),
                loadImage('soldiers/die/sprite.png'),
                loadImage('monsters/sprite.png'),
                loadImage('monsters/attack/sprite.png'),
                loadImage('monsters/die/sprite.png'),
                loadImage('bullets/1.png'),
                loadImage('bullets/2.png'),
                loadImage('bullets/3.png'),
                loadImage('shootfx/spritesheet.png'),
                loadJson('shootfx/spritesheet.json'),
                loadImage('explosion/spritesheet.png'),
                loadJson('explosion/spritesheet.json'),
            ]);

        return {
            background,
            bullets: [bullet1, bullet2, bullet3],
            soldier: gridSheet(soldier, 61),
            soldierDie: gridSheet(soldierDie, 45),
            monster: gridSheet(monster, 60),
            monsterAttack: gridSheet(monsterAttack, 40),
            monsterDie: gridSheet(monsterDie, 50),
            flash: atlasSheet(flash, flashJson, 18),
            explosion: atlasSheet(explosion, explosionJson, 28),
        };
    };

    // Drawing

    // Draws one frame with the top left corner of its untrimmed source at (left, top). A rotated frame is stored turned
    // 90° clockwise in the atlas, so it is turned back.
    const drawFrame = (sheet, frame, left, top, scale, alpha = 1) => {
        const x = left + frame.offsetX * scale;
        const y = top + frame.offsetY * scale;
        const width = frame.width * scale;
        const height = frame.height * scale;

        context.save();
        context.globalAlpha = alpha;

        if (frame.rotated) {
            context.translate(x, y + height);
            context.rotate(-Math.PI / 2);
            context.drawImage(sheet.image, frame.x, frame.y, frame.height, frame.width, 0, 0, height, width);
        } else {
            context.drawImage(sheet.image, frame.x, frame.y, frame.width, frame.height, x, y, width, height);
        }

        context.restore();
    };

    // The frame of an animation `elapsed` ms after it started, looping or holding its last frame.
    const frameOf = (sheet, elapsed, loop) => {
        // A frame's timestamp can be a little older than the moment the animation started, hence the floor at 0.
        const index = Math.max(0, Math.floor(elapsed / sheet.frameMs));

        return sheet.frames[loop ? index % sheet.frames.length : Math.min(index, sheet.frames.length - 1)];
    };

    // Draws a character animation standing on the ground, centred on `centerX`, `drop` scene pixels lower.
    const drawCharacter = (animation, centerX, scale, now, drop = 0) => {
        const frame = frameOf(animation.sheet, now - animation.startedAt, animation.loop);

        drawFrame(animation.sheet, frame, centerX - (frame.sourceWidth * scale) / 2, GROUND_Y - frame.sourceHeight * scale + drop, scale);
    };

    const muzzle = () => {
        const frame = sprites.soldier.frames[0];

        return {
            x: SOLDIER_CENTER_X - (frame.sourceWidth * SOLDIER_SCALE) / 2 + MUZZLE.x * SOLDIER_SCALE,
            y: GROUND_Y - frame.sourceHeight * SOLDIER_SCALE + MUZZLE.y * SOLDIER_SCALE,
        };
    };

    const drawHealthBar = (now) => {
        if (state.monster.animation.sheet === sprites.monsterDie) {
            return;
        }

        const width = 110;
        const height = 10;
        const x = state.monster.x - width / 2;
        const y = GROUND_Y - sprites.monster.frames[0].sourceHeight * MONSTER_SCALE - 24;
        const share = state.shownHealth / state.maxHealth;

        context.save();
        context.fillStyle = 'rgba(27, 31, 59, 0.55)';
        context.beginPath();
        context.roundRect(x - 2, y - 2, width + 4, height + 4, 7);
        context.fill();
        context.fillStyle = '#ffffff';
        context.beginPath();
        context.roundRect(x, y, width, height, 5);
        context.fill();

        if (share > 0) {
            context.fillStyle = share > 0.5 ? '#25c559' : share > 0.25 ? '#f2b800' : '#fb3f40';
            context.beginPath();
            context.roundRect(x, y, Math.max(width * share, height), height, 5);
            context.fill();
        }

        context.restore();

        if (now < state.monster.hitUntil) {
            context.save();
            context.globalAlpha = (state.monster.hitUntil - now) / 160;
            context.fillStyle = '#ffffff';
            context.beginPath();
            context.roundRect(x, y, width, height, 5);
            context.fill();
            context.restore();
        }
    };

    const draw = (now) => {
        context.drawImage(sprites.background, 0, 0, WIDTH, HEIGHT);

        drawCharacter(state.soldier.animation, SOLDIER_CENTER_X, SOLDIER_SCALE, now);

        // The monster shakes a little when hit.
        const shake = now < state.monster.hitUntil ? Math.sin(now / 12) * 4 : 0;

        const drop = state.monster.animation.sheet === sprites.monsterDie ? MONSTER_DIE_DROP : 0;

        drawCharacter(state.monster.animation, state.monster.x + shake, MONSTER_SCALE, now, drop);
        drawHealthBar(now);

        state.bullets.forEach((bullet) => {
            context.drawImage(bullet.image, bullet.x, bullet.y - (bullet.image.naturalHeight * BULLET_SCALE) / 2, bullet.image.naturalWidth * BULLET_SCALE, bullet.image.naturalHeight * BULLET_SCALE);
        });

        state.flashes.forEach((flash) => {
            const frame = frameOf(sprites.flash, now - flash.startedAt, false);

            drawFrame(sprites.flash, frame, flash.x - 22 * FLASH_SCALE, flash.y - 78 * FLASH_SCALE, FLASH_SCALE);
        });

        state.explosions.forEach((explosion) => {
            const frame = frameOf(sprites.explosion, now - explosion.startedAt, false);

            drawFrame(
                sprites.explosion,
                frame,
                explosion.x - (frame.sourceWidth * EXPLOSION_SCALE) / 2,
                explosion.y - (frame.sourceHeight * EXPLOSION_SCALE) / 2,
                EXPLOSION_SCALE,
            );
        });
    };

    // Game state

    const animation = (sheet, loop = true) => ({ sheet, loop, startedAt: performance.now() });

    const shuffle = (items) => {
        const shuffled = [...items];

        for (let index = shuffled.length - 1; index > 0; index -= 1) {
            const other = Math.floor(Math.random() * (index + 1));

            [shuffled[index], shuffled[other]] = [shuffled[other], shuffled[index]];
        }

        return shuffled;
    };

    const lettersOf = (word) => [...word].filter(isLetter);

    const newState = () => {
        const maxHealth = config.words.reduce((total, { word }) => total + lettersOf(word).length, 0);

        return {
            phase: 'ready',
            queue: shuffle(config.words),
            current: null,
            typed: [],
            isRevealing: false,
            isAdvancing: false,
            pendingLetters: [],
            wordsDone: 0,
            elapsed: 0,
            health: maxHealth,
            shownHealth: maxHealth,
            maxHealth,
            soldier: { animation: animation(sprites.soldier) },
            monster: { x: MONSTER_START_X, animation: animation(sprites.monster), hitUntil: 0 },
            bullets: [],
            flashes: [],
            explosions: [],
        };
    };

    const shoot = () => {
        const { x, y } = muzzle();
        const now = performance.now();

        state.bullets.push({ x, y, image: sprites.bullets[Math.floor(Math.random() * sprites.bullets.length)] });
        state.flashes.push({ x, y, startedAt: now });
        playShot();
    };

    const update = (now, elapsedMs) => {
        if (state.phase === 'playing') {
            state.elapsed = Math.min(state.elapsed + elapsedMs, totalMs);

            const progress = state.elapsed / totalMs;

            state.monster.x = MONSTER_START_X + (MONSTER_END_X - MONSTER_START_X) * progress;
            renderTime();

            if (state.elapsed >= totalMs) {
                lose();
            }
        }

        const hitX = state.monster.x - MONSTER_HIT_OFFSET;

        state.bullets = state.bullets.filter((bullet) => {
            bullet.x += (BULLET_SPEED * elapsedMs) / 1000;

            if (bullet.x < hitX) {
                return true;
            }

            state.explosions.push({ x: hitX + 20, y: bullet.y, startedAt: now });
            state.monster.hitUntil = now + 160;

            return false;
        });

        state.shownHealth = Math.max(state.health, state.shownHealth - (HEALTH_DRAIN_PER_SECOND * elapsedMs) / 1000);

        // The monster falls once the last bullet has hit it.
        if (state.phase === 'won' && state.bullets.length === 0 && state.monster.animation.sheet !== sprites.monsterDie) {
            state.monster.animation = animation(sprites.monsterDie, false);
        }

        const flashMs = sprites.flash.frames.length * sprites.flash.frameMs;
        const explosionMs = sprites.explosion.frames.length * sprites.explosion.frameMs;

        state.flashes = state.flashes.filter((flash) => now - flash.startedAt < flashMs);
        state.explosions = state.explosions.filter((explosion) => now - explosion.startedAt < explosionMs);
    };

    const loop = (now) => {
        const elapsedMs = lastFrameAt === null ? 0 : Math.min(now - lastFrameAt, MAX_FRAME_MS);

        lastFrameAt = now;
        // Asked for first, so one failed frame can never stop the game.
        requestAnimationFrame(loop);
        update(now, elapsedMs);
        draw(now);
    };

    // HUD and word panel

    // Time counts up from 0 to the limit, and the bar fills as it goes.
    const renderTime = () => {
        timeBar.style.width = `${(state.elapsed / totalMs) * 100}%`;
        timeText.textContent = `${Math.floor(state.elapsed / 1000)}/${totalMs / 1000}s`;
        wordsDoneText.textContent = state.wordsDone;
    };

    const letterBoxes = () => [...slotsBox.querySelectorAll('[data-letter]')];

    const renderWord = () => {
        const { word, meaning } = state.current;

        meaningText.textContent = meaning;
        slotsBox.replaceChildren(
            ...word.split(/\s+/).map((part) => {
                const group = document.createElement('span');

                group.className = 'flex flex-wrap justify-center gap-[0.8cqw]';
                [...part].forEach((character) => {
                    const box = template(isLetter(character) ? 'letter' : 'letter-fixed');

                    if (!isLetter(character)) {
                        box.textContent = character;
                    }

                    group.append(box);
                });

                return group;
            }),
        );

        markActive();
    };

    // The box the next letter goes into is highlighted.
    const markActive = () => {
        letterBoxes().forEach((box, index) => {
            if (index === state.typed.length) {
                box.dataset.state = 'active';
            } else {
                delete box.dataset.state;
            }
        });
    };

    // Runs `callback` after `ms` unless another game has started since, so a restart never inherits an old timer.
    const later = (callback, ms) => {
        const game = state;

        setTimeout(() => {
            if (state === game) {
                callback();
            }
        }, ms);
    };

    const nextWord = () => {
        if (state.phase !== 'playing') {
            return;
        }

        state.current = state.queue.shift();
        state.typed = [];
        state.isRevealing = false;
        state.isAdvancing = false;
        renderWord();
        focusTyping();

        // Letters typed while the finished word was still on screen belong to this one.
        state.pendingLetters.splice(0).forEach(typeLetter);
    };

    const focusTyping = () => typing.focus({ preventScroll: true });

    const canType = () => state?.phase === 'playing' && !state.isRevealing && state.current !== null;

    const typeLetter = (letter) => {
        if (!canType()) {
            return;
        }

        if (state.isAdvancing) {
            state.pendingLetters.push(letter);

            return;
        }

        letterBoxes()[state.typed.length].textContent = letter;
        state.typed.push(letter);
        markActive();
        shoot();

        if (state.typed.length === lettersOf(state.current.word).length) {
            grade();
        }
    };

    const eraseLetter = () => {
        if (!canType() || state.isAdvancing || state.typed.length === 0) {
            return;
        }

        state.typed.pop();
        letterBoxes()[state.typed.length].textContent = '';
        markActive();
    };

    // Grades the word once every box is filled.
    const grade = () => {
        const expected = lettersOf(state.current.word);
        const boxes = letterBoxes();
        const isRight = state.typed.every((letter, index) => plainLetter(letter) === plainLetter(expected[index]));

        if (isRight) {
            boxes.forEach((box, index) => {
                box.textContent = expected[index];
                box.dataset.state = 'correct';
            });
            state.health -= expected.length;
            state.wordsDone += 1;
            renderTime();

            if (state.queue.length === 0) {
                win();

                return;
            }

            state.isAdvancing = true;
            later(nextWord, NEXT_WORD_MS);

            return;
        }

        // A wrong word is marked wrong, without the answer, and asked again at the end.
        boxes.forEach((box) => {
            box.dataset.state = 'wrong';
        });
        state.queue.push(state.current);
        state.isRevealing = true;
        playWrong();
        later(nextWord, REVEAL_MS);
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
        wakeShotAudio();
        state = newState();
        state.phase = 'playing';
        startScreen.hidden = true;
        resultScreen.hidden = true;
        hud.hidden = false;
        panel.hidden = false;
        renderTime();

        if (!isMuted) {
            sounds.music.currentTime = 0;
            sounds.music.play().catch(() => {});
        }

        playSound('roar');
        nextWord();
    };

    const resultNote = resultScreen.querySelector('[data-result-note]');
    const bestTimes = document.querySelector('[data-best-times]');

    // A won game: the time goes to the server, which keeps it when it is the best of the player, and the list of the
    // fastest players on the page is replaced by the one it answers with. A guest is told to sign in to be ranked.
    const saveTime = async (milliseconds) => {
        if (!config.saveUrl) {
            resultNote.textContent = 'Đăng nhập để lưu vào bảng xếp hạng.';
            resultNote.hidden = false;

            return;
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

        } catch {
            resultNote.textContent = 'Không lưu được thời gian, bạn thử lại sau nhé.';
            resultNote.hidden = false;
        }
    };

    const finish = (title, text, sound) => {
        resultNote.hidden = true;
        panel.hidden = true;
        typing.blur();
        sounds.music.pause();

        later(() => playSound(sound), 600);
        later(() => {
            resultScreen.querySelector('[data-result-title]').textContent = title;
            resultScreen.querySelector('[data-result-text]').textContent = text;
            resultScreen.hidden = false;
        }, RESULT_DELAY_MS);
    };

    const win = () => {
        state.phase = 'won';
        // It falls when the last bullet hits (see update), or after a moment if the scene was not drawn meanwhile.
        later(() => {
            if (state.monster.animation.sheet !== sprites.monsterDie) {
                state.monster.animation = animation(sprites.monsterDie, false);
            }
        }, 600);

        const seconds = Math.round(state.elapsed / 1000);

        finish('Quái vật đã gục!', `Thắng sau ${seconds} giây.`, 'win');
        saveTime(Math.round(state.elapsed));
    };

    const lose = () => {
        state.phase = 'lost';
        state.monster.animation = animation(sprites.monsterAttack);
        playSound('roar');

        later(() => {
            state.soldier.animation = animation(sprites.soldierDie, false);
        }, ATTACK_MS);

        finish('Quái vật tràn tới!', `Hạ được ${state.wordsDone}/${config.words.length} từ. Thử lại nhé!`, 'lose');
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
            stopSounds();
        } else if (state?.phase === 'playing') {
            sounds.music.play().catch(() => {});
        }

        focusTyping();
    });

    // A short "pew", synthesised so no file is needed: a square wave gliding down quickly, as in the first version of the
    // game on tienganh.pro.vn. The audio context is
    // made and woken up by the click on Start: shots are fired from timers, where a browser would keep a new one muted.
    let shotAudio = null;

    const wakeShotAudio = () => {
        const AudioContext = window.AudioContext ?? window.webkitAudioContext;

        if (!AudioContext) {
            return;
        }

        shotAudio ??= new AudioContext();

        if (shotAudio.state === 'suspended') {
            shotAudio.resume();
        }
    };

    const playShot = () => {
        if (isMuted || !shotAudio) {
            return;
        }

        try {

            const at = shotAudio.currentTime;
            const duration = 0.12;
            const source = shotAudio.createOscillator();
            const gain = shotAudio.createGain();

            source.type = 'square';
            source.frequency.setValueAtTime(900, at);
            source.frequency.exponentialRampToValueAtTime(120, at + duration);
            gain.gain.setValueAtTime(0.2, at);
            gain.gain.exponentialRampToValueAtTime(0.001, at + duration);
            source.connect(gain).connect(shotAudio.destination);
            source.start(at);
            source.stop(at + duration);
        } catch {
            // A missing sound must never break the game.
        }
    };

    loadSprites()
        .then((loaded) => {
            sprites = loaded;
            state = newState();
            startButton.disabled = false;
            startButton.querySelector('[data-start-label]').textContent = 'Bắt đầu';
            requestAnimationFrame(loop);
        })
        .catch(() => {
            startButton.querySelector('[data-start-label]').textContent = 'Không tải được trò chơi';
        });
}
