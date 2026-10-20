{{--
    Sidebar card of the vocabulary and sentence set pages (styled like x-game-promo): the title and a note that the review
    is not saved while the lesson review is, on a soft primary panel, then the "Ôn tập nhanh" button to their members-only review. `source` names the page in the note ("từ vựng", "câu mẫu").
--}}
@props([
    'title',
    'href',
    'source',
])

<div {{ $attributes->class(['flex flex-col gap-5 rounded-lg bg-surface-card p-6 shadow-card']) }}>
    <div class="flex flex-col gap-4 rounded-md bg-primary-bg p-5">
        <div class="flex items-center gap-3">
            <x-icon name="book-open" size="lg" class="text-primary" />
            <h2 class="type-h3 text-fg-strong">{{ $title }}</h2>
        </div>

        <p class="type-body text-pretty text-fg-muted">
            Ôn tập nhanh ở {{ $source }} không lưu kết quả và không tính vào điểm số xếp hạng. Truy cập bài học tương ứng để ôn tập đầy đủ hơn và được lưu lại kết quả.
        </p>
    </div>

    {{-- Open to members only; a guest is sent to log in and anyone else is told why it is locked --}}
    <x-button size="lg" icon-left="book-open" full-width :href="$href">Ôn tập nhanh</x-button>
</div>
