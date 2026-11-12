// The form that makes or edits a slideshow (resources/views/slides/index.blade.php and edit.blade.php): adds and removes slide rows, numbers them,
// and previews the chosen image.
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
        input.addEventListener('change', () => {
            const file = input.files[0];
            preview.hidden = !file && !savedSrc;
            preview.src = file ? URL.createObjectURL(file) : savedSrc;
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
