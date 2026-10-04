@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl bg-verified-soft p-3 text-sm font-medium text-verified']) }}>
        {{ $status }}
    </div>
@endif
