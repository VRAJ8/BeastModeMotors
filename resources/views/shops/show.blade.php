<x-layouts.site :title="$shop->name" :description="$shop->name.' has confirmed '.$stats['confirmed'].' service records on Beast Mode Motors car passports.'">
    <section class="grain border-b border-line">
        <div class="container-x py-12">
            <a href="{{ route('shops.index') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink"><x-heroicon-m-arrow-left class="size-4" /> All shops</a>
            <div class="mt-5 flex flex-wrap items-start gap-5">
                <span class="grid size-16 shrink-0 place-items-center rounded-2xl bg-ink font-display text-2xl font-bold text-white">{{ str($shop->name)->substr(0, 1)->upper() }}</span>
                <div class="min-w-0 flex-1">
                    <h1 class="display text-3xl sm:text-4xl">{{ $shop->name }}</h1>
                    <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-ink-soft">
                        @if ($shop->location())<span class="flex items-center gap-1"><x-heroicon-m-map-pin class="size-4 text-muted" /> {{ $shop->location() }}</span>@endif
                        @if ($shop->phone)<span class="flex items-center gap-1"><x-heroicon-m-phone class="size-4 text-muted" /> {{ $shop->phone }}</span>@endif
                        @if ($shop->website)<a href="{{ $shop->website }}" rel="nofollow noopener" target="_blank" class="flex items-center gap-1 hover:underline"><x-heroicon-m-globe-alt class="size-4 text-muted" /> {{ parse_url($shop->website, PHP_URL_HOST) }}</a>@endif
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="stamp border-verified text-verified"><x-heroicon-s-check-badge class="size-3.5" /> Verifies its work</span>
                        @if ($stats['fast'])<span class="badge-green"><x-heroicon-m-bolt class="size-3" /> Usually answers within a day</span>@endif
                        @foreach ($shop->specialties ?? [] as $specialty)<span class="badge-gray">{{ $specialty }}</span>@endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container-x grid gap-8 py-10 lg:grid-cols-[1fr_340px]">
        <div class="space-y-8">
            @if ($shop->about)
                <section class="card card-pad text-[15px] leading-relaxed whitespace-pre-line text-ink-soft">{{ $shop->about }}</section>
            @endif

            <section>
                <h2 class="panel-title mb-4">Recently confirmed work</h2>
                <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                    @foreach ($recent as $record)
                        <li class="flex flex-wrap items-center gap-3 p-4">
                            <x-heroicon-s-check-badge class="size-5 shrink-0 text-verified" />
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $record->title }}</p>
                                <p class="text-sm text-muted">{{ $record->vehicle->title() }} · {{ $record->category->getLabel() }}</p>
                            </div>
                            <span class="num text-sm text-muted">{{ $record->performed_on->format('M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-muted">Owners' names, VINs and prices are never shown here.</p>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card card-pad">
                <p class="eyebrow">Track record</p>
                <dl class="mt-4 grid grid-cols-2 gap-4">
                    <div><dt class="text-xs text-muted">Records confirmed</dt><dd class="num mt-1 text-2xl font-semibold">{{ $stats['confirmed'] }}</dd></div>
                    <div><dt class="text-xs text-muted">Cars worked on</dt><dd class="num mt-1 text-2xl font-semibold">{{ $stats['cars'] }}</dd></div>
                    <div><dt class="text-xs text-muted">Requests answered</dt><dd class="num mt-1 text-2xl font-semibold">{{ $stats['response_rate'] !== null ? $stats['response_rate'].'%' : '—' }}</dd></div>
                    <div><dt class="text-xs text-muted">Typical answer</dt><dd class="num mt-1 text-2xl font-semibold">{{ $stats['median_hours'] === null ? '—' : ($stats['median_hours'] < 1 ? '<1h' : ($stats['median_hours'] < 48 ? round($stats['median_hours']).'h' : round($stats['median_hours'] / 24).'d')) }}</dd></div>
                </dl>
                @if ($stats['disputed'])
                    <p class="mt-4 text-xs text-muted">Also flagged {{ $stats['disputed'] }} {{ str('record')->plural($stats['disputed']) }} as not matching their invoices — that's the system working.</p>
                @endif
                @if ($stats['makes']->isNotEmpty())
                    <div class="divider my-5"></div>
                    <p class="eyebrow">Makes they've confirmed work on</p>
                    <p class="mt-2 flex flex-wrap gap-1.5">@foreach ($stats['makes'] as $make)<span class="badge-gray">{{ $make }}</span>@endforeach</p>
                @endif
            </section>
            <section class="rounded-2xl border border-line bg-paper-deep/60 p-5 text-sm text-ink-soft">
                <p class="font-semibold text-ink">Had work done here?</p>
                <p class="mt-1">Log it in your car's passport and pick {{ $shop->name }} as the shop — they'll get a one-click link to confirm it.</p>
                <a href="{{ auth()->check() ? route('garage') : route('register') }}" class="link mt-3 inline-block">{{ auth()->check() ? 'Go to your garage' : 'Start a free passport' }}</a>
            </section>
        </aside>
    </div>
</x-layouts.site>
