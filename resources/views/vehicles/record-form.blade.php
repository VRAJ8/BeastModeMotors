<x-vehicle.shell :vehicle="$vehicle" active="history" :title="$record ? 'Edit record' : 'Log work'">
    <div class="mb-6">
        <h2 class="display text-2xl">{{ $record ? 'Edit record' : 'Log work' }}</h2>
        <p class="text-sm text-muted">{{ $record ? $record->title : 'Add anything done to the car — servicing, repairs, tires, upgrades.' }}</p>
    </div>
    <livewire:record-form :vehicle="$vehicle" :record="$record" />
</x-vehicle.shell>
