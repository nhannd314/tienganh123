/*
 * The exam paper (resources/views/exams/take.blade.php): counts the time down and submits the answers when it is up,
 * keeps the paper full screen (pausing it when full screen is left), warns before leaving the page with answers not
 * submitted, limits how many times a word to listen to may be heard, and records the words and sentences read aloud,
 * which the server scores with Azure and keeps in the attempt.
 */
import { isRecordingSupported, startRecording } from './wav-recorder';

const URGENT_SECONDS = 60;

const form = document.querySelector('[data-exam-form]');

if (form) {
    const csrf = form.querySelector('input[name="_token"]').value;
    let isSubmitting = false;

    const submit = () => {
        if (isSubmitting) {
            return;
        }

        isSubmitting = true;
        form.querySelectorAll('[data-exam-submit]').forEach((button) => {
            button.disabled = true;
        });
        form.submit();
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submit();
    });

    // Enter in an answer moves to the next answer instead of submitting the whole exam.
    form.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || !event.target.matches('input[type="text"]')) {
            return;
        }

        event.preventDefault();
        const inputs = [...form.querySelectorAll('input[type="text"]')];
        inputs[inputs.indexOf(event.target) + 1]?.focus();
    });

    window.addEventListener('beforeunload', (event) => {
        if (!isSubmitting) {
            event.preventDefault();
        }
    });

    // The paper is only shown full screen: leaving it (Esc, another window) covers the paper and blocks it until the
    // student goes back. The time keeps running, as it is counted by the server. A browser without the Fullscreen API
    // (e.g. Safari on iPhone) shows the paper as it is.
    const fullscreenCover = document.querySelector('[data-exam-fullscreen]');

    if (document.fullscreenEnabled && fullscreenCover) {
        const title = fullscreenCover.querySelector('[data-exam-fullscreen-title]');
        const text = fullscreenCover.querySelector('[data-exam-fullscreen-text]');
        let hasStarted = false;

        const showPaper = (isFullscreen) => {
            fullscreenCover.hidden = isFullscreen;
            form.inert = !isFullscreen;

            if (isFullscreen) {
                hasStarted = true;
            } else if (hasStarted) {
                title.textContent = 'Bài thi đang tạm dừng';
                text.textContent = 'Bạn đã thoát chế độ toàn màn hình. Thời gian làm bài vẫn được tính, hãy quay lại ngay nhé.';
            }
        };

        fullscreenCover.querySelector('[data-exam-fullscreen-enter]').addEventListener('click', () => {
            document.documentElement.requestFullscreen().catch(() => {
                text.textContent = 'Trình duyệt không cho vào toàn màn hình, bạn thử bấm lại nhé.';
            });
        });

        document.addEventListener('fullscreenchange', () => showPaper(document.fullscreenElement !== null));
        showPaper(document.fullscreenElement !== null);
    }

    const timer = form.querySelector('[data-exam-timer]');

    if (timer) {
        const text = timer.querySelector('[data-exam-timer-text]');
        const endsAt = Date.now() + Number(timer.dataset.seconds) * 1000;

        const tick = () => {
            const seconds = Math.max(0, Math.round((endsAt - Date.now()) / 1000));

            text.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
            timer.toggleAttribute('data-urgent', seconds <= URGENT_SECONDS);

            if (seconds === 0) {
                clearInterval(interval);
                submit();
            }
        };

        const interval = setInterval(tick, 1000);
        tick();
    }

    // A word to listen to may be heard a few times only; the plays left are remembered for the attempt, so reloading
    // the page does not give them back (the session storage may be unavailable, then they only last the page).
    const storageKey = (listen) => `exam-listen:${form.dataset.attempt}:${listen.dataset.examListen}`;
    const readPlaysLeft = (listen) => {
        try {
            const stored = sessionStorage.getItem(storageKey(listen));

            return stored === null ? Number(listen.dataset.playsLeft) : Number(stored);
        } catch {
            return Number(listen.dataset.playsLeft);
        }
    };

    form.querySelectorAll('[data-exam-listen]').forEach((listen) => {
        const button = listen.querySelector('[data-audio-button]');
        const status = listen.querySelector('[data-exam-listen-status]');
        let playsLeft = readPlaysLeft(listen);

        const show = () => {
            status.textContent = playsLeft > 0 ? `Còn ${playsLeft} lượt nghe` : 'Đã hết lượt nghe';
            button.disabled = playsLeft <= 0;
        };

        button.addEventListener('click', () => {
            playsLeft -= 1;

            try {
                sessionStorage.setItem(storageKey(listen), String(playsLeft));
            } catch {
                // Not remembered: the plays left only last the page.
            }

            // Disabled once the click has reached the player of resources/js/app.js, so the last play still sounds.
            setTimeout(show);
        });

        show();
    });

    const pronounceUrl = form.dataset.pronounceUrl;

    form.querySelectorAll('[data-exam-reading]').forEach((reading) => {
        const maxRecordingMs = Number(reading.dataset.maxRecordingMs);
        const button = reading.querySelector('[data-exam-record]');
        const label = reading.querySelector('[data-exam-record-label]');
        const status = reading.querySelector('[data-exam-record-status]');
        let recording = null;

        if (!isRecordingSupported()) {
            button.disabled = true;
            status.textContent = 'Trình duyệt không cho phép ghi âm trên trang này.';

            return;
        }

        const score = async (audio) => {
            const body = new FormData();
            body.append('group_id', reading.dataset.group);
            body.append('key', reading.dataset.key);
            body.append('audio', audio, 'reading.wav');

            const response = await fetch(pronounceUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body,
            });
            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                status.textContent = result.message ?? 'Chưa chấm được, bạn đọc lại nhé.';
                button.disabled = response.status === 422;

                return;
            }

            status.textContent = `Lần này: ${result.score}/10 · Điểm cao nhất: ${result.best}/10 · còn ${result.attemptsLeft} lượt đọc`;
            button.disabled = result.attemptsLeft <= 0;
        };

        button.addEventListener('click', async () => {
            if (recording) {
                recording.stop();

                return;
            }

            try {
                recording = await startRecording(maxRecordingMs);
            } catch {
                status.textContent = 'Không mở được micro, bạn hãy cho phép dùng micro nhé.';

                return;
            }

            label.textContent = 'Bấm để dừng';
            status.textContent = 'Đang ghi âm…';

            const audio = await recording.done;

            recording = null;
            label.textContent = 'Bấm để đọc';
            button.disabled = true;
            status.textContent = 'Đang chấm điểm…';

            try {
                await score(audio);
            } catch {
                status.textContent = 'Chưa chấm được, bạn đọc lại nhé.';
                button.disabled = false;
            }
        });
    });
}
