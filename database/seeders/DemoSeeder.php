<?php

namespace Database\Seeders;

use App\Enums\AcquiredVia;
use App\Enums\DealStatus;
use App\Enums\DocumentType;
use App\Enums\ExpenseCategory;
use App\Enums\FuelType;
use App\Enums\ListingStatus;
use App\Enums\OdometerSource;
use App\Enums\OfferStatus;
use App\Enums\ProviderType;
use App\Enums\ReportReason;
use App\Enums\ServiceCategory;
use App\Enums\VerificationStatus;
use App\Models\Deal;
use App\Models\Document;
use App\Models\Listing;
use App\Models\Ownership;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use App\Notifications\VerificationAnswered;
use App\Services\ListingPublisher;
use App\Services\MaintenancePlanner;
use App\Services\OwnershipTransfer;
use App\Services\ScamShield;
use App\Services\VinDecoder;
use App\Support\CarIllustration;
use App\Support\ListingFilters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A believable little world: owners with multi-year histories, cars for sale, and deals in every state.
 */
class DemoSeeder extends Seeder
{
    private string $password;

    /** @var array<string, User> */
    private array $people = [];

    /** @var array<string, string> Shop email by name, so every record from one shop links to one profile. */
    private array $shopEmails = [];

    public function __construct(
        private VinDecoder $vins,
        private MaintenancePlanner $planner,
        private ListingPublisher $publisher,
        private ScamShield $shield,
    ) {}

    public function run(): void
    {
        // Demo people get in-app notifications only: nothing is emailed, even with a real mailer configured.
        config(['queue.default' => 'sync', 'mail.default' => 'array']);

        // All or nothing: a half-built demo would otherwise count as "already seeded" on every later boot.
        DB::transaction(fn () => $this->seed());
    }

    private function seed(): void
    {
        mt_srand(8);
        $this->password = Hash::make('password');

        $this->people();

        // Alex — the demo owner: three very different cars.
        $porsche = $this->car('alex', [
            'vin' => ['WP0AB2A9', 'K', 'S'], 'year' => 2019, 'make' => 'Porsche', 'model' => '911', 'trim' => 'Carrera S',
            'body' => 'Coupe', 'engine' => '3.0L 6-cyl 443 hp', 'drivetrain' => 'RWD', 'transmission' => '8-speed PDK',
            'fuel' => FuelType::Gasoline, 'color' => 'Chalk', 'shape' => 'coupe',
        ], [
            ['jordan', AcquiredVia::New, '2019-04-12', 12, 6200, 'high', [['Porsche Coral Gables', ProviderType::Dealer, 'service@porschecoralgables.test']]],
            ['alex', AcquiredVia::Dealer, '2021-06-02', 13850, 6900, 'high', [['Eastside Euro Specialists', ProviderType::Independent, 'shop@eastsideeuro.test'], ['Porsche Coral Gables', ProviderType::Dealer, 'service@porschecoralgables.test']]],
        ], extras: [
            ['2022-09-14', 'Michelin Pilot Sport 4S ×4', ServiceCategory::Tires, 'Tire Kingdom', ProviderType::Independent, 168000, 'receipt', []],
            ['2023-03-20', 'XPEL ceramic coating & front PPF', ServiceCategory::Detailing, 'Gloss Lab Miami', ProviderType::Independent, 245000, 'receipt', []],
            ['2024-11-08', 'Front brake pads & rotors', ServiceCategory::Repair, 'Eastside Euro Specialists', ProviderType::Independent, 189500, 'verified', []],
        ]);

        $tesla = $this->car('alex', [
            'vin' => ['5YJ3E1EB', 'M', 'F'], 'year' => 2021, 'make' => 'Tesla', 'model' => 'Model 3', 'trim' => 'Long Range AWD',
            'body' => 'Sedan', 'engine' => 'Dual motor', 'drivetrain' => 'AWD', 'transmission' => 'Single-speed',
            'fuel' => FuelType::Electric, 'color' => 'Midnight Silver', 'shape' => 'sedan', 'nickname' => 'The commuter',
        ], [
            ['alex', AcquiredVia::New, '2021-03-18', 9, 11800, 'mixed', [['Tesla Service Miami', ProviderType::Dealer, 'service-miami@tesla.test'], ['Owner', ProviderType::Diy, null]]],
        ], extras: [
            ['2023-08-02', 'Replaced 12V battery', ServiceCategory::Repair, 'Tesla Mobile Service', ProviderType::Dealer, 0, 'receipt', []],
        ]);

        $tacoma = $this->car('alex', [
            'vin' => ['3TMCZ5AN', 'G', 'M'], 'year' => 2016, 'make' => 'Toyota', 'model' => 'Tacoma', 'trim' => 'TRD Off-Road Double Cab',
            'body' => 'Pickup', 'engine' => '3.5L 6-cyl 278 hp', 'drivetrain' => '4WD', 'transmission' => '6-speed automatic',
            'fuel' => FuelType::Gasoline, 'color' => 'Quicksand Beige', 'shape' => 'truck',
        ], [
            ['alex', AcquiredVia::PrivateSale, '2020-02-09', 48200, 9400, 'low', [['Owner', ProviderType::Diy, null], ['Coastal 4x4', ProviderType::Independent, 'hello@coastal4x4.test']]],
        ], extras: [
            ['2020-05-16', 'Old Man Emu 2.5" lift & BFGoodrich KO2s', ServiceCategory::Modification, 'Coastal 4x4', ProviderType::Independent, 384000, 'disputed', []],
            ['2022-07-01', 'Rear differential fluid', ServiceCategory::Maintenance, 'Owner', ProviderType::Diy, 4800, 'self', []],
        ]);

        // Sam — the demo buyer: a thin-history daily, and an MX-5 bought through the platform.
        $this->car('sam', [
            'vin' => ['19XFB2F5', 'E', 'E'], 'year' => 2014, 'make' => 'Honda', 'model' => 'Civic', 'trim' => 'EX',
            'body' => 'Sedan', 'engine' => '1.8L 4-cyl 143 hp', 'drivetrain' => 'FWD', 'transmission' => 'CVT',
            'fuel' => FuelType::Gasoline, 'color' => 'Dyno Blue', 'shape' => 'sedan',
        ], [
            ['sam', AcquiredVia::PrivateSale, '2022-10-01', 88400, 8000, 'thin', [['Jiffy Lube', ProviderType::Independent, null]]],
        ]);

        $miata = $this->car('chris', [
            'vin' => ['JM1NDAM7', 'J', '0'], 'year' => 2018, 'make' => 'Mazda', 'model' => 'MX-5 Miata', 'trim' => 'RF Grand Touring',
            'body' => 'Convertible', 'engine' => '2.0L 4-cyl 155 hp', 'drivetrain' => 'RWD', 'transmission' => '6-speed manual',
            'fuel' => FuelType::Gasoline, 'color' => 'Soul Red', 'shape' => 'coupe',
        ], [
            ['chris', AcquiredVia::New, '2018-05-20', 8, 5200, 'high', [['Mazda of South Austin', ProviderType::Dealer, 'service@mazdasouthaustin.test'], ['Zoom Zoom Garage', ProviderType::Independent, 'shop@zoomzoom.test']]],
        ], until: now()->subMonths(4));

        // Marketplace sellers.
        $bmw = $this->car('priya', [
            'vin' => ['WBA5U9C0', 'L', 'A'], 'year' => 2020, 'make' => 'BMW', 'model' => 'M340i', 'trim' => 'xDrive Sedan',
            'body' => 'Sedan', 'engine' => '3.0L 6-cyl 382 hp', 'drivetrain' => 'AWD', 'transmission' => '8-speed automatic',
            'fuel' => FuelType::Gasoline, 'color' => 'Portimao Blue', 'shape' => 'sedan',
        ], [
            ['priya', AcquiredVia::New, '2020-08-14', 6, 9100, 'high', [['BMW of Denver Downtown', ProviderType::Dealer, 'service@bmwdenver.test']]],
        ]);

        $sellers = [
            ['dana', ['4S4BTGLD', 'N', '3'], 2022, 'Subaru', 'Outback', 'Onyx Edition XT', 'Wagon', '2.4L turbo 4-cyl 260 hp', 'AWD', 'CVT', FuelType::Gasoline, 'Autumn Green', 'suv', '2022-01-22', 11, 12500, 'high', [['Subaru of Portland', ProviderType::Dealer, 'service@subaruportland.test']], 3_390_000, 'Portland', 'OR', '97214',
                "Selling because we've moved somewhere with a garage too small for it. Dealer-serviced every 6,000 miles — every visit is shop-verified in the passport. Thule crossbars and all-weather mats included. Never off-road beyond forest roads."],
            ['marcus', ['1FTEW1EG', 'H', 'F'], 2017, 'Ford', 'F-150', 'Lariat SuperCrew 4x4', 'Pickup', '3.5L EcoBoost V6 375 hp', '4WD', '10-speed automatic', FuelType::Gasoline, 'Shadow Black', 'truck', '2019-03-02', 41200, 14500, 'mixed', [['Bell Auto Care', ProviderType::Independent, 'service@bellauto.test'], ['Owner', ProviderType::Diy, null]], 2_790_000, 'Atlanta', 'GA', '30318',
                'Daily driver and occasional boat tower (5,000 lb max). Timing chain and cam phasers done at 98k with receipts. Spray-in bedliner, tonneau cover. Small dent on the tailgate, shown in photos.'],
            ['elena', ['3FMTK3SU', 'M', 'M'], 2021, 'Ford', 'Mustang Mach-E', 'Premium AWD Extended Range', 'SUV', 'Dual motor 346 hp', 'AWD', 'Single-speed', FuelType::Electric, 'Rapid Red', 'suv', '2021-05-11', 14, 9800, 'mixed', [['Ford Service San Diego', ProviderType::Dealer, 'service@fordsd.test'], ['Owner', ProviderType::Diy, null]], 3_150_000, 'San Diego', 'CA', '92104',
                'Battery health 94% (screenshot in the listing). Charged mostly at home on Level 2. Both recalls done at the dealer and linked in the passport. Includes mobile charger and a spare key card.'],
            ['tom', ['JTHBE1BL', 'F', '5'], 2015, 'Lexus', 'GS 350', 'F Sport', 'Sedan', '3.5L V6 306 hp', 'RWD', '8-speed automatic', FuelType::Gasoline, 'Ultra White', 'sedan', '2015-09-30', 18, 8300, 'high', [['Lexus of Dublin', ProviderType::Dealer, 'service@lexusdublin.test']], 2_150_000, 'Columbus', 'OH', '43215',
                'One owner from new, dealer serviced for its whole life — 13 visits, all confirmed by Lexus of Dublin. Mark Levinson audio, adaptive suspension. Retiring and downsizing to one car.'],
            ['grace', ['3VW447AU', 'K', 'M'], 2019, 'Volkswagen', 'Golf GTI', 'Autobahn', 'Hatchback', '2.0L turbo 4-cyl 228 hp', 'FWD', '6-speed manual', FuelType::Gasoline, 'Pure Grey', 'hatch', '2019-07-15', 9, 10200, 'low', [['Owner', ProviderType::Diy, null], ['Seattle Euro', ProviderType::Independent, 'info@seattleeuro.test']], 2_290_000, 'Seattle', 'WA', '98103',
                'Manual GTI with the Autobahn package. I do my own oil changes (photos of oil and filter receipts for most). Stage 1 tune was removed and the car is back to stock software.'],
        ];

        $listings = [];
        foreach ($sellers as [$who, $vin, $year, $make, $model, $trim, $body, $engine, $drive, $trans, $fuel, $color, $shape, $since, $startMiles, $perYear, $quality, $providers, $price, $city, $state, $zip, $desc]) {
            $car = $this->car($who, compact('vin', 'year', 'make', 'model', 'trim', 'body', 'engine', 'fuel', 'color', 'shape') + ['drivetrain' => $drive, 'transmission' => $trans], [
                [$who, AcquiredVia::Dealer, $since, $startMiles, $perYear, $quality, $providers],
            ]);
            $listings[$who] = $this->list($car, $price, $city, $state, $zip, $desc, daysAgo: mt_rand(2, 30));
        }

        // Recalls (illustrative demo data, not real NHTSA campaigns).
        $tesla->recalls()->create(['campaign_number' => 'DEMO-24V-017', 'component' => 'Electrical system: 12V power distribution', 'summary' => 'Demo recall: a connector in the 12V power distribution system may lose contact, which can disable some displays.', 'consequence' => 'Loss of rear-view camera image increases the risk of a crash.', 'remedy' => 'Dealers will inspect and replace the connector free of charge.', 'reported_on' => now()->subMonths(2)]);
        $porsche->recalls()->create(['campaign_number' => 'DEMO-20V-482', 'component' => 'Seats: front seat belt pretensioner', 'summary' => 'Demo recall: the pretensioner wiring may be routed too close to the seat frame.', 'remedy' => 'Wiring re-routed by a dealer.', 'reported_on' => '2020-08-03', 'resolved_at' => '2020-10-15']);
        foreach ([$porsche, $tesla, $tacoma, $bmw] as $v) {
            $v->forceFill(['recalls_checked_at' => now()->subDays(3)])->saveQuietly();
        }

        $this->expenses($porsche, 'fuel', 18, 72_00, 13.5);
        $this->expenses($tesla, 'charging', 24, 31_00, 85);
        $this->expenses($tacoma, 'fuel', 10, 88_00, 19);

        $this->documents($porsche, $tesla);

        // Alex's Porsche is for sale, with buyers talking to him.
        $porscheListing = $this->list($porsche, 11_250_000, 'Miami', 'FL', '33137',
            "Chalk 911 Carrera S on PDK with Sport Chrono, sport exhaust and front-axle lift. Second owner; the first owner had it serviced at the Porsche dealer, I've used an independent Porsche specialist since — every visit is verified in the passport and the receipts are attached.\n\nGaraged, never tracked, XPEL front and ceramic coated. Selling to make room for a Cayman GT4. Happy to meet at your inspector of choice.",
            daysAgo: 9);
        $bmwListing = $this->list($bmw, 4_390_000, 'Denver', 'CO', '80205',
            'Dealer-maintained M340i xDrive with the Executive and Driving Assistance Pro packages. Michelin all-seasons at 60% tread. No accidents, never smoked in.', daysAgo: 21);

        $this->dealOnPorsche($porscheListing);
        $this->scamAttempt($porscheListing);
        $this->agreedDealOnBmw($bmwListing);
        $this->completedMiataSale($miata);

        $listings['marcus']->reports()->create([
            'reporter_id' => $this->people['grace']->getKey(),
            'reason' => ReportReason::Misrepresented,
            'details' => 'Says no accidents, but one of the photos shows overspray on the rear bumper.',
        ]);

        // A pending verification request and a few notifications for the demo owner. It goes to the shop that
        // actually did that job: Alex's visits alternate between two shops.
        $latest = $porsche->records()->where('provider_email', 'shop@eastsideeuro.test')->first();
        $latest->update(['verified_at' => null, 'shop_id' => null]);
        $latest->verifications()->delete();
        $latest->verifications()->create([
            'requested_by' => $this->people['alex']->getKey(), 'shop_id' => Shop::forEmail('shop@eastsideeuro.test', 'Eastside Euro Specialists')->id, 'shop_name' => $latest->provider_name, 'shop_email' => 'shop@eastsideeuro.test',
            'status' => VerificationStatus::Pending, 'expires_at' => now()->addDays(12), 'created_at' => now()->subDays(2),
        ]);

        $answered = $porsche->records()->whereNotNull('verified_at')->first()->verifications()->first();
        $this->people['alex']->notifyNow(new VerificationAnswered($answered), ['database']);

        $this->shopProfiles();

        // Sam, the demo buyer, is watching for well-documented cars.
        foreach ([['min_score' => 70], ['make' => 'Porsche', 'max_price' => 90000]] as $filters) {
            $criteria = ListingFilters::from($filters);
            $this->people['sam']->savedSearches()->create(['filters' => $criteria->toArray(), 'filters_hash' => $criteria->hash(), 'notified_through' => now()]);
        }

        foreach (Listing::public()->get() as $listing) {
            $this->publisher->refreshScore($listing);
        }
    }

    private function people(): void
    {
        $people = [
            'admin' => ['Morgan Hale', 'admin@beastmodemotors.test', 'Chicago', 'IL', true],
            'alex' => ['Alex Rivera', 'owner@beastmodemotors.test', 'Miami', 'FL', false],
            'sam' => ['Sam Okafor', 'buyer@beastmodemotors.test', 'Austin', 'TX', false],
            'priya' => ['Priya Shah', 'priya@example.com', 'Denver', 'CO', false],
            'jordan' => ['Jordan Lee', 'jordan@example.com', 'Miami', 'FL', false],
            'chris' => ['Chris Morgan', 'chris@example.com', 'Austin', 'TX', false],
            'kevin' => ['Kevin Doyle', 'kdoyle.cars@example.com', null, null, false],
            'dana' => ['Dana Whitfield', 'dana@example.com', 'Portland', 'OR', false],
            'marcus' => ['Marcus Bell', 'marcus@example.com', 'Atlanta', 'GA', false],
            'elena' => ['Elena Ruiz', 'elena@example.com', 'San Diego', 'CA', false],
            'tom' => ['Tom Becker', 'tom@example.com', 'Columbus', 'OH', false],
            'grace' => ['Grace Kim', 'grace@example.com', 'Seattle', 'WA', false],
        ];

        foreach ($people as $key => [$name, $email, $city, $state, $admin]) {
            $user = User::forceCreate([
                'name' => $name, 'email' => $email, 'city' => $city, 'state' => $state, 'is_admin' => $admin,
                'password' => $this->password, 'email_verified_at' => now(),
                'created_at' => now()->subDays(mt_rand(60, 900)),
            ]);
            $this->people[$key] = $user;
        }
    }

    /**
     * Build a car with one or more ownership chapters and a generated service history.
     *
     * Each chapter: [person, acquired via, start date, start miles, miles per year, evidence quality, providers].
     *
     * @param  array<string, mixed>  $spec
     * @param  list<array>  $chapters
     * @param  list<array>  $extras  [date, title, category, provider, provider type, cost cents, evidence, tasks]
     */
    private function car(string $owner, array $spec, array $chapters, array $extras = [], ?Carbon $until = null): Vehicle
    {
        [$prefix, $yearCode, $plant] = $spec['vin'];
        $vin = $this->vins->withCheckDigit($prefix.'0'.$yearCode.$plant.str_pad((string) mt_rand(100000, 999999), 6, '0'));

        $vehicle = Vehicle::create([
            'user_id' => $this->people[$owner]->getKey(),
            'vin' => $vin, 'vin_valid' => true, 'decode_source' => 'nhtsa',
            'year' => $spec['year'], 'make' => $spec['make'], 'model' => $spec['model'], 'trim' => $spec['trim'],
            'body' => $spec['body'], 'engine' => $spec['engine'], 'drivetrain' => $spec['drivetrain'],
            'transmission' => $spec['transmission'], 'fuel_type' => $spec['fuel'], 'exterior_color' => $spec['color'],
            'nickname' => $spec['nickname'] ?? null,
        ]);

        $path = "vehicles/{$vehicle->id}/photos/studio.svg";
        VehiclePhoto::disk()->put($path, CarIllustration::svg($spec['shape'], $spec['color']));
        $vehicle->photos()->create(['path' => $path, 'position' => 0]);

        $this->planner->createDefaults($vehicle, $spec['fuel']);
        $until ??= now();

        foreach ($chapters as $i => [$person, $via, $since, $startMiles, $perYear, $quality, $providers]) {
            $start = Carbon::parse($since);
            $end = isset($chapters[$i + 1]) ? Carbon::parse($chapters[$i + 1][2]) : null;
            $endMiles = $end ? $chapters[$i + 1][3] : null;

            $ownership = $vehicle->ownerships()->create([
                'user_id' => $this->people[$person]->getKey(),
                'owner_number' => $i + 1,
                'acquired_via' => $via,
                'started_on' => $start,
                'ended_on' => $end,
                'start_mileage' => $startMiles,
                'end_mileage' => $endMiles,
            ]);
            $vehicle->readings()->create(['ownership_id' => $ownership->id, 'reading' => $startMiles, 'recorded_on' => $start, 'source' => OdometerSource::Purchase]);

            $this->generateHistory($vehicle, $ownership, $start, $end ?? $until, $startMiles, $perYear, $quality, $providers, $spec['fuel']);

            if (! $end) {
                // The owner's latest reading, with the miles for the day it was taken: records logged after it
                // carry more miles, so the history never appears to run backwards.
                $closingOn = $until->copy()->subDays(6);
                $miles = $startMiles + (int) ($perYear * $start->diffInDays($closingOn) / 365);
                $vehicle->readings()->create(['ownership_id' => $ownership->id, 'reading' => $miles, 'recorded_on' => $closingOn, 'source' => OdometerSource::Manual]);
            }
        }

        foreach ($extras as [$date, $title, $category, $provider, $type, $cost, $evidence, $tasks]) {
            $on = Carbon::parse($date);
            $ownership = $vehicle->ownerships()->where('started_on', '<=', $on)->reorder()->orderByDesc('owner_number')->first();
            $miles = $this->milesAt($vehicle, $on);
            $this->record($vehicle, $ownership, $on, $miles, $title, $category, $provider, $type, $cost, $evidence, $tasks);
        }

        // A typo'd reading on the Tacoma that a buyer would notice.
        if ($spec['make'] === 'Toyota') {
            $vehicle->readings()->create(['ownership_id' => $vehicle->ownerships()->first()->id, 'reading' => 61520, 'recorded_on' => '2021-09-03', 'source' => OdometerSource::Manual]);
        }

        $vehicle->refreshMileage();

        return $vehicle->fresh();
    }

    private function generateHistory(Vehicle $vehicle, Ownership $ownership, Carbon $from, Carbon $to, int $startMiles, int $perYear, string $quality, array $providers, FuelType $fuel): void
    {
        $electric = $fuel === FuelType::Electric;
        $stepMonths = $electric ? 12 : max(6, min(12, (int) floor(7500 / $perYear * 12)));
        $date = $from->copy()->addMonths($stepMonths)->addDays(mt_rand(-20, 20));
        $n = 0;

        while ($date->lt($to)) {
            $miles = $startMiles + (int) ($perYear * $from->diffInDays($date) / 365);
            [$provider, $type, $email] = $providers[$n % count($providers)];

            if ($quality === 'thin' && $n % 3 !== 0) {
                $date->addMonths($stepMonths);
                $n++;

                continue;
            }

            $evidence = match ($quality) {
                'high' => $type === ProviderType::Diy ? 'receipt' : ($n % 4 === 3 ? 'receipt' : 'verified'),
                'mixed' => ['verified', 'receipt', 'self', 'receipt'][$n % 4],
                default => ['self', 'receipt', 'self', 'self'][$n % 4],
            };

            if ($type === ProviderType::Diy && $evidence === 'verified') {
                $evidence = 'receipt';
            }

            [$title, $tasks, $cost] = $electric
                ? ($n % 2 ? ['Annual service & tire rotation', ['Tire rotation', 'Cabin air filter'], 18500] : ['Tire rotation & inspection', ['Tire rotation'], 6500])
                : match ($n % 4) {
                    1 => [mt_rand(0, 1) ? 'Minor service' : 'Oil service & inspection', ['Engine oil & filter', 'Tire rotation', 'Cabin air filter'], mt_rand(180, 320) * 100],
                    3 => ['Major service', ['Engine oil & filter', 'Tire rotation', 'Engine air filter', 'Cabin air filter', 'Brake fluid'], mt_rand(520, 980) * 100],
                    default => ['Oil & filter change', ['Engine oil & filter', 'Tire rotation'], mt_rand(85, 160) * 100],
                };

            if ($type === ProviderType::Diy) {
                $cost = (int) ($cost * 0.3);
                $title = 'DIY '.lcfirst($title);
            }

            $this->record($vehicle, $ownership, $date->copy(), $miles, $title, ServiceCategory::Maintenance, $provider, $type, $cost, $evidence, $tasks, $email);

            $followUp = $date->copy()->addDays(mt_rand(20, 60));

            if (! $electric && $n % 2 === 1 && mt_rand(0, 2) === 0 && $followUp->lt($to->copy()->subDays(10))) {
                // Same straight-line mileage as everything else, so an inspection never out-runs a later reading.
                $followUpMiles = $startMiles + (int) ($perYear * $from->diffInDays($followUp) / 365);
                // Owners who do their own servicing still need a state station for the inspection.
                [$station, $stationType, $stationEmail] = $type === ProviderType::Diy
                    ? ['Main Street Inspection Station', ProviderType::Independent, 'inspections@mainstreetstation.test']
                    : [$provider, $type, $email];
                $this->record($vehicle, $ownership, $followUp, $followUpMiles, 'State safety inspection', ServiceCategory::Inspection, $station, $stationType, 3500, $evidence === 'self' ? 'receipt' : $evidence, ['State inspection'], $stationEmail);
            }

            $date->addMonths($stepMonths)->addDays(mt_rand(-15, 15));
            $n++;
        }
    }

    private function record(Vehicle $vehicle, Ownership $ownership, Carbon $on, int $miles, string $title, ServiceCategory $category, string $provider, ProviderType $type, int $cost, string $evidence, array $tasks, ?string $email = null): ServiceRecord
    {
        if ($type !== ProviderType::Diy) {
            $email ??= $this->shopEmails[$provider] ?? 'service@'.Str::slug($provider, '').'.test';
            $this->shopEmails[$provider] ??= $email;
        }

        $backfilled = $evidence === 'self' && mt_rand(0, 2) === 0;
        $loggedAt = $backfilled ? $on->copy()->addMonths(mt_rand(3, 14)) : $on->copy()->addHours(mt_rand(2, 72));
        $shop = in_array($evidence, ['verified', 'disputed'], true) ? Shop::forEmail($email, $provider) : null;
        $answeredAt = $shop ? $loggedAt->copy()->addMinutes($this->answerMinutes($shop)) : null;

        $record = new ServiceRecord([
            'vehicle_id' => $vehicle->id,
            'ownership_id' => $ownership->id,
            'logged_by' => $ownership->user_id,
            'category' => $category,
            'title' => $title,
            'performed_on' => $on->toDateString(),
            'mileage' => $miles,
            'cost_cents' => $cost ?: null,
            'provider_type' => $type,
            'provider_name' => $type === ProviderType::Diy ? null : $provider,
            'provider_email' => $email,
            'verified_at' => $evidence === 'verified' ? $answeredAt : null,
            'disputed_at' => $evidence === 'disputed' ? $answeredAt : null,
            'shop_id' => $evidence === 'verified' ? $shop->id : null,
        ]);
        $record->created_at = min($loggedAt, now());
        $record->save();

        if (in_array($evidence, ['verified', 'receipt', 'disputed'], true)) {
            $this->receipt($vehicle, $record, $provider);
        }

        if (in_array($evidence, ['verified', 'disputed'], true)) {
            $record->verifications()->create([
                'requested_by' => $ownership->user_id,
                'shop_id' => $shop->id,
                'shop_name' => $provider,
                'shop_email' => $email,
                'status' => $evidence === 'verified' ? VerificationStatus::Confirmed : VerificationStatus::Disputed,
                'responder_name' => ['Dee Marshall', 'Luis Ortega', 'Kim Tran', 'Rob Feld'][mt_rand(0, 3)].' (service advisor)',
                'response_note' => $evidence === 'disputed' ? 'We fitted the tires and did the alignment, but the lift kit was installed elsewhere. Our invoice was $1,140.' : null,
                'responder_ip' => '203.0.113.'.mt_rand(2, 250),
                'responded_at' => $record->verified_at ?? $record->disputed_at,
                'expires_at' => $loggedAt->copy()->addDays(14),
                'created_at' => $loggedAt,
            ]);
        }

        if ($tasks) {
            $this->planner->applyRecord($record, $vehicle->reminders()->whereIn('task', $tasks)->pluck('id')->all());
        }

        return $record;
    }

    /**
     * Each shop has its own habits: dealers answer within hours, small shops can take a day or two.
     */
    private function answerMinutes(Shop $shop): int
    {
        $base = crc32($shop->email) % 3;

        return [mt_rand(40, 360), mt_rand(240, 1300), mt_rand(1200, 3600)][$base];
    }

    private function shopProfiles(): void
    {
        foreach ([
            'shop@eastsideeuro.test' => ['Miami', 'FL', '(305) 555-0144', 'https://eastsideeuro.example', ['Porsche', 'BMW', 'Audi', 'Pre-purchase inspections'], "Independent Porsche and BMW specialists since 2009. Factory-trained technicians, OEM parts, and we photograph every job for the customer's records."],
            'service@porschecoralgables.test' => ['Coral Gables', 'FL', '(305) 555-0110', null, ['Porsche'], null],
            'service@bmwdenver.test' => ['Denver', 'CO', '(303) 555-0190', null, ['BMW', 'MINI'], 'Franchise BMW service department.'],
            'service@lexusdublin.test' => ['Dublin', 'OH', '(614) 555-0162', null, ['Lexus', 'Toyota'], null],
            'shop@zoomzoom.test' => ['Austin', 'TX', '(512) 555-0133', 'https://zoomzoomgarage.example', ['Mazda', 'Miata', 'Track prep'], 'Two-bay Mazda specialist. We love Miatas.'],
            'service@subaruportland.test' => ['Portland', 'OR', null, null, ['Subaru'], null],
            'service@bellauto.test' => ['Atlanta', 'GA', '(404) 555-0108', null, ['Ford', 'Trucks', 'Diesel'], null],
            'service@fordsd.test' => ['San Diego', 'CA', null, null, ['Ford', 'EV'], null],
        ] as $email => [$city, $state, $phone, $website, $specialties, $about]) {
            Shop::where('email', $email)->update([
                'city' => $city, 'state' => $state, 'phone' => $phone, 'website' => $website,
                'specialties' => json_encode($specialties), 'about' => $about, 'profile_completed_at' => now()->subDays(mt_rand(5, 200)), 'name_confirmed_at' => now()->subDays(200),
                // Each demo car has one owner per shop, so the directory's several-owners rule would hide them all.
                'vetted_at' => now()->subDays(mt_rand(1, 60)),
            ]);
        }
    }

    private function milesAt(Vehicle $vehicle, Carbon $on): int
    {
        $before = $vehicle->readings()->where('recorded_on', '<=', $on)->reorder()->orderByDesc('recorded_on')->first();
        $after = $vehicle->readings()->where('recorded_on', '>', $on)->orderBy('recorded_on')->first();

        if ($before && $after) {
            $span = max(1, $before->recorded_on->diffInDays($after->recorded_on));

            return (int) ($before->reading + ($after->reading - $before->reading) * $before->recorded_on->diffInDays($on) / $span);
        }

        return ($before ?? $after)?->reading ?? 0;
    }

    private function receipt(Vehicle $vehicle, ServiceRecord $record, string $provider): void
    {
        $path = "vehicles/{$vehicle->id}/documents/receipt-{$record->id}.pdf";
        Document::disk()->put($path, $this->invoicePdf($provider, $record));

        $record->documents()->create([
            'vehicle_id' => $vehicle->id,
            'ownership_id' => $record->ownership_id,
            'uploaded_by' => $record->logged_by,
            'type' => DocumentType::Receipt,
            'name' => 'Invoice '.$record->performed_on->format('Y-m-d').' '.$provider,
            'path' => $path,
            'mime' => 'application/pdf',
            'size' => Document::disk()->size($path),
            'created_at' => $record->created_at,
        ]);
    }

    /**
     * A tiny hand-built one-page PDF invoice (fast enough to make dozens while seeding).
     */
    private function invoicePdf(string $provider, ServiceRecord $record): string
    {
        $esc = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        $lines = [
            [20, 72, 740, $provider],
            [11, 72, 715, 'INVOICE  #'.(10000 + $record->id).'   Date: '.$record->performed_on->format('m/d/Y')],
            [11, 72, 680, 'Vehicle: '.$record->vehicle->fullTitle()],
            [11, 72, 662, 'VIN: '.$record->vehicle->vin.'    Odometer in: '.number_format($record->mileage)],
            [12, 72, 625, 'Work performed: '.$record->title],
            [12, 72, 590, 'Total: '.money((int) $record->cost_cents, true)],
            [9, 72, 540, 'Demo document generated for Beast Mode Motors.'],
        ];
        $content = collect($lines)->map(fn ($l) => "BT /F1 {$l[0]} Tf {$l[1]} {$l[2]} Td ({$esc($l[3])}) Tj ET")->implode("\n");

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function expenses(Vehicle $vehicle, string $kind, int $count, int $avgCents, float $avgVolume): void
    {
        $ownership = $vehicle->currentOwnership;
        $odometer = $vehicle->current_mileage - $count * 280;

        foreach (range($count, 1) as $i) {
            $odometer += mt_rand(220, 340);
            $vehicle->expenses()->create([
                'ownership_id' => $ownership->id,
                'category' => $kind === 'fuel' ? ExpenseCategory::Fuel : ExpenseCategory::Charging,
                'amount_cents' => (int) ($avgCents * mt_rand(85, 115) / 100),
                'spent_on' => now()->subDays($i * 15 + mt_rand(0, 5)),
                'odometer' => min($odometer, $vehicle->current_mileage),
                'volume' => round($avgVolume * mt_rand(85, 115) / 100, 2),
            ]);
        }

        foreach ([[ExpenseCategory::Insurance, 98_000, 6], [ExpenseCategory::Insurance, 98_000, 0], [ExpenseCategory::Registration, 32_500, 3], [ExpenseCategory::Cleaning, 6_500, 1]] as [$cat, $amount, $monthsAgo]) {
            $vehicle->expenses()->create(['ownership_id' => $ownership->id, 'category' => $cat, 'amount_cents' => $amount, 'spent_on' => now()->subMonths($monthsAgo)->subDays(4)]);
        }
    }

    private function documents(Vehicle $porsche, Vehicle $tesla): void
    {
        foreach ([
            [$porsche, DocumentType::Title, 'Florida certificate of title', null],
            [$porsche, DocumentType::Warranty, 'Porsche Approved warranty', now()->addMonths(5)],
            [$porsche, DocumentType::Manual, "Owner's manual & maintenance booklet", null],
            [$tesla, DocumentType::Insurance, 'Insurance card — State Farm', now()->addDays(18)],
            [$tesla, DocumentType::Registration, 'Florida registration', now()->addMonths(7)],
        ] as [$vehicle, $type, $name, $expires]) {
            $path = "vehicles/{$vehicle->id}/documents/".str($name)->slug().'.pdf';
            Document::disk()->put($path, $this->invoicePdf($name, $vehicle->records()->first()));
            $vehicle->documents()->create([
                'ownership_id' => $vehicle->currentOwnership->id,
                'uploaded_by' => $vehicle->user_id,
                'type' => $type, 'name' => $name, 'path' => $path, 'mime' => 'application/pdf',
                'size' => Document::disk()->size($path), 'expires_on' => $expires,
            ]);
        }
    }

    private function list(Vehicle $vehicle, int $price, string $city, string $state, string $zip, string $description, int $daysAgo): Listing
    {
        $listing = $vehicle->listings()->create([
            'seller_id' => $vehicle->user_id, 'status' => ListingStatus::Draft, 'price_cents' => $price,
            'mileage' => $vehicle->current_mileage, 'city' => $city, 'state' => $state, 'zip' => $zip, 'description' => $description,
        ]);
        $this->publisher->publish($listing);
        $listing->refresh();
        $listing->update(['published_at' => now()->subDays($daysAgo), 'views' => mt_rand(40, 900)]);
        $listing->shareLink->update(['created_at' => now()->subDays($daysAgo), 'views' => mt_rand(10, 300)]);

        return $listing->fresh();
    }

    private function message(Deal $deal, ?User $from, string $body, Carbon $at, bool $read = true): void
    {
        $flags = $from ? $this->shield->scan($body) : [];
        $deal->messages()->create([
            'user_id' => $from?->getKey(), 'body' => $body, 'risk_flags' => $flags ?: null,
            'read_at' => $read ? $at->copy()->addMinutes(20) : null, 'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    private function dealOnPorsche(Listing $listing): void
    {
        [$alex, $sam] = [$this->people['alex'], $this->people['sam']];
        $deal = Deal::create(['listing_id' => $listing->id, 'vehicle_id' => $listing->vehicle_id, 'buyer_id' => $sam->id, 'seller_id' => $alex->id, 'status' => DealStatus::Open]);
        $t = now()->subDays(6)->setTime(18, 12);

        $this->message($deal, $sam, "Hi Alex — love the spec on this one. Is the PDK service history all in the passport? I'm in Austin but can fly down for a viewing.", $t);
        $this->message($deal, $alex, 'Hey Sam, yes — everything since new is in there, and the shop has verified each visit. The brake job last November is the most recent big item. Happy to meet at Eastside Euro so you can put it on the lift.', $t->copy()->addHours(3));
        $this->message($deal, $sam, 'Great. Before I book flights — would you consider $104k? Tires are getting on a bit.', $t->copy()->addDay());

        $deal->offers()->create(['user_id' => $sam->id, 'amount_cents' => 10_400_000, 'note' => 'Tires are about 60% — happy to move quickly.', 'status' => OfferStatus::Countered, 'expires_at' => $t->copy()->addDays(4), 'responded_at' => $t->copy()->addDays(1)->addHours(5), 'created_at' => $t->copy()->addDay()]);
        $this->message($deal, null, 'Buyer offered $104,000 — “Tires are about 60% — happy to move quickly.”', $t->copy()->addDay());
        $deal->offers()->create(['user_id' => $alex->id, 'amount_cents' => 10_950_000, 'note' => 'Meet you partway — includes the car cover and battery tender.', 'status' => OfferStatus::Pending, 'expires_at' => now()->addHours(52), 'created_at' => now()->subHours(20)]);
        $this->message($deal, null, 'Seller countered with $109,500 — “Meet you partway — includes the car cover and battery tender.”', now()->subHours(20));
        $deal->forceFill(['updated_at' => now()->subHours(20)])->saveQuietly();
    }

    private function scamAttempt(Listing $listing): void
    {
        $kevin = $this->people['kevin'];
        $deal = Deal::create(['listing_id' => $listing->id, 'vehicle_id' => $listing->vehicle_id, 'buyer_id' => $kevin->id, 'seller_id' => $listing->seller_id, 'status' => DealStatus::Open]);
        $t = now()->subDays(2)->setTime(3, 41);

        $this->message($deal, $kevin, 'Hello is this still available? I will buy at your asking price no problem.', $t, read: false);
        $this->message($deal, $kevin, "I'm currently deployed overseas so my shipping agent will collect the car. I will mail a cashier's check for \$118,000 to cover his fee, please send the difference back to him by wire. Also I sent you a 6 digit code to confirm you are real, send it to me.", $t->copy()->addMinutes(4), read: false);
    }

    private function agreedDealOnBmw(Listing $listing): void
    {
        [$priya, $sam] = [$this->people['priya'], $this->people['sam']];
        $deal = Deal::create(['listing_id' => $listing->id, 'vehicle_id' => $listing->vehicle_id, 'buyer_id' => $sam->id, 'seller_id' => $priya->id, 'status' => DealStatus::Open]);
        $t = now()->subDays(12)->setTime(10, 5);

        $this->message($deal, $sam, 'Hi Priya, is the M340i still available? Any curb rash on the wheels?', $t);
        $this->message($deal, $priya, 'Hi! Yes it is. One small scuff on the rear left wheel, otherwise clean. Every service was at BMW Denver — all verified on the passport.', $t->copy()->addHours(2));
        $deal->offers()->create(['user_id' => $sam->id, 'amount_cents' => 4_100_000, 'status' => OfferStatus::Countered, 'expires_at' => $t->copy()->addDays(3), 'responded_at' => $t->copy()->addDay(), 'created_at' => $t->copy()->addHours(5)]);
        $this->message($deal, null, 'Buyer offered $41,000.', $t->copy()->addHours(5));
        $deal->offers()->create(['user_id' => $priya->id, 'amount_cents' => 4_180_000, 'status' => OfferStatus::Accepted, 'expires_at' => $t->copy()->addDays(4), 'responded_at' => $t->copy()->addDays(1)->addHours(6), 'created_at' => $t->copy()->addDay()]);
        $this->message($deal, null, 'Seller countered with $41,800.', $t->copy()->addDay());
        $this->message($deal, null, 'Price agreed at $41,800. Next: inspection and handover.', $t->copy()->addDays(1)->addHours(6));
        $this->message($deal, $sam, "Deal! I've booked a pre-purchase inspection at Mile High Motorwerks for Saturday at 10am.", $t->copy()->addDays(1)->addHours(7));

        $deal->update([
            'status' => DealStatus::Agreed, 'agreed_price_cents' => 4_180_000, 'agreed_at' => $t->copy()->addDays(1)->addHours(6),
            'handover' => ['bill_of_sale' => now()->subDays(2)->toIso8601String(), 'vin_checked' => now()->subDays(3)->toIso8601String()],
        ]);
        $deal->inspection()->create([
            'scheduled_for' => now()->subDays(3)->setTime(10, 0), 'location' => 'Mile High Motorwerks, Denver', 'inspector' => 'Ray Castillo',
            'results' => collect(config('passport.inspection'))->flatMap(fn ($items) => array_keys($items))
                ->mapWithKeys(fn ($key) => [$key => ['result' => in_array($key, ['tires', 'brakes'], true) ? 'attention' : 'pass', 'note' => match ($key) {
                    'tires' => 'Rears at 5/32", budget for replacement within a year.',
                    'brakes' => 'Front pads ~30%.',
                    default => '',
                }]])->all(),
            'summary' => 'Clean car. No codes, no leaks, drives straight. Tires and front pads are the only upcoming costs.',
            'completed_at' => now()->subDays(3)->setTime(11, 40),
        ]);
        $this->message($deal, null, 'Buyer completed the inspection: 16 good, 2 need attention, 0 problems.', now()->subDays(3)->setTime(11, 40));
        $listing->update(['status' => ListingStatus::Pending]);
    }

    private function completedMiataSale(Vehicle $miata): void
    {
        [$chris, $sam] = [$this->people['chris'], $this->people['sam']];
        $listing = $miata->listings()->create([
            'seller_id' => $chris->id, 'status' => ListingStatus::Pending, 'price_cents' => 2_450_000, 'mileage' => $miata->current_mileage,
            'city' => 'Austin', 'state' => 'TX', 'description' => 'Soul Red RF GT, six-speed manual. Dealer then independent specialist serviced. Selling because a baby seat does not fit.',
            'published_at' => now()->subMonths(5),
        ]);
        $deal = Deal::create([
            'listing_id' => $listing->id, 'vehicle_id' => $miata->id, 'buyer_id' => $sam->id, 'seller_id' => $chris->id,
            'status' => DealStatus::Agreed, 'agreed_price_cents' => 2_380_000, 'agreed_at' => now()->subMonths(4)->subDays(6),
            'sale_mileage' => $miata->current_mileage + 40, 'odometer_status' => 'actual', 'buyer_confirmed_at' => now()->subMonths(4), 'seller_confirmed_at' => now()->subMonths(4),
            'handover' => collect(config('passport.handover'))->map(fn () => now()->subMonths(4)->toIso8601String())->all(),
        ]);
        $this->message($deal, $sam, 'Hi Chris — is the RF still available? Could I see it this weekend?', now()->subMonths(4)->subDays(10));
        $this->message($deal, $chris, 'Yes! Saturday morning works. Bring your own mechanic if you like.', now()->subMonths(4)->subDays(10)->addHours(2));

        app(OwnershipTransfer::class)->complete($deal);

        // Backdate the transfer so the new owner has a few months of history.
        $soldOn = now()->subMonths(4);
        $miata->ownerships()->where('owner_number', 1)->update(['ended_on' => $soldOn]);
        $miata->ownerships()->where('owner_number', 2)->update(['started_on' => $soldOn]);
        $miata->readings()->where('source', OdometerSource::Sale)->update(['recorded_on' => $soldOn]);
        $deal->update(['completed_at' => $soldOn]);
        $deal->messages()->whereNull('user_id')->update(['created_at' => $soldOn]);
        $listing->update(['sold_at' => $soldOn]);

        // Notifications are sent after the seed commits; clear these inboxes at this point in that same queue,
        // so everything up to and including the sale is gone and anything seeded later stays.
        DB::afterCommit(function () use ($sam, $chris) {
            $sam->notifications()->delete();
            $chris->notifications()->delete();
        });

        // Sam has driven it since and logged a service.
        $now = Ownership::where('vehicle_id', $miata->id)->where('owner_number', 2)->first();
        $this->record($miata->fresh(), $now, now()->subMonth(), $miata->fresh()->current_mileage + 2100, 'Oil & filter change', ServiceCategory::Maintenance, 'Zoom Zoom Garage', ProviderType::Independent, 9800, 'verified', ['Engine oil & filter', 'Tire rotation'], 'shop@zoomzoom.test');
        $miata->readings()->create(['ownership_id' => $now->id, 'reading' => $miata->fresh()->current_mileage + 900, 'recorded_on' => now()->subDays(4), 'source' => OdometerSource::Manual]);
        $miata->refreshMileage();
    }
}
