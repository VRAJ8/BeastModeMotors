<x-layouts.site title="Shops that verify their work" description="Independent shops and dealers that confirm their work on Beast Mode Motors car passports, with their response times and track record.">
    <section class="grain border-b border-line">
        <div class="container-x py-12">
            <x-page-header eyebrow="Shop directory" title="Shops that stand behind their work" text="Every shop here has confirmed real service records for their customers. Ask one of them to verify your next service — it takes them one click." />
            <form method="GET" class="mt-8 flex max-w-2xl flex-col gap-2 sm:flex-row">
                <label for="q" class="sr-only">Search</label>
                <input id="q" name="q" value="{{ $search }}" class="input flex-1" placeholder="Shop name or city">
                <label for="state" class="sr-only">State</label>
                <select id="state" name="state" class="input sm:w-48">
                    <option value="">Any state</option>
                    @foreach ($states as $code => $name)
                        <option value="{{ $code }}" @selected($state === $code)>{{ $name }}</option>
                    @endforeach
                </select>
                <button class="btn-primary">Search</button>
            </form>
        </div>
    </section>

    <div class="container-x py-10">
        @if ($shops->isEmpty())
            <x-empty icon="heroicon-o-wrench-screwdriver" title="No shops found" text="Shops appear here once they confirm their first record." />
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($shops as $shop)
                    @php($stats = $shop->stats())
                    <a href="{{ route('shops.show', $shop) }}" class="card card-pad group transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-ink/5">
                        <div class="flex items-start justify-between gap-3">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-ink font-display text-lg font-bold text-white">{{ str($shop->name)->substr(0, 1)->upper() }}</span>
                            @if ($stats['fast'])
                                <span class="badge-green"><x-heroicon-m-bolt class="size-3" /> Verifies within a day</span>
                            @endif
                        </div>
                        <p class="mt-4 font-display text-lg font-semibold group-hover:underline">{{ $shop->name }}</p>
                        <p class="text-sm text-muted">{{ $shop->location() ?? 'Location not added yet' }}</p>
                        <dl class="mt-5 grid grid-cols-3 gap-2 border-t border-dashed border-line-strong pt-4 text-center">
                            <div><dt class="eyebrow">Confirmed</dt><dd class="num mt-1 font-semibold">{{ $stats['confirmed'] }}</dd></div>
                            <div><dt class="eyebrow">Cars</dt><dd class="num mt-1 font-semibold">{{ $stats['cars'] }}</dd></div>
                            <div><dt class="eyebrow">Answers</dt><dd class="num mt-1 font-semibold">{{ $stats['response_rate'] !== null ? $stats['response_rate'].'%' : '—' }}</dd></div>
                        </dl>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $shops->links() }}</div>
        @endif

        <div class="mt-14 rounded-2xl bg-ink p-8 text-white sm:flex sm:items-center sm:justify-between sm:gap-8">
            <div>
                <p class="font-display text-xl font-semibold">Run a shop?</p>
                <p class="mt-1 text-white/70">There's nothing to sign up for. When a customer asks you to confirm a record, you'll get a one-click link — and your profile appears here.</p>
            </div>
            <a href="{{ route('how-it-works') }}" class="btn mt-5 shrink-0 bg-white text-ink hover:bg-paper sm:mt-0">How verification works</a>
        </div>
    </div>
</x-layouts.site>
