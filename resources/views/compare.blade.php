@php
    $rows = [
        ['label' => 'Price', 'key' => 'price', 'format' => fn ($v) => money($v->price), 'best' => 'price'],
        ['label' => 'Est. monthly', 'key' => null, 'format' => fn ($v) => money(\App\Support\Finance::monthlyPayment($v->price)).'/mo'],
        ['label' => 'Year', 'key' => 'year', 'format' => fn ($v) => $v->year, 'best' => 'year'],
        ['label' => 'Mileage', 'key' => 'mileage', 'format' => fn ($v) => number_format($v->mileage).' mi', 'best' => 'mileage'],
        ['label' => 'Condition', 'format' => fn ($v) => $v->condition->getLabel()],
        ['label' => 'Body', 'format' => fn ($v) => $v->body_type->getLabel()],
        ['label' => 'Engine', 'format' => fn ($v) => $v->engine],
        ['label' => 'Horsepower', 'key' => 'horsepower', 'format' => fn ($v) => $v->horsepower ? $v->horsepower.' hp' : '—', 'best' => 'horsepower'],
        ['label' => 'Torque', 'key' => 'torque', 'format' => fn ($v) => $v->torque ? $v->torque.' lb-ft' : '—', 'best' => 'torque'],
        ['label' => '0–60 mph', 'key' => 'zero_to_sixty', 'format' => fn ($v) => $v->zero_to_sixty ? $v->zero_to_sixty.' s' : '—', 'best' => 'zero_to_sixty'],
        ['label' => 'Top speed', 'key' => 'top_speed', 'format' => fn ($v) => $v->top_speed ? $v->top_speed.' mph' : '—', 'best' => 'top_speed'],
        ['label' => 'Powertrain', 'format' => fn ($v) => $v->fuel_type->getLabel()],
        ['label' => 'Transmission', 'format' => fn ($v) => $v->transmission->getLabel()],
        ['label' => 'Drivetrain', 'format' => fn ($v) => $v->drivetrain->getLabel()],
        ['label' => 'Exterior', 'format' => fn ($v) => $v->exterior_color],
    ];
@endphp

<x-layouts.storefront title="Compare">
    <x-site.page-header eyebrow="Head to head" title="Compare cars">
        Line up to {{ \App\Support\CompareList::MAX }} cars side by side. The best figure in each row is highlighted in gold.
    </x-site.page-header>

    <div class="container-x py-12">
        @if ($vehicles->isEmpty())
            <div class="card px-6 py-20 text-center">
                <p class="font-display text-4xl text-white">Nothing to compare yet</p>
                <p class="mx-auto mt-2 max-w-md text-mist">Tap the ⇄ button on any car in our inventory to add it here.</p>
                <a href="{{ route('vehicles.index') }}" class="btn-gold mt-6" wire:navigate>Browse inventory</a>
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-white/5">
                <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="bg-carbon">
                            <th scope="col" class="w-40 p-4 align-bottom">
                                <form method="POST" action="{{ route('compare.clear') }}">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold tracking-wider text-gold uppercase hover:text-gold-light">Clear all</button>
                                </form>
                            </th>
                            @foreach ($vehicles as $vehicle)
                                <th scope="col" class="p-4 align-top font-normal">
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="group block">
                                        <img src="{{ $vehicle->cover_image }}" data-fallback="{{ asset(\App\Models\Vehicle::PLACEHOLDER_IMAGE) }}" alt="" class="aspect-[16/10] w-full rounded-lg object-cover">
                                        <p class="mt-3 text-xs tracking-widest text-mist uppercase">{{ $vehicle->year }} · {{ $vehicle->brand->name }}</p>
                                        <p class="font-display text-2xl tracking-wide text-white group-hover:text-gold">{{ $vehicle->model }} {{ $vehicle->trim }}</p>
                                    </a>
                                    <form method="POST" action="{{ route('compare.remove', $vehicle) }}" class="mt-2">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-mist hover:text-ember">Remove</button>
                                    </form>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-t border-white/5 odd:bg-ink even:bg-carbon/50">
                                <th scope="row" class="p-4 font-medium text-mist">{{ $row['label'] }}</th>
                                @foreach ($vehicles as $vehicle)
                                    @php
                                        $isBest = isset($row['best']) && $vehicles->count() > 1 && $vehicle->{$row['key']} !== null && $vehicle->{$row['key']} == $best[$row['best']];
                                    @endphp
                                    <td @class(['p-4', 'font-semibold text-gold' => $isBest, 'text-white' => ! $isBest])>{{ $row['format']($vehicle) ?: '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr class="border-t border-white/5">
                            <td class="p-4"></td>
                            @foreach ($vehicles as $vehicle)
                                <td class="p-4"><a href="{{ route('vehicles.show', $vehicle) }}" class="btn-gold w-full px-3 py-2.5">View & book</a></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.storefront>
