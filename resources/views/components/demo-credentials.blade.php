@if (config('dealership.demo'))
    <div {{ $attributes->class('rounded-lg border border-gold/30 bg-gold/5 p-4 text-sm') }}>
        <p class="eyebrow mb-2">Demo accounts</p>
        <p class="text-silver">Customer: <code class="text-white">customer@beastmodemotors.test</code></p>
        <p class="text-silver">Staff (<a href="/admin" class="link-gold">/admin</a>): <code class="text-white">admin@beastmodemotors.test</code></p>
        <p class="text-silver">Password for both: <code class="text-white">password</code></p>
    </div>
@endif
