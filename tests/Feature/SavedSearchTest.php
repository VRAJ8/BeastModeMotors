<?php

use App\Enums\ListingStatus;
use App\Livewire\Marketplace;
use App\Livewire\SavedSearches;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\SavedSearchMatches;
use App\Support\ListingFilters;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

function savedSearch(User $user, array $filters, ?CarbonInterface $since = null): SavedSearch
{
    $criteria = ListingFilters::from($filters);

    return $user->savedSearches()->create(['filters' => $criteria->toArray(), 'filters_hash' => $criteria->hash(), 'notified_through' => $since ?? now()->subDay()]);
}

it('cleans up filters once, and describes and links them', function () {
    $filters = ListingFilters::from(['q' => '  m340i  ', 'make' => 'BMW', 'max_price' => '60,000', 'min_score' => 70, 'state' => 'co', 'fuel' => 'nope', 'max_miles' => 'abc']);

    expect($filters->toArray())->toBe(['q' => 'm340i', 'make' => 'BMW', 'max_price' => 60000, 'min_score' => 70, 'state' => 'CO'])
        ->and($filters->describe())->toBe('“m340i” · BMW · Under $60,000 · Score 70+ · Colorado')
        ->and($filters->url())->toBe(route('marketplace', ['q' => 'm340i', 'make' => 'BMW', 'max_price' => 60000, 'min_score' => 70, 'state' => 'CO']))
        ->and($filters->hash())->toBe(ListingFilters::from(['make' => 'BMW', 'q' => 'M340I', 'state' => 'CO', 'max_price' => 60000, 'min_score' => '70'])->hash())
        ->and(ListingFilters::from(['fuel' => 'x', 'min_score' => 0])->isEmpty())->toBeTrue();
});

it('saves a search from the marketplace once, up to the limit', function () {
    $user = User::factory()->create();

    $page = Livewire::actingAs($user)->test(Marketplace::class)
        ->assertDontSee('Save this search')
        ->set('make', 'Porsche')->set('minScore', 70)
        ->assertSee('Save this search')
        ->call('saveSearch')
        ->assertSee('Manage');

    $page->call('saveSearch');
    expect($user->savedSearches()->count())->toBe(1)
        ->and($user->savedSearches()->first()->filters)->toBe(['make' => 'Porsche', 'min_score' => 70]);

    foreach (range(1, SavedSearch::PER_USER - 1) as $year) {
        savedSearch($user, ['min_year' => 2000 + $year]);
    }

    $page->set('make', 'Toyota')->call('saveSearch')->assertHasErrors('saveSearch');
    expect($user->savedSearches()->count())->toBe(SavedSearch::PER_USER);
});

it('sends guests to sign in, then back to their search', function () {
    Livewire::test(Marketplace::class)->set('make', 'Porsche')->call('saveSearch')->assertRedirect(route('login'));

    expect(session('url.intended'))->toBe(route('marketplace', ['make' => 'Porsche']));
});

it('emails each buyer once a day with the new cars matching their searches', function () {
    $buyer = User::factory()->create();
    $search = savedSearch($buyer, ['min_score' => 70]);
    savedSearch($buyer, ['state' => 'TX', 'min_score' => 50]);
    $quiet = savedSearch(User::factory()->create(), ['make' => 'Nothing Like This']);

    $match = liveListing(['score' => 85, 'published_at' => now()->subHour()]);
    liveListing(['score' => 40, 'published_at' => now()->subHour()]);                 // below both searches
    liveListing(['score' => 90, 'published_at' => now()->subDays(3)]);                // published before the last run
    liveListing(['score' => 90, 'status' => ListingStatus::Pending, 'published_at' => now()->subHour()]); // already under offer
    $own = liveListing(['score' => 95, 'published_at' => now()->subHour()]);
    $own->vehicle->update(['user_id' => $buyer->id]);
    $own->update(['seller_id' => $buyer->id]);

    $this->artisan('passport:search-alerts')->assertSuccessful();

    Notification::assertSentToTimes($buyer, SavedSearchMatches::class, 1);
    Notification::assertSentTo($buyer, SavedSearchMatches::class, function (SavedSearchMatches $n) use ($match) {
        $mail = $n->toMail($n)->render()->toHtml();

        return $n->cars === 1 && count($n->searches) === 2
            && str_contains($mail, e($match->vehicle->fullTitle()))
            && str_contains($n->unsubscribeUrl, 'signature=');
    });
    Notification::assertNotSentTo($quiet->user, SavedSearchMatches::class);
    expect($search->fresh()->notified_through->isToday())->toBeTrue()
        ->and($quiet->fresh()->notified_through->isToday())->toBeTrue();

    // Nothing new the next day: no email.
    Notification::fake();
    $this->artisan('passport:search-alerts')->assertSuccessful();
    Notification::assertNothingSent();
});

it('skips searches with email turned off, and lets the buyer manage them', function () {
    $buyer = User::factory()->create();
    $search = savedSearch($buyer, ['min_score' => 70]);
    $other = savedSearch(User::factory()->create(), ['min_score' => 70]);
    liveListing(['score' => 85, 'published_at' => now()->subHour()]);

    Livewire::actingAs($buyer)->test(SavedSearches::class)
        ->assertSee('Score 70+')
        ->assertSeeHtml('<span class="num">1</span> car for sale now')
        ->call('toggleAlerts', $search->id);

    expect($search->fresh()->email_alerts)->toBeFalse();
    $this->artisan('passport:search-alerts');
    Notification::assertNotSentTo($buyer, SavedSearchMatches::class);

    Livewire::actingAs($buyer)->test(SavedSearches::class)->call('delete', $other->id)->assertNotFound();
    Livewire::actingAs($buyer)->test(SavedSearches::class)->call('delete', $search->id);
    expect(SavedSearch::whereKey($search->id)->exists())->toBeFalse()->and($other->fresh())->not->toBeNull();

    $this->actingAs($buyer)->get(route('saved'))->assertOk()->assertSee('Saved searches');
});

it('unsubscribes from a signed link only after a click, and from a mail client\'s one-click POST', function () {
    $buyer = User::factory()->create();
    $search = savedSearch($buyer, ['min_score' => 70]);
    $url = URL::signedRoute('saved-searches.unsubscribe', $buyer);

    $this->get($url)->assertOk()->assertSee('Stop the emails');
    expect($search->fresh()->email_alerts)->toBeTrue();

    $this->post($url, ['List-Unsubscribe' => 'One-Click'])->assertOk()->assertSee('won\'t get these emails', false);
    expect($search->fresh()->email_alerts)->toBeFalse();

    $this->post(route('saved-searches.unsubscribe', $buyer))->assertForbidden();
});

it('turns links into readable text in the plain-text email', function () {
    expect(md_plain('• ['.md('2021 [Tesla] Model 3').'](https://example.test/cars/a?b=1) — '.md('$31,500 · Score 88')))
        ->toBe('• 2021 [Tesla] Model 3 (https://example.test/cars/a?b=1) — $31,500 · Score 88');
});
