/*
 * Lesson review quiz (part 1: word → meaning, part 2: meaning → word, part 3: heard word → meaning, part 4: meaning →
 * typed word, part 5: word → spoken and scored out of 10 by Azure on the server (part 10 the same for a whole sentence), parts 7 and 11: a sentence or paragraph with its marked
 * words typed into inputs). Loaded only by
 * resources/views/lessons/review.blade.php.
 *
 * Questions come from the server as JSON. In the `choice` mode an answer is graded the moment it is picked; in the
 * `spell` mode the word is typed into one box per letter and graded as soon as every box is filled; in the `sentence`
 * mode every marked word of the sentence is an input, graded when "Kiểm tra" is pressed; the `write` mode (part 8) has the
 * whole sentence typed into one box the same way (part 9 after hearing it); the `passage` mode (part 11) shows the whole
 * passage with an input for each of its words and grades them all at once. The next question follows after
 * `autoAdvanceMs`, and the results are sent to the server once, after the last question has been answered. A sound
 * tells a right answer from a wrong one, the same in every part.
 */
import { playCorrect, playWrong } from './sound-effects';
import { isRecordingSupported, startRecording } from './wav-recorder';

const MAX_RECORDING_MS = 4000;

const TIMER_RUNNING = ['border-primary-border', 'bg-primary-bg', 'text-primary-text'];
const TIMER_FINISHED = ['border-success-border', 'bg-success-bg', 'text-success-text'];

const SAVE_ATTEMPTS = 3;
const SAVE_RETRY_MS = 2000;

const root = document.querySelector('[data-review]');

if (root) {
    startReview(root);
}

function startReview(root) {
    const config = JSON.parse(root.querySelector('[data-review-config]').textContent);
    const { questions, submitUrl, csrf, autoAdvanceMs, canSave, optionsLang, passPercent, autoListen, mode } = config;
    const { pronounceUrl, pronunciationPassScore, recordHint, step, maxRecordingMs } = config;
    const total = questions.length;

    const answersBox = root.querySelector('[data-answers]');
    const slotsBox = root.querySelector('[data-slots]');
    const sentenceBox = root.querySelector('[data-sentence]');
    const translationBox = root.querySelector('[data-sentence-translation]');
    const checkButton = root.querySelector('[data-check]');
    const writeInput = root.querySelector('[data-write]');
    const recordButton = root.querySelector('[data-record]');
    const recordStatus = root.querySelector('[data-record-status]');
    const recordScore = root.querySelector('[data-record-score]');
    const alertBox = root.querySelector('[data-alert]');
    const prompt = root.querySelector('[data-prompt]');
    const playArea = root.querySelector('[data-play]');
    const resultArea = root.querySelector('[data-result]');
    const timerPill = root.querySelector('[data-timer-pill]');
    const ring = document.querySelector('[data-ring-bar]');

    const state = { index: 0, answers: [], seconds: 0, finished: false, timer: null, advance: null };

    const template = (name) => root.querySelector(`template[data-tpl="${name}"]`).content.firstElementChild.cloneNode(true);
    const pad = (value) => String(value).padStart(2, '0');
    const formatTime = (seconds) => `${pad(Math.floor(seconds / 60))}:${pad(seconds % 60)}`;

    const setFill = (bar, value, max) => {
        bar.setAttribute('aria-valuenow', String(value));
        bar.setAttribute('aria-valuemax', String(max));
        bar.querySelector('[data-progress-fill]').style.width = `${max > 0 ? (value / max) * 100 : 0}%`;
    };

    // Speak mode shows the average of the best score of each word read so far, e.g. "8,5"; the other modes the number of right answers.
    const formatScore = (score) => (Math.round(score * 10) / 10).toString().replace('.', ',');
    const formatAverage = (scores) => formatScore(scores.length === 0 ? 0 : scores.reduce((sum, score) => sum + score, 0) / scores.length);

    // The part being played: its progress bar and its score.
    const renderPartStats = () => {
        const answered = state.answers.filter(Boolean);
        const correct = mode === 'speak' ? formatAverage(answered.map((answer) => answer.score)) : answered.filter((answer) => answer.correct).length;

        setFill(root.querySelector('[data-part-bar]'), answered.length, total);
        document.querySelectorAll('[data-correct-count]').forEach((element) => {
            element.textContent = String(correct);
        });
    };

    // The sidebar: the best score of the user in the lesson and their rank, as saved on the server.
    const applyStanding = (standing) => {
        if (!standing) {
            return;
        }

        document.querySelectorAll('[data-score]').forEach((element) => {
            element.textContent = String(standing.score);
        });
        document.querySelectorAll('[data-rank]').forEach((element) => {
            element.textContent = standing.rank;
        });
    };

    // The sidebar: progress over all the parts, as saved on the server.
    const applyProgress = (progress) => {
        if (!progress) {
            return;
        }

        ['done', 'correct', 'wrong'].forEach((key) => {
            document.querySelectorAll(`[data-stat-text="${key}"]`).forEach((element) => {
                element.textContent = String(progress[key]);
            });
            document.querySelectorAll(`[data-stat-bar="${key}"]`).forEach((bar) => setFill(bar, progress[key], progress.total));
        });
        document.querySelectorAll('[data-pronunciation-text]').forEach((element) => {
            element.textContent = formatScore(progress.pronunciation);
        });
        document.querySelectorAll('[data-pronunciation-bar]').forEach((bar) => setFill(bar, progress.pronunciation, 10));
        document.querySelectorAll('[data-stat-total]').forEach((element) => {
            element.textContent = String(progress.total);
        });
        document.querySelectorAll('[data-total-time]').forEach((element) => {
            element.textContent = formatTime(progress.seconds);
        });

        document.querySelector('[data-ring-value]').textContent = String(progress.percent);
        document.querySelector('[data-ring]').setAttribute('aria-label', `Hoàn thành ${progress.percent}%`);
        ring.setAttribute('stroke-dashoffset', String(Number(ring.dataset.circumference) * (1 - progress.percent / 100)));
    };

    const renderTimer = () => {
        document.querySelectorAll('[data-timer]').forEach((element) => {
            element.textContent = formatTime(state.seconds);
        });
        timerPill.classList.remove(...TIMER_RUNNING, ...TIMER_FINISHED);
        timerPill.classList.add(...(state.finished ? TIMER_FINISHED : TIMER_RUNNING));
    };

    const buildAnswer = (text, variant, disabled) => {
        const card = template(variant ? `answer-${variant}` : 'answer');

        card.querySelector('[data-text]').textContent = text;
        card.disabled = disabled;

        if (optionsLang) {
            card.lang = optionsLang;
        }

        return card;
    };

    const renderQuestion = () => {
        const question = questions[state.index];

        if (mode === 'passage') {
            resetPassage();

            return;
        }

        root.querySelector('[data-q-current]').textContent = String(state.index + 1);
        const promptWord = prompt.querySelector('[data-prompt-word]');

        if (promptWord) {
            promptWord.textContent = question.prompt;
        }

        // Part 1 shows the English word with its IPA and listen buttons; part 2 only the meaning; part 3 only a listen
        // button, labelled without the word.
        const ipaRow = prompt.querySelector('[data-prompt-ipa-row]');

        if (ipaRow) {
            prompt.querySelector('[data-prompt-ipa]').textContent = question.phonetic ?? '';
            ipaRow.hidden = !question.phonetic;
        }

        prompt.querySelectorAll('[data-audio-button]').forEach((button) => {
            button.dataset.text = question.prompt;

            // A recorded sentence is played when there is one, otherwise the text is spoken.
            if (question.audio !== undefined) {
                button.dataset.src = question.audio ?? '';
            }

            if (promptWord) {
                button.setAttribute('aria-label', `Nghe phát âm ${question.prompt}`);
            }
        });

        // Speak the word as the question appears. Before the first click on the page the browser may stay silent.
        if (autoListen) {
            prompt.querySelector('[data-prompt-listen]')?.click();
        }

        alertBox.replaceChildren();

        if (mode === 'speak') {
            resetRecorder();

            return;
        }

        if (mode === 'spell') {
            renderSlots(question.word);

            return;
        }

        if (mode === 'sentence') {
            renderSentence(question);

            return;
        }

        if (mode === 'write') {
            writeInput.value = '';
            writeInput.disabled = false;
            writeInput.classList.remove('border-success', 'bg-success-bg', 'text-success-text', 'border-danger', 'bg-danger-bg', 'text-danger-text');
            writeInput.classList.add('border-line-default');
            checkButton.disabled = false;
            writeInput.focus({ preventScroll: true });

            return;
        }

        answersBox.replaceChildren(
            ...question.options.map((option, optionIndex) => {
                const card = buildAnswer(option, null, false);

                card.addEventListener('click', () => pick(optionIndex));

                return card;
            }),
        );
    };

    const showResult = (question, picked) => {
        answersBox.replaceChildren(
            ...question.options.map((option, optionIndex) => {
                const variant = optionIndex === question.answer ? 'correct' : optionIndex === picked ? 'wrong' : null;

                return buildAnswer(option, variant, true);
            }),
        );

        showAlert(picked === question.answer, question.options[question.answer]);
    };

    const showAlert = (isCorrect, correctText) => {
        if (isCorrect) {
            alertBox.replaceChildren(template('alert-success'));

            return;
        }

        const alert = template('alert-danger');

        alert.querySelector('[data-correct-text]').textContent = correctText;
        alertBox.replaceChildren(alert);
    };

    const pick = (picked) => {
        if (state.answers[state.index] || state.finished) {
            return;
        }

        const question = questions[state.index];

        record(question, question.options[picked], picked === question.answer);
        showResult(question, picked);
    };

    // Spell mode: the boxes of the current word, in order.
    const letterInputs = () => [...slotsBox.querySelectorAll('[data-letter]')];
    const isLetter = (character) => /[\p{L}\p{N}]/u.test(character);

    const isComplete = () => letterInputs().every((input) => input.value !== '');

    // Puts `text` into the boxes from `index` on (one letter typed, or a pasted word), then focuses the next box. The
    // word is graded as soon as the last empty box is filled.
    const fillFrom = (index, text) => {
        const inputs = letterInputs();
        let position = index;

        [...text].filter(isLetter).forEach((letter) => {
            if (position < inputs.length) {
                inputs[position].value = letter;
                position += 1;
            }
        });

        if (isComplete()) {
            check();

            return;
        }

        inputs[Math.min(position, inputs.length - 1)].focus();
    };

    const renderSlots = (word) => {
        const groups = word.split(/\s+/).map((part) => {
            const group = document.createElement('div');

            group.className = 'flex flex-wrap justify-center gap-2 sm:gap-3';
            [...part].forEach((character) => {
                if (isLetter(character)) {
                    group.append(template('letter'));
                } else {
                    const fixed = template('letter-fixed');

                    fixed.textContent = character;
                    group.append(fixed);
                }
            });

            return group;
        });

        slotsBox.replaceChildren(...groups);

        const inputs = letterInputs();

        inputs.forEach((input, index) => {
            input.setAttribute('aria-label', `Chữ cái thứ ${index + 1}/${inputs.length}`);
            input.addEventListener('focus', () => input.select());
            input.addEventListener('input', () => {
                const typed = input.value;

                input.value = '';
                fillFrom(index, typed);
            });
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && input.value === '' && index > 0) {
                    event.preventDefault();
                    inputs[index - 1].value = '';
                    inputs[index - 1].focus();
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    event.preventDefault();
                    inputs[index - 1].focus();
                } else if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                    event.preventDefault();
                    inputs[index + 1].focus();
                }
            });
        });

        inputs[0]?.focus({ preventScroll: true });
    };

    // The typed word, with the spaces and hyphens of the real one.
    const typedWord = (word) => {
        const letters = letterInputs().map((input) => input.value);

        return word
            .split(/\s+/)
            .map((part) => [...part].map((character) => (isLetter(character) ? (letters.shift() ?? '') : character)).join(''))
            .join(' ');
    };

    const check = () => {
        if (!isComplete() || state.answers[state.index] || state.finished) {
            return;
        }

        const question = questions[state.index];
        const typed = typedWord(question.word);
        const isCorrect = typed.toLowerCase() === question.word.toLowerCase();
        const expected = [...question.word].filter(isLetter);

        letterInputs().forEach((input, index) => {
            const isRight = input.value.toLowerCase() === expected[index]?.toLowerCase();

            input.disabled = true;
            input.classList.remove('border-line-default');
            input.classList.add(...(isRight ? ['border-success', 'bg-success-bg', 'text-success-text'] : ['border-danger', 'bg-danger-bg', 'text-danger-text']));
        });

        record(question, typed, isCorrect);
        showAlert(isCorrect, question.word);
    };

    // Sentence mode: the text of the sentence with an input in place of each marked word. The words typed are joined by `|`,
    // as LessonReviewController expects, and graded like there: ignoring case and extra spaces.
    const blankInputs = () => [...sentenceBox.querySelectorAll('[data-blank]')];
    const normalize = (text) => text.trim().replace(/\s+/g, ' ').toLowerCase();

    const renderSentence = (question) => {
        sentenceBox.replaceChildren(
            ...question.segments.map((segment) => {
                if (!segment.isMarked) {
                    const text = document.createElement('span');

                    text.className = 'whitespace-pre-wrap';
                    text.textContent = segment.text;

                    return text;
                }

                const input = template('blank');

                input.size = Math.max([...segment.text].length, 3);
                input.setAttribute('aria-label', 'Từ cần điền');

                return input;
            }),
        );
        translationBox.textContent = question.translation ?? '';
        translationBox.hidden = !question.translation;
        checkButton.disabled = false;

        const inputs = blankInputs();

        inputs.forEach((input, index) => {
            input.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();

                if (inputs[index + 1]) {
                    inputs[index + 1].focus();
                } else {
                    checkSentence();
                }
            });
        });

        inputs[0]?.focus({ preventScroll: true });
    };

    const checkSentence = () => {
        if (state.answers[state.index] || state.finished) {
            return;
        }

        const question = questions[state.index];
        const expected = question.segments.filter((segment) => segment.isMarked).map((segment) => segment.text);
        const inputs = blankInputs();
        let isCorrect = true;

        inputs.forEach((input, index) => {
            const isRight = normalize(input.value) === normalize(expected[index]);

            isCorrect &&= isRight;
            input.disabled = true;
            input.classList.remove('border-line-default');
            input.classList.add(...(isRight ? ['border-success', 'bg-success-bg', 'text-success-text'] : ['border-danger', 'bg-danger-bg', 'text-danger-text']));
        });

        checkButton.disabled = true;
        record(question, inputs.map((input) => input.value).join('|'), isCorrect);
        showAlert(isCorrect, expected.join(', '));
    };

    // Passage mode (part 11): the whole passage is on the page with an input for each word; "Kiểm tra" grades them all at once and
    // the graded passage stays on the page, with the right word beside each wrong one, under the summary.
    const passageInputs = () => [...root.querySelectorAll('[data-passage] [data-blank]')];

    const resetPassage = () => {
        passageInputs().forEach((input, index) => {
            input.value = '';
            input.disabled = false;
            input.classList.remove('border-success', 'bg-success-bg', 'text-success-text', 'border-danger', 'bg-danger-bg', 'text-danger-text');
            input.classList.add('border-line-default');
            input.nextElementSibling.hidden = true;

            if (!input.dataset.bound) {
                input.dataset.bound = '1';
                input.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();

                    const inputs = passageInputs();

                    if (inputs[inputs.indexOf(input) + 1]) {
                        inputs[inputs.indexOf(input) + 1].focus();
                    } else {
                        checkPassage();
                    }
                });
            }

            if (index === 0) {
                input.focus({ preventScroll: true });
            }
        });
        checkButton.disabled = false;
    };

    const checkPassage = () => {
        if (state.finished) {
            return;
        }

        const inputs = passageInputs();

        state.answers = inputs.map((input) => {
            const question = questions.find((candidate) => String(candidate.id) === input.dataset.id);
            const isCorrect = normalize(input.value) === normalize(question.word);
            const hint = input.nextElementSibling;

            input.disabled = true;
            input.classList.remove('border-line-default');
            input.classList.add(...(isCorrect ? ['border-success', 'bg-success-bg', 'text-success-text'] : ['border-danger', 'bg-danger-bg', 'text-danger-text']));
            hint.textContent = isCorrect ? '' : `(${question.word})`;
            hint.hidden = isCorrect;

            return { wordId: question.id, selected: input.value, correct: isCorrect, score: null };
        });

        checkButton.disabled = true;
        (state.answers.every((answer) => answer.correct) ? playCorrect : playWrong)();
        renderPartStats();
        finish();
        showSummary();
    };

    // Write mode: the sentence typed whole, compared like the server does: any case, no punctuation, single spaces.
    const normalizeSentence = (text) => text.replace(/[’‘]/g, "'").replace(/[^\p{L}\p{N}' ]+/gu, ' ').trim().replace(/\s+/g, ' ').toLowerCase();

    const checkWrite = () => {
        if (state.answers[state.index] || state.finished || writeInput.value.trim() === '') {
            return;
        }

        const question = questions[state.index];
        const isCorrect = normalizeSentence(writeInput.value) === normalizeSentence(question.word);

        writeInput.disabled = true;
        writeInput.classList.remove('border-line-default');
        writeInput.classList.add(...(isCorrect ? ['border-success', 'bg-success-bg', 'text-success-text'] : ['border-danger', 'bg-danger-bg', 'text-danger-text']));
        checkButton.disabled = true;
        record(question, writeInput.value, isCorrect);
        showAlert(isCorrect, question.word);
    };

    // Speak mode: the word is recorded and sent to the server, which has Azure score it out of 10. A word may be read
    // again while its best score stays under the pass score and readings are left.
    let recording = null;

    const setRecordStatus = (text) => {
        recordStatus.textContent = text;
    };

    const resetRecorder = () => {
        recording = null;
        delete recordButton.dataset.recording;
        recordButton.disabled = false;
        recordButton.setAttribute('aria-label', 'Bấm để đọc');
        recordScore.hidden = true;
        setRecordStatus(recordHint ?? 'Bấm micro rồi đọc to từ này nhé!');
    };

    const showScore = (score) => {
        recordScore.querySelector('[data-record-score-value]').textContent = String(score);
        recordScore.classList.remove('text-success-text', 'text-warning-text');
        recordScore.classList.add(score >= pronunciationPassScore ? 'text-success-text' : 'text-warning-text');
        recordScore.hidden = false;
    };

    const sendRecording = async (question, audio) => {
        const body = new FormData();

        body.append('word_id', String(question.id));
        body.append('step', String(step));
        body.append('audio', audio, 'word.wav');

        const response = await fetch(pronounceUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body,
        });
        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(result.message ?? 'Chưa chấm được, bạn đọc lại nhé.');
        }

        return result;
    };

    const finishRecording = async () => {
        if (!recording) {
            return;
        }

        // Cleared before waiting, so a click and the time limit never send the same reading twice.
        const current = recording;

        recording = null;

        const question = questions[state.index];
        const audio = await current.stop();

        delete recordButton.dataset.recording;
        recordButton.disabled = true;
        recordButton.setAttribute('aria-label', 'Bấm để đọc');
        setRecordStatus('Đang chấm điểm…');

        try {
            const { score, best, passed, attemptsLeft } = await sendRecording(question, audio);

            showScore(score);

            if (passed || attemptsLeft <= 0) {
                setRecordStatus(passed ? 'Tuyệt vời!' : `Điểm cao nhất của bạn: ${best}/10`);

                // Part 6 hides the word while it is read from memory; show it once the word is over.
                if (question.word) {
                    setRecordStatus(`${recordStatus.textContent} Từ đúng: ${question.word}`);
                }
                record(question, String(best), passed, best);
                alertBox.replaceChildren(template(passed ? 'alert-success' : 'alert-speak-low'));

                return;
            }

            playWrong();
            alertBox.replaceChildren(template('alert-speak-low'));
            setRecordStatus(`Đọc lại nhé! Bạn còn ${attemptsLeft} lần.`);
        } catch (error) {
            setRecordStatus(error.message);
        }

        recordButton.disabled = false;
    };

    const toggleRecording = async () => {
        if (state.answers[state.index] || state.finished) {
            return;
        }

        if (recording) {
            finishRecording();

            return;
        }

        if (!isRecordingSupported()) {
            setRecordStatus('Trình duyệt chưa cho dùng micro. Hãy mở trang bằng https.');

            return;
        }

        try {
            recording = await startRecording(maxRecordingMs ?? MAX_RECORDING_MS);
        } catch {
            setRecordStatus('Bạn cần cho phép trang dùng micro để đọc.');

            return;
        }

        recordButton.dataset.recording = '';
        recordButton.setAttribute('aria-label', 'Bấm để dừng');
        alertBox.replaceChildren();
        setRecordStatus('Đang nghe… đọc xong bấm micro lần nữa.');

        // Stops by itself after the maximum recording time.
        const current = recording;

        current.done.then(() => {
            if (recording === current) {
                finishRecording();
            }
        });
    };

    // Keeps the answer, plays its sound and moves on to the next question or, after the last, to the summary.
    const record = (question, selected, isCorrect, score = null) => {
        state.answers[state.index] = { wordId: question.id, selected, correct: isCorrect, score };

        (isCorrect ? playCorrect : playWrong)();
        renderPartStats();

        if (state.index < total - 1) {
            state.advance = setTimeout(() => {
                state.index += 1;
                renderQuestion();
            }, autoAdvanceMs);

            return;
        }

        finish();
        state.advance = setTimeout(showSummary, autoAdvanceMs);
    };

    const submit = async (attempt = 1) => {
        try {
            const response = await fetch(submitUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    duration: state.seconds,
                    answers: state.answers.map((answer) => ({ word_id: answer.wordId, selected: answer.selected })),
                }),
            });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            // The result is saved at this point, so a bad body or a failed redraw must not trigger another submit.
            const body = await response.json().catch(() => ({}));

            try {
                applyProgress(body.progress);
                applyStanding(body.standing);
            } catch {
                // nothing to retry
            }
        } catch {
            if (attempt < SAVE_ATTEMPTS) {
                setTimeout(() => submit(attempt + 1), SAVE_RETRY_MS);
            }
        }
    };

    const showSummary = () => {
        const correct = state.answers.filter((answer) => answer.correct).length;

        resultArea.querySelector('[data-result-correct]').textContent = String(correct);

        // Same rule as LessonReview::isPassed: at least passPercent of the answers right.
        const isPassed = correct * 100 >= total * passPercent;

        resultArea.querySelector('[data-result-passed]').hidden = !isPassed;
        resultArea.querySelectorAll('[data-result-failed]').forEach((element) => {
            element.hidden = isPassed;
        });
        resultArea.hidden = false;

        // The passage stays on the page, graded; the other parts give the room of the question to the summary.
        if (mode !== 'passage') {
            resultArea.style.minHeight = `${playArea.offsetHeight}px`;
            playArea.hidden = true;
        }
    };

    const finish = () => {
        state.finished = true;
        clearInterval(state.timer);
        renderTimer();
        // A guest plays without saving: the server only keeps the results of a signed-in user.
        if (canSave) {
            submit();
        }
    };

    const startTimer = () => {
        clearInterval(state.timer);
        state.timer = setInterval(() => {
            state.seconds += 1;
            renderTimer();
        }, 1000);
    };

    const restart = () => {
        // The readings left of each word are counted on the server; reloading the page starts them again.
        if (mode === 'speak') {
            window.location.reload();

            return;
        }

        clearTimeout(state.advance);
        Object.assign(state, { index: 0, answers: [], seconds: 0, finished: false });
        resultArea.hidden = true;
        playArea.hidden = false;
        renderQuestion();
        renderPartStats();
        renderTimer();
        startTimer();
    };

    document.querySelectorAll('[data-review-restart]').forEach((button) => button.addEventListener('click', restart));
    recordButton?.addEventListener('click', toggleRecording);
    checkButton?.addEventListener('click', { write: checkWrite, passage: checkPassage }[mode] ?? checkSentence);
    writeInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            checkWrite();
        }
    });

    renderQuestion();
    renderPartStats();
    renderTimer();
    startTimer();
}
