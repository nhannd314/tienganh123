/*
 * Short answer sounds, synthesised with the Web Audio API so no audio file is needed. Shared by every part of the
 * lesson review. Browsers only start audio after a user gesture, so play them from a click handler.
 *
 * SOUND_PRESETS holds a few styles to choose from (they can be heard on the /demo page); DEFAULT_PRESET is the one
 * the review plays. Everything goes through a compressor so overlapping notes never clip at full volume.
 */
const VOLUME = 0.5;

export const DEFAULT_PRESET = 'marimba';

let context = null;
let output = null;

const audioContext = () => {
    const AudioContext = window.AudioContext ?? window.webkitAudioContext;

    if (!AudioContext) {
        return null;
    }

    if (!context) {
        context = new AudioContext();
        output = context.createDynamicsCompressor();
        output.connect(context.destination);
    }

    if (context.state === 'suspended') {
        context.resume();
    }

    return context;
};

/*
 * One note, `start` seconds from now and fading out over `duration`. Options: `type` (oscillator wave), `level`
 * (share of VOLUME), `slideTo` (frequency glided to over `slide` seconds), `partials` (quieter overtones as
 * [ratio, level, duration]) and `lowpass` (cut-off of a filter that closes as the note fades, for a plucked sound).
 */
const note = (audio, { frequency, start = 0, duration, type = 'sine', level = 1, slideTo = null, slide = 0.08, partials = [], lowpass = null }) => {
    const at = audio.currentTime + start;
    let destination = output;

    if (lowpass) {
        const filter = audio.createBiquadFilter();

        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(lowpass, at);
        filter.frequency.exponentialRampToValueAtTime(Math.max(frequency, 200), at + duration);
        filter.connect(output);
        destination = filter;
    }

    [[1, level, duration], ...partials].forEach(([ratio, partialLevel, partialDuration]) => {
        const source = audio.createOscillator();
        const gain = audio.createGain();
        const peak = VOLUME * partialLevel;

        source.type = type;
        source.frequency.setValueAtTime(frequency * ratio, at);

        if (slideTo) {
            source.frequency.exponentialRampToValueAtTime(slideTo * ratio, at + slide);
        }

        gain.gain.setValueAtTime(0.0001, at);
        gain.gain.exponentialRampToValueAtTime(peak, at + 0.008);
        gain.gain.exponentialRampToValueAtTime(0.0001, at + partialDuration);

        source.connect(gain).connect(destination);
        source.start(at);
        source.stop(at + partialDuration);
    });
};

const play = (notes) => {
    try {
        const audio = audioContext();

        if (audio) {
            notes.forEach((options) => note(audio, options));
        }
    } catch {
        // A missing sound must never break the quiz.
    }
};

const marimba = (frequency, start, duration = 0.4) => ({ frequency, start, duration, partials: [[4, 0.3, 0.08], [10, 0.08, 0.03]] });
const pluck = (frequency, start, duration = 0.6) => ({ frequency, start, duration, type: 'triangle', lowpass: frequency * 6 });
const bell = (frequency, start, duration = 0.3) => ({ frequency, start, duration, partials: [[2, 0.25, duration * 0.6]] });
const coin = (frequency, start, duration = 0.09) => ({ frequency, start, duration, type: 'square', level: 0.3 });
const pop = (frequency, start) => ({ frequency, start, duration: 0.12, slideTo: frequency * 1.8, slide: 0.05 });

/**
 * Each style: its Vietnamese label and the notes for a right answer (three) and a wrong one.
 */
export const SOUND_PRESETS = {
    marimba: {
        label: 'Đàn gỗ (marimba)',
        correct: [marimba(523.3, 0), marimba(659.3, 0.1), marimba(784, 0.2, 0.6)],
        wrong: [marimba(329.6, 0, 0.3), marimba(261.6, 0.16, 0.5)],
    },
    pluck: {
        label: 'Đàn dây gảy (harp)',
        correct: [pluck(587.3, 0), pluck(740, 0.09), pluck(880, 0.18, 0.9)],
        wrong: [pluck(311.1, 0, 0.4), pluck(233.1, 0.18, 0.7)],
    },
    bell: {
        label: 'Chuông nhỏ',
        correct: [bell(1046.5, 0), bell(1318.5, 0.11), bell(1568, 0.22, 0.5)],
        wrong: [bell(659.3, 0, 0.25), bell(554.4, 0.18, 0.45)],
    },
    coin: {
        label: 'Trò chơi điện tử (8-bit)',
        correct: [coin(987.8, 0), coin(1318.5, 0.08), coin(1975.5, 0.16, 0.3)],
        wrong: [
            { frequency: 196, duration: 0.18, type: 'square', level: 0.25, slideTo: 185, slide: 0.18 },
            { frequency: 146.8, start: 0.2, duration: 0.35, type: 'square', level: 0.25, slideTo: 110, slide: 0.35 },
        ],
    },
    pop: {
        label: 'Bong bóng (pop)',
        correct: [pop(600, 0), pop(800, 0.1), pop(1000, 0.2)],
        wrong: [{ frequency: 420, duration: 0.45, slideTo: 180, slide: 0.4 }],
    },
};

export const playPreset = (presetKey, kind) => play(SOUND_PRESETS[presetKey]?.[kind] ?? []);

export const playCorrect = () => playPreset(DEFAULT_PRESET, 'correct');

export const playWrong = () => playPreset(DEFAULT_PRESET, 'wrong');
