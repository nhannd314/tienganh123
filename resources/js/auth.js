/*
 * Login / sign-up forms: show and hide the password, validate in the browser, and show a busy state while submitting.
 * The server validates again, so this only saves the visitor a round trip. Errors appear after the first submit
 * attempt and then follow the typing.
 */
const FIELD_ERROR = ['border-danger-border', 'bg-danger-bg'];
const FIELD_OK = ['border-line-default', 'bg-surface-card', 'hover:border-line-hover'];
const HINT_ERROR = 'text-danger-text';
const HINT_MUTED = 'text-fg-muted';

const EMAIL_PATTERN = /^\S+@\S+\.\S+$/;
const MIN_PASSWORD_LENGTH = 6;

const swapIcon = (button, name) => {
    const icon = button.querySelector('i[class*="icon-"]');

    if (icon) {
        icon.className = icon.className.replace(/icon-[\w-]+/, `icon-${name}`);
    }
};

// One button shows or hides every password field of its form.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');

    if (!button) {
        return;
    }

    const inputs = button.closest('form').querySelectorAll('[data-password-input]');
    const show = inputs[0].type === 'password';

    inputs.forEach((input) => {
        input.type = show ? 'text' : 'password';
    });
    button.setAttribute('aria-pressed', String(show));
    button.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    swapIcon(button, show ? 'eye-off' : 'eye');
});

// Leaving for Google takes a moment: show that the click was received.
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-google-button]');

    if (!link || link.getAttribute('aria-busy') === 'true') {
        return;
    }

    link.setAttribute('aria-busy', 'true');
    link.classList.add('cursor-progress', 'pointer-events-none');
    link.insertAdjacentHTML('afterbegin', '<i aria-hidden="true" class="icon-loader-circle inline-flex flex-none animate-spin text-xl leading-none"></i>');
    link.querySelector('img')?.remove();
});

const emailRule = (value) => {
    if (value === '') {
        return 'Bạn chưa nhập email.';
    }

    return EMAIL_PATTERN.test(value) ? null : 'Email chưa đúng định dạng, bạn kiểm tra lại nhé.';
};

/*
 * Rules per form: each gets the field's value (a boolean for a checkbox) and a reader for the other fields of the form,
 * and returns the error message or null. Fields without a rule are not checked.
 */
const rules = {
    login: {
        email: emailRule,
        password: (value) => (value === '' ? 'Bạn chưa nhập mật khẩu.' : null),
    },
    register: {
        name: (value) => (value === '' ? 'Bạn chưa nhập họ và tên.' : null),
        email: emailRule,
        password: (value) => (value.length < MIN_PASSWORD_LENGTH ? 'Mật khẩu cần có ít nhất 6 ký tự.' : null),
        password_confirmation: (value, read) => {
            if (value === '') {
                return 'Bạn chưa nhập lại mật khẩu.';
            }

            return value === read('password') ? null : 'Mật khẩu nhập lại chưa khớp.';
        },
        agree: (checked) => (checked ? null : 'Bạn cần đồng ý với điều khoản để tiếp tục.'),
    },
};

// Passwords are compared as typed; the other fields are trimmed.
const readValue = (input) => {
    if (input.type === 'checkbox') {
        return input.checked;
    }

    return input.hasAttribute('data-password-input') ? input.value : input.value.trim();
};

const showError = (field, message) => {
    const input = field.querySelector('[data-input]');
    const hint = field.querySelector('[data-field-hint]');
    const icon = field.querySelector('[data-field-icon]');
    const text = message ?? hint.dataset.defaultHint ?? '';

    if (input.type !== 'checkbox') {
        input.classList.remove(...(message ? FIELD_OK : FIELD_ERROR));
        input.classList.add(...(message ? FIELD_ERROR : FIELD_OK));
    }

    if (message) {
        input.setAttribute('aria-invalid', 'true');
    } else {
        input.removeAttribute('aria-invalid');
    }

    hint.textContent = text;
    hint.classList.toggle('hidden', text === '');
    hint.classList.toggle(HINT_ERROR, Boolean(message));
    hint.classList.toggle(HINT_MUTED, !message);

    if (icon) {
        icon.hidden = !message;
    }
};

document.querySelectorAll('[data-auth-form]').forEach((form) => {
    const formRules = rules[form.dataset.authForm] ?? {};
    const fields = [...form.querySelectorAll('[data-field]')];
    const read = (name) => {
        const input = form.querySelector(`[name="${name}"]`);

        return input ? readValue(input) : '';
    };
    let tried = false;

    const validateField = (field) => {
        const rule = formRules[field.dataset.field];

        if (!rule) {
            return true;
        }

        const message = rule(readValue(field.querySelector('[data-input]')), read);

        showError(field, message);

        return message === null;
    };

    // A field can depend on another one (the repeated password), so every field is checked again.
    ['input', 'change'].forEach((type) => {
        form.addEventListener(type, () => {
            if (tried) {
                fields.forEach(validateField);
            }
        });
    });

    form.addEventListener('submit', (event) => {
        tried = true;

        const valid = fields.map(validateField).every(Boolean);

        if (!valid) {
            event.preventDefault();
            form.querySelector('[aria-invalid="true"]')?.focus();

            return;
        }

        const submit = form.querySelector('[data-submit]');

        submit.setAttribute('aria-busy', 'true');
        submit.classList.add('cursor-progress');
        submit.insertAdjacentHTML('afterbegin', '<i aria-hidden="true" class="icon-loader-circle inline-flex flex-none animate-spin text-xl leading-none"></i>');
        // Disabling after the browser has read the form keeps a double click from submitting twice.
        setTimeout(() => {
            submit.disabled = true;
        });
    });
});
