@props([
    'title' => null,
    'mainClass' => 'py-8',
    'pageTitle' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'noindex' => false,
])

@php
    $siteName = 'TiếngAnhPro';
    $defaultDescription = 'TiếngAnhPro giúp bé học tiếng Anh vui mỗi ngày với video sinh động, flashcard, từ vựng có hình minh họa và bài tập đa dạng.';
    $metaTitle = $pageTitle ?? (($title ? $title.' — ' : '').$siteName);
    $metaDescription = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($description ?? $defaultDescription))), 160);
    $canonicalUrl = url()->current();
    $metaImage = $image ? (str_starts_with($image, 'http') ? $image : url($image)) : asset('images/teacher-kids.png');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $metaTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <meta name="robots" content="{{ $noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
        <meta name="author" content="{{ $siteName }}">
        <meta name="theme-color" content="#0F62E0">
        <link rel="canonical" href="{{ $canonicalUrl }}">

        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:locale" content="vi_VN">
        <meta property="og:type" content="{{ $type }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ $metaImage }}">
        <meta property="og:image:alt" content="{{ $siteName }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $metaImage }}">

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('images/logo-icon.png') }}">

        @if (filled($learningActivity ?? null))
            <meta name="learning-activity" content="{{ encrypt($learningActivity) }}">
        @endif

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700&display=swap&subset=vietnamese">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen">
        {{ $header ?? '' }}

        <main class="mx-auto max-w-layout px-4 xl:px-10 {{ $mainClass }}">
            {{ $slot }}
        </main>

        {{ $footer ?? '' }}

        <x-leaderboard />
        <x-learners-now />
        <x-achievement-toast />

        {{ $scripts ?? '' }}
    </body>
</html>
