@php
    $user = auth()->user();
    $unread = $user?->unreadNotifications()->count() ?? 0;
    $links = $user
        ? [['Garage', route('garage'), request()->routeIs('garage', 'vehicles.*', 'records.*')], ['Deals', route('deals.index'), request()->routeIs('deals.*')], ['Marketplace', route('marketplace'), request()->routeIs('marketplace', 'listings.*')], ['VIN check', route('vin-check'), request()->routeIs('vin-check')]]
        : [['Marketplace', route('marketplace'), request()->routeIs('marketplace', 'listings.*')], ['VIN check', route('vin-check'), request()->routeIs('vin-check')], ['How it works', route('how-it-works'), request()->routeIs('how-it-works')], ['Selling safely', route('safety'), request()->routeIs('safety')]];
@endphp

<header x-data="{ open: false }" class="no-print sticky top-0 z-40 border-b border-line bg-paper/85 backdrop-blur-md">
    <nav class="container-x flex h-16 items-center justify-between gap-6" aria-label="Main">
        <a href="{{ $user ? route('garage') : route('home') }}" class="shrink-0" aria-label="{{ config('passport.name') }} home">
            <x-logo />
        </a>

        <div class="hidden flex-1 items-center gap-1 md:flex">
            @foreach ($links as [$label, $url, $active])
                <a href="{{ $url }}" @class(['rounded-lg px-3 py-2 text-sm font-medium transition', 'bg-surface text-ink shadow-xs ring-1 ring-line' => $active, 'text-ink-soft hover:text-ink' => ! $active])>{{ $label }}</a>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('notifications') }}" class="relative rounded-lg p-2 text-ink-soft hover:bg-paper-deep hover:text-ink" title="Notifications">
                    <x-heroicon-o-bell class="size-5" />
                    @if ($unread)
                        <span class="absolute top-1 right-1 grid min-w-4 place-items-center rounded-full bg-accent px-1 text-[10px] leading-4 font-bold text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                    <span class="sr-only">{{ $unread }} unread notifications</span>
                </a>

                <div x-data="{ menu: false }" class="relative hidden md:block" @click.outside="menu = false">
                    <button type="button" @click="menu = !menu" class="flex items-center gap-2 rounded-full border border-line bg-surface py-1 pr-3 pl-1 text-sm font-medium hover:border-line-strong" :aria-expanded="menu">
                        <span class="grid size-7 place-items-center rounded-full bg-ink text-[11px] font-bold text-white">{{ $user->initials() }}</span>
                        {{ str($user->name)->before(' ') }}
                        <x-heroicon-m-chevron-down class="size-4 text-muted" />
                    </button>
                    <div x-cloak x-show="menu" x-transition.origin.top.right class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-line bg-surface py-1 shadow-xl shadow-ink/5">
                        <a href="{{ route('saved') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-paper"><x-heroicon-o-heart class="size-4 text-muted" /> Saved cars</a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-paper"><x-heroicon-o-user-circle class="size-4 text-muted" /> Account</a>
                        @if ($user->is_admin)
                            <a href="{{ url('/admin') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-paper"><x-heroicon-o-shield-check class="size-4 text-muted" /> Trust & safety</a>
                        @endif
                        <div class="my-1 border-t border-line"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm hover:bg-paper"><x-heroicon-o-arrow-right-start-on-rectangle class="size-4 text-muted" /> Sign out</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn-ghost hidden sm:inline-flex">Sign in</a>
                <a href="{{ route('register') }}" class="btn-primary hidden sm:inline-flex">Start a passport</a>
            @endauth

            <button type="button" class="rounded-lg p-2 text-ink md:hidden" @click="open = !open" :aria-expanded="open" aria-label="Menu">
                <x-heroicon-o-bars-3 x-show="!open" class="size-6" />
                <x-heroicon-o-x-mark x-cloak x-show="open" class="size-6" />
            </button>
        </div>
    </nav>

    <div x-cloak x-show="open" x-transition class="border-t border-line bg-paper md:hidden">
        <div class="container-x space-y-1 py-3">
            @foreach ($links as [$label, $url, $active])
                <a href="{{ $url }}" @class(['block rounded-lg px-3 py-2.5 text-base font-medium', 'bg-surface ring-1 ring-line' => $active])>{{ $label }}</a>
            @endforeach
            <div class="divider my-2"></div>
            @auth
                <a href="{{ route('saved') }}" class="block rounded-lg px-3 py-2.5">Saved cars</a>
                <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2.5">Account</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="block w-full rounded-lg px-3 py-2.5 text-left">Sign out</button>
                </form>
            @else
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <a href="{{ route('login') }}" class="btn-secondary">Sign in</a>
                    <a href="{{ route('register') }}" class="btn-primary">Start free</a>
                </div>
            @endauth
        </div>
    </div>
</header>
