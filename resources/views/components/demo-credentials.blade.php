@if (config('passport.demo'))
    <div {{ $attributes->class('rounded-xl border border-accent/30 bg-accent-soft p-4 text-sm') }}>
        <p class="eyebrow mb-2 !text-accent-dark">Demo accounts · password <code class="font-mono">password</code></p>
        <ul class="space-y-1 text-ink-soft">
            <li><code class="font-mono text-ink">owner@beastmodemotors.test</code> — owner with cars & a live sale</li>
            <li><code class="font-mono text-ink">buyer@beastmodemotors.test</code> — buyer mid-deal</li>
            <li><code class="font-mono text-ink">admin@beastmodemotors.test</code> — trust & safety (<a href="/admin" class="link">/admin</a>)</li>
        </ul>
    </div>
@endif
