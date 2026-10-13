// x-audio-button: speak the word with the browser's speech synthesis.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-audio-button]');

    if (!button || button.disabled) {
        return;
    }

    const stopPlaying = () => delete button.dataset.playing;

    button.dataset.playing = '';
    setTimeout(stopPlaying, 1200);

    if (!('speechSynthesis' in window)) {
        return;
    }

    const utterance = new SpeechSynthesisUtterance(button.dataset.text ?? '');
    utterance.lang = button.dataset.lang || 'en-US';
    utterance.rate = 0.85;
    utterance.onend = stopPlaying;

    speechSynthesis.cancel();
    speechSynthesis.speak(utterance);
});

/*
 * Disclosure toggles: `data-toggle="<id>"` shows/hides the element with that id and keeps aria-expanded in sync.
 * `data-toggle-group` closes the other toggles of the group; `data-toggle-icon` swaps the button's icon while open;
 * `data-toggle-close="<id>"` only closes. Escape closes everything that is open.
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

    const icon = button.querySelector('i[class*="icon-"]');

    if (icon) {
        icon.className = icon.className.replace(/icon-[\w-]+/, 'icon-check');
        clearTimeout(button.copyTimer);
        button.copyTimer = setTimeout(() => {
            icon.className = icon.className.replace(/icon-[\w-]+/, 'icon-copy');
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
    const root = document.createElement('div');
    const clone = element.cloneNode(true);
    const cleanUp = () => {
        root.remove();
        delete document.body.dataset.printOnly;
    };

    clone.setAttribute('data-expanded', '');
    root.dataset.printRoot = '';
    root.append(clone);
    document.body.append(root);
    document.body.dataset.printOnly = '';
    window.addEventListener('afterprint', cleanUp, { once: true });
    window.print();
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-print-flashcards]')) {
        printElement(document.querySelector('[data-flashcards]'));
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

// Example sentence card (lesson page): reveal the Vietnamese meaning of one sentence and drop the button.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-example-toggle]');
    const meaning = button?.closest('[data-example]')?.querySelector('[data-example-meaning]');

    if (!meaning) {
        return;
    }

    meaning.classList.remove('invisible');
    button.hidden = true;
});

/*
 * Payment page: listen on the payment's private channel (`data-payment-channel`) for the `paid` event Reverb pushes
 * when the transfer arrives, then show the success message and go to what was bought. `data-payment-status` is the
 * URL that tells whether it is already paid, asked each time the channel is (re)subscribed.
 */
const PAYMENT_REDIRECT_DELAY_MS = 1500;
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
        setTimeout(() => window.location.assign(redirect), PAYMENT_REDIRECT_DELAY_MS);
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
