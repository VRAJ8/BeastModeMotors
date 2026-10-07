<div class="grid gap-6 lg:grid-cols-[1fr_340px]">
    <div class="space-y-8">
        @foreach (['car' => ['Travels with the car', 'Receipts, inspection reports, warranties and manuals stay with the car when you sell, so the next owner inherits the proof.'], 'personal' => ['Stays with you', 'Title, registration and insurance are private. They\'re never shown on a shared passport and are removed from the car when ownership transfers.']] as $group => [$heading, $explain])
            <section>
                <h2 class="panel-title">{{ $heading }}</h2>
                <p class="text-sm text-muted">{{ $explain }}</p>
                @if (($groups[$group] ?? collect())->isEmpty())
                    <p class="mt-4 rounded-xl border border-dashed border-line-strong px-4 py-6 text-center text-sm text-muted">Nothing here yet.</p>
                @else
                    <ul class="mt-4 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                        @foreach ($groups[$group] as $doc)
                            <li class="flex flex-wrap items-center gap-3 p-3 sm:px-4" wire:key="doc-{{ $doc->id }}">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-paper text-ink-soft">
                                    @if ($doc->isImage()) <x-heroicon-o-photo class="size-5" /> @else <x-heroicon-o-document-text class="size-5" /> @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('documents.show', [$vehicle, $doc]) }}" target="_blank" class="block truncate text-sm font-medium hover:underline">{{ $doc->name }}</a>
                                    <p class="text-xs text-muted">
                                        {{ $doc->type->getLabel() }} · {{ $doc->humanSize() }} · {{ $doc->created_at->format('M j, Y') }}
                                        @if ($doc->record) · <a href="{{ route('records.edit', [$vehicle, $doc->record]) }}" class="hover:underline">{{ str($doc->record->title)->limit(30) }}</a>@endif
                                    </p>
                                </div>
                                @switch($doc->expiryState())
                                    @case('expired') <span class="badge-red">Expired {{ $doc->expires_on->format('M j, Y') }}</span> @break
                                    @case('soon') <span class="badge-amber">Expires {{ $doc->expires_on->format('M j') }}</span> @break
                                    @case('ok') <span class="badge-gray">Until {{ $doc->expires_on->format('M Y') }}</span> @break
                                @endswitch
                                @if ($doc->ownership_id === $currentOwnershipId)
                                    <button wire:click="delete({{ $doc->id }})" wire:confirm="Delete “{{ $doc->name }}”?" class="rounded-lg p-2 text-muted hover:bg-danger-soft hover:text-danger" title="Delete"><x-heroicon-m-trash class="size-4" /></button>
                                @else
                                    <span class="badge-gray" title="Uploaded by a previous owner — part of the car's history">Previous owner</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>

    <aside>
        <form wire:submit="upload" class="card card-pad sticky top-24">
            <h3 class="panel-title">Upload a document</h3>
            <div class="mt-4 space-y-4">
                <label class="flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-dashed border-line-strong bg-paper px-4 py-6 text-center text-sm hover:border-ink">
                    <x-heroicon-o-document-arrow-up class="size-6 text-muted" />
                    <span class="font-medium">{{ $file ? $file->getClientOriginalName() : 'Choose a file' }}</span>
                    <span class="text-xs text-muted">PDF or photo · up to 10 MB</span>
                    <input type="file" wire:model="file" accept=".pdf,image/*" class="sr-only">
                </label>
                <div wire:loading wire:target="file" class="text-xs text-muted">Uploading…</div>
                @error('file') <p class="error">{{ $message }}</p> @enderror
                <div>
                    <label class="label" for="doc-type">Type</label>
                    <select id="doc-type" wire:model.live="type" class="input">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="doc-name">Name</label>
                    <input id="doc-name" wire:model="name" class="input" placeholder="e.g. Florida title">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>
                @if ($expiring)
                    <div>
                        <label class="label" for="expires_on">Expires on <span class="font-normal text-muted">(we'll remind you)</span></label>
                        <input id="expires_on" type="date" wire:model="expires_on" class="input">
                    </div>
                @endif
            </div>
            <button class="btn-primary mt-6 w-full" wire:loading.attr="disabled" wire:target="file,upload">Store securely</button>
            <p class="mt-3 flex items-center gap-1.5 text-xs text-muted"><x-heroicon-m-lock-closed class="size-3.5" /> Stored privately. Only you can open them.</p>
        </form>
    </aside>
</div>
