<?php

use App\Enums\DealStatus;
use App\Enums\DocumentType;
use App\Enums\OfferStatus;
use App\Enums\VerificationStatus;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\Vehicles\Pages\ManageVehicles;
use App\Models\Deal;
use App\Models\Document;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DealEnded;
use App\Notifications\DealUpdate;
use App\Notifications\RecallsFound;
use App\Notifications\VerificationAnswered;
use App\Services\AccountDeletion;
use App\Services\DealFlow;
use App\Services\RecallSync;
use App\Services\ShopVerifier;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Point the app at a mail server that refuses everything, or only the given addresses.
 */
function brokenMail(array $refuse = []): void
{
    Mail::extend('broken', fn () => new class($refuse) extends AbstractTransport
    {
        public function __construct(private array $refuse)
        {
            parent::__construct();
        }

        protected function doSend(SentMessage $message): void
        {
            $to = collect($message->getEnvelope()->getRecipients())->map->getAddress();

            if ($this->refuse === [] || $to->intersect($this->refuse)->isNotEmpty()) {
                throw new TransportException('550 mailbox unavailable');
            }
        }

        public function __toString(): string
        {
            return 'broken://';
        }
    });

    config(['mail.mailers.broken' => ['transport' => 'broken'], 'mail.default' => 'broken']);
}

/**
 * A deal with an agreed price and every handover step ticked, ready for both sides to confirm.
 */
function readyToHandOver(): Deal
{
    $deal = deal();
    $flow = app(DealFlow::class);
    $flow->respond($flow->offer($deal, $deal->buyer, 2_300_000), $deal->seller, true);

    foreach (config('passport.handover') as $key => $item) {
        if ($item['required']) {
            $flow->toggleHandover($deal->fresh(), $item['by'] === 'buyer' ? $deal->buyer : $deal->seller, $key);
        }
    }

    return $deal->fresh();
}

// --- Email failures ---------------------------------------------------------------------------------------------

it('never rolls back a completed sale because an email bounced', function () {
    $deal = readyToHandOver();
    $flow = app(DealFlow::class);
    $flow->confirm($deal, $deal->seller, $deal->vehicle->current_mileage + 10);

    brokenMail();

    try {
        $flow->confirm($deal->fresh(), $deal->buyer);
    } catch (TransportException) {
        // On the sync queue the mail error still surfaces; what matters is that it comes after the commit.
    }

    expect($deal->fresh()->status)->toBe(DealStatus::Completed)
        ->and($deal->vehicle->fresh()->user_id)->toBe($deal->buyer_id)
        ->and($deal->vehicle->ownerships()->count())->toBe(2);
});

it('frees a verification request whose email never reached the shop', function () {
    brokenMail();
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);

    try {
        app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside', 'desk@eastside.test');
    } catch (TransportException) {
    }

    expect($record->verifications()->first()->status)->toBe(VerificationStatus::Cancelled)
        ->and($record->fresh()->pendingVerification)->toBeNull()
        ->and($record->fresh()->canRequestVerification())->toBeTrue();
});

it('keeps sending reminders to everyone else when one address bounces', function () {
    brokenMail(refuse: ['bounce@example.com']);
    $bouncing = car(['current_mileage' => 40000]);
    $bouncing->owner->update(['email' => 'bounce@example.com']);
    $fine = car(['current_mileage' => 40000]);

    foreach ([$bouncing, $fine] as $vehicle) {
        $vehicle->reminders()->create(['task' => 'Engine oil & filter', 'interval_miles' => 5000, 'last_done_mileage' => 30000, 'last_done_on' => now()->subYear()]);
    }

    $this->artisan('passport:send-reminders')->assertSuccessful();

    expect($fine->reminders()->first()->notified_at)->not->toBeNull()
        // Not marked as sent, so tomorrow's run tries again.
        ->and($bouncing->reminders()->first()->notified_at)->toBeNull();
});

// --- Recalls ------------------------------------------------------------------------------------------------------

it('treats the first successful recall check as a baseline, not news', function () {
    Notification::fake();
    $campaign = fn (string $number) => ['NHTSACampaignNumber' => $number, 'Component' => 'AIR BAGS', 'Summary' => 'May not deploy.', 'ReportReceivedDate' => '01/02/2024'];
    Http::fakeSequence('api.nhtsa.gov/*')
        ->push(['results' => [$campaign('19V100000'), $campaign('21V200000')]])
        ->push(['results' => [$campaign('19V100000'), $campaign('21V200000'), $campaign('26V300000')]]);
    $vehicle = car();

    app(RecallSync::class)->sync($vehicle);
    Notification::assertNothingSent();

    app(RecallSync::class)->sync($vehicle->fresh());
    Notification::assertSentTo($vehicle->owner, RecallsFound::class, fn ($n) => $n->recalls->pluck('campaign_number')->all() === ['26V300000']);
});

// --- What a sale resets -------------------------------------------------------------------------------------------

it('lets the shop still answer after a sale, telling the new owner, and re-arms expiry alerts for the buyer', function () {
    Notification::fake();
    $deal = readyToHandOver();
    $vehicle = $deal->vehicle;
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);
    $verification = app(ShopVerifier::class)->request($record, $vehicle->owner, 'Eastside', 'desk@eastside.test');
    $warranty = Document::create([
        'vehicle_id' => $vehicle->id, 'ownership_id' => $vehicle->currentOwnership->id, 'uploaded_by' => $vehicle->user_id,
        'type' => DocumentType::Warranty, 'name' => 'Powertrain warranty', 'path' => 'w.pdf', 'mime' => 'application/pdf', 'size' => 10,
        'expires_on' => now()->addDays(20), 'expiry_notified_at' => now(),
    ]);

    app(DealFlow::class)->confirm($deal, $deal->seller, $vehicle->current_mileage + 10);
    app(DealFlow::class)->confirm($deal->fresh(), $deal->buyer);

    expect($verification->fresh()->isAnswerable())->toBeTrue()
        ->and($warranty->fresh()->expiry_notified_at)->toBeNull();

    // The shop confirms after the sale: the record is verified, and it's the buyer who hears about it.
    app(ShopVerifier::class)->answer($verification->fresh(), true, 'Dee', null, '127.0.0.1');

    expect($record->fresh()->evidence())->toBe(ServiceRecord::EVIDENCE_VERIFIED);
    Notification::assertSentTo($deal->buyer, VerificationAnswered::class);
    Notification::assertNotSentTo($deal->seller, VerificationAnswered::class);
});

it('tells a buyer their conversation ended when the seller deletes their account and car', function () {
    Notification::fake();
    $deal = deal();
    $title = $deal->vehicle->title();

    app(AccountDeletion::class)->delete($deal->seller);

    expect(Deal::find($deal->id))->toBeNull();
    Notification::assertSentTo($deal->buyer, DealEnded::class, fn (DealEnded $n) => str_contains($n->body, $title));
    Notification::assertNotSentTo($deal->buyer, DealUpdate::class);
});

it('leaves the demo buyer\'s inbox empty of the back-dated sale', function () {
    config(['passport.demo' => true]);
    $this->seed(DemoSeeder::class);

    $buyer = User::firstWhere('email', 'buyer@beastmodemotors.test');

    expect($buyer->notifications()->where('data', 'like', '%is yours%')->count())->toBe(0);
});

// --- What goes into notifications ---------------------------------------------------------------------------------

it('keeps a cancellation reason out of the email', function () {
    Notification::fake();
    $deal = deal();

    app(DealFlow::class)->cancel($deal, $deal->buyer, 'Text me on WhatsApp +1 555 0100, I can send a Zelle deposit today');

    Notification::assertSentTo($deal->seller, DealUpdate::class, fn (DealUpdate $n) => ! str_contains((string) $n->body, 'WhatsApp'));
});

it('does not repeat a flagged message in the notification', function () {
    Notification::fake();
    $deal = deal();

    app(DealFlow::class)->post($deal, $deal->buyer, 'Can you send the VIN? I will pay by gift card and arrange shipping.');

    Notification::assertSentTo($deal->seller, DealUpdate::class, fn (DealUpdate $n) => ! str_contains((string) $n->body, 'gift card'));
});

// --- Account deletion ---------------------------------------------------------------------------------------------

it('keeps a bought car\'s history when its owner deletes their account, and lets staff hand it on', function () {
    Notification::fake();
    $deal = readyToHandOver();
    app(DealFlow::class)->confirm($deal, $deal->seller, $deal->vehicle->current_mileage + 10);
    app(DealFlow::class)->confirm($deal->fresh(), $deal->buyer);
    $vehicle = $deal->vehicle->fresh();
    $buyer = $deal->buyer;

    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);
    $vehicle->expenses()->create(['ownership_id' => $vehicle->currentOwnership->id, 'category' => 'fuel', 'amount_cents' => 5000, 'spent_on' => now()]);
    $insurance = Document::create([
        'vehicle_id' => $vehicle->id, 'ownership_id' => $vehicle->currentOwnership->id, 'uploaded_by' => $buyer->id,
        'type' => DocumentType::Insurance, 'name' => 'Insurance card', 'path' => 'i.pdf', 'mime' => 'application/pdf', 'size' => 10,
    ]);
    $link = $vehicle->shareLinks()->create(['created_by' => $buyer->id, 'label' => 'Mechanic']);
    $ownOnly = car(['user_id' => $buyer->id]);

    app(AccountDeletion::class)->delete($buyer);

    $vehicle = $vehicle->fresh();
    expect(User::find($buyer->id))->toBeNull()
        ->and($vehicle->user_id)->toBeNull()
        ->and($record->fresh())->not->toBeNull()
        ->and($vehicle->expenses()->count())->toBe(0)
        ->and($insurance->fresh())->toBeNull()
        ->and($link->fresh()->revoked_at)->not->toBeNull()
        ->and(Vehicle::find($ownOnly->id))->toBeNull()
        // The seller's side of the sale is untouched.
        ->and($deal->fresh()->status)->toBe(DealStatus::Completed)
        ->and(Deal::involving($deal->seller)->count())->toBe(1);

    $claimant = User::factory()->create();
    Livewire::actingAs(User::factory()->admin()->create())->test(ManageVehicles::class)
        ->assertTableActionHidden('release', $vehicle)
        ->callTableAction('assign', $vehicle, ['email' => $claimant->email])
        ->assertHasNoTableActionErrors();

    expect($vehicle->fresh()->user_id)->toBe($claimant->id)
        ->and($vehicle->ownerships()->count())->toBe(3)
        ->and($vehicle->fresh()->currentOwnership->user_id)->toBe($claimant->id);
});

it('keeps the seller\'s deal and the buyer\'s offers when a buyer deletes their account', function () {
    Notification::fake();
    $deal = deal();
    app(DealFlow::class)->offer($deal, $deal->buyer, 2_000_000);

    app(AccountDeletion::class)->delete($deal->buyer);

    $deal = $deal->fresh();
    expect($deal->status)->toBe(DealStatus::Cancelled)
        ->and($deal->buyer_id)->toBeNull()
        ->and($deal->buyer->name)->toBe(User::DELETED)
        ->and($deal->offers()->count())->toBe(1)
        ->and($deal->offers()->first()->user->name)->toBe(User::DELETED);
});

it('stays deleted when someone signed in with "remember me" deletes their account', function () {
    // Logging out cycles the remember-me token, which saves the user: after a delete, that would re-insert them.
    $user = User::factory()->create(['remember_token' => 'remembered-token']);

    $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertRedirect('/');

    expect(User::find($user->id))->toBeNull()->and(User::count())->toBe(0);
});

it('uses the same rules when staff delete an account', function () {
    Notification::fake();
    $deal = readyToHandOver();

    Livewire::actingAs(User::factory()->admin()->create())->test(ManageUsers::class)
        ->callTableAction('delete', $deal->buyer);

    // An agreed sale blocks deletion, from the admin as from the profile page.
    expect(User::find($deal->buyer_id))->not->toBeNull();
});

// --- Reads that must not depend on the scheduler having run ------------------------------------------------------

it('ignores an offer past its deadline even before housekeeping marks it expired', function () {
    $deal = deal();
    $deal->offers()->create(['user_id' => $deal->buyer_id, 'amount_cents' => 100000, 'status' => OfferStatus::Pending, 'expires_at' => now()->subMinute()]);

    expect($deal->fresh()->pendingOffer)->toBeNull();
});

it('counts a request past its deadline as unanswered even before housekeeping runs', function () {
    Notification::fake();
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside', 'desk@eastside.test');
    $this->travel(15)->days();

    $stats = Shop::firstWhere('email', 'desk@eastside.test')->stats();

    expect($stats['unanswered'])->toBe(1)->and($stats['response_rate'])->toBe(0);
});

// --- Scale ----------------------------------------------------------------------------------------------------

it('builds the shop directory in the same number of queries however many shops it lists', function () {
    Notification::fake();
    $queriesFor = function (int $shops) {
        foreach (range(1, $shops) as $i) {
            $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
            $verification = app(ShopVerifier::class)->request($record, $record->vehicle->owner, "Shop {$i}", "desk{$i}@shop{$i}.test");
            app(ShopVerifier::class)->answer($verification, true, 'Dee', null, '127.0.0.1');
        }
        Shop::query()->update(['vetted_at' => now()]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('shops.index'))->assertOk();

        return count(DB::getQueryLog());
    };

    $few = $queriesFor(2);
    $more = $queriesFor(4);

    expect($more)->toBe($few);
});

// --- Configuration --------------------------------------------------------------------------------------------

it('accepts extra hostnames exactly, never look-alikes', function () {
    config(['app.url' => 'https://beastmodemotors.com', 'app.trusted_hosts' => ['beast-mode.onrender.com']]);

    $patterns = app(TrustHosts::class)->hosts();
    $accepted = fn (string $host) => collect($patterns)->contains(fn ($p) => preg_match('{'.$p.'}i', $host) === 1);

    expect($accepted('beastmodemotors.com'))->toBeTrue()
        ->and($accepted('www.beastmodemotors.com'))->toBeTrue()
        ->and($accepted('beast-mode.onrender.com'))->toBeTrue()
        ->and($accepted('beast-modeXonrender.com'))->toBeFalse()
        ->and($accepted('beast-mode.onrender.com.evil.net'))->toBeFalse()
        ->and($accepted('evil.net'))->toBeFalse();
});

it('trusts the hostname Render gives the service, and uses its address until APP_URL is set', function () {
    $render = ['RENDER_EXTERNAL_HOSTNAME' => 'bmm-prod-ab12.onrender.com', 'RENDER_EXTERNAL_URL' => 'https://bmm-prod-ab12.onrender.com'];

    $fresh = configWithEnv('app.php', $render + ['APP_URL' => '', 'TRUSTED_HOSTS' => '']);
    expect($fresh['url'])->toBe('https://bmm-prod-ab12.onrender.com')
        ->and($fresh['trusted_hosts'])->toBe(['bmm-prod-ab12.onrender.com']);

    $live = configWithEnv('app.php', $render + ['APP_URL' => 'https://beastmodemotors.com', 'TRUSTED_HOSTS' => 'beastmodemotors.net, bmm-prod-ab12.onrender.com']);
    expect($live['url'])->toBe('https://beastmodemotors.com')
        ->and($live['trusted_hosts'])->toBe(['beastmodemotors.net', 'bmm-prod-ab12.onrender.com']);

    $elsewhere = configWithEnv('app.php', ['APP_URL' => '', 'TRUSTED_HOSTS' => '', 'RENDER_EXTERNAL_HOSTNAME' => '', 'RENDER_EXTERNAL_URL' => '']);
    expect($elsewhere['url'])->toBe('http://localhost')
        ->and($elsewhere['trusted_hosts'])->toBe([]);
});

it('only rebuilds the demo nightly when that is switched on explicitly', function (bool $demo, bool $nightly, string $database, bool $runs) {
    $connection = config('database.default');
    config(['passport.demo' => $demo, 'passport.demo_nightly_reset' => $nightly, 'database.default' => $database]);

    $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'demo:seed'));
    $passes = $event->filtersPass(app());
    config(['database.default' => $connection]); // the test's own transaction is rolled back on this connection

    expect($passes)->toBe($runs);
})->with([
    'demo with nightly reset' => [true, true, 'sqlite', true],
    'demo without it' => [true, false, 'sqlite', false],
    'not a demo' => [false, true, 'sqlite', false],
    'demo settings on a real database server' => [true, true, 'pgsql', false],
]);

it('sends scheduled jobs\' output to the configured log', function () {
    foreach (app(Schedule::class)->events() as $event) {
        expect($event->output)->toBe(config('passport.schedule_output'))
            ->and($event->shouldAppendOutput)->toBeTrue();
    }
});

it('refuses to wipe a database that is not a demo', function () {
    config(['passport.demo' => false]);
    $user = User::factory()->create();

    $this->artisan('demo:seed --fresh')->assertFailed();
    $this->artisan('demo:seed')->assertFailed();

    expect(User::find($user->id))->not->toBeNull();
});

it('leaves nothing personal behind that foreign keys would not catch', function () {
    $user = User::factory()->create(['email' => 'leaving@example.com']);
    $user->notify(new DealEnded('A title', 'A body'));
    DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $user->id, 'ip_address' => '203.0.113.9', 'user_agent' => 'Phone', 'payload' => '', 'last_activity' => now()->timestamp]);
    DB::table('password_reset_tokens')->insert(['email' => 'leaving@example.com', 'token' => 'x', 'created_at' => now()]);

    app(AccountDeletion::class)->delete($user);

    expect(DB::table('notifications')->where('notifiable_id', $user->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('id', 'other-device')->exists())->toBeFalse()
        ->and(DB::table('password_reset_tokens')->where('email', 'leaving@example.com')->exists())->toBeFalse();
});
