<x-layouts.storefront :title="$title ?? null">
    @isset($header)
        <section class="border-b border-white/5 bg-carbon">
            <div class="container-x py-10">
                {{ $header }}
            </div>
        </section>
    @endisset

    <div class="container-x py-10">
        {{ $slot }}
    </div>
</x-layouts.storefront>
