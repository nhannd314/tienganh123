{{--
    Draws the rectangle around the object of each word of a word scene, on the picture of the scene. It works on the state of the
    items repeater (`$statePath`), whose rectangles are fractions (0 to 1) of the picture width and height: choose a word, then drag on the
    picture, or click two opposite corners. Escape forgets a corner that was clicked. Nothing is saved until the form is saved.
--}}
<div
    wire:ignore
    x-data="{
        items: $wire.$entangle(@js($statePath)),
        path: @js($statePath),
        active: null,
        corner: null,
        pressed: null,
        dragged: false,
        draft: null,
        get keys() {
            return Object.keys(this.items ?? {})
        },
        isDrawn(key) {
            const item = this.items?.[key]

            return item && [item.x, item.y, item.width, item.height].every((value) => value !== null && value !== '' && value !== undefined)
        },
        rectangleOf(key) {
            const item = this.items[key]

            return { x: Number(item.x), y: Number(item.y), width: Number(item.width), height: Number(item.height) }
        },
        select(key) {
            this.active = key
            this.corner = null
            this.draft = null
        },
        point(event) {
            const box = this.$refs.surface.getBoundingClientRect()
            const clamp = (value) => Math.min(1, Math.max(0, value))

            return { x: clamp((event.clientX - box.left) / box.width), y: clamp((event.clientY - box.top) / box.height) }
        },
        between(a, b) {
            return { x: Math.min(a.x, b.x), y: Math.min(a.y, b.y), width: Math.abs(a.x - b.x), height: Math.abs(a.y - b.y) }
        },
        down(event) {
            if (this.active === null) {
                return
            }

            this.pressed = this.point(event)
            this.dragged = false
            this.$refs.surface.setPointerCapture(event.pointerId)
        },
        move(event) {
            if (this.active === null) {
                return
            }

            const here = this.point(event)

            if (this.pressed) {
                const rectangle = this.between(this.pressed, here)

                this.dragged = this.dragged || rectangle.width > 0.01 || rectangle.height > 0.01
                this.draft = this.dragged ? rectangle : null
            } else if (this.corner) {
                this.draft = this.between(this.corner, here)
            }
        },
        up(event) {
            if (!this.pressed) {
                return
            }

            const here = this.point(event)

            if (this.dragged) {
                this.commit(this.between(this.pressed, here))
            } else if (this.corner) {
                this.commit(this.between(this.corner, here))
            } else {
                this.corner = here
            }

            this.pressed = null
            this.dragged = false
        },
        commit(rectangle) {
            this.corner = null
            this.draft = null

            if (rectangle.width < 0.01 || rectangle.height < 0.01) {
                return
            }

            Object.entries(rectangle).forEach(([name, value]) => {
                $wire.$set(`${this.path}.${this.active}.${name}`, Math.round(value * 10000) / 10000, false)
            })

            const next = this.keys.find((key) => key !== this.active && !this.isDrawn(key))

            if (next) {
                this.active = next
            }
        },
        clear() {
            ['x', 'y', 'width', 'height'].forEach((name) => $wire.$set(`${this.path}.${this.active}.${name}`, null, false))
            this.corner = null
            this.draft = null
        },
        style(rectangle) {
            return `left:${rectangle.x * 100}%;top:${rectangle.y * 100}%;width:${rectangle.width * 100}%;height:${rectangle.height * 100}%`
        },
    }"
    x-on:keydown.escape.window="corner = null; draft = null"
    class="word-scene-editor"
>
    <style>
        .word-scene-editor { display: flex; flex-direction: column; gap: 0.75rem; }
        .word-scene-editor__words { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .word-scene-editor__word { border: 1px solid rgb(var(--gray-300)); border-radius: 9999px; padding: 0.25rem 0.75rem; font-size: 0.875rem; cursor: pointer; background: transparent; color: inherit; }
        .word-scene-editor__word[data-active] { background: rgb(var(--primary-600)); border-color: rgb(var(--primary-600)); color: #fff; }
        .word-scene-editor__word[data-drawn]::after { content: ' ✓'; }
        .word-scene-editor__surface { position: relative; width: 100%; max-width: 60rem; touch-action: none; user-select: none; cursor: crosshair; line-height: 0; }
        .word-scene-editor__surface img { width: 100%; height: auto; display: block; pointer-events: none; border-radius: 0.5rem; }
        .word-scene-editor__box { position: absolute; border: 2px solid rgb(var(--primary-500)); background: rgb(var(--primary-500) / 0.18); pointer-events: none; }
        .word-scene-editor__box[data-active] { border-color: rgb(var(--danger-500)); background: rgb(var(--danger-500) / 0.2); }
        .word-scene-editor__box > span { position: absolute; left: -2px; top: -1.4rem; background: rgb(var(--primary-600)); color: #fff; font-size: 0.75rem; line-height: 1.2rem; padding: 0 0.4rem; border-radius: 0.25rem; white-space: nowrap; }
        .word-scene-editor__corner { position: absolute; width: 0.75rem; height: 0.75rem; margin: -0.375rem 0 0 -0.375rem; border-radius: 9999px; background: rgb(var(--danger-500)); pointer-events: none; }
    </style>

    @if (blank($imageUrl))
        <p class="text-sm text-gray-500 dark:text-gray-400">Chưa có ảnh. Hãy tải ảnh lên hoặc bấm “Tạo ảnh AI”, lưu lại rồi mới vẽ vùng cho từng từ.</p>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Chọn một từ bên dưới, rồi kéo chuột trên ảnh để vẽ hình chữ nhật bao quanh vật thể, hoặc bấm lần lượt hai góc đối diện (Esc để bỏ góc đã bấm). Nhớ bấm Lưu sau khi vẽ.
        </p>

        <div class="word-scene-editor__words">
            <template x-for="key in keys" :key="key">
                <button
                    type="button"
                    class="word-scene-editor__word"
                    :data-active="active === key ? '' : null"
                    :data-drawn="isDrawn(key) ? '' : null"
                    x-text="items[key].label || 'Chưa chọn từ'"
                    x-on:click="select(key)"
                ></button>
            </template>

            <button type="button" class="word-scene-editor__word" x-show="active !== null && isDrawn(active)" x-on:click="clear()">Xóa vùng của từ này</button>
        </div>

        <div
            x-ref="surface"
            class="word-scene-editor__surface"
            x-on:pointerdown.prevent="down($event)"
            x-on:pointermove="move($event)"
            x-on:pointerup="up($event)"
        >
            <img src="{{ $imageUrl }}" alt="">

            <template x-for="key in keys.filter((key) => isDrawn(key))" :key="key">
                <div class="word-scene-editor__box" :data-active="active === key ? '' : null" :style="style(rectangleOf(key))">
                    <span x-text="items[key].label"></span>
                </div>
            </template>

            <div class="word-scene-editor__box" data-active x-show="draft" :style="draft ? style(draft) : ''"></div>
            <div class="word-scene-editor__corner" x-show="corner" :style="corner ? `left:${corner.x * 100}%;top:${corner.y * 100}%` : ''"></div>
        </div>
    @endif
</div>
