<x-layouts.storefront :title="$title ?? 'Account'">
    <section class="relative flex min-h-[calc(100vh-4.5rem)] items-center justify-center overflow-hidden px-4 py-16">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(212,168,87,0.12),transparent_65%)]"></div>
        <div class="card relative w-full max-w-md p-8 shadow-2xl shadow-black/60">
            <a href="{{ route('home') }}" class="mx-auto mb-6 block w-fit">
                <x-application-logo class="h-20 w-20" />
            </a>
            {{ $slot }}
        </div>
    </section>
</x-layouts.storefront>
