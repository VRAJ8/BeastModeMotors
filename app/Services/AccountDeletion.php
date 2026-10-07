<?php

namespace App\Services;

use App\Enums\DealStatus;
use App\Enums\DocumentType;
use App\Enums\ListingStatus;
use App\Models\Deal;
use App\Models\Document;
use App\Models\Expense;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DealEnded;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deletes an account without taking other people's history with it.
 *
 * A car whose passport holds someone else's history — an earlier owner, a sale made here, a shop's dispute — stays registered
 * with no owner: its records travel on to whoever owns it next, while this person's private things (costs,
 * personal paperwork, share links) are deleted. A car with only their own history is deleted outright.
 */
class AccountDeletion
{
    public function __construct(private DealFlow $deals) {}

    /**
     * @throws ValidationException when the account can't be deleted yet
     */
    public function ensureDeletable(User $user): void
    {
        // An agreed sale involves someone else's money and plans: finish or cancel it first.
        if (Deal::involving($user)->where('status', DealStatus::Agreed)->exists()) {
            throw ValidationException::withMessages(['password' => 'You have an agreed sale in progress. Complete or cancel it in your deal room before deleting your account.']);
        }
    }

    public function delete(User $user): void
    {
        $this->ensureDeletable($user);

        // Tell everyone mid-conversation, rather than letting their deals silently disappear.
        foreach (Deal::involving($user)->where('status', DealStatus::Open)->with('vehicle')->get() as $deal) {
            // The seller's own car (and so this deal) is about to be deleted: a notice that links to the deal
            // would fail to send, so the other person gets a plain one instead.
            $goesWithCar = $deal->vehicle->user_id === $user->getKey() && ! self::hasSharedHistory($deal->vehicle);

            $this->deals->cancel($deal, $user, 'Account deleted', notify: ! $goesWithCar);

            $other = $deal->counterparty($user);

            if ($goesWithCar && $other->exists) {
                $other->notify(new DealEnded(
                    "{$user->publicName()} closed their account",
                    "The {$deal->vehicle->title()} is no longer for sale, so your conversation about it has ended.",
                ));
            }
        }

        DB::transaction(function () use ($user) {
            foreach ($user->vehicles()->get() as $vehicle) {
                self::hasSharedHistory($vehicle) ? $this->release($vehicle) : $vehicle->delete();
            }

            $user->delete();
        });
    }

    /**
     * History that isn't only this person's to erase: an earlier owner, a sale made here, or a shop's dispute.
     */
    public static function hasSharedHistory(Vehicle $vehicle): bool
    {
        return $vehicle->ownerships()->count() > 1
            || Deal::where('vehicle_id', $vehicle->getKey())->where('status', DealStatus::Completed)->exists()
            || $vehicle->records()->whereNotNull('disputed_at')->exists();
    }

    /**
     * The owner leaves; the car's history stays, waiting for its next owner to claim it.
     */
    private function release(Vehicle $vehicle): void
    {
        $ownership = $vehicle->currentOwnership;

        $ownership?->update(['ended_on' => now()->toDateString(), 'end_mileage' => $vehicle->current_mileage]);

        if ($ownership) {
            Expense::where('ownership_id', $ownership->getKey())->delete();
        }

        Document::where('vehicle_id', $vehicle->getKey())
            ->whereNotIn('type', collect(DocumentType::cases())->filter->transfersWithCar()->map->value->all())
            ->get()
            ->each->delete();

        $vehicle->shareLinks()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $vehicle->listings()
            ->whereIn('status', [ListingStatus::Draft, ListingStatus::Active, ListingStatus::Pending])
            ->update(['status' => ListingStatus::Withdrawn]);
        $vehicle->update(['user_id' => null, 'nickname' => null]);
    }
}
