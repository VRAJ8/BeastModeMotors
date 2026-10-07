<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\SavedSearchMatches;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Throwable;

class SendSearchAlerts extends Command
{
    protected $signature = 'passport:search-alerts';

    protected $description = 'Email buyers the new listings that match their saved searches (one email per person)';

    public function handle(): int
    {
        $now = now();
        $sent = 0;

        User::whereHas('savedSearches', fn ($q) => $q->where('email_alerts', true))
            ->with(['savedSearches' => fn ($q) => $q->where('email_alerts', true)])
            ->chunkById(100, function ($users) use ($now, &$sent) {
                foreach ($users as $user) {
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
                            'cars' => $new->take(SavedSearchMatches::SHOWN)->map(fn (Listing $listing) => [
                                'title' => $listing->vehicle->fullTitle(),
                                'url' => route('listings.show', $listing),
                                'details' => money($listing->price_cents).' · '.number_format($listing->mileage).' mi · Score '.$listing->score.' · '.$listing->location(),
                            ])->values()->all(),
                        ];
                        // A car matching two searches counts once in the headline.
                        $cars += array_fill_keys($new->modelKeys(), true);
                    }

                    // With nothing new, just move the window on. If sending fails, the same cars go out tomorrow.
                    if ($searches === [] || $this->send($user, $searches, count($cars))) {
                        SavedSearch::whereKey($user->savedSearches->modelKeys())->update(['notified_through' => $now]);
                        $sent += $searches === [] ? 0 : 1;
                    }
                }
            });

        $this->components->info("Saved-search emails: {$sent}.");

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $searches
     */
    private function send(User $user, array $searches, int $cars): bool
    {
        try {
            $user->notify(new SavedSearchMatches($searches, $cars, URL::signedRoute('saved-searches.unsubscribe', $user)));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
