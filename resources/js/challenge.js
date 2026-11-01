/*
 * Challenges between learners (x-learners-now, ChallengeController). The challenge button of another learner in the "Đang học bài" list
 * opens `[data-challenge-dialog]`: pick a course, a unit, then a lesson, and send. The tab then waits for the answer,
 * which comes over the private user channel (ChallengeUpdated, `.challenge`): accepted takes it to the challenge page,
 * declined, cancelled or no answer in time says so.
 *
 * Every tab of the opponent gets `[data-challenge-incoming]`: accepting goes to the challenge page; declining, closing it
 * (button or Escape) or letting the time run out declines. Once answered in one tab, the popup closes in the others.
 */
const post = (url, csrf, body = {}) => fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
    body: JSON.stringify(body),
});

const fillSelect = (select, items, placeholder) => {
    select.replaceChildren(new Option(placeholder, ''), ...items.map((item) => new Option(item.title, item.id)));
    select.disabled = items.length === 0;
};

// Counts down from `seconds` into `output`, calling `onEnd` at zero; returns a function that stops it.
const countdown = (seconds, output, onEnd) => {
    let left = seconds;

    output.textContent = left;

    const timer = setInterval(() => {
        left -= 1;
        output.textContent = Math.max(0, left);

        if (left <= 0) {
            clearInterval(timer);
            onEnd();
        }
    }, 1000);

    return () => clearInterval(timer);
};

export const startChallenges = (root) => {
    const userId = Number(root.dataset.userId);
    const csrf = root.dataset.csrf;
    const dialog = root.querySelector('[data-challenge-dialog]');
    const form = dialog.querySelector('[data-challenge-form]');
    const selects = {
        course: dialog.querySelector('[data-challenge-course]'),
        unit: dialog.querySelector('[data-challenge-unit]'),
        lesson: dialog.querySelector('[data-challenge-lesson]'),
    };
    const pick = dialog.querySelector('[data-challenge-pick]');
    const waiting = dialog.querySelector('[data-challenge-waiting]');
    const message = dialog.querySelector('[data-challenge-message]');
    const submit = dialog.querySelector('[data-challenge-submit]');
    const incoming = root.querySelector('[data-challenge-incoming]');
    let courses = null;
    let opponent = null;
    let sent = null;
    let stopWaiting = () => {};
    let received = null;
    let stopIncoming = () => {};

    const showMessage = (element, text) => {
        element.textContent = text;
        element.hidden = !text;
    };

    // Sending -----------------------------------------------------------------------------------------------------

    const setWaiting = (isWaiting) => {
        pick.hidden = isWaiting;
        waiting.hidden = !isWaiting;
        submit.hidden = isWaiting;
    };

    const resetSend = () => {
        stopWaiting();
        sent = null;
        setWaiting(false);
        submit.disabled = false;
    };

    const loadCourses = async () => {
        courses ??= (await (await fetch(root.dataset.challengeLessonsUrl, { headers: { Accept: 'application/json' } })).json()).courses;
        fillSelect(selects.course, courses, 'Chọn khóa học');
        fillSelect(selects.unit, [], 'Chọn unit');
        fillSelect(selects.lesson, [], 'Chọn bài học');
    };

    const openSend = async (learner) => {
        resetSend();
        opponent = learner;
        dialog.querySelectorAll('[data-challenge-opponent]').forEach((element) => {
            element.textContent = learner.name;
        });
        showMessage(message, '');
        dialog.showModal();

        try {
            await loadCourses();
        } catch {
            showMessage(message, 'Không tải được danh sách bài học, bạn thử lại nhé.');
        }
    };

    const selectedCourse = () => courses?.find((course) => String(course.id) === selects.course.value);

    selects.course.addEventListener('change', () => {
        fillSelect(selects.unit, selectedCourse()?.units ?? [], 'Chọn unit');
        fillSelect(selects.lesson, [], 'Chọn bài học');
    });

    selects.unit.addEventListener('change', () => {
        const unit = selectedCourse()?.units.find((item) => String(item.id) === selects.unit.value);

        fillSelect(selects.lesson, unit?.lessons ?? [], 'Chọn bài học');
    });

    // Closing while waiting takes the challenge back, so the opponent's popup closes too.
    const closeSend = () => {
        if (sent) {
            post(sent.cancelUrl, csrf).catch(() => {});
        }

        resetSend();
        dialog.close();
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!selects.lesson.value) {
            showMessage(message, 'Bạn chọn khóa học, unit và bài học nhé.');

            return;
        }

        showMessage(message, '');
        submit.disabled = true;

        try {
            const response = await post(root.dataset.challengeStoreUrl, csrf, { opponent_id: opponent.id, lesson_id: Number(selects.lesson.value) });
            const data = await response.json();

            if (!response.ok) {
                showMessage(message, Object.values(data.errors ?? {})[0]?.[0] ?? data.message ?? 'Không gửi được lời thách đấu, bạn thử lại nhé.');
                submit.disabled = false;

                return;
            }

            sent = data;
            setWaiting(true);
            stopWaiting = countdown(data.expiresInSeconds, dialog.querySelector('[data-challenge-countdown]'), () => {
                resetSend();
                showMessage(message, `${opponent.name} chưa trả lời. Bạn thử lại sau nhé.`);
            });
        } catch {
            showMessage(message, 'Không gửi được lời thách đấu, bạn thử lại nhé.');
            submit.disabled = false;
        }
    });

    dialog.querySelector('[data-challenge-close]').addEventListener('click', closeSend);
    dialog.querySelector('[data-challenge-cancel]').addEventListener('click', closeSend);
    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeSend();
    });

    root.querySelector('[data-learners-list]').addEventListener('click', (event) => {
        const button = event.target.closest('[data-challenge-button]');

        if (button) {
            openSend({ id: Number(button.dataset.opponentId), name: button.dataset.opponentName });
        }
    });

    // Receiving ---------------------------------------------------------------------------------------------------

    const closeIncoming = () => {
        stopIncoming();
        received = null;
        incoming.close();
    };

    const decline = () => {
        if (received) {
            post(received.declineUrl, csrf).catch(() => {});
        }

        closeIncoming();
    };

    const openIncoming = (challenge) => {
        stopIncoming();
        received = challenge;

        const avatar = incoming.querySelector('[data-challenge-incoming-avatar]');

        avatar.hidden = !challenge.challenger.avatarUrl;
        incoming.querySelector('[data-challenge-incoming-icon]').hidden = Boolean(challenge.challenger.avatarUrl);

        if (challenge.challenger.avatarUrl) {
            avatar.src = challenge.challenger.avatarUrl;
        }

        incoming.querySelector('[data-challenge-incoming-name]').textContent = challenge.challenger.name;
        incoming.querySelector('[data-challenge-incoming-course]').textContent = challenge.course;
        incoming.querySelector('[data-challenge-incoming-unit]').textContent = challenge.unit;
        incoming.querySelector('[data-challenge-incoming-lesson]').textContent = challenge.lesson;
        incoming.querySelector('[data-challenge-accept]').disabled = false;
        showMessage(incoming.querySelector('[data-challenge-incoming-message]'), '');

        if (!incoming.open) {
            incoming.showModal();
        }

        stopIncoming = countdown(challenge.expiresInSeconds, incoming.querySelector('[data-challenge-incoming-countdown]'), decline);
    };

    incoming.querySelector('[data-challenge-accept]').addEventListener('click', async (event) => {
        const button = event.currentTarget;

        if (!received) {
            return;
        }

        button.disabled = true;

        try {
            const response = await post(received.acceptUrl, csrf);
            const data = await response.json();

            if (response.ok) {
                window.location.assign(data.redirect);

                return;
            }

            stopIncoming();
            showMessage(incoming.querySelector('[data-challenge-incoming-message]'), data.message ?? 'Không chấp nhận được lời thách đấu này.');
        } catch {
            button.disabled = false;
        }
    });

    incoming.querySelector('[data-challenge-decline]').addEventListener('click', decline);
    incoming.querySelector('[data-challenge-incoming-close]').addEventListener('click', decline);
    incoming.addEventListener('cancel', (event) => {
        event.preventDefault();
        decline();
    });

    // Updates from the server -------------------------------------------------------------------------------------

    window.Echo.private(`App.Models.User.${userId}`).listen('.challenge', (challenge) => {
        if (challenge.opponentId === userId) {
            if (challenge.status === 'pending') {
                openIncoming(challenge);
            } else if (received?.id === challenge.id) {
                // Answered in another tab, or taken back by the challenger.
                closeIncoming();
            }

            return;
        }

        // Only the tab that sent the challenge follows the answer.
        if (challenge.challengerId !== userId || sent?.id !== challenge.id) {
            return;
        }

        if (challenge.status === 'accepted') {
            stopWaiting();
            window.location.assign(challenge.url);
        } else if (challenge.status === 'declined') {
            resetSend();
            showMessage(message, `${challenge.opponentName} đã từ chối lời thách đấu.`);
        }
    });
};
