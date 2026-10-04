@props(['vehicle', 'fullVin' => false])

@php
    $facts = array_filter([
        'VIN' => $fullVin ? $vehicle->vin : $vehicle->maskedVin(),
        'Engine' => $vehicle->engine,
        'Drivetrain' => $vehicle->drivetrain,
        'Transmission' => $vehicle->transmission,
        'Fuel' => $vehicle->fuel_type->getLabel(),
        'Body' => $vehicle->body,
        'Colour' => $vehicle->exterior_color,
        'Odometer' => miles($vehicle->current_mileage),
    ]);
@endphp

<dl {{ $attributes->class('grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4') }}>
    @foreach ($facts as $label => $value)
        <div @class(['col-span-2' => $label === 'VIN'])>
            <dt class="eyebrow">{{ $label }}</dt>
            <dd @class(['mt-1 text-sm font-medium', 'vin' => $label === 'VIN', 'num' => $label === 'Odometer'])>
                {{ $value }}
                @if ($label === 'VIN' && $vehicle->vin_valid)
                    <x-heroicon-s-check-circle class="inline size-4 align-[-3px] text-verified" title="Check digit verified" />
                @endif
            </dd>
        </div>
    @endforeach
</dl>
