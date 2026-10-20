{{-- Sidebar of a vocabulary (its page and its review): `sidebar` is VocabularySidebar::for(); the slot comes last. --}}
@props([
    'sidebar',
])

<aside {{ $attributes->class(['flex min-w-0 max-w-full flex-col gap-6']) }}>
    <x-course-link-list :courses="$sidebar['courses']" />
    <x-vocabulary-link-list title="Từ vựng cùng chủ đề" :vocabularies="$sidebar['sameTopic']" />
    <x-vocabulary-link-list title="Từ vựng liên quan" :vocabularies="$sidebar['sameCourse']" />

    {{ $slot }}
</aside>
