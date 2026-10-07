<?php

use App\Enums\ListingStatus;
use App\Livewire\ListingActions;
use App\Livewire\SavedSearches;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\BuyerAlerts;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

function watch(User $user, Listing $listing): void
{
    Livewire::actingAs($user)->test(ListingActions::class, ['listing' => $listing])->call('toggleSave');
}

it('records a cut on a live listing, and forgets it when the price goes back up', function () {
    $listing = liveListing(['price_cents' => 3_000_000]);

    $listing->update(['price_cents' => 2_850_000]);
    expect($listing->fresh()->previous_price_cents)->toBe(3_000_000)
        ->and($listing->fresh()->recentDropFrom())->toBe(3_000_000);

    $listing->update(['price_cents' => 2_900_000]);
    expect($listing->fresh()->previous_price_cents)->toBeNull()->and($listing->fresh()->recentDropFrom())->toBeNull();

    $listing->update(['price_cents' => 2_700_000, 'description' => 'Changed']);
    $this->travel(31)->days();
    expect($listing->fresh()->recentDropFrom())->toBeNull();

    $draft = Listing::factory()->draft()->create(['price_cents' => 3_000_000]);
    $draft->update(['price_cents' => 2_000_000]);
    expect($draft->fresh()->previous_price_cents)->toBeNull();
});

it('shows the old price on the card and the listing', function () {
    $listing = liveListing(['price_cents' => 3_000_000]);
    $listing->update(['price_cents' => 2_850_000]);

    $this->get(route('listings.show', $listing))->assertOk()->assertSee('Price dropped from')->assertSee('$30,000');
    $this->get(route('marketplace'))->assertOk()->assertSee('$30,000');
});

it('emails a buyer once when a car they saved drops below the price they were last told', function () {
    $buyer = User::factory()->create();
    $listing = liveListing(['price_cents' => 3_000_000]);
    watch($buyer, $listing);
    expect($buyer->savedListings()->first()->pivot->notified_price_cents)->toBe(3_000_000);

    $listing->update(['price_cents' => 2_850_000]);
    $this->artisan('passport:buyer-alerts')->assertSuccessful();

    Notification::assertSentTo($buyer, BuyerAlerts::class, function (BuyerAlerts $n) use ($listing) {
        $mail = $n->toMail($n)->render()->toHtml();

        return $n->drops[0]['was'] === 3_000_000 && $n->drops[0]['now'] === 2_850_000 && $n->searches === []
            && $n->toMail($n)->subject === 'A car you saved dropped its price'
            && str_contains($mail, 'now $28,500, was $30,000')
            && $n->toArray($n)['url'] === route('listings.show', $listing);
    });
    expect($buyer->savedListings()->first()->pivot->notified_price_cents)->toBe(2_850_000);

    // No new cut: no email. A rise and a cut that stays above what they were told: still no email.
    Notification::fake();
    $this->artisan('passport:buyer-alerts');
    $listing->fresh()->update(['price_cents' => 3_100_000]);
    $listing->fresh()->update(['price_cents' => 2_900_000]);
    $this->artisan('passport:buyer-alerts');
    Notification::assertNothingSent();

    $listing->fresh()->update(['price_cents' => 2_700_000]);
    $this->artisan('passport:buyer-alerts');
    Notification::assertSentTo($buyer, BuyerAlerts::class, fn (BuyerAlerts $n) => $n->drops[0]['was'] === 2_850_000);
});

it('leaves out cars no longer on sale, and buyers who turned price drops off', function () {
    $buyer = User::factory()->create();
    $quiet = User::factory()->create();
    $pending = liveListing(['price_cents' => 3_000_000]);
    $muted = liveListing(['price_cents' => 3_000_000]);
    watch($buyer, $pending);
    watch($quiet, $muted);

    $pending->update(['price_cents' => 2_500_000, 'status' => ListingStatus::Pending]);
    $muted->update(['price_cents' => 2_500_000]);
    Livewire::actingAs($quiet)->test(SavedSearches::class)->call('togglePriceDrops');
    expect($quiet->fresh()->price_drop_alerts)->toBeFalse();

    $this->artisan('passport:buyer-alerts');
    Notification::assertNothingSent();
});

it('puts price drops and new search matches in one email', function () {
    $buyer = User::factory()->create();
    $saved = liveListing(['price_cents' => 3_000_000, 'score' => 50]);
    watch($buyer, $saved);
    $buyer->savedSearches()->create(['filters' => ['min_score' => 80], 'filters_hash' => 'x', 'notified_through' => now()->subDay()]);
    liveListing(['score' => 90, 'published_at' => now()->subHour()]);
    $saved->update(['price_cents' => 2_800_000]);

    $this->artisan('passport:buyer-alerts');

    Notification::assertSentToTimes($buyer, BuyerAlerts::class, 1);
    Notification::assertSentTo($buyer, BuyerAlerts::class, fn (BuyerAlerts $n) => count($n->drops) === 1 && $n->cars === 1
        && $n->toMail($n)->subject === 'A car you saved dropped its price, and 1 new car matches your saved search');
});

it('turns price-drop emails off from the unsubscribe link too', function () {
    $buyer = User::factory()->create();

    $this->post(URL::signedRoute('saved-searches.unsubscribe', $buyer))->assertOk();

    expect($buyer->fresh()->price_drop_alerts)->toBeFalse();
});
