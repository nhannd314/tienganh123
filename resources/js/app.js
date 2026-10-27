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

// Copy text to the clipboard; the async API needs a secure context, so fall back to a temporary textarea.
const copyText = (text) => {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).catch(() => {});

        return;
    }

    const field = document.createElement('textarea');

    field.value = text;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.opacity = '0';
    document.body.append(field);
    field.select();
    document.execCommand('copy');
    field.remove();
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
        copyText(table.dataset.copyText ?? '');
        swapIcon(button, 'check');
        clearTimeout(button.copyTimer);
        button.copyTimer = setTimeout(() => swapIcon(button, 'copy'), 1500);
    } else if (action === 'zoom-in' || action === 'zoom-out') {
        const current = Number(table.style.getPropertyValue('--vs') || 1);
        const next = current + (action === 'zoom-in' ? ZOOM_STEP : -ZOOM_STEP);
        const scale = Math.round(Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, next)) * 10) / 10;

        table.style.setProperty('--vs', String(scale));
        syncZoomButtons(scale);
    }
});

syncZoomButtons(1);

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

// Profile avatar: crop the chosen photo to a centred square and shrink it before upload (phone photos are huge).
const AVATAR_SIZE = 512;

document.addEventListener('change', async (event) => {
    const input = event.target.closest('[data-avatar-input]');
    const file = input?.files?.[0];

    if (!file || !file.type.startsWith('image/')) {
        return;
    }

    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const side = Math.min(bitmap.width, bitmap.height);
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = Math.min(AVATAR_SIZE, side);

        const context = canvas.getContext('2d');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(bitmap, (bitmap.width - side) / 2, (bitmap.height - side) / 2, side, side, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));

        if (!blob) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(new File([blob], 'avatar.jpg', { type: 'image/jpeg' }));
        input.files = transfer.files;

        const preview = document.querySelector('[data-avatar-preview]');

        if (preview) {
            preview.innerHTML = '';
            const image = new Image();
            image.src = URL.createObjectURL(blob);
            image.alt = '';
            image.className = 'size-16 flex-none rounded-full border-2 border-primary-border object-cover';
            preview.append(image);
        }
    } catch {
        // The browser cannot process the photo; the original is uploaded and the server resizes it.
    }
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
 * with the activity of the tab they opened last (`since`), and stays while any of their tabs is open.
 */
const learnersNow = document.querySelector('[data-learners-now]');

if (learnersNow) {
    import('./echo').then(() => {
        const channel = window.Echo.join('learning');
        const avatarStyles = JSON.parse(learnersNow.dataset.avatarStyles);
        const template = learnersNow.querySelector('[data-learner-template]');
        const list = learnersNow.querySelector('[data-learners-list]');
        const badge = learnersNow.querySelector('[data-learners-badge]');
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
