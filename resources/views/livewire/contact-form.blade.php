<div class="card p-6 sm:p-8">
    @if ($sent)
        <div class="py-10 text-center">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-gold/15 text-2xl text-gold">✓</div>
            <h3 class="mt-4 font-display text-4xl tracking-wide text-white">Message sent</h3>
            <p class="mt-2 text-mist">Thanks, {{ str($name)->before(' ') }}. We usually reply within a few hours.</p>
        </div>
    @else
        <form wire:submit="submit" class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="c-topic" class="label">How can we help?</label>
                <select id="c-topic" wire:model="topic" class="input">
                    @foreach (\App\Livewire\ContactForm::TOPICS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="c-name" class="label">Full name</label>
                <input id="c-name" type="text" wire:model="name" class="input" autocomplete="name">
                @error('name') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="c-phone" class="label">Phone <span class="normal-case">(optional)</span></label>
                <input id="c-phone" type="tel" wire:model="phone" class="input" autocomplete="tel">
            </div>
            <div class="sm:col-span-2">
                <label for="c-email" class="label">Email</label>
                <input id="c-email" type="email" wire:model="email" class="input" autocomplete="email">
                @error('email') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="c-message" class="label">Message</label>
                <textarea id="c-message" wire:model="message" rows="5" class="input"></textarea>
                @error('message') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="hidden" aria-hidden="true"><input type="text" wire:model="website" tabindex="-1" autocomplete="off"></div>
            @error('form') <p class="text-sm text-red-400 sm:col-span-2">{{ $message }}</p> @enderror
            <div class="sm:col-span-2">
                <button type="submit" class="btn-gold w-full sm:w-auto">Send message</button>
            </div>
        </form>
    @endif
</div>
