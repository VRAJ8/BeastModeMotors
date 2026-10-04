<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>For sale sign · {{ $vehicle->title() }}</title>
    @vite(['resources/css/app.css'])
    <style>@page { size: letter landscape; margin: 0.4in; }</style>
</head>
<body class="bg-white">
    <div class="no-print flex items-center justify-between border-b border-line bg-paper px-6 py-3 text-sm">
        <a href="{{ route('vehicles.share', $vehicle) }}" class="link">← Back</a>
        <span class="text-muted">Prints on one US Letter page, landscape.</span>
        <button onclick="window.print()" class="btn-primary btn-sm">Print</button>
    </div>
    <main class="mx-auto flex max-w-[10in] items-stretch gap-10 p-10">
        <div class="flex flex-1 flex-col">
            <p class="font-display text-[96px] leading-none font-extrabold tracking-tight text-accent">FOR SALE</p>
            <p class="mt-6 font-display text-5xl font-bold tracking-tight">{{ $vehicle->title() }}</p>
            @if ($vehicle->trim)<p class="mt-1 text-2xl text-ink-soft">{{ $vehicle->trim }}</p>@endif
            <div class="mt-8 flex gap-10">
                @if ($listing)
                    <div><p class="eyebrow">Asking</p><p class="num mt-1 text-4xl font-semibold">{{ money($listing->price_cents) }}</p></div>
                @endif
                <div><p class="eyebrow">Odometer</p><p class="num mt-1 text-4xl font-semibold">{{ number_format($vehicle->current_mileage) }}</p></div>
                <div><p class="eyebrow">Owners</p><p class="num mt-1 text-4xl font-semibold">{{ $vehicle->ownerCount() }}</p></div>
            </div>
            <div class="mt-auto flex items-center gap-4 pt-10">
                <x-score-ring :score="$score" size="md" />
                <p class="text-lg leading-snug">Passport Score <strong>{{ $score['total'] }}/100</strong><br><span class="text-ink-soft">{{ $vehicle->records->count() }} service records, {{ $vehicle->records->filter(fn ($r) => $r->evidence() === 'verified')->count() }} confirmed by the shop</span></p>
            </div>
        </div>
        <div class="flex w-[3.6in] flex-col items-center justify-center rounded-3xl border-4 border-ink p-6 text-center">
            <div class="[&_svg]:size-[3in]">{!! $qr !!}</div>
            <p class="mt-4 font-display text-2xl font-bold">Scan for the full history</p>
            <p class="mt-1 text-sm text-ink-soft">Service records, odometer checks & recalls</p>
            <div class="mt-4"><x-logo /></div>
        </div>
    </main>
</body>
</html>
