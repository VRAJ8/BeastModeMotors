@php
    $links = [
        ['label' => 'Inventory', 'route' => 'vehicles.index', 'active' => 'vehicles.*'],
        ['label' => 'Brands', 'route' => 'brands.index', 'active' => 'brands.*'],
        ['label' => 'Sell / Trade-in', 'route' => 'sell', 'active' => 'sell'],
        ['label' => 'About', 'route' => 'about', 'active' => 'about'],
        ['label' => 'Contact', 'route' => 'contact', 'active' => 'contact'],
    ];
@endphp

<header x-data="{ open: false, scrolled: false }"
        x-init="scrolled = window.scrollY > 10"
        @scroll.window="scrolled = window.scrollY > 10"
        :class="scrolled || open ? 'bg-ink/95 backdrop-blur border-white/5' : 'bg-transparent border-transparent'"
        class="sticky top-0 z-50 border-b transition-colors duration-300">
    <nav class="container-x flex h-18 items-center justify-between gap-6" aria-label="Main">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ config('dealership.name') }} home">
            <img src="{{ asset('images/bmm-logo.png') }}" alt="" class="h-12 w-12 object-contain">
            <span class="hidden font-display text-2xl leading-none tracking-wider text-white sm:block">
                Beast Mode <span class="text-gold">Motors</span>
            </span>
        </a>

        <div class="hidden items-center gap-1 lg:flex">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   @class([
                       'rounded-md px-3 py-2 text-sm font-medium transition',
                       'text-gold' => request()->routeIs($link['active']),
                       'text-silver hover:text-white' => ! request()->routeIs($link['active']),
                   ])>{{ $link['label'] }}</a>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <livewire:compare-badge />

            @auth
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-md px-2 py-2 text-sm text-silver hover:text-white">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-gold font-semibold text-ink">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                            <span class="hidden md:inline">{{ str(auth()->user()->name)->before(' ') }}</span>
                            @if (($unread = auth()->user()->unreadNotifications()->count()) > 0)
                                <span class="badge bg-ember text-white">{{ $unread }}</span>
                            @endif
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('garage')">My Garage</x-dropdown-link>
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                        @if (auth()->user()->is_admin)
                            <x-dropdown-link href="/admin">Back-office</x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            @else
                <a href="{{ route('login') }}" class="btn-ghost hidden sm:inline-flex">Sign in</a>
                <a href="{{ route('vehicles.index') }}" class="btn-gold hidden px-4 py-2.5 sm:inline-flex">Shop cars</a>
            @endauth

            <button @click="open = !open" class="rounded-md p-2 text-silver hover:text-white lg:hidden" :aria-expanded="open" aria-controls="mobile-menu">
                <span class="sr-only">Toggle menu</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path x-show="!open" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    <path x-show="open" x-cloak stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
    </nav>

    <div id="mobile-menu" x-show="open" x-cloak x-transition.opacity class="border-t border-white/5 lg:hidden">
        <div class="container-x space-y-1 py-4">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" class="block rounded-md px-3 py-2 text-base {{ request()->routeIs($link['active']) ? 'text-gold' : 'text-silver' }}">{{ $link['label'] }}</a>
            @endforeach
            @guest
                <a href="{{ route('login') }}" class="block rounded-md px-3 py-2 text-base text-silver">Sign in</a>
                <a href="{{ route('register') }}" class="block rounded-md px-3 py-2 text-base text-gold">Create account</a>
            @endguest
        </div>
    </div>
</header>
