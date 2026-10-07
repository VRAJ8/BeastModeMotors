@php($vehicle = $transfer->vehicle)
<p class="eyebrow">Passport transfer</p>
<h1 class="display mt-2 text-3xl">{{ $transfer->sender->publicName() }} is handing you the passport for their {{ $vehicle->title() }}</h1>
<div class="card mt-6 flex items-center gap-4 p-4">
    @if ($cover = $vehicle->coverPhotoUrl())
        <img src="{{ $cover }}" alt="" class="size-20 shrink-0 rounded-xl object-cover">
    @else
        <div class="grid size-20 shrink-0 place-items-center rounded-xl bg-paper"><x-heroicon-o-truck class="size-8 text-muted" /></div>
    @endif
    <div class="min-w-0 text-sm">
        <p class="font-semibold">{{ $vehicle->fullTitle() }}</p>
        <p class="num text-muted">VIN {{ $vehicle->maskedVin() }}</p>
        <p class="text-muted">{{ $vehicle->records()->count() }} service records and the full odometer history come with it.</p>
    </div>
</div>
