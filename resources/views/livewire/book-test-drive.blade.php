<div>
    @if ($reference)
        <div class="py-6 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-gold/15 text-2xl text-gold">✓</div>
            <h3 class="mt-4 font-display text-3xl tracking-wide text-white">You're on the list</h3>
            <p class="mt-2 text-mist">We'll confirm your slot by email shortly.</p>
            <p class="mt-4 inline-block rounded-md border border-gold/30 px-4 py-2 font-mono text-gold">{{ $reference }}</p>
            @auth
                <p class="mt-4"><a href="{{ route('garage') }}" class="link-gold">Manage it in My Garage →</a></p>
            @endauth
        </div>
    @elseif (! $vehicle->isAvailable())
        <p class="text-mist">This car is {{ strtolower($vehicle->status->getLabel()) }}, so test drives are closed. <a href="{{ route('vehicles.index') }}" class="link-gold">Browse similar cars</a>.</p>
    @else
        <form wire:submit="book" class="space-y-5">
            <div>
                <p class="label">Choose a day</p>
                <div class="-mx-1 flex snap-x gap-2 overflow-x-auto px-1 pb-2">
                    @foreach ($this->dates as $day)
                        <label wire:key="day-{{ $day->toDateString() }}" class="shrink-0 snap-start cursor-pointer">
                            <input type="radio" wire:model.live="date" value="{{ $day->toDateString() }}" class="peer sr-only">
                            <span class="flex w-16 flex-col items-center rounded-lg border border-steel py-2 text-center transition peer-checked:border-gold peer-checked:bg-gold peer-checked:text-ink peer-focus-visible:ring-2 peer-focus-visible:ring-gold">
                                <span class="text-[10px] font-semibold tracking-widest uppercase opacity-80">{{ $day->format('D') }}</span>
                                <span class="font-display text-2xl leading-none">{{ $day->format('j') }}</span>
                                <span class="text-[10px] uppercase opacity-80">{{ $day->format('M') }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('date') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <p class="label">Available times</p>
                <div wire:loading.class="opacity-50" wire:target="date" class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                    @forelse ($this->timeSlots as $slot)
                        <label wire:key="slot-{{ $date }}-{{ $slot->format('Hi') }}" class="cursor-pointer">
                            <input type="radio" wire:model="time" value="{{ $slot->format('H:i') }}" class="peer sr-only">
                            <span class="block rounded-md border border-steel py-2 text-center text-sm transition peer-checked:border-gold peer-checked:text-gold peer-focus-visible:ring-2 peer-focus-visible:ring-gold hover:border-mist">{{ $slot->format('g:i A') }}</span>
                        </label>
                    @empty
                        <p class="col-span-full text-sm text-mist">No slots left on this day — try another.</p>
                    @endforelse
                </div>
                @error('time') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="td-name" class="label">Full name</label>
                    <input id="td-name" type="text" wire:model="name" class="input" autocomplete="name">
                    @error('name') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="td-phone" class="label">Phone <span class="normal-case">(optional)</span></label>
                    <input id="td-phone" type="tel" wire:model="phone" class="input" autocomplete="tel">
                    @error('phone') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="td-email" class="label">Email</label>
                <input id="td-email" type="email" wire:model="email" class="input" autocomplete="email">
                @error('email') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="td-notes" class="label">Anything we should know? <span class="normal-case">(optional)</span></label>
                <textarea id="td-notes" wire:model="notes" rows="2" class="input" placeholder="Trade-in, financing questions, preferred route…"></textarea>
            </div>

            <div class="hidden" aria-hidden="true">
                <label for="td-website">Website</label>
                <input id="td-website" type="text" wire:model="website" tabindex="-1" autocomplete="off">
            </div>

            @error('form') <p class="text-sm text-red-400">{{ $message }}</p> @enderror

            <button type="submit" class="btn-gold w-full" wire:loading.attr="disabled" wire:target="book">
                <span wire:loading.remove wire:target="book">Request test drive</span>
                <span wire:loading wire:target="book">Booking…</span>
            </button>
            <p class="text-center text-xs text-mist">Free, no obligation. Bring a valid driving licence.</p>
        </form>
    @endif
</div>
