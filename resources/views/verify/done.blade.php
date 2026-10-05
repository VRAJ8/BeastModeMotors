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
        @if ($verification->shop && $verification->status === \App\Enums\VerificationStatus::Confirmed)
            <div class="card card-pad mt-10 text-left">
                <p class="font-semibold">Your shop now has a public profile</p>
                <p class="mt-1 text-sm text-ink-soft">It shows the work you've confirmed and how quickly you answer. Add your address, phone and specialties so car owners can find you.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ $verification->shop->editUrl() }}" class="btn-primary btn-sm">Complete your profile</a>
                    <a href="{{ route('shops.show', $verification->shop) }}" class="btn-secondary btn-sm">View it</a>
                </div>
            </div>
        @endif
        <a href="{{ route('how-it-works') }}" class="link mt-6 inline-block">What is Beast Mode Motors?</a>
    </div>
</x-layouts.site>
