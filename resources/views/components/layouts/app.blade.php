@props([
    'title' => null,
    'mainClass' => 'py-8',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name', 'FunEnglish') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

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

        <x-messenger-button />

        {{ $scripts ?? '' }}
    </body>
</html>
