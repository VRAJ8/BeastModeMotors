<div class="grid gap-6 lg:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        @if ($listing?->status === \App\Enums\ListingStatus::Removed)
            <div class="rounded-2xl bg-danger-soft p-4 text-sm text-danger">This listing was removed by our trust & safety team: {{ $listing->removed_reason }}</div>
        @endif

        <section class="card card-pad">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="panel-title">Photos</h2>
                    <p class="text-sm text-muted">The first photo is the cover. Show the odometer and the VIN plate — buyers love that.</p>
                </div>
                <label class="btn-secondary cursor-pointer">
                    <x-heroicon-m-photo class="size-4" /> Add photos
                    <input type="file" wire:model="photos" multiple accept="image/*" class="sr-only">
                </label>
            </div>
            <div wire:loading wire:target="photos" class="mt-3 text-xs text-muted">Uploading…</div>
            @error('photos.*') <p class="error">{{ $message }}</p> @enderror
            @if ($gallery->isEmpty())
                <p class="mt-4 rounded-xl border border-dashed border-line-strong py-10 text-center text-sm text-muted">No photos yet.</p>
            @else
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($gallery as $photo)
                        <figure class="group relative aspect-[4/3] overflow-hidden rounded-xl bg-paper-deep" wire:key="photo-{{ $photo->id }}">
                            <img src="{{ $photo->url() }}" alt="" class="size-full object-cover">
                            @if ($loop->first)
                                <span class="badge-orange absolute top-2 left-2">Cover</span>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 flex justify-end gap-1 bg-gradient-to-t from-ink/70 p-2 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
                                @unless ($loop->first)
                                    <button wire:click="makeCover({{ $photo->id }})" class="rounded-md bg-white/90 px-2 py-1 text-[11px] font-semibold">Make cover</button>
                                @endunless
                                <button wire:click="deletePhoto({{ $photo->id }})" class="rounded-md bg-white/90 p-1 text-danger" title="Delete"><x-heroicon-m-trash class="size-4" /></button>
                            </div>
                        </figure>
                    @endforeach
                </div>
            @endif
        </section>

        <form wire:submit="save" class="card card-pad">
            <h2 class="panel-title">The listing</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-2">
                    <label class="label" for="price">Asking price</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 grid place-items-center text-sm text-muted">$</span>
                        <input id="price" inputmode="decimal" wire:model="price" class="input num pl-7">
                    </div>
                    @error('price') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="city">City</label>
                    <input id="city" wire:model="city" class="input">
                    @error('city') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="state">State</label>
                    <select id="state" wire:model="state" class="input">
                        <option value="">—</option>
                        @foreach ($states as $code => $name)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                    @error('state') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="zip">ZIP</label>
                    <input id="zip" wire:model="zip" inputmode="numeric" maxlength="5" class="input num">
                    @error('zip') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-6">
                    <label class="label" for="description">Description</label>
                    <textarea id="description" wire:model="description" rows="7" class="input" placeholder="Why you're selling, how it's been used, known issues, what's included. The passport covers the history — use this for the story."></textarea>
                    @error('description') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
            @error('publish')
                <ul class="mt-4 list-inside list-disc rounded-xl bg-danger-soft p-3 text-sm text-danger">
                    @foreach ($errors->get('publish') as $message) <li>{{ is_array($message) ? implode(' ', $message) : $message }}</li> @endforeach
                </ul>
            @enderror
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button class="btn-secondary">Save {{ $listing && $listing->status !== \App\Enums\ListingStatus::Draft ? 'changes' : 'draft' }}</button>
                @if (! $listing || $listing->status === \App\Enums\ListingStatus::Draft)
                    <button type="button" wire:click="publish" class="btn-accent" @disabled($blockers)>Publish listing</button>
                @endif
            </div>
        </form>
    </div>

    <aside class="space-y-6">
        <section class="card card-pad">
            <p class="eyebrow">Status</p>
            @if (! $listing)
                <p class="display mt-1 text-xl">Not listed</p>
            @else
                <p class="mt-2"><span class="badge-{{ ['draft' => 'gray', 'active' => 'green', 'pending' => 'amber', 'removed' => 'red'][$listing->status->value] ?? 'gray' }} text-xs">{{ $listing->status->getLabel() }}</span></p>
                @if ($listing->isPublic())
                    <p class="mt-3 text-sm text-muted"><span class="num">{{ $listing->views }}</span> views since {{ $listing->published_at->format('M j') }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('listings.show', $listing) }}" target="_blank" class="btn-secondary btn-sm">View listing</a>
                        @if ($listing->status === \App\Enums\ListingStatus::Active)
                            <button wire:click="withdraw" wire:confirm="Take the car off the market? Open conversations stay open." class="btn-ghost btn-sm">Withdraw</button>
                        @endif
                    </div>
                @endif
            @endif

            @if (! $listing || $listing->status === \App\Enums\ListingStatus::Draft)
                <div class="divider my-5"></div>
                <p class="text-sm font-semibold">Before you can publish</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($blockers as $blocker)
                        <li class="flex gap-2 text-ink-soft"><x-heroicon-o-x-circle class="size-5 shrink-0 text-muted" /> {{ $blocker }}</li>
                    @empty
                        <li class="flex gap-2 text-verified"><x-heroicon-s-check-circle class="size-5" /> Ready to publish</li>
                    @endforelse
                </ul>
            @endif
        </section>

        @if ($listing && $listing->deals->isNotEmpty())
            <section class="card card-pad">
                <h3 class="panel-title">Interested buyers</h3>
                <ul class="mt-3 divide-y divide-line">
                    @foreach ($listing->deals as $deal)
                        <li class="py-3">
                            <a href="{{ route('deals.show', $deal) }}" class="flex items-center justify-between gap-3 text-sm hover:underline">
                                <span class="font-medium">{{ $deal->buyer->publicName() }}</span>
                                <span class="badge-{{ ['open' => 'blue', 'agreed' => 'amber', 'completed' => 'green', 'cancelled' => 'gray'][$deal->status->value] }}">{{ $deal->pendingOffer ? 'Offer '.money($deal->pendingOffer->amount_cents) : $deal->status->getLabel() }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="card card-pad">
            <p class="eyebrow">How selling works</p>
            <ol class="mt-3 space-y-3 text-sm text-ink-soft">
                <li><strong class="text-ink">1.</strong> Your listing shows the full passport and Passport Score.</li>
                <li><strong class="text-ink">2.</strong> Buyers message and make offers in a deal room. Scam patterns get flagged automatically.</li>
                <li><strong class="text-ink">3.</strong> Agree a price, let them inspect, tick off the handover checklist together.</li>
                <li><strong class="text-ink">4.</strong> When you both confirm, the passport moves to their garage. Your costs and personal documents stay with you.</li>
            </ol>
        </section>

        @if ($pastListings->isNotEmpty())
            <p class="text-xs text-muted">Previous listings: {{ $pastListings->map(fn ($l) => $l->status->getLabel().' '.$l->updated_at->format('M Y'))->implode(', ') }}</p>
        @endif
    </aside>
</div>
