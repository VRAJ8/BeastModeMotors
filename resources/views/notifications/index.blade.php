<x-layouts.site title="Notifications" robots="noindex">
    <div class="container-x max-w-3xl py-10">
        <x-page-header eyebrow="Inbox" title="Notifications">
            <x-slot:actions>
                @if (auth()->user()->unreadNotifications()->exists())
                    <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="btn-secondary">Mark all read</button></form>
                @endif
            </x-slot:actions>
        </x-page-header>

        @if ($notifications->isEmpty())
            <x-empty class="mt-8" icon="heroicon-o-bell" title="Nothing yet" text="Maintenance reminders, recalls, shop verifications and deal updates will appear here." />
        @else
            <ul class="mt-8 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                @foreach ($notifications as $n)
                    <li>
                        <a href="{{ route('notifications.open', $n->id) }}" @class(['flex gap-4 p-4 hover:bg-paper', 'bg-accent-soft/40' => ! $n->read_at])>
                            <span @class(['mt-1.5 size-2.5 shrink-0 rounded-full', 'bg-verified' => ($n->data['tone'] ?? '') === 'success', 'bg-danger' => ($n->data['tone'] ?? '') === 'danger', 'bg-warn' => ($n->data['tone'] ?? '') === 'warning', 'bg-documented' => ! in_array($n->data['tone'] ?? '', ['success', 'danger', 'warning'])])></span>
                            <span class="min-w-0 flex-1">
                                <span @class(['block text-sm', 'font-semibold' => ! $n->read_at])>{{ $n->data['title'] ?? 'Update' }}</span>
                                @if (! empty($n->data['body']))<span class="mt-0.5 block truncate text-sm text-muted">{{ $n->data['body'] }}</span>@endif
                                <span class="mt-1 block text-xs text-muted">{{ $n->created_at->diffForHumans() }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-layouts.site>
