<section>
    <h2 class="panel-title">Saved searches</h2>
    @if ($searches->isEmpty())
        <p class="mt-2 text-sm text-muted">Filter the <a href="{{ route('marketplace') }}" class="underline">marketplace</a> and tap <em>Save this search</em> to get a daily email when a new car matches.</p>
    @else
        <ul class="mt-4 space-y-2">
            @foreach ($searches as ['search' => $search, 'criteria' => $criteria, 'matches' => $matches])
                <li class="card flex flex-wrap items-center justify-between gap-3 p-4" wire:key="search-{{ $search->id }}">
                    <div class="min-w-0">
                        <a href="{{ $criteria->url() }}" class="font-medium hover:underline">{{ $criteria->describe() }}</a>
                        <p class="text-xs text-muted"><span class="num">{{ $matches }}</span> {{ str('car')->plural($matches) }} for sale now · saved {{ $search->created_at->format('M j') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="flex cursor-pointer items-center gap-1.5 text-sm">
                            <input type="checkbox" @checked($search->email_alerts) wire:click="toggleAlerts({{ $search->id }})">
                            Email new matches
                        </label>
                        <button wire:click="delete({{ $search->id }})" wire:confirm="Delete this saved search?" class="btn-ghost btn-sm text-danger hover:bg-danger-soft hover:text-danger">Delete</button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
