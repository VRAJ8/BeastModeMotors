<x-layouts.site :title="$title ?? 'Account'">
    <section class="grain relative flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-16">
        <div class="card w-full max-w-md p-8 shadow-xl shadow-ink/5">
            {{ $slot }}
        </div>
    </section>
</x-layouts.site>
