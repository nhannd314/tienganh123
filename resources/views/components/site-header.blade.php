{{-- Main site header with the shared navigation. `active` is the id of the current nav item. --}}
@props([
    'active' => null,
])

@php
    $navItems = [
        ['id' => 'home', 'label' => 'Trang chủ', 'icon' => 'house', 'href' => route('home')],
        ['id' => 'courses', 'label' => 'Khóa học', 'icon' => 'book-open', 'href' => route('courses.index')],
        ['id' => 'vocab', 'label' => 'Từ vựng', 'icon' => 'book-a', 'href' => route('vocabularies.index')],
        ['id' => 'flashcards', 'label' => 'Flashcard', 'icon' => 'layers', 'href' => route('flashcards.index')],
        ['id' => 'sentences', 'label' => 'Câu mẫu', 'icon' => 'file-text', 'href' => route('sentences.index')],
    ];
@endphp

<x-nav-bar :items="$navItems" :active="$active" :home-href="route('home')" />
