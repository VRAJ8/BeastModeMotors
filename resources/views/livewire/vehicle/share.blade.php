<div class="grid gap-6 lg:grid-cols-[1fr_340px]">
    <div>
        <h2 class="panel-title">Share links</h2>
        <p class="text-sm text-muted">Give a buyer, insurer or mechanic a read-only view of the passport. Each link has its own privacy settings and can be revoked any time.</p>

        @if ($active->isEmpty())
            <x-empty class="mt-6" icon="heroicon-o-link" title="No active links" text="Create one to send the passport to someone — they don't need an account." />
        @else
            <ul class="mt-6 space-y-3">
                @foreach ($active as $link)
                    <li class="card p-4" wire:key="link-{{ $link->id }}" x-data="{ copied: false }">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $link->label }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    <span class="num">{{ $link->views }}</span> {{ str('view')->plural($link->views) }}{{ $link->last_viewed_at ? ', last '.$link->last_viewed_at->diffForHumans() : '' }} ·
                                    {{ $link->expires_at ? 'expires '.$link->expires_at->format('M j') : 'no expiry' }}
                                </p>
                                <p class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="{{ $link->show_full_vin ? 'badge-amber' : 'badge-gray' }}">{{ $link->show_full_vin ? 'Full VIN' : 'VIN masked' }}</span>
                                    <span class="{{ $link->show_costs ? 'badge-amber' : 'badge-gray' }}">{{ $link->show_costs ? 'Costs shown' : 'Costs hidden' }}</span>
                                    <span class="{{ $link->show_documents ? 'badge-blue' : 'badge-gray' }}">{{ $link->show_documents ? 'Receipts viewable' : 'Receipts hidden' }}</span>
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <button type="button" class="btn-secondary btn-sm" x-on:click="navigator.clipboard.writeText(@js($link->url())); copied = true; setTimeout(() => copied = false, 2000)">
                                    <x-heroicon-m-clipboard class="size-4" /> <span x-text="copied ? 'Copied' : 'Copy link'"></span>
                                </button>
                                <button wire:click="$set('qrFor', {{ $link->id }})" class="btn-ghost btn-sm">QR</button>
                                <a href="{{ $link->url() }}" target="_blank" class="btn-ghost btn-sm">Open</a>
                                <button wire:click="revoke({{ $link->id }})" wire:confirm="Revoke this link? Anyone holding it loses access." class="btn-ghost btn-sm text-danger hover:bg-danger-soft hover:text-danger">Revoke</button>
                            </div>
                        </div>
                        <p class="vin mt-3 truncate rounded-lg bg-paper px-3 py-2 text-xs text-ink-soft">{{ $link->url() }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($inactive->isNotEmpty())
            <h3 class="eyebrow mt-8 mb-2">Revoked or expired</h3>
            <ul class="space-y-1 text-sm text-muted">
                @foreach ($inactive as $link)
                    <li>{{ $link->label }} · {{ $link->views }} views · {{ $link->revoked_at ? 'revoked '.$link->revoked_at->format('M j') : 'expired '.$link->expires_at->format('M j') }}</li>
                @endforeach
            </ul>
        @endif

        <section class="card card-pad mt-8 grid gap-4 sm:grid-cols-2">
            <div>
                <p class="font-semibold">Passport report (PDF)</p>
                <p class="mt-1 text-sm text-muted">The full history as a printable document — handy for insurers and appraisers.</p>
                @if ($first = $active->first())
                    <a href="{{ route('passport.pdf', $first) }}" class="btn-secondary btn-sm mt-3"><x-heroicon-m-arrow-down-tray class="size-4" /> Download using “{{ str($first->label)->limit(18) }}”</a>
                @else
                    <p class="mt-3 text-xs text-muted">Create a link first; the PDF uses its privacy settings.</p>
                @endif
            </div>
            <div>
                <p class="font-semibold">“For sale” window sign</p>
                <p class="mt-1 text-sm text-muted">Print it, tape it inside the window. Passers-by scan the QR code to see the verified history.</p>
                <a href="{{ route('vehicles.sign', $vehicle) }}" target="_blank" class="btn-secondary btn-sm mt-3"><x-heroicon-m-printer class="size-4" /> Print sign</a>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        @if ($qr)
            <div class="card card-pad text-center">
                <div class="mx-auto w-fit rounded-xl border border-line bg-white p-3 [&_svg]:size-44">{!! $qr !!}</div>
                <p class="mt-3 text-sm text-muted">Scan to open the passport.</p>
                <button wire:click="$set('qrFor', null)" class="btn-ghost btn-sm mt-1">Hide</button>
            </div>
        @endif

        <form wire:submit="create" class="card card-pad">
            <h3 class="panel-title">New link</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="label" for="label">Who is it for?</label>
                    <input id="label" wire:model="label" class="input" placeholder="e.g. Buyer — Sam">
                    @error('label') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="expires">Expires</label>
                    <select id="expires" wire:model="expires" class="input">
                        <option value="7">In 7 days</option>
                        <option value="30">In 30 days</option>
                        <option value="90">In 90 days</option>
                        <option value="never">Never</option>
                    </select>
                </div>
                <fieldset class="space-y-3">
                    <legend class="label">They can see</legend>
                    <label class="flex gap-3 text-sm"><input type="checkbox" wire:model="show_documents" class="checkbox mt-0.5"> <span>Receipts attached to records<span class="block text-xs text-muted">Title, registration and insurance are never shared.</span></span></label>
                    <label class="flex gap-3 text-sm"><input type="checkbox" wire:model="show_full_vin" class="checkbox mt-0.5"> <span>Full VIN<span class="block text-xs text-muted">Buyers need it to run their own checks.</span></span></label>
                    <label class="flex gap-3 text-sm"><input type="checkbox" wire:model="show_costs" class="checkbox mt-0.5"> <span>What each service cost</span></label>
                </fieldset>
            </div>
            <button class="btn-primary mt-6 w-full">Create link</button>
        </form>
    </aside>
</div>
