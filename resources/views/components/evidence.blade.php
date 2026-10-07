@props(['record'])

@switch($record->evidence())
    @case('verified')
        <span {{ $attributes->class('stamp border-verified text-verified') }} title="Confirmed by {{ $record->shop?->name ?? $record->provider_name ?? 'the shop' }} on {{ $record->verified_at->format('M j, Y') }}">
            <x-heroicon-s-check-badge class="size-3.5" /> Shop verified
        </span>
        @break
    @case('documented')
        <span {{ $attributes->class('badge-blue') }} title="A receipt or invoice is attached"><x-heroicon-m-paper-clip class="size-3" /> Receipt</span>
        @break
    @case('disputed')
        <span {{ $attributes->class('stamp border-danger text-danger') }} title="The shop said this doesn't match their records"><x-heroicon-s-exclamation-triangle class="size-3.5" /> Disputed</span>
        @break
    @default
        <span {{ $attributes->class('badge-gray') }} title="Entered by the owner without a receipt">Self-reported</span>
@endswitch
