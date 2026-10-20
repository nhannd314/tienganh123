{{-- Sidebar of a sentence set (its page and its review): `sidebar` is SentenceSetSidebar::for(); the slot comes last. --}}
@props([
    'sidebar',
])

<aside {{ $attributes->class(['flex min-w-0 max-w-full flex-col gap-6']) }}>
    <x-course-link-list :courses="$sidebar['courses']" />
    <x-vocabulary-link-list title="Bộ từ vựng liên quan" :vocabularies="$sidebar['vocabularies']" />
    <x-flashcard-set-link-list title="Flashcard liên quan" :flashcard-sets="$sidebar['flashcardSets']" />
    <x-sentence-set-link-list title="Bộ câu cùng chủ đề" :sentence-sets="$sidebar['sameTopic']" />

    {{ $slot }}
</aside>
