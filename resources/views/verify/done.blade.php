<x-layouts.site title="Thank you" robots="noindex">
    <div class="container-x max-w-xl py-20 text-center">
        <x-heroicon-s-check-badge class="mx-auto size-14 text-verified" />
        <h1 class="display mt-4 text-3xl">Thank you</h1>
        <p class="mt-2 text-ink-soft">
            @if ($verification->status === \App\Enums\VerificationStatus::Confirmed)
                The record now carries a “Shop verified” stamp. You've helped your customer prove their {{ $verification->record->vehicle->title() }} was looked after.
            @else
                We've told the owner the record doesn't match your files.
            @endif
        </p>
        <a href="{{ route('how-it-works') }}" class="link mt-6 inline-block">What is Beast Mode Motors?</a>
    </div>
</x-layouts.site>
