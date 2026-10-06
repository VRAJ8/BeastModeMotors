<?php

namespace App\Livewire;

use App\Enums\DealStatus;
use App\Enums\InspectionResult;
use App\Models\Deal;
use App\Models\Offer;
use App\Services\DealFlow;
use App\Services\ScamShield;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DealRoom extends Component
{
    #[Locked]
    public Deal $deal;

    public string $body = '';

    public string $amount = '';

    public string $note = '';

    public ?int $saleMileage = null;

    public string $cancelReason = '';

    public bool $cancelling = false;

    /** Inspection form state. */
    public string $scheduled_for = '';

    public string $location = '';

    public string $inspector = '';

    public string $summary = '';

    /** @var array<string, array{result: string, note: string}> */
    public array $results = [];

    public function mount(): void
    {
        abort_unless($this->deal->isParticipant(Auth::user()), 403);

        if ($inspection = $this->deal->inspection) {
            $this->scheduled_for = (string) $inspection->scheduled_for?->format('Y-m-d\TH:i');
            $this->location = (string) $inspection->location;
            $this->inspector = (string) $inspection->inspector;
            $this->summary = (string) $inspection->summary;
            $this->results = $inspection->results ?? [];
        }

        // Only the seller, mid-sale, needs the car's current odometer (as a starting point for the handover
        // reading). Public properties are visible in the page, so nobody else gets it.
        if ($this->deal->roleOf(Auth::user()) === 'seller' && $this->deal->status === DealStatus::Agreed) {
            $this->saleMileage = $this->deal->vehicle->current_mileage;
        }
    }

    private function role(): string
    {
        return $this->deal->roleOf(Auth::user());
    }

    public function send(DealFlow $flow): void
    {
        $this->validate(['body' => ['required', 'string', 'max:2000']]);
        $message = $flow->post($this->deal, Auth::user(), $this->body);
        $this->reset('body');

        if (ScamShield::highestSeverity($message->risk_flags) === ScamShield::HIGH) {
            $this->dispatch('toast', message: 'Sent — the other person will see a safety warning on that message.');
        }
    }

    public function makeOffer(DealFlow $flow): void
    {
        $this->amount = clean_amount($this->amount);
        $this->validate([
            'amount' => ['required', 'numeric', 'min:500', 'max:100000000'],
            'note' => ['nullable', 'string', 'max:300'],
        ]);

        $flow->offer($this->deal, Auth::user(), to_cents($this->amount), $this->note ?: null);
        $this->reset('amount', 'note');
        $this->dispatch('toast', message: 'Offer sent. It stays open for 72 hours.');
    }

    public function respond(int $offerId, bool $accept, DealFlow $flow): void
    {
        $offer = Offer::where('deal_id', $this->deal->getKey())->findOrFail($offerId);
        $flow->respond($offer, Auth::user(), $accept);
        $this->deal->refresh();
        $this->dispatch('toast', message: $accept ? 'Deal agreed! Next: inspection and handover.' : 'Offer declined.');
    }

    public function saveInspection(bool $complete = false): void
    {
        abort_unless($this->role() === 'buyer' && $this->deal->status === DealStatus::Agreed, 403);

        $this->validate([
            'scheduled_for' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:160'],
            'inspector' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'results.*.result' => ['nullable', 'in:'.implode(',', array_column(InspectionResult::cases(), 'value'))],
            'results.*.note' => ['nullable', 'string', 'max:300'],
        ]);

        $inspection = $this->deal->inspection()->firstOrCreate([]);
        $wasComplete = $inspection->completed_at !== null;

        $inspection->update([
            'scheduled_for' => $this->scheduled_for ?: null,
            'location' => $this->location ?: null,
            'inspector' => $this->inspector ?: null,
            'summary' => $this->summary ?: null,
            'results' => $this->results,
            'completed_at' => $complete ? ($inspection->completed_at ?? now()) : $inspection->completed_at,
        ]);

        if ($complete && ! $wasComplete) {
            $tally = $inspection->fresh()->tally();
            app(DealFlow::class)->system($this->deal, sprintf('Buyer completed the inspection: %d good, %d need attention, %d problems.',
                $tally['pass'] ?? 0, $tally['attention'] ?? 0, $tally['fail'] ?? 0));
        }

        $this->deal->unsetRelation('inspection');
        $this->dispatch('toast', message: $complete ? 'Inspection recorded and shared with the seller.' : 'Inspection saved.');
    }

    public function toggle(string $key, DealFlow $flow): void
    {
        $flow->toggleHandover($this->deal, Auth::user(), $key);
        $this->deal->refresh();
    }

    public function confirm(DealFlow $flow)
    {
        $done = $flow->confirm($this->deal, Auth::user(), $this->role() === 'seller' ? $this->saleMileage : null);
        $this->deal->refresh();

        if ($done) {
            session()->flash('toast', $this->role() === 'buyer' ? 'Congratulations — the car and its history are in your garage.' : 'Sale complete. Thank you for passing the history on.');

            return $this->redirectRoute('deals.show', $this->deal);
        }

        $this->dispatch('toast', message: 'Confirmed. Waiting for the other side.');

        return null;
    }

    public function cancel(DealFlow $flow): void
    {
        $this->validate(['cancelReason' => ['nullable', 'string', 'max:255']]);
        $flow->cancel($this->deal, Auth::user(), $this->cancelReason ?: null);
        $this->deal->refresh();
        $this->reset('cancelling', 'cancelReason');
    }

    public function render()
    {
        $user = Auth::user();

        // Opening the room reads the other person's messages.
        $this->deal->messages()
            ->whereNull('read_at')
            ->whereNotNull('user_id')
            ->where('user_id', '!=', $user->getKey())
            ->update(['read_at' => now()]);

        $this->deal->load(['vehicle.photos', 'listing', 'buyer', 'seller', 'inspection', 'pendingOffer']);

        return view('livewire.deal-room', [
            'role' => $this->role(),
            'me' => $user,
            'other' => $this->deal->counterparty($user),
            'messages' => $this->deal->messages()->with('user')->get(),
            'offers' => $this->deal->offers()->with('user')->get(),
            'checklist' => config('passport.inspection'),
            'resultOptions' => InspectionResult::options(),
        ]);
    }
}
