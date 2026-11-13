import { shrinkInputImage } from './image-resize';

// x-audio-button: play the recorded file (data-src) when there is one, otherwise - or when the file cannot be played -
// speak the text with the browser's speech synthesis (Web Speech API).
const speak = (text, lang, onEnd) => {
    if (!('speechSynthesis' in window) || !text) {
        onEnd();

        return;
    }

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = lang || 'en-US';
    utterance.rate = 0.85;
    utterance.onend = onEnd;
    utterance.onerror = onEnd;

    speechSynthesis.cancel();
    speechSynthesis.speak(utterance);
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-audio-button]');

    if (!button || button.disabled) {
        return;
    }

    // Flashcard picture shakes on every click, restarting the animation when clicked repeatedly.
    const image = (button.closest('article') ?? button).querySelector('[data-flashcard-image]');

    if (image) {
        image.classList.remove('shake');
        void image.offsetWidth;
        image.classList.add('shake');
    }

    const stopPlaying = () => delete button.dataset.playing;
    const speakText = () => speak(button.dataset.text, button.dataset.lang, stopPlaying);

    button.dataset.playing = '';
    setTimeout(stopPlaying, 1200);

    if (!button.dataset.src) {
        speakText();

        return;
    }

    window.speechSynthesis?.cancel();
    window.playingAudio?.pause();
    window.playingAudio = new Audio(button.dataset.src);
    window.playingAudio.onended = stopPlaying;
    window.playingAudio.onerror = speakText;
    window.playingAudio.play().catch(speakText);
});

/*
 * Disclosure toggles: `data-toggle="<id>"` shows/hides the element with that id and keeps aria-expanded in sync.
 * `data-toggle-group` closes the other toggles of the group; `data-toggle-icon` swaps the button's icon while open;
 * `data-toggle-close="<id>"` only closes. Escape closes everything that is open, and a click outside closes an open
 * target marked `data-toggle-outside` (a dropdown menu).
 */
const toggleButtons = () => document.querySelectorAll('[data-toggle]');

const setOpen = (id, isOpen) => {
    const target = document.getElementById(id);

    if (!target) {
        return;
    }

    target.hidden = !isOpen;

    document.querySelectorAll(`[data-toggle="${id}"]`).forEach((button) => {
        button.setAttribute('aria-expanded', String(isOpen));

        const icon = button.querySelector('i[class*="icon-"]');

        if (icon && button.dataset.toggleIcon) {
            icon.dataset.iconClosed ??= [...icon.classList].find((name) => name.startsWith('icon-'));
            icon.classList.remove(icon.dataset.iconClosed, `icon-${button.dataset.toggleIcon}`);
            icon.classList.add(isOpen ? `icon-${button.dataset.toggleIcon}` : icon.dataset.iconClosed);
        }
    });
};

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-toggle-outside]:not([hidden])').forEach((target) => {
        if (!target.contains(event.target) && !event.target.closest(`[data-toggle="${target.id}"]`)) {
            setOpen(target.id, false);
        }
    });

    const closer = event.target.closest('[data-toggle-close]');

    if (closer) {
        setOpen(closer.dataset.toggleClose, false);

        return;
    }

    const button = event.target.closest('[data-toggle]');

    if (!button) {
        return;
    }

    const id = button.dataset.toggle;
    const isOpen = button.getAttribute('aria-expanded') !== 'true';

    if (isOpen && button.dataset.toggleGroup) {
        toggleButtons().forEach((other) => {
            if (other.dataset.toggleGroup === button.dataset.toggleGroup && other.dataset.toggle !== id) {
                setOpen(other.dataset.toggle, false);
            }
        });
    }

    setOpen(id, isOpen);
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    toggleButtons().forEach((button) => {
        if (button.getAttribute('aria-expanded') === 'true') {
            setOpen(button.dataset.toggle, false);
        }
    });
});

// Copy buttons: `data-copy` holds the text to put on the clipboard. The async API needs a secure context, so a
// temporary textarea is the fallback.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy]');

    if (!button || button.hasAttribute('data-vocab-action')) {
        return;
    }

    const text = button.dataset.copy;

    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).catch(() => {});
    } else {
        const field = document.createElement('textarea');

        field.value = text;
        field.setAttribute('readonly', '');
        field.style.position = 'fixed';
        field.style.opacity = '0';
        document.body.append(field);
        field.select();
        document.execCommand('copy');
        field.remove();
    }

    // The icon turns into a check for a moment; a `[data-copy-label]` inside the button also swaps its text for its
    // `data-copied` text.
    const icon = button.querySelector('i[class*="icon-"]');
    const label = button.querySelector('[data-copy-label]');

    if (icon || label) {
        const originalIcon = button.copyOriginalIcon ??= icon?.className.match(/icon-[\w-]+/)?.[0];
        const originalLabel = button.copyOriginalLabel ??= label?.textContent;

        icon?.classList.replace(originalIcon, 'icon-check');
        if (label) {
            label.textContent = label.dataset.copied ?? originalLabel;
        }

        clearTimeout(button.copyTimer);
        button.copyTimer = setTimeout(() => {
            icon?.classList.replace('icon-check', originalIcon);
            if (label) {
                label.textContent = originalLabel;
            }
        }, 1500);
    }
});

/*
 * Vocabulary table tools (lesson page): `data-vocab-action` is one of expand · print · zoom-in · zoom-out.
 * The table is `[data-vocab-table]`; its font scale lives in the --vs custom property,.
 */
const ZOOM_MIN = 0.8;
const ZOOM_MAX = 1.4;
const ZOOM_STEP = 0.1;

const swapIcon = (button, name) => {
    const icon = button.querySelector('i[class*="icon-"]');

    if (icon) {
        icon.className = icon.className.replace(/icon-[\w-]+/, `icon-${name}`);
    }
};

const syncZoomButtons = (scale) => {
    document.querySelectorAll('[data-vocab-action="zoom-out"]').forEach((button) => {
        button.disabled = scale <= ZOOM_MIN;
    });
    document.querySelectorAll('[data-vocab-action="zoom-in"]').forEach((button) => {
        button.disabled = scale >= ZOOM_MAX;
    });
};

// Print only one element: print a clone of it and hide everything else (see app.css).
const printElement = (element) => {
    const heading = element.closest('section')?.querySelector('h2');
    const root = document.createElement('div');
    const clone = element.cloneNode(true);
    const cleanUp = () => {
        root.remove();
        delete document.body.dataset.printOnly;
    };

    clone.setAttribute('data-expanded', '');
    clone.querySelectorAll('.shake').forEach((image) => image.classList.remove('shake'));
    root.dataset.printRoot = '';
    if (heading) {
        const title = heading.cloneNode(true);

        title.style.marginBottom = '1.5rem';
        root.append(title);
    }

    root.append(clone);
    document.body.append(root);
    document.body.dataset.printOnly = '';
    window.addEventListener('afterprint', cleanUp, { once: true });
    window.print();
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-flashcard-toggle]');

    if (!button) {
        return;
    }

    const hidden = button.getAttribute('aria-pressed') !== 'true';

    button.closest('section').querySelectorAll('[data-flashcards] > article').forEach((card) => {
        card.toggleAttribute('data-word-hidden', hidden);
    });

    const label = hidden ? 'Hiện từ' : 'Ẩn từ';

    button.setAttribute('aria-pressed', String(hidden));
    button.title = button.ariaLabel = label;
    swapIcon(button, hidden ? 'eye' : 'eye-off');
});

// Passage: hide or show the marked words (labels), so they can be recalled before checking.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-passage-labels-toggle]');
    const passage = button?.closest('section')?.querySelector('[data-passage]');

    if (!passage) {
        return;
    }

    const hidden = button.getAttribute('aria-pressed') !== 'true';

    passage.toggleAttribute('data-labels-hidden', hidden);
    button.setAttribute('aria-pressed', String(hidden));
    button.title = button.ariaLabel = hidden ? 'Hiện từ' : 'Ẩn từ';
    swapIcon(button, hidden ? 'eye' : 'eye-off');
});

// Align the fixed player with the main (left) column of the lesson page.
const placePassagePlayer = () => {
    const inner = document.querySelector('[data-passage-player-inner]');
    const column = document.querySelector('[data-lesson-main]');

    if (inner && column) {
        const rect = column.getBoundingClientRect();

        inner.style.marginLeft = `${rect.left}px`;
        inner.style.width = `${rect.width}px`;
    }
};

window.addEventListener('resize', placePassagePlayer);
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        requestAnimationFrame(placePassagePlayer);
    }
});

// Passage audio: the play button opens a player fixed to the bottom of the page and starts the audio; closing stops it.
document.addEventListener('click', (event) => {
    const play = event.target.closest('[data-passage-play]');
    const close = event.target.closest('[data-passage-player-close]');
    const player = document.querySelector('[data-passage-player]');
    const audio = player?.querySelector('audio');

    if (!audio || (!play && !close)) {
        return;
    }

    if (close) {
        audio.pause();
        player.hidden = true;

        return;
    }

    placePassagePlayer();
    player.hidden = false;
    audio.paused ? audio.play().catch(() => {}) : audio.pause();
});

document.querySelectorAll('[data-passage-player] audio').forEach((audio) => {
    const sync = () => {
        const button = document.querySelector('[data-passage-play]');
        const playing = !audio.paused;

        button.title = button.ariaLabel = playing ? 'Tạm dừng' : 'Phát audio';
        swapIcon(button, playing ? 'pause' : 'play');
    };

    audio.addEventListener('play', sync);
    audio.addEventListener('pause', sync);
});

// Word scene card: hide or show the words written under the objects of the picture.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-word-scene-toggle]');

    if (!button) {
        return;
    }

    const hidden = button.getAttribute('aria-pressed') !== 'true';
    const label = hidden ? 'Hiện từ' : 'Ẩn từ';

    button.closest('section').querySelector('[data-word-scene]')?.toggleAttribute('data-words-hidden', hidden);
    button.setAttribute('aria-pressed', String(hidden));
    button.title = button.ariaLabel = label;
    swapIcon(button, hidden ? 'eye' : 'eye-off');
});

document.addEventListener('click', (event) => {
    const printButton = event.target.closest('[data-print-flashcards]');

    if (printButton) {
        printElement(printButton.closest('section').querySelector('[data-flashcards]'));
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-sidebar-toggle]');
    const layout = button?.closest('[data-sidebar-layout]');

    if (!layout) {
        return;
    }

    const hidden = layout.toggleAttribute('data-sidebar-hidden');
    const label = hidden ? 'Hiện thanh bên' : 'Ẩn thanh bên';

    document.querySelectorAll('[data-sidebar-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-pressed', String(hidden));
        toggle.title = toggle.ariaLabel = label;
        swapIcon(toggle, hidden ? 'panel-right-open' : 'panel-right-close');
    });
});

// Copy dialog of the vocabulary table: one "word [n] meaning" line per word, meanings optional. The dialog's copy button
// is handled by the generic `[data-copy]` listener, so its text is kept in `data-copy`.
const renderCopyList = (dialog) => {
    const withMeaning = dialog.querySelector('[data-copy-meaning-toggle]').checked;
    const text = JSON.parse(dialog.dataset.items)
        .map((item) => (withMeaning && item.meaning ? `${item.word} ${item.meaning}` : item.word))
        .join('\n');

    dialog.querySelector('[data-copy-output]').value = text;
    dialog.querySelector('[data-copy]').dataset.copy = text;
};

document.addEventListener('change', (event) => {
    const dialog = event.target.closest('[data-copy-dialog]');

    if (dialog && event.target.matches('[data-copy-meaning-toggle]')) {
        renderCopyList(dialog);
    }
});

document.addEventListener('click', (event) => {
    const dialog = event.target.closest('[data-copy-dialog]');

    // Closes with the X button or a click on the backdrop (the dialog element itself).
    if (dialog && (event.target === dialog || event.target.closest('[data-copy-close]'))) {
        dialog.close();
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-vocab-action]');
    const table = document.querySelector('[data-vocab-table]');

    if (!button || !table) {
        return;
    }

    const action = button.dataset.vocabAction;

    if (action === 'print') {
        printElement(table);
    } else if (action === 'expand') {
        const expanded = table.toggleAttribute('data-expanded');
        const label = expanded ? 'Hiển thị rút gọn' : 'Hiển thị đầy đủ';

        button.setAttribute('aria-pressed', String(expanded));
        button.title = button.ariaLabel = label;
        swapIcon(button, expanded ? 'eye-off' : 'eye');
    } else if (action === 'copy') {
        const dialog = button.closest('section')?.querySelector('[data-copy-dialog]');

        if (dialog) {
            renderCopyList(dialog);
            dialog.showModal();
        }
    } else if (action === 'zoom-in' || action === 'zoom-out') {
        const current = Number(table.style.getPropertyValue('--vs') || 1);
        const next = current + (action === 'zoom-in' ? ZOOM_STEP : -ZOOM_STEP);
        const scale = Math.round(Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, next)) * 10) / 10;

        table.style.setProperty('--vs', String(scale));
        syncZoomButtons(scale);
    }
});

syncZoomButtons(1);

/*
 * Listen and repeat (vocabulary table): `[data-vocab-action="listen-repeat"]` opens the full screen `[data-repeat-dialog]` (like the
 * chant). It says "Listen and repeat", then shows every word of the table, one at a time, and reads it once in UK and once in US,
 * pausing after each reading so the student can repeat. The readings use the audio buttons of the table row.
 * `data-repeat-close` or Escape closes it and stops everything.
 */
const REPEAT_PAUSE_MS = 2200;
const repeatSessions = new WeakMap();

const wait = (milliseconds) => new Promise((resolve) => setTimeout(resolve, milliseconds));

// Play the recorded file of an audio button, or speak its text when there is no file or it cannot be played.
const playAudioButton = (button) => new Promise((resolve) => {
    const speakText = () => speak(button.dataset.text, button.dataset.lang, resolve);

    if (!button.dataset.src) {
        speakText();

        return;
    }

    window.speechSynthesis?.cancel();
    window.playingAudio?.pause();
    window.playingAudio = new Audio(button.dataset.src);
    window.playingAudio.onended = resolve;
    window.playingAudio.onerror = speakText;
    window.playingAudio.play().catch(speakText);
});

// Say a short instruction ("Listen and repeat"): play its recorded file (made with Azure), or speak the text when there is no file or it cannot be played.
const playIntro = (src, text) => new Promise((resolve) => {
    const speakText = () => speak(text, 'en-US', resolve);

    if (!src) {
        speakText();

        return;
    }

    window.speechSynthesis?.cancel();
    window.playingAudio?.pause();
    window.playingAudio = new Audio(src);
    window.playingAudio.onended = resolve;
    window.playingAudio.onerror = speakText;
    window.playingAudio.play().catch(speakText);
});

const showRepeatWord = (dialog, slide, lang, index, total) => {
    const isUk = lang === 'en-GB';
    const image = dialog.querySelector('[data-repeat-image]');

    image.closest('[data-repeat-picture]').hidden = !slide.image;
    image.src = slide.image ?? '';
    dialog.querySelector('[data-repeat-intro]').hidden = true;
    dialog.querySelector('[data-repeat-word]').hidden = false;
    dialog.querySelector('[data-repeat-title]').textContent = slide.title;
    const accent = dialog.querySelector('[data-repeat-accent]');

    accent.textContent = isUk ? 'UK' : 'US';
    accent.classList.toggle('bg-primary', isUk);
    accent.classList.toggle('bg-secondary', !isUk);

    const ipa = dialog.querySelector('[data-repeat-ipa]');
    const meaning = dialog.querySelector('[data-repeat-meaning]');

    ipa.textContent = (isUk ? slide.ipaUk : slide.ipaUs) ?? '';
    ipa.hidden = !ipa.textContent;
    meaning.textContent = slide.meaning ?? '';
    meaning.hidden = !slide.meaning;
    dialog.querySelector('[data-repeat-counter]').textContent = `${index + 1} / ${total}`;

    const stage = dialog.querySelector('[data-repeat-stage]');

    stage.classList.remove('chant-in');
    void stage.offsetWidth;
    stage.classList.add('chant-in');
};

const runListenAndRepeat = async (dialog, table, session) => {
    const slides = JSON.parse(dialog.dataset.slides);
    const rows = [...table.querySelectorAll('tbody tr')];

    dialog.querySelector('[data-repeat-intro]').hidden = false;
    dialog.querySelector('[data-repeat-word]').hidden = true;
    dialog.querySelector('[data-repeat-counter]').textContent = '';

    await playIntro(dialog.dataset.introSrc, 'Listen and repeat');
    await wait(1000);

    for (const [index, row] of rows.entries()) {
        for (const lang of ['en-GB', 'en-US']) {
            const audioButton = row.querySelector(`[data-audio-button][data-lang="${lang}"]`);

            if (session.cancelled) {
                return;
            }

            if (!audioButton || !slides[index]) {
                continue;
            }

            showRepeatWord(dialog, slides[index], lang, index, rows.length);
            await playAudioButton(audioButton);

            if (session.cancelled) {
                return;
            }

            await wait(REPEAT_PAUSE_MS);
        }
    }

    if (!session.cancelled) {
        dialog.close();
    }
};

document.addEventListener('click', (event) => {
    const open = event.target.closest('[data-vocab-action="listen-repeat"]');
    const close = event.target.closest('[data-repeat-close]');

    if (close) {
        close.closest('[data-repeat-dialog]').close();

        return;
    }

    const table = document.querySelector('[data-vocab-table]');
    const dialog = open?.closest('section')?.querySelector('[data-repeat-dialog]');

    if (!dialog || !table) {
        return;
    }

    const session = { cancelled: false };

    repeatSessions.set(dialog, session);
    window.speechSynthesis?.cancel();
    window.playingAudio?.pause();
    dialog.showModal();
    dialog.requestFullscreen?.().catch(() => {});
    runListenAndRepeat(dialog, table, session);
});

// `close` does not bubble: stop the readings when the dialog is closed (Escape, the close button, the end).
document.addEventListener('close', (event) => {
    const dialog = event.target;

    if (!dialog.matches?.('[data-repeat-dialog]')) {
        return;
    }

    const session = repeatSessions.get(dialog);

    if (session) {
        session.cancelled = true;
        repeatSessions.delete(dialog);
    }

    window.speechSynthesis?.cancel();
    window.playingAudio?.pause();

    if (document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
    }
}, true);

// Leaving full screen (Escape) closes the popup.
document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement) {
        document.querySelectorAll('[data-repeat-dialog][open]').forEach((dialog) => dialog.close());
    }
});

/*
 * Word scene card: `[data-word-scene-read]` reads the words of the picture one after another after a spoken intro, pausing after
 * each word for the student: "repeat" goes in the order of the picture and lights up the word being read ("Listen and repeat"),
 * "find" goes in random order and lights up nothing, so the student has to find the object ("Listen and find"), with the beat of
 * `data-music-src` playing quietly underneath. The same button stops it. The words are read through the audio buttons over the picture.
 */
const SCENE_PAUSE_MS = 2200;
const SCENE_MUSIC_VOLUME = 0.15;
let sceneReading = null;

const shuffled = (list) => {
    const copy = [...list];

    for (let index = copy.length - 1; index > 0; index--) {
        const other = Math.floor(Math.random() * (index + 1));

        [copy[index], copy[other]] = [copy[other], copy[index]];
    }

    return copy;
};

const stopSceneReading = () => {
    if (!sceneReading) {
        return;
    }

    const { button, wordButtons } = sceneReading;

    sceneReading.cancelled = true;
    sceneReading.music?.pause();
    sceneReading = null;
    window.speechSynthesis?.cancel();
    window.playingAudio?.pause();
    wordButtons.forEach((wordButton) => delete wordButton.dataset.playing);
    button.setAttribute('aria-pressed', 'false');
    button.title = button.ariaLabel = button.dataset.label;
    swapIcon(button, button.dataset.icon);
};

const startSceneReading = async (button) => {
    const mode = button.dataset.wordSceneRead;
    const wordButtons = [...button.closest('section').querySelectorAll('[data-word-scene] [data-audio-button]')];

    if (wordButtons.length === 0) {
        return;
    }

    button.dataset.label ??= button.ariaLabel;
    button.dataset.icon ??= button.querySelector('i[class*="icon-"]')?.className.match(/icon-([\w-]+)/)?.[1];

    const session = { button, wordButtons, cancelled: false };

    sceneReading = session;
    button.setAttribute('aria-pressed', 'true');
    button.title = button.ariaLabel = 'Dừng đọc';
    swapIcon(button, 'square');
    window.speechSynthesis?.cancel();
    window.playingAudio?.pause();

    await playIntro(button.dataset.introSrc, mode === 'find' ? 'Listen and find' : 'Listen and repeat');

    if (session.cancelled) {
        return;
    }

    // The background music (a beat) plays under the words, quietly so that they can be heard, until the reading ends or is stopped.
    if (button.dataset.musicSrc) {
        session.music = new Audio(button.dataset.musicSrc);
        session.music.loop = true;
        session.music.volume = SCENE_MUSIC_VOLUME;
        session.music.play().catch(() => {});
    }

    await wait(1000);

    for (const wordButton of mode === 'find' ? shuffled(wordButtons) : wordButtons) {
        if (session.cancelled) {
            return;
        }

        if (mode === 'repeat') {
            wordButton.dataset.playing = '';
        }

        await playAudioButton(wordButton);
        delete wordButton.dataset.playing;

        if (session.cancelled) {
            return;
        }

        await wait(SCENE_PAUSE_MS);
    }

    if (!session.cancelled) {
        stopSceneReading();
    }
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-word-scene-read]');

    if (!button) {
        return;
    }

    const wasReading = sceneReading?.button === button;

    stopSceneReading();

    if (!wasReading) {
        startSceneReading(button);
    }
});

// Example sentence picture (lesson page): a click on the picture plays the sentence through the audio button beside the sentence.
document.addEventListener('click', (event) => {
    const picture = event.target.closest('[data-example-image]');

    if (picture && !event.target.closest('[data-audio-button]')) {
        picture.closest('[data-example]')?.querySelector('[data-audio-button]')?.click();
    }
});

// Example sentences (lesson page): the button beside the title hides or shows again every sentence with its Vietnamese meaning (shown at first).
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-example-toggle]');

    if (!button) {
        return;
    }

    const hidden = button.getAttribute('aria-pressed') !== 'true';

    button.closest('section').querySelectorAll('[data-example-sentence], [data-example-meaning]').forEach((text) => {
        text.classList.toggle('invisible', hidden);
    });

    const label = hidden ? 'Hiện câu và nghĩa' : 'Ẩn câu và nghĩa';

    button.setAttribute('aria-pressed', String(hidden));
    button.title = button.ariaLabel = label;
    swapIcon(button, hidden ? 'eye' : 'eye-off');
});

/*
 * Payment page: listen on the payment's private channel (`data-payment-channel`) for the `paid` event Reverb pushes
 * when the transfer arrives, then show the success message in a dialog, counting down to what was bought (or going
 * there at once from its button). `data-payment-status` is the URL that tells whether it is already paid, asked each
 * time the channel is (re)subscribed.
 */
const PAYMENT_REDIRECT_DELAY_SECONDS = 5;
const paymentStatus = document.querySelector('[data-payment-status]');

if (paymentStatus) {
    let isDone = false;

    const showPaid = (redirect) => {
        if (isDone) {
            return;
        }

        isDone = true;
        paymentStatus.querySelector('[data-payment-pending]').hidden = true;
        paymentStatus.querySelector('[data-payment-paid]').hidden = false;

        const dialog = paymentStatus.querySelector('[data-payment-paid-dialog]');
        const countdown = dialog.querySelector('[data-payment-countdown]');
        let secondsLeft = PAYMENT_REDIRECT_DELAY_SECONDS;

        dialog.querySelector('[data-payment-go]').href = redirect;
        countdown.textContent = secondsLeft;
        dialog.showModal();

        const timer = setInterval(() => {
            secondsLeft -= 1;
            countdown.textContent = Math.max(secondsLeft, 0);

            if (secondsLeft <= 0) {
                clearInterval(timer);
                window.location.assign(redirect);
            }
        }, 1000);
    };

    // Covers a transfer that arrived before the page subscribed, or while the connection was down.
    const checkPayment = async () => {
        try {
            const response = await fetch(paymentStatus.dataset.paymentStatus, { headers: { Accept: 'application/json' } });
            const { paid, redirect } = response.ok ? await response.json() : {};

            if (paid) {
                showPaid(redirect);
            }
        } catch {
            // The next (re)connection checks again.
        }
    };

    // Echo is only needed here, so it loads with the payment page.
    import('./echo').then(() => {
        window.Echo.private(paymentStatus.dataset.paymentChannel)
            .subscribed(checkPayment)
            .listen('.paid', ({ redirect }) => showPaid(redirect));
    });
}

// x-word-popover: place the popover under (or above) its label and point the arrow at the label.
const POPOVER_GAP = 10;
const POPOVER_MARGIN = 8;

document.addEventListener(
    'toggle',
    (event) => {
        const popover = event.target;

        if (!(popover instanceof HTMLElement) || !popover.matches('[data-word-popover]') || event.newState !== 'open') {
            return;
        }

        const trigger = document.querySelector(`[popovertarget="${popover.id}"]`);

        if (!trigger) {
            return;
        }

        const anchor = trigger.getBoundingClientRect();
        const { offsetWidth: width, offsetHeight: height } = popover;
        const left = Math.min(Math.max(anchor.left + anchor.width / 2 - width / 2, POPOVER_MARGIN), window.innerWidth - width - POPOVER_MARGIN);
        const fitsBelow = anchor.bottom + POPOVER_GAP + height <= window.innerHeight;
        const placeAbove = !fitsBelow && anchor.top - POPOVER_GAP - height >= 0;

        popover.dataset.placement = placeAbove ? 'top' : 'bottom';
        popover.style.left = `${left}px`;
        popover.style.top = `${placeAbove ? anchor.top - POPOVER_GAP - height : anchor.bottom + POPOVER_GAP}px`;
        popover.style.setProperty('--arrow-x', `${anchor.left + anchor.width / 2 - left}px`);

        const close = () => popover.hidePopover();

        window.addEventListener('scroll', close, { once: true, capture: true });
        window.addEventListener('resize', close, { once: true });
    },
    true,
);

// Profile avatar: crop the chosen photo to a centred square and shrink it before upload (phone photos are huge). The server
// does the same (App\Services\AvatarImage) for a photo the browser cannot process.
const AVATAR_SIZE = 512;
let avatarPreviewUrl = null;

document.addEventListener('change', async (event) => {
    const input = event.target.closest('[data-avatar-input]');

    if (!input) {
        return;
    }

    const file = await shrinkInputImage(input, { maxWidth: AVATAR_SIZE, maxHeight: AVATAR_SIZE, square: true });
    const preview = document.querySelector('[data-avatar-preview]');

    if (!file || !preview) {
        return;
    }

    if (avatarPreviewUrl) {
        URL.revokeObjectURL(avatarPreviewUrl);
    }

    avatarPreviewUrl = URL.createObjectURL(file);
    const image = new Image();
    image.src = avatarPreviewUrl;
    image.alt = '';
    image.className = 'size-16 flex-none rounded-full border-2 border-primary-border object-cover';
    preview.replaceChildren(image);
});

/*
 * x-chant: `[data-chant-open]` opens its `[data-chant-dialog]` full screen on the first slide and starts at once: a song of
 * `data-tracks` plays (the next one when it ends). The next slide comes after CHANT_SLIDE_MS, and the slides round again, while the bar
 * on top (`data-chant-progress`) fills; the wide side zones (`data-chant-next|prev`) and the arrow keys go through the slides by hand,
 * which restarts the wait. A click in the middle of the screen or space pauses and resumes the song and the wait; `data-chant-close`
 * or Escape closes the popup. The wait is counted in JS, not by a CSS animation, because the reduced-motion rule of the page speeds
 * CSS animations up.
 */
// public/chant/beat.mp3 is about 93.75 BPM (0.64 s a beat): a slide lasts 20 beats (5 bars).
const CHANT_SLIDE_MS = 12800;

const chants = new WeakMap();

const showChantSlide = (dialog, index) => {
    const chant = chants.get(dialog);

    chant.index = (index + chant.slides.length) % chant.slides.length;

    const slide = chant.slides[chant.index];
    const image = dialog.querySelector('[data-chant-image]');

    image.closest('[data-chant-picture]').hidden = !slide.image;
    image.src = slide.image ?? '';

    Object.entries({ title: slide.title, ipa: slide.ipa, meaning: slide.meaning }).forEach(([name, text]) => {
        const element = dialog.querySelector(`[data-chant-${name}]`);

        element.textContent = text ?? '';
        element.hidden = !text || chant.isTextHidden;
    });

    dialog.querySelector('[data-chant-counter]').textContent = `${chant.index + 1} / ${chant.slides.length}`;

    const stage = dialog.querySelector('[data-chant-stage]');

    stage.classList.remove('chant-in');
    void stage.offsetWidth;
    stage.classList.add('chant-in');

    const upcoming = chant.slides[(chant.index + 1) % chant.slides.length];

    if (upcoming.image) {
        new Image().src = upcoming.image;
    }

    chant.elapsed = 0;
    dialog.querySelector('[data-chant-progress]').style.width = '0';
    armChantTimer(dialog);
};

// Count the time of the current slide frame by frame, unless the chant is paused, and go to the next slide when CHANT_SLIDE_MS is over.
const armChantTimer = (dialog) => {
    const chant = chants.get(dialog);

    cancelAnimationFrame(chant.frame);
    chant.last = null;

    if (chant.paused) {
        return;
    }

    const tick = (now) => {
        // A hidden tab stops the frames: do not count the time it was away.
        chant.elapsed += Math.min(now - (chant.last ?? now), 250);
        chant.last = now;

        if (chant.elapsed >= CHANT_SLIDE_MS) {
            showChantSlide(dialog, chant.index + 1);

            return;
        }

        dialog.querySelector('[data-chant-progress]').style.width = `${(chant.elapsed / CHANT_SLIDE_MS) * 100}%`;
        chant.frame = requestAnimationFrame(tick);
    };

    chant.frame = requestAnimationFrame(tick);
};

const playChantTrack = (dialog) => {
    const chant = chants.get(dialog);

    if (!chant?.tracks.length) {
        return;
    }

    chant.audio = new Audio(chant.tracks[chant.track % chant.tracks.length]);
    chant.audio.onended = () => {
        chant.track++;
        playChantTrack(dialog);
    };

    if (!chant.paused) {
        chant.audio.play().catch(() => {});
    }
};

const setChantPaused = (dialog, paused) => {
    const chant = chants.get(dialog);
    chant.paused = paused;
    dialog.toggleAttribute('data-paused', paused);
    armChantTimer(dialog);

    if (paused) {
        chant.audio?.pause();
    } else {
        chant.audio?.play().catch(() => {});
    }
};

const stopChant = (dialog) => {
    const chant = chants.get(dialog);

    if (!chant) {
        return;
    }

    cancelAnimationFrame(chant.frame);
    chant.audio?.pause();
    chants.delete(dialog);

    if (document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
    }
};

document.addEventListener('click', (event) => {
    const open = event.target.closest('[data-chant-open]');

    if (open) {
        const dialog = open.closest('[data-chant]').querySelector('[data-chant-dialog]');
        const tracks = JSON.parse(dialog.dataset.tracks);

        window.speechSynthesis?.cancel();
        window.playingAudio?.pause();

        // The hide button of the card the chant belongs to (flashcards, example sentences) also hides the text of the slides.
        const isTextHidden = open.closest('section')?.querySelector('[data-flashcard-toggle], [data-example-toggle]')?.getAttribute('aria-pressed') === 'true';

        chants.set(dialog, {
            isTextHidden,
            slides: JSON.parse(dialog.dataset.slides),
            tracks,
            track: Math.floor(Math.random() * Math.max(tracks.length, 1)),
            index: 0,
            elapsed: 0,
            paused: false,
            audio: null,
        });

        dialog.showModal();
        dialog.requestFullscreen?.().catch(() => {});
        setChantPaused(dialog, false);
        showChantSlide(dialog, 0);
        playChantTrack(dialog);

        return;
    }

    const dialog = event.target.closest('[data-chant-dialog]');
    const control = event.target.closest('[data-chant-close], [data-chant-prev], [data-chant-next]');

    if (!dialog || !chants.has(dialog)) {
        return;
    }

    const chant = chants.get(dialog);

    // A click on the screen itself, not on a button, pauses or resumes the chant.
    const isScreen = !control && !event.target.closest('button');

    if (isScreen) {
        setChantPaused(dialog, !chant.paused);
    } else if (!control) {
        return;
    } else if (control.hasAttribute('data-chant-close')) {
        dialog.close();
    } else if (control.hasAttribute('data-chant-prev')) {
        showChantSlide(dialog, chant.index - 1);
    } else if (control.hasAttribute('data-chant-next')) {
        showChantSlide(dialog, chant.index + 1);
    }
});

document.addEventListener('keydown', (event) => {
    const dialog = document.querySelector('[data-chant-dialog][open]');

    if (!dialog || !chants.has(dialog)) {
        return;
    }

    const chant = chants.get(dialog);

    if (event.key === 'ArrowRight') {
        showChantSlide(dialog, chant.index + 1);
    } else if (event.key === 'ArrowLeft') {
        showChantSlide(dialog, chant.index - 1);
    } else if (event.key === ' ' && !event.target.closest('button')) {
        event.preventDefault();

        setChantPaused(dialog, !chant.paused);
    }
});

// `close` does not bubble: stop the song when the dialog is closed (Escape, the close button).
document.addEventListener('close', (event) => {
    if (event.target.matches?.('[data-chant-dialog]')) {
        stopChant(event.target);
    }
}, true);

// Leaving full screen (Escape) closes the popup.
document.addEventListener('fullscreenchange', () => {
    if (!document.fullscreenElement) {
        document.querySelectorAll('[data-chant-dialog][open]').forEach((dialog) => dialog.close());
    }
});

/*
 * "Đang học bài" (x-learners-now): every page of a signed-in user (`data-learners-now`) joins the `learning` presence channel
 * with what it is about (the `learning-activity` meta, see ShareLearningActivity) and lists, live, the users on the site,
 * the latest first, with their number on the button. Each tab is a member of its own (`socket`): a user is listed once,
 * with the activity of the tab they opened last (`since`), and stays while any of their tabs is open. The challenge button of another
 * learner opens the challenge popup (see challenge.js).
 */
const learnersNow = document.querySelector('[data-learners-now]');

if (learnersNow) {
    import('./echo').then(() => {
        const channel = window.Echo.join('learning');
        const avatarStyles = JSON.parse(learnersNow.dataset.avatarStyles);
        const template = learnersNow.querySelector('[data-learner-template]');
        const list = learnersNow.querySelector('[data-learners-list]');
        const badge = learnersNow.querySelector('[data-learners-badge]');
        const userId = Number(learnersNow.dataset.userId);
        let members = [];

        const render = () => {
            const latestTabs = new Map();

            members.forEach((tab) => {
                if ((latestTabs.get(tab.id)?.since ?? -1) < tab.since) {
                    latestTabs.set(tab.id, tab);
                }
            });

            const learners = [...latestTabs.values()].sort((first, second) => second.since - first.since);

            list.replaceChildren(...learners.map((learner, index) => {
                const item = template.content.firstElementChild.cloneNode(true);
                const image = item.querySelector('[data-learner-image]');
                const challengeButton = item.querySelector('[data-challenge-button]');

                // Only the challenge button opens the challenge popup; the learner cannot challenge themselves.
                if (learner.id === userId) {
                    challengeButton.remove();
                } else {
                    challengeButton.dataset.opponentId = learner.id;
                    challengeButton.dataset.opponentName = learner.name;
                    challengeButton.ariaLabel = `Thách đấu ${learner.name}`;
                }

                item.querySelector('[data-learner-avatar]').classList.add(...avatarStyles[index % avatarStyles.length].split(' '));
                item.querySelector('[data-learner-name]').textContent = learner.name;
                item.querySelector('[data-learner-activity]').textContent = learner.activity;

                if (learner.avatarUrl) {
                    image.src = learner.avatarUrl;
                } else {
                    image.remove();
                    item.querySelector('[data-learner-initials]').textContent = learner.initials;
                }

                return item;
            }));

            list.hidden = learners.length === 0;
            learnersNow.querySelector('[data-learners-empty]').hidden = learners.length > 0;
            learnersNow.querySelector('[data-learners-count]').textContent = learners.length;
            badge.textContent = learners.length > 99 ? '99+' : learners.length;
            badge.hidden = learners.length === 0;
            learnersNow.querySelector('[data-toggle="learners-now"]').ariaLabel = `Đang học: ${learners.length} bạn`;
        };

        import('./challenge').then(({ startChallenges }) => startChallenges(learnersNow));

        channel
            .here((current) => {
                members = current;
                render();
            })
            .joining((member) => {
                members = [...members.filter(({ socket }) => socket !== member.socket), member];
                render();
            })
            .leaving((member) => {
                members = members.filter(({ socket }) => socket !== member.socket);
                render();
            });
    });
}

/*
 * x-achievement-toast: listen on the public `achievements` channel (see LearnerAchieved) and show each achievement in a
 * toast, one after the other, for ACHIEVEMENT_TOAST_MS each (or until closed).
 */
const ACHIEVEMENT_TOAST_MS = 6000;
const ACHIEVEMENT_QUEUE_MAX = 5;

/*
 * Confetti of the achievement toast: one burst, from the icon of the card, of CONFETTI_PIECES small diamonds and dots of
 * many colours. They shoot out every way, slow down while spinning and vanish where they stop. Skipped when the visitor
 * prefers reduced motion.
 */
const CONFETTI_COLORS = ['--color-secondary', '--color-primary', '--color-accent', '--color-success', '--color-danger', '--color-pink-500'];
const CONFETTI_PIECES = 40;

const randomBetween = (min, max) => min + Math.random() * (max - min);

const launchConfetti = (layer) => {
    if (!layer || !layer.animate || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const origin = layer.closest('.achievement-toast').querySelector('[data-achievement-icon]:not([hidden])')?.parentElement;
    const layerBox = layer.getBoundingClientRect();
    const originBox = (origin ?? layer).getBoundingClientRect();
    const x = originBox.left + originBox.width / 2 - layerBox.left;
    const y = originBox.top + originBox.height / 2 - layerBox.top;

    for (let index = 0; index < CONFETTI_PIECES; index++) {
        const piece = document.createElement('span');
        const isDiamond = index % 4 !== 0;
        const size = isDiamond ? randomBetween(5, 8) : randomBetween(4, 6);
        const color = `var(${CONFETTI_COLORS[index % CONFETTI_COLORS.length]})`;
        // Spread evenly around the circle, each a little off its slot.
        const angle = ((index + randomBetween(-0.4, 0.4)) / CONFETTI_PIECES) * Math.PI * 2;
        const distance = randomBetween(50, 130);
        const dx = Math.cos(angle) * distance;
        const dy = Math.sin(angle) * distance;
        const spin = randomBetween(-360, 360);

        piece.style.cssText = `position:absolute;left:${x - size / 2}px;top:${y - size / 2}px;width:${size}px;height:${size}px;`
            + `background:${color};border-radius:${isDiamond ? '1px' : '9999px'};opacity:0`;
        layer.append(piece);
        // Shoots out fast, then slows down; it only fades once it has (nearly) stopped.
        piece.animate([
            { transform: 'translate(0, 0) rotate(45deg) scale(0.3)', opacity: 1, easing: 'cubic-bezier(0.15, 0.85, 0.3, 1)' },
            { transform: `translate(${dx * 0.9}px, ${dy * 0.9}px) rotate(${45 + spin * 0.7}deg) scale(1)`, opacity: 1, offset: 0.55, easing: 'ease-in' },
            { transform: `translate(${dx}px, ${dy}px) rotate(${45 + spin}deg) scale(0.5)`, opacity: 0 },
        ], { duration: randomBetween(1600, 2100), delay: randomBetween(0, 80), fill: 'both' })
            .finished.then(() => piece.remove(), () => piece.remove());
    }
};

const achievementToasts = document.querySelector('[data-achievement-toasts]');

if (achievementToasts) {
    const queue = [];
    let current = null;

    const showNext = () => {
        current?.remove();
        current = null;

        const achievement = queue.shift();

        if (!achievement) {
            return;
        }

        const toast = achievementToasts.querySelector('[data-achievement-template]').content.firstElementChild.cloneNode(true);

        ['title', 'name', 'message', 'value', 'subject'].forEach((field) => {
            toast.querySelector(`[data-achievement-${field}]`).textContent = achievement[field];
        });
        toast.querySelectorAll('[data-achievement-icon]').forEach((icon) => {
            icon.hidden = icon.dataset.achievementIcon !== achievement.kind;
        });

        const timer = setTimeout(showNext, ACHIEVEMENT_TOAST_MS);

        toast.querySelector('[data-achievement-close]').addEventListener('click', () => {
            clearTimeout(timer);
            showNext();
        });

        current = toast;
        achievementToasts.append(toast);
        launchConfetti(toast.querySelector('[data-achievement-confetti]'));
    };

    import('./echo').then(() => {
        window.Echo.channel('achievements').listen('.achieved', (achievement) => {
            // A burst (many learners at once) keeps only the latest few.
            queue.push(achievement);
            queue.splice(0, Math.max(queue.length - ACHIEVEMENT_QUEUE_MAX, 0));

            if (!current) {
                showNext();
            }
        });
    });
}
