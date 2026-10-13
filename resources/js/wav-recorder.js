/*
 * Records the microphone as a WAV file of 16 kHz, 16-bit mono PCM, the format the Azure pronunciation assessment
 * takes, without any library. The microphone is only available on a secure page (https, or localhost).
 */
const TARGET_RATE = 16000;

export const isRecordingSupported = () => window.isSecureContext && Boolean(navigator.mediaDevices?.getUserMedia);

const merge = (chunks) => {
    const samples = new Float32Array(chunks.reduce((length, chunk) => length + chunk.length, 0));
    let offset = 0;

    chunks.forEach((chunk) => {
        samples.set(chunk, offset);
        offset += chunk.length;
    });

    return samples;
};

// Averages the samples that fall into each 16 kHz sample.
const downsample = (samples, rate) => {
    if (rate === TARGET_RATE) {
        return samples;
    }

    const ratio = rate / TARGET_RATE;
    const result = new Float32Array(Math.floor(samples.length / ratio));

    for (let index = 0; index < result.length; index += 1) {
        const start = Math.floor(index * ratio);
        const end = Math.min(Math.floor((index + 1) * ratio), samples.length);
        let sum = 0;

        for (let position = start; position < end; position += 1) {
            sum += samples[position];
        }

        result[index] = sum / Math.max(1, end - start);
    }

    return result;
};

const encodeWav = (samples) => {
    const buffer = new ArrayBuffer(44 + samples.length * 2);
    const view = new DataView(buffer);
    const text = (offset, value) => [...value].forEach((character, index) => view.setUint8(offset + index, character.charCodeAt(0)));

    text(0, 'RIFF');
    view.setUint32(4, 36 + samples.length * 2, true);
    text(8, 'WAVE');
    text(12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20, 1, true);
    view.setUint16(22, 1, true);
    view.setUint32(24, TARGET_RATE, true);
    view.setUint32(28, TARGET_RATE * 2, true);
    view.setUint16(32, 2, true);
    view.setUint16(34, 16, true);
    text(36, 'data');
    view.setUint32(40, samples.length * 2, true);

    samples.forEach((sample, index) => {
        const clamped = Math.max(-1, Math.min(1, sample));

        view.setInt16(44 + index * 2, clamped < 0 ? clamped * 0x8000 : clamped * 0x7fff, true);
    });

    return new Blob([view], { type: 'audio/wav' });
};

/**
 * Starts recording; it stops by itself after `maxMs`. Returns `stop()` and `done`, a promise of the WAV blob.
 * Rejects when the microphone cannot be opened (no permission, no device).
 */
export async function startRecording(maxMs) {
    const stream = await navigator.mediaDevices.getUserMedia({
        audio: { channelCount: 1, echoCancellation: true, noiseSuppression: true },
    });
    const AudioContext = window.AudioContext ?? window.webkitAudioContext;
    const context = new AudioContext();
    const source = context.createMediaStreamSource(stream);
    const processor = context.createScriptProcessor(4096, 1, 1);
    const chunks = [];
    let resolveDone;
    let timer = null;

    const done = new Promise((resolve) => {
        resolveDone = resolve;
    });

    processor.onaudioprocess = (event) => chunks.push(new Float32Array(event.inputBuffer.getChannelData(0)));
    source.connect(processor);
    processor.connect(context.destination);

    let isStopped = false;

    const stop = () => {
        if (!isStopped) {
            isStopped = true;
            clearTimeout(timer);
            processor.disconnect();
            source.disconnect();
            stream.getTracks().forEach((track) => track.stop());
            resolveDone(encodeWav(downsample(merge(chunks), context.sampleRate)));
            context.close();
        }

        return done;
    };

    timer = setTimeout(stop, maxMs);

    return { stop, done };
}
