<x-layouts.site title="Your shop profile" robots="noindex">
    <div class="container-x max-w-2xl py-12">
        <p class="eyebrow">Shop profile</p>
        <h1 class="display mt-2 text-3xl">{{ $shop->name }}</h1>
        <p class="mt-2 text-ink-soft">This is what car owners see when they pick your shop or read a record you confirmed. This link works for 7 days and was sent only to <strong>{{ $shop->email }}</strong>.</p>

        <form method="POST" action="{{ $action }}" class="card card-pad mt-8 space-y-4">
            @csrf
            <div>
                <label class="label" for="name">Shop name</label>
                <input id="name" name="name" value="{{ old('name', $shop->name) }}" class="input" required>
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="label" for="city">City</label>
                    <input id="city" name="city" value="{{ old('city', $shop->city) }}" class="input">
                </div>
                <div>
                    <label class="label" for="state">State</label>
                    <select id="state" name="state" class="input">
                        <option value="">—</option>
                        @foreach ($states as $code => $label)
                            <option value="{{ $code }}" @selected(old('state', $shop->state) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone', $shop->phone) }}" class="input">
                </div>
                <div>
                    <label class="label" for="website">Website</label>
                    <input id="website" name="website" type="url" value="{{ old('website', $shop->website) }}" class="input" placeholder="https://">
                    @error('website') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="label" for="specialties">Specialties <span class="font-normal text-muted">(comma separated)</span></label>
                <input id="specialties" name="specialties" value="{{ old('specialties', implode(', ', $shop->specialties ?? [])) }}" class="input" placeholder="e.g. Porsche, BMW, Diagnostics">
            </div>
            <div>
                <label class="label" for="about">About the shop</label>
                <textarea id="about" name="about" rows="4" class="input">{{ old('about', $shop->about) }}</textarea>
            </div>
            <div class="flex items-center justify-between gap-4 pt-2">
                <a href="{{ route('shops.show', $shop) }}" class="link text-sm" target="_blank">View public profile</a>
                <button class="btn-primary">Save profile</button>
            </div>
        </form>
    </div>
</x-layouts.site>
