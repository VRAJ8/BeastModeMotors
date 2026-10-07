<?php

namespace App\Livewire;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Listing;
use App\Services\DealFlow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Buyer-side actions on a listing: save, start a conversation, report.
 */
class ListingActions extends Component
{
    #[Locked]
    public Listing $listing;

    public string $message = '';

    public bool $reporting = false;

    public string $reason = 'scam';

    public string $details = '';

    public function mount(): void
    {
        $this->message = "Hi, is the {$this->listing->vehicle->title()} still available? I'd like to arrange a viewing.";
    }

    public function toggleSave()
    {
        if (! Auth::check()) {
            session()->put('url.intended', route('listings.show', $this->listing));

            return $this->redirectRoute('login');
        }

        Auth::user()->savedListings()->toggle($this->listing->getKey());

        return null;
    }

    public function contact(DealFlow $deals)
    {
        if (! Auth::check()) {
            session()->put('url.intended', route('listings.show', $this->listing));

            return $this->redirectRoute('login');
        }

        $this->validate(['message' => ['required', 'string', 'min:2', 'max:2000']]);

        $key = 'deal-start:'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('message', 'You\'ve contacted a lot of sellers recently. Try again in an hour.');

            return null;
        }
        RateLimiter::hit($key, 3600);

        $deal = $deals->start($this->listing, Auth::user(), $this->message);

        return $this->redirectRoute('deals.show', $deal);
    }

    public function report(): void
    {
        abort_unless(Auth::check(), 403);

        $this->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->listing->reports()->firstOrCreate(
            ['reporter_id' => Auth::id(), 'status' => ReportStatus::Open],
            ['reason' => $this->reason, 'details' => $this->details ?: null],
        );

        $this->reset('reporting', 'details');
        $this->dispatch('toast', message: 'Thanks — our trust & safety team will review it.');
    }

    public function render()
    {
        return view('livewire.listing-actions', [
            'saved' => Auth::user()?->savedListings()->whereKey($this->listing->getKey())->exists() ?? false,
            'reasons' => ReportReason::options(),
        ]);
    }
}
