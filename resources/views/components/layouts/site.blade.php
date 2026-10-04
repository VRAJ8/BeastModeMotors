@props([
    'title' => null,
    'description' => 'Beast Mode Motors is a car passport: a verified, transferable history of your car — service records confirmed by the shops that did the work, odometer checks, recalls and running costs — and a safer way to sell it privately.',
    'image' => null,
    'robots' => null,
])

@php
    $pageTitle = $title ? $title.' · '.config('passport.name') : config('passport.name').' — The verified history your car deserves';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f5f3ee">
    @if ($robots)
        <meta name="robots" content="{{ $robots }}">
    @endif

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('passport.name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body class="flex min-h-full flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:rounded-lg focus:bg-ink focus:px-4 focus:py-2 focus:text-white">Skip to content</a>

    <x-site.nav />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />
    <x-site.toast />

    @livewireScripts
</body>
</html>
