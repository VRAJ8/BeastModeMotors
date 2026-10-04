@props(['label', 'value'])

<div class="rounded-lg border border-white/5 bg-graphite/60 p-4">
    <dt class="text-[11px] font-semibold tracking-widest text-mist uppercase">{{ $label }}</dt>
    <dd class="mt-1 font-medium text-white">{{ $value ?: '—' }}</dd>
</div>
