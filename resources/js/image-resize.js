// Shrinks a photo chosen in a file input before it is uploaded (phone photos are several MB and far bigger than they are shown):
// turned upright, cropped to the centre square when asked, scaled down to fit and saved as JPEG on white. The form waits for it
// on submit, so the photo that is sent is always the processed one. When the browser cannot process the photo, the original is
// sent and the server checks it.

// A photo that already fits and is lighter than this is sent as it is, so it does not lose quality for nothing.
const KEEP_BYTES = 600 * 1024;

const pendingByForm = new WeakMap();

/**
 * Process the photo of `input` and put the result back in the input.
 *
 * @param {HTMLInputElement} input
 * @param {{ maxWidth: number, maxHeight: number, square?: boolean, quality?: number }} options
 * @returns {Promise<File|null>} the file now in the input, or null when the input was left as it was
 */
export const shrinkInputImage = (input, options) => {
    const file = input.files?.[0];

    if (!file || !file.type.startsWith('image/')) {
        return Promise.resolve(null);
    }

    const work = resizeImage(file, options)
        .then((resized) => {
            // Another photo was chosen meanwhile, or the photo was kept as it is.
            if (!resized || input.files?.[0] !== file) {
                return null;
            }

            const transfer = new DataTransfer();
            transfer.items.add(resized);
            input.files = transfer.files;

            return resized;
        })
        .catch(() => null);

    if (input.form) {
        const pending = pendingByForm.get(input.form) ?? new Set();
        pending.add(work);
        pendingByForm.set(input.form, pending);
        input.form.setAttribute('aria-busy', 'true');
        work.finally(() => {
            pending.delete(work);
            if (pending.size === 0) {
                input.form.removeAttribute('aria-busy');
            }
        });
    }

    return work;
};

/**
 * The photo cropped and scaled down as asked, or null when it is better sent as it is.
 *
 * @param {File} file
 * @param {{ maxWidth: number, maxHeight: number, square?: boolean, quality?: number }} options
 * @returns {Promise<File|null>}
 */
export const resizeImage = async (file, { maxWidth, maxHeight, square = false, quality = 0.85 }) => {
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });

    try {
        let sourceX = 0;
        let sourceY = 0;
        let sourceWidth = bitmap.width;
        let sourceHeight = bitmap.height;

        if (square) {
            const side = Math.min(bitmap.width, bitmap.height);
            sourceX = (bitmap.width - side) / 2;
            sourceY = (bitmap.height - side) / 2;
            sourceWidth = side;
            sourceHeight = side;
        }

        const scale = Math.min(1, maxWidth / sourceWidth, maxHeight / sourceHeight);
        const width = Math.max(1, Math.round(sourceWidth * scale));
        const height = Math.max(1, Math.round(sourceHeight * scale));
        const isCropped = sourceWidth !== bitmap.width || sourceHeight !== bitmap.height;

        if (!isCropped && scale === 1 && file.size <= KEEP_BYTES) {
            return null;
        }

        const canvas = drawScaled(bitmap, sourceX, sourceY, sourceWidth, sourceHeight, width, height);
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));

        // Re-saving a photo that already fits only helps when it makes the file lighter.
        if (!blob || (!isCropped && scale === 1 && blob.size >= file.size)) {
            return null;
        }

        const name = `${file.name.replace(/\.[^.]*$/, '') || 'image'}.jpg`;

        return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
    } finally {
        bitmap.close?.();
    }
};

/**
 * Draw the part of the bitmap at the given size on white. A big step down is done in halves, so the result stays sharp
 * instead of grainy in browsers that scale in one pass with a cheap filter.
 */
const drawScaled = (bitmap, sourceX, sourceY, sourceWidth, sourceHeight, width, height) => {
    let source = bitmap;
    let x = sourceX;
    let y = sourceY;
    let currentWidth = sourceWidth;
    let currentHeight = sourceHeight;

    while (currentWidth / 2 > width && currentHeight / 2 > height) {
        const half = document.createElement('canvas');
        half.width = Math.round(currentWidth / 2);
        half.height = Math.round(currentHeight / 2);
        const context = half.getContext('2d');
        context.imageSmoothingQuality = 'high';
        context.drawImage(source, x, y, currentWidth, currentHeight, 0, 0, half.width, half.height);

        source = half;
        x = 0;
        y = 0;
        currentWidth = half.width;
        currentHeight = half.height;
    }

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d');
    context.fillStyle = '#fff';
    context.fillRect(0, 0, width, height);
    context.imageSmoothingQuality = 'high';
    context.drawImage(source, x, y, currentWidth, currentHeight, 0, 0, width, height);

    return canvas;
};

// A form still processing a photo is sent once the photo is ready, with the processed file.
document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;
        const pending = pendingByForm.get(form);

        if (!pending || pending.size === 0) {
            return;
        }

        event.preventDefault();
        const submitter = event.submitter;
        Promise.allSettled([...pending]).then(() => form.requestSubmit(submitter ?? undefined));
    },
    true,
);
