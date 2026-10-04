<x-layouts.site :title="$title ?? null" robots="noindex">
    @isset($header)
        <section class="border-b border-line">
            <div class="container-x py-8">
                {{ $header }}
            </div>
        </section>
    @endisset

    <div class="container-x py-8">
        {{ $slot }}
    </div>
</x-layouts.site>
