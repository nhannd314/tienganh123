/*
 * Editor of a word scene (admin/word-scenes/edit): draw a rectangle around an object of the picture, then type the word of the object.
 *
 * Draw by dragging on the picture, or by clicking two opposite corners; an input then asks the word and Enter saves it with its
 * rectangle at once (`data-store-url`). Drawing a word the scene already has again redraws it. Escape forgets the corner or the
 * rectangle in progress. The words are listed beside the picture, with a button to remove each one (`deleteUrl` of the item).
 *
 * Rectangles are fractions (0 to 1) of the picture width and height, so they do not depend on the size the picture is shown at.
 * `data-items` is the list of the words of the scene: {id, word, x, y, width, height, deleteUrl}.
 */
const MIN_SIDE = 0.01;
const DRAG_DISTANCE = 0.01;

const root = document.querySelector('[data-word-scene-editor]');

if (root) {
    const { csrf, storeUrl } = root.dataset;
    const part = (name) => root.querySelector(`[data-editor-${name}]`);
    const surface = part('surface');
    const boxes = part('boxes');
    const draftBox = part('draft');
    const cornerDot = part('corner');
    const prompt = part('prompt');
    const wordInput = part('word');
    const errorText = part('error');
    const list = part('list');
    const emptyText = part('empty');

    let items = JSON.parse(root.dataset.items);
    let corner = null;
    let pressed = null;
    let dragged = false;
    let pending = null;
    let busy = false;

    const request = (url, options = {}) => fetch(url, {
        ...options,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
    });

    const isDrawn = (item) => [item.x, item.y, item.width, item.height].every((value) => value !== null && value !== undefined);

    const place = (element, rectangle) => {
        element.style.left = `${rectangle.x * 100}%`;
        element.style.top = `${rectangle.y * 100}%`;
        element.style.width = `${rectangle.width * 100}%`;
        element.style.height = `${rectangle.height * 100}%`;
    };

    const showRectangle = (element, rectangle) => {
        element.hidden = !rectangle;

        if (rectangle) {
            place(element, rectangle);
        }
    };

    const render = () => {
        boxes.replaceChildren(...items.filter(isDrawn).map((item) => {
            const box = document.createElement('div');
            const label = document.createElement('span');

            box.className = 'absolute border-2 border-primary bg-primary/20';
            label.className = 'absolute -top-6 -left-0.5 rounded-sm bg-primary px-2 text-sm leading-6 whitespace-nowrap text-on-primary';
            label.textContent = item.word;
            box.append(label);
            place(box, item);

            return box;
        }));

        list.replaceChildren(...items.map(listRow));

        emptyText.hidden = items.length > 0;
    };

    const listRow = (item) => {
        const row = document.createElement('li');
        const name = document.createElement('span');
        const remove = document.createElement('button');

        row.className = 'inline-flex items-center gap-2 rounded-full border border-line-default py-1 pr-1.5 pl-4';
        name.className = 'type-body text-fg-strong';
        name.lang = 'en';
        name.textContent = isDrawn(item) ? item.word : `${item.word} (chưa vẽ vùng)`;
        remove.type = 'button';
        remove.title = remove.ariaLabel = `Xóa ${item.word}`;
        remove.className = 'inline-flex size-6 cursor-pointer items-center justify-center rounded-full text-danger-text transition-interactive hover:bg-danger-bg';
        remove.innerHTML = '<i class="icon-x text-base leading-none" aria-hidden="true"></i>';
        remove.addEventListener('click', () => removeItem(item, remove));
        row.append(name, remove);

        return row;
    };

    const removeItem = async (item, button) => {
        button.disabled = true;

        const response = await request(item.deleteUrl, { method: 'DELETE' }).catch(() => null);

        if (response?.ok) {
            items = items.filter((other) => other.id !== item.id);
            render();
        } else {
            button.disabled = false;
        }
    };

    const pointOf = (event) => {
        const box = surface.getBoundingClientRect();
        const clamp = (value) => Math.min(1, Math.max(0, value));

        return { x: clamp((event.clientX - box.left) / box.width), y: clamp((event.clientY - box.top) / box.height) };
    };

    const between = (a, b) => ({ x: Math.min(a.x, b.x), y: Math.min(a.y, b.y), width: Math.abs(a.x - b.x), height: Math.abs(a.y - b.y) });

    const showCorner = () => {
        cornerDot.hidden = !corner;

        if (corner) {
            cornerDot.style.left = `${corner.x * 100}%`;
            cornerDot.style.top = `${corner.y * 100}%`;
        }
    };

    // The input of the word sits under the rectangle, or above it when the rectangle is near the bottom of the picture.
    const showPrompt = () => {
        prompt.hidden = !pending;

        if (!pending) {
            return;
        }

        const below = pending.y + pending.height < 0.8;

        prompt.style.left = `${Math.min(pending.x, 0.6) * 100}%`;
        prompt.style.top = `${(below ? pending.y + pending.height : pending.y) * 100}%`;
        prompt.style.transform = below ? 'none' : 'translateY(-100%)';
        prompt.style.marginTop = below ? '0.5rem' : '-0.5rem';
    };

    const cancel = () => {
        corner = null;
        pending = null;
        showCorner();
        showRectangle(draftBox, null);
        showPrompt();
    };

    const finish = (rectangle) => {
        corner = null;
        showCorner();

        if (rectangle.width < MIN_SIDE || rectangle.height < MIN_SIDE) {
            showRectangle(draftBox, null);

            return;
        }

        pending = rectangle;
        wordInput.value = '';
        errorText.hidden = true;
        showRectangle(draftBox, pending);
        showPrompt();
        wordInput.focus();
    };

    surface.addEventListener('pointerdown', (event) => {
        event.preventDefault();
        pressed = pointOf(event);
        dragged = false;
        surface.setPointerCapture(event.pointerId);
    });

    surface.addEventListener('pointermove', (event) => {
        const here = pointOf(event);

        if (pressed) {
            const rectangle = between(pressed, here);

            dragged = dragged || rectangle.width > DRAG_DISTANCE || rectangle.height > DRAG_DISTANCE;
            showRectangle(draftBox, dragged ? rectangle : pending);
        } else if (corner) {
            showRectangle(draftBox, between(corner, here));
        }
    });

    surface.addEventListener('pointerup', (event) => {
        if (!pressed) {
            return;
        }

        const here = pointOf(event);

        if (dragged) {
            finish(between(pressed, here));
        } else if (corner) {
            finish(between(corner, here));
        } else {
            pending = null;
            showPrompt();
            showRectangle(draftBox, null);
            corner = here;
            showCorner();
        }

        pressed = null;
        dragged = false;
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            cancel();
        }
    });

    part('cancel').addEventListener('click', cancel);

    prompt.addEventListener('submit', async (event) => {
        event.preventDefault();

        const word = wordInput.value.trim();

        if (!pending || !word || busy) {
            return;
        }

        busy = true;
        errorText.hidden = true;

        const response = await request(storeUrl, { method: 'POST', body: JSON.stringify({ word, ...pending }) }).catch(() => null);

        busy = false;

        if (!response?.ok) {
            const body = await response?.json().catch(() => null);

            errorText.textContent = body?.errors ? Object.values(body.errors)[0][0] : 'Không lưu được từ.';
            errorText.hidden = false;

            return;
        }

        const saved = await response.json();

        items = [...items.filter((item) => item.id !== saved.id), saved];
        cancel();
        render();
    });

    render();
}
