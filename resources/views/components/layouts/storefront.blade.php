@props([
    'title' => null,
    'description' => 'Beast Mode Motors — Miami\'s home of exotic, luxury and performance cars. Browse certified supercars, book a test drive, and get an instant trade-in estimate.',
    'image' => null,
])

@php
    $pageTitle = $title ? $title.' · '.config('dealership.name') : config('dealership.name').' — Exotic & Performance Cars';
    $ogImage = $image ?? asset('images/bmm-logo.png');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a0a0a">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('dealership.name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/png" href="{{ asset('images/bmm-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body class="flex min-h-full flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[100] focus:rounded focus:bg-gold focus:px-4 focus:py-2 focus:text-ink">Skip to content</a>

    <x-site.nav />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />
    <x-site.toast />

    @livewireScripts
</body>
</html>
