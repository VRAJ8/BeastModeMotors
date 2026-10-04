<x-layouts.site title="Deals" robots="noindex">
    <div class="container-x py-10">
        <x-page-header eyebrow="Deals" title="Buying & selling" text="Every conversation, offer, inspection and handover in one place." />

        @foreach (['selling' => ['Selling', $selling], 'buying' => ['Buying', $buying]] as $key => [$heading, $deals])
            <section class="mt-10">
                <h2 class="panel-title mb-4">{{ $heading }}</h2>
                @if ($deals->isEmpty())
                    <p class="rounded-2xl border border-dashed border-line-strong px-6 py-8 text-center text-sm text-muted">
                        {{ $key === 'selling' ? 'When buyers contact you about a listed car, the conversation appears here.' : 'Find a car on the marketplace and message the seller to start a deal.' }}
                    </p>
                @else
                    <ul class="divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                        @foreach ($deals as $deal)
                            <li>
                                <a href="{{ route('deals.show', $deal) }}" class="flex items-center gap-4 p-4 hover:bg-paper">
                                    <x-car-photo :vehicle="$deal->vehicle" class="h-14 w-20 shrink-0 rounded-xl" />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-semibold">{{ $deal->vehicle->title() }}</p>
                                        <p class="text-sm text-muted">{{ $key === 'selling' ? $deal->buyer->publicName() : $deal->seller->publicName() }} · updated {{ $deal->updated_at->diffForHumans() }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="badge-{{ ['open' => 'blue', 'agreed' => 'amber', 'completed' => 'green', 'cancelled' => 'gray'][$deal->status->value] }}">{{ $deal->status->getLabel() }}</span>
                                        @if ($deal->pendingOffer)<span class="num text-xs text-muted">Offer {{ money($deal->pendingOffer->amount_cents) }}</span>@endif
                                        @if ($deal->unread_count)<span class="badge-orange">{{ $deal->unread_count }} new</span>@endif
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.site>
