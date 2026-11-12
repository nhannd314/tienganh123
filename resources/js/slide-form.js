import { shrinkInputImage } from './image-resize';

// The form that makes or edits a slideshow (resources/views/slides/index.blade.php and edit.blade.php): adds and removes slide rows, numbers them,
// previews the chosen image and shrinks it to fit a full HD screen before upload (the slideshow shows it whole, so it is not cropped).
const SLIDE_MAX_WIDTH = 1920;
const SLIDE_MAX_HEIGHT = 1080;

const form = document.querySelector('[data-slide-form]');

if (form) {
    const list = form.querySelector('[data-slide-list]');
    const template = form.querySelector('[data-slide-template]');
    const addButton = form.querySelector('[data-slide-add]');
    const maxSlides = Number(form.dataset.maxSlides);
    let nextIndex = Number(form.dataset.nextIndex || 0);

    const renumber = () => {
        const rows = list.querySelectorAll('[data-slide-row]');
        rows.forEach((row, position) => {
            row.querySelector('[data-slide-number]').textContent = position + 1;
            row.querySelector('[data-slide-remove]').hidden = rows.length === 1;
        });
        addButton.disabled = rows.length >= maxSlides;
    };

    const bindRow = (row) => {
        const input = row.querySelector('[data-slide-image]');
        const preview = row.querySelector('[data-slide-preview]');
        const savedSrc = preview.getAttribute('src');
        let previewUrl = null;
        const showPreview = (file) => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            previewUrl = file ? URL.createObjectURL(file) : null;
            preview.hidden = !file && !savedSrc;
            preview.src = previewUrl ?? savedSrc ?? '';
        };

        input.addEventListener('change', async () => {
            showPreview(input.files[0]);
            const resized = await shrinkInputImage(input, { maxWidth: SLIDE_MAX_WIDTH, maxHeight: SLIDE_MAX_HEIGHT, quality: 0.82 });
            if (resized) {
                showPreview(resized);
            }
        });

        row.querySelector('[data-slide-remove]').addEventListener('click', () => {
            row.remove();
            renumber();
        });
    };

    const addRow = () => {
        const index = nextIndex++;
        const row = template.content.firstElementChild.cloneNode(true);

        row.querySelectorAll('[name*="__INDEX__"]').forEach((field) => {
            field.name = field.name.replace('__INDEX__', index);
        });

        bindRow(row);
        list.append(row);
        renumber();
    };

    addButton.addEventListener('click', addRow);
    list.querySelectorAll('[data-slide-row]').forEach(bindRow);

    if (list.children.length === 0) {
        addRow();
    } else {
        renumber();
    }
}
