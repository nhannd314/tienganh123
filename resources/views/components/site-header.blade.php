{{-- Main site header with the shared navigation. `active` is the id of the current nav item. --}}
@props([
    'active' => null,
])

@php
    $navItems = [
        ['id' => 'home', 'label' => 'Trang chủ', 'icon' => 'house', 'href' => route('home')],
        ['id' => 'courses', 'label' => 'Khóa học', 'icon' => 'book-open', 'href' => route('courses.index')],
        ['id' => 'vocab', 'label' => 'Từ vựng', 'icon' => 'book-a', 'href' => '#'],
        ['id' => 'tests', 'label' => 'Bài kiểm tra', 'icon' => 'clipboard-check', 'href' => '#'],
        ['id' => 'games', 'label' => 'Trò chơi', 'icon' => 'gamepad-2', 'href' => '#'],
    ];
@endphp

<x-nav-bar :items="$navItems" :active="$active" :home-href="route('home')" :notifications="2" />
