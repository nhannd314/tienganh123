/*
 * /demo page: lets the answer sound styles be heard side by side. A button carries `data-sound-preset` and
 * `data-sound-kind` (correct · wrong).
 */
import { playPreset } from './sound-effects';

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-sound-preset]');

    if (button) {
        playPreset(button.dataset.soundPreset, button.dataset.soundKind);
    }
});
