<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\BuyerAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Throwable;

class SendBuyerAlerts extends Command
{
    protected $signature = 'passport:buyer-alerts';

    protected $description = 'Email buyers, once a day: price drops on cars they saved, and new cars matching their saved searches';

    public function handle(): int
    {
        $now = now();
        $sent = 0;

        User::query()
            ->where(fn ($q) => $q->whereHas('savedSearches', fn ($s) => $s->where('email_alerts', true))
                ->orWhere(fn ($q) => $q->where('price_drop_alerts', true)->whereHas('savedListings')))
            ->with(['savedSearches' => fn ($q) => $q->where('email_alerts', true)])
            ->chunkById(100, function ($users) use ($now, &$sent) {
                foreach ($users as $user) {
                    $drops = $this->priceDrops($user);
                    [$searches, $cars] = $this->newMatches($user, $now);

                    if ($drops->isEmpty() && $searches === []) {
                        // Nothing new: just move the window on.
                        SavedSearch::whereKey($user->savedSearches->modelKeys())->update(['notified_through' => $now]);

                        continue;
                    }

                    // If sending fails, nothing moves on, so the same news goes out tomorrow.
                    if ($this->send($user, $drops, $searches, $cars)) {
                        SavedSearch::whereKey($user->savedSearches->modelKeys())->update(['notified_through' => $now]);
                        $drops->each(fn (Listing $listing) => $user->savedListings()->updateExistingPivot($listing->getKey(), ['notified_price_cents' => $listing->price_cents]));
                        $sent++;
                    }
                }
            });

        $this->components->info("Buyer alert emails: {$sent}.");

        return self::SUCCESS;
    }

    /**
     * Saved cars still on sale whose price is now below the lowest the buyer has been told about.
     *
     * @return Collection<int, Listing>
     */
    private function priceDrops(User $user): Collection
    {
        if (! $user->price_drop_alerts) {
            return collect();
        }

        return $user->savedListings()
            ->where('listings.status', ListingStatus::Active)
            ->where('listings.seller_id', '!=', $user->getKey())
            ->whereColumn('listings.price_cents', '<', 'saved_listings.notified_price_cents')
            ->with('vehicle')
            ->get();
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: int} Each search with new cars, and how many cars that is in all.
     */
    private function newMatches(User $user, Carbon $now): array
    {
        $searches = [];
        $cars = [];

        foreach ($user->savedSearches as $search) {
            $criteria = $search->criteria();
            $new = $criteria->apply(Listing::query())
                // Only cars still on sale, published since the last run, and not the buyer's own.
                ->where('status', ListingStatus::Active)
                ->where('published_at', '>', $search->notified_through)
                ->where('published_at', '<=', $now)
                ->where('seller_id', '!=', $user->getKey())
                ->with('vehicle')
                ->latest('published_at')
                ->get();

            if ($new->isEmpty()) {
                continue;
            }

            $searches[] = [
                'label' => $criteria->describe(),
                'url' => $criteria->url(),
                'total' => $new->count(),
                'cars' => $new->take(BuyerAlerts::SHOWN)->map(fn (Listing $listing) => [
                    'title' => $listing->vehicle->fullTitle(),
                    'url' => route('listings.show', $listing),
                    'details' => money($listing->price_cents).' · '.self::details($listing),
                ])->values()->all(),
            ];
            // A car matching two searches counts once in the headline.
            $cars += array_fill_keys($new->modelKeys(), true);
        }

        return [$searches, count($cars)];
    }

    private static function details(Listing $listing): string
    {
        return number_format($listing->mileage).' mi · Score '.$listing->score.' · '.$listing->location();
    }

    /**
     * @param  Collection<int, Listing>  $drops
     * @param  list<array<string, mixed>>  $searches
     */
    private function send(User $user, Collection $drops, array $searches, int $cars): bool
    {
        $drops = $drops->map(fn (Listing $listing) => [
            'title' => $listing->vehicle->fullTitle(),
            'url' => route('listings.show', $listing),
            'was' => (int) $listing->pivot->notified_price_cents,
            'now' => $listing->price_cents,
            'details' => self::details($listing),
        ])->values()->all();

        try {
            $user->notify(new BuyerAlerts($drops, $searches, $cars, URL::signedRoute('saved-searches.unsubscribe', $user)));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
