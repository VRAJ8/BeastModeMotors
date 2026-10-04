<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Seeds a realistic exotic & performance line-up.
 */
class ShowroomSeeder extends Seeder
{
    public function run(): void
    {
        $brands = collect([
            ['Lamborghini', 'Italy', 1963, 'Raging bulls from Sant\'Agata Bolognese — naturally aspirated V12 theatre and outrageous design.'],
            ['Ferrari', 'Italy', 1939, 'Maranello\'s prancing horse: seventy years of racing pedigree distilled into road cars.'],
            ['McLaren', 'United Kingdom', 1963, 'Woking-built carbon-tubbed supercars engineered with Formula 1 precision.'],
            ['Porsche', 'Germany', 1931, 'Stuttgart\'s benchmark sports cars, from the everyday 911 to track-bred GT models.'],
            ['Aston Martin', 'United Kingdom', 1913, 'British grand tourers that pair muscular V12s with hand-stitched luxury.'],
            ['Rolls-Royce', 'United Kingdom', 1904, 'The pinnacle of automotive luxury — effortless, silent, bespoke.'],
            ['Bentley', 'United Kingdom', 1919, 'Crewe\'s fusion of handcrafted interiors and formidable W12 and V8 performance.'],
            ['Mercedes-AMG', 'Germany', 1967, 'Affalterbach\'s "one man, one engine" philosophy applied to sports cars and super-SUVs.'],
            ['Bugatti', 'France', 1909, 'Molsheim hypercars that redefine what is physically possible on four wheels.'],
            ['BMW M', 'Germany', 1972, 'Motorsport-derived M cars that are as happy on a track day as on the commute.'],
        ])->mapWithKeys(fn (array $b) => [$b[0] => Brand::create([
            'name' => $b[0],
            'country' => $b[1],
            'founded_year' => $b[2],
            'description' => $b[3],
        ])]);

        foreach ($this->vehicles() as $i => $data) {
            $brand = $brands[$data['brand']];
            unset($data['brand']);

            $vehicle = new Vehicle($data + [
                'interior_color' => 'Nero Ade',
                'published_at' => now()->subDays(40 - $i * 2),
                'status' => 'available',
            ]);
            $vehicle->brand()->associate($brand);
            $vehicle->views = random_int(40, 2400);
            $vehicle->save();
        }
    }

    protected static function img(string ...$ids): array
    {
        return array_map(fn ($id) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w=1600&q=80", $ids);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function vehicles(): array
    {
        return [
            [
                'brand' => 'Lamborghini', 'model' => 'Aventador', 'trim' => 'SVJ', 'year' => 2021, 'price' => 589000, 'mileage' => 2100,
                'body_type' => 'coupe', 'condition' => 'certified', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'drivetrain' => 'awd',
                'engine' => '6.5L Naturally Aspirated V12', 'horsepower' => 759, 'torque' => 531, 'zero_to_sixty' => 2.8, 'top_speed' => 217,
                'exterior_color' => 'Verde Mantis', 'vin' => 'ZHWUM6ZD5MLA10421', 'is_featured' => true,
                'description' => 'The last of the great naturally aspirated V12 Lamborghinis. Aerodinamica Lamborghini Attiva 2.0, a Nürburgring lap record pedigree and a soundtrack that will rearrange your priorities.',
                'features' => ['ALA 2.0 active aerodynamics', 'Carbon ceramic brakes', 'Front axle lift', 'Sensonum premium audio', 'Carbon fibre engine bay', 'Rear-view camera'],
                'images' => self::img('1544636331-e26879cd4d9b', '1621135802920-133df287f89c', '1617814076367-b759c7d7e738'),
            ],
            [
                'brand' => 'Ferrari', 'model' => '488', 'trim' => 'GTB', 'year' => 2019, 'price' => 279000, 'mileage' => 8400,
                'body_type' => 'coupe', 'condition' => 'pre_owned', 'fuel_type' => 'petrol', 'transmission' => 'dual_clutch', 'drivetrain' => 'rwd',
                'engine' => '3.9L Twin-Turbo V8', 'horsepower' => 661, 'torque' => 561, 'zero_to_sixty' => 3.0, 'top_speed' => 205,
                'exterior_color' => 'Rosso Corsa', 'vin' => 'ZFF79ALA4K0241187', 'is_featured' => true,
                'description' => 'Ferrari\'s International Engine of the Year winning twin-turbo V8 in its most celebrated chassis. Fresh service, full Ferrari history and Daytona racing seats.',
                'features' => ['Daytona racing seats', 'Carbon fibre steering wheel with LEDs', 'Front lift system', 'Scuderia shields', 'JBL professional audio'],
                'images' => self::img('1583121274602-3e2820c69888', '1592198084033-aade902d1aae'),
            ],
            [
                'brand' => 'McLaren', 'model' => '720S', 'trim' => 'Performance', 'year' => 2020, 'price' => 299500, 'mileage' => 5600,
                'body_type' => 'coupe', 'condition' => 'certified', 'fuel_type' => 'petrol', 'transmission' => 'dual_clutch', 'drivetrain' => 'rwd',
                'engine' => '4.0L Twin-Turbo V8', 'horsepower' => 710, 'torque' => 568, 'zero_to_sixty' => 2.8, 'top_speed' => 212,
                'exterior_color' => 'Papaya Spark', 'vin' => 'SBM14DCA7LW004187', 'is_featured' => true,
                'description' => 'Monocage II carbon tub, dihedral doors and a chassis that reads the road like braille. One of the most complete supercars ever built.',
                'features' => ['Proactive Chassis Control II', 'Folding driver display', 'Bowers & Wilkins audio', 'Carbon exterior pack', 'Vehicle lift'],
                'images' => self::img('1503736334956-4c8f8e92946d', '1631295868223-63265b40d9e4'),
            ],
            [
                'brand' => 'Porsche', 'model' => '911', 'trim' => 'GT3 RS', 'year' => 2024, 'price' => 389000, 'mileage' => 320,
                'body_type' => 'coupe', 'condition' => 'new', 'fuel_type' => 'petrol', 'transmission' => 'dual_clutch', 'drivetrain' => 'rwd',
                'engine' => '4.0L Naturally Aspirated Flat-6', 'horsepower' => 518, 'torque' => 342, 'zero_to_sixty' => 3.0, 'top_speed' => 184,
                'exterior_color' => 'Shark Blue', 'vin' => 'WP0AF2A93RS273015', 'is_featured' => true,
                'description' => 'A race car with number plates. DRS-style rear wing, 9,000 rpm flat-six and Weissach package — the sharpest 911 Porsche has ever sold.',
                'features' => ['Weissach package', 'Magnesium wheels', 'Clubsport package', 'Front axle lift', 'Full bucket seats', 'Track Precision app'],
                'images' => self::img('1503376780353-7e6692767b70', '1603584173870-7f23fdae1b7a'),
            ],
            [
                'brand' => 'Porsche', 'model' => 'Taycan', 'trim' => 'Turbo S', 'year' => 2023, 'price' => 174900, 'mileage' => 6900,
                'body_type' => 'sedan', 'condition' => 'certified', 'fuel_type' => 'electric', 'transmission' => 'automatic', 'drivetrain' => 'awd',
                'engine' => 'Dual Permanent-Magnet Motors', 'horsepower' => 750, 'torque' => 774, 'zero_to_sixty' => 2.6, 'top_speed' => 161,
                'exterior_color' => 'Frozen Blue', 'vin' => 'WP0AB2Y15PSA35012',
                'description' => 'Electric, silent, and devastatingly quick. Launch control delivers 750 hp of overboost and a 270 kW charging curve keeps road trips civilised.',
                'features' => ['Performance Battery Plus', 'Rear-axle steering', 'Burmester 3D audio', 'Passenger display', 'Panoramic roof'],
                'images' => self::img('1614162692292-7ac56d7f7f1e'),
            ],
            [
                'brand' => 'Aston Martin', 'model' => 'DBS', 'trim' => 'Superleggera', 'year' => 2021, 'price' => 249000, 'mileage' => 7300,
                'body_type' => 'grand_tourer', 'condition' => 'pre_owned', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'drivetrain' => 'rwd',
                'engine' => '5.2L Twin-Turbo V12', 'horsepower' => 715, 'torque' => 664, 'zero_to_sixty' => 3.3, 'top_speed' => 211,
                'exterior_color' => 'Magnetic Silver', 'vin' => 'SCFRMFCW5MGM04421', 'is_featured' => true,
                'description' => 'A brute in a bespoke suit. 664 lb-ft of V12 torque wrapped in carbon fibre bodywork with an interior trimmed by hand in Gaydon.',
                'features' => ['Carbon fibre roof', 'Bang & Olufsen BeoSound', 'Heated & ventilated seats', '360° camera', 'Carbon ceramic brakes'],
                'images' => self::img('1580414057403-c5f451f30e1c'),
            ],
            [
                'brand' => 'Rolls-Royce', 'model' => 'Cullinan', 'trim' => 'Black Badge', 'year' => 2023, 'price' => 459000, 'mileage' => 3100,
                'body_type' => 'suv', 'condition' => 'certified', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'drivetrain' => 'awd',
                'engine' => '6.75L Twin-Turbo V12', 'horsepower' => 591, 'torque' => 664, 'zero_to_sixty' => 4.5, 'top_speed' => 155,
                'exterior_color' => 'Diamond Black', 'vin' => 'SLATV8C09PU217744',
                'description' => 'The darker alter-ego of the world\'s most luxurious SUV. Starlight headliner, viewing suite and a magic-carpet ride on any surface.',
                'features' => ['Starlight headliner', 'Viewing Suite', 'Rear theatre configuration', 'Bespoke audio', 'Night vision', 'Lambswool floor mats'],
                'images' => self::img('1606664515524-ed2f786a0bd6'),
            ],
            [
                'brand' => 'Bentley', 'model' => 'Continental GT', 'trim' => 'Speed', 'year' => 2022, 'price' => 289000, 'mileage' => 9200,
                'body_type' => 'grand_tourer', 'condition' => 'pre_owned', 'fuel_type' => 'petrol', 'transmission' => 'dual_clutch', 'drivetrain' => 'awd',
                'engine' => '6.0L Twin-Turbo W12', 'horsepower' => 650, 'torque' => 664, 'zero_to_sixty' => 3.5, 'top_speed' => 208,
                'exterior_color' => 'Beluga', 'vin' => 'SCBCG2ZG8NC093321',
                'description' => 'Continent-crushing pace with a cabin that smells of leather and polished wood. The W12 Speed is the most dynamic Continental ever made.',
                'features' => ['Rotating display', 'Naim for Bentley audio', 'Mulliner driving spec', 'All-wheel steering', 'Massage seats'],
                'images' => self::img('1580273916550-e323be2ae537'),
            ],
            [
                'brand' => 'Mercedes-AMG', 'model' => 'G 63', 'trim' => null, 'year' => 2024, 'price' => 219000, 'mileage' => 1500,
                'body_type' => 'suv', 'condition' => 'new', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'drivetrain' => 'awd',
                'engine' => '4.0L Twin-Turbo V8', 'horsepower' => 577, 'torque' => 627, 'zero_to_sixty' => 4.5, 'top_speed' => 149,
                'exterior_color' => 'Obsidian Black', 'vin' => 'W1NYC7HJ1RX481120',
                'description' => 'An icon since 1979. Three locking differentials, hand-built AMG V8 and a presence nothing else on the road can match.',
                'features' => ['AMG Night package', 'Burmester surround sound', 'Three locking differentials', 'Active multicontour seats', 'Widescreen cockpit'],
                'images' => self::img('1520031441872-265e4ff70366'),
            ],
            [
                'brand' => 'Mercedes-AMG', 'model' => 'GT', 'trim' => 'Black Series', 'year' => 2021, 'price' => 425000, 'mileage' => 1900,
                'body_type' => 'coupe', 'condition' => 'certified', 'fuel_type' => 'petrol', 'transmission' => 'dual_clutch', 'drivetrain' => 'rwd',
                'engine' => '4.0L Twin-Turbo Flat-Plane V8', 'horsepower' => 720, 'torque' => 590, 'zero_to_sixty' => 3.1, 'top_speed' => 202,
                'exterior_color' => 'Magmabeam Orange', 'vin' => 'WDDYJ8KA0MA039876',
                'description' => 'The Nürburgring production-car record holder. Flat-plane crank V8, manually adjustable aero and coilovers — a GT3 car you can register.',
                'features' => ['Track package with roll cage', 'Adjustable rear wing', 'Carbon fibre bonnet', 'AMG coilover suspension', 'Carbon ceramic brakes'],
                'images' => self::img('1617814076367-b759c7d7e738'),
            ],
            [
                'brand' => 'Bugatti', 'model' => 'Chiron', 'trim' => 'Sport', 'year' => 2020, 'price' => 3250000, 'mileage' => 900,
                'body_type' => 'hypercar', 'condition' => 'certified', 'fuel_type' => 'petrol', 'transmission' => 'dual_clutch', 'drivetrain' => 'awd',
                'engine' => '8.0L Quad-Turbo W16', 'horsepower' => 1479, 'torque' => 1180, 'zero_to_sixty' => 2.4, 'top_speed' => 261,
                'exterior_color' => 'Atlantic Blue / Carbon', 'vin' => 'VF9SP3V36LM795012', 'is_featured' => true,
                'description' => 'Sixteen cylinders, four turbochargers, and 1,479 horsepower. Fewer than 60 Chiron Sports were built; this one has under 1,000 miles.',
                'features' => ['Sky View glass roof', 'Carbon fibre wipers', 'Titanium exhaust', 'Accuphase audio', 'Top-speed key'],
                'images' => self::img('1600712242805-5f78671b24da'),
            ],
            [
                'brand' => 'BMW M', 'model' => 'M4', 'trim' => 'Competition xDrive', 'year' => 2024, 'price' => 89900, 'mileage' => 2400,
                'body_type' => 'coupe', 'condition' => 'new', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'drivetrain' => 'awd',
                'engine' => '3.0L Twin-Turbo Inline-6', 'horsepower' => 523, 'torque' => 479, 'zero_to_sixty' => 3.4, 'top_speed' => 180,
                'exterior_color' => 'Isle of Man Green', 'vin' => 'WBS33AZ0XRCP41188',
                'description' => 'The everyday supercar slayer. All-wheel drive with a 2WD mode for the brave, M Carbon bucket seats and a laptimer built in.',
                'features' => ['M Carbon bucket seats', 'M Drive Professional', 'Harman Kardon audio', 'Head-up display', 'Carbon roof'],
                'images' => self::img('1555215695-3004980ad54e'),
            ],
            [
                'brand' => 'Lamborghini', 'model' => 'Urus', 'trim' => 'Performante', 'year' => 2023, 'price' => 279000, 'mileage' => 4200,
                'body_type' => 'suv', 'condition' => 'certified', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'drivetrain' => 'awd',
                'engine' => '4.0L Twin-Turbo V8', 'horsepower' => 657, 'torque' => 627, 'zero_to_sixty' => 3.1, 'top_speed' => 190,
                'exterior_color' => 'Giallo Auge', 'vin' => 'ZPBUB3ZL2PLA22517',
                'description' => 'The Super SUV goes rally mode. Lowered, lighter, with steel springs and the Rally drive mode for unpaved roads.',
                'features' => ['Rally driving mode', 'Akrapovič titanium exhaust', 'Carbon fibre bonnet', 'Bang & Olufsen 3D audio', 'Night vision'],
                'images' => self::img('1621135802920-133df287f89c'),
            ],
            [
                'brand' => 'Ferrari', 'model' => 'SF90', 'trim' => 'Stradale', 'year' => 2022, 'price' => 549000, 'mileage' => 2800,
                'body_type' => 'hypercar', 'condition' => 'certified', 'fuel_type' => 'hybrid', 'transmission' => 'dual_clutch', 'drivetrain' => 'awd',
                'engine' => '4.0L Twin-Turbo V8 + 3 Electric Motors', 'horsepower' => 986, 'torque' => 590, 'zero_to_sixty' => 2.5, 'top_speed' => 211,
                'exterior_color' => 'Giallo Modena', 'vin' => 'ZFF95NLA8N0273310',
                'description' => 'Ferrari\'s first series-production plug-in hybrid, and the most powerful road car Maranello has ever built. 16 miles of silent eDrive, then pure violence.',
                'features' => ['Assetto Fiorano package', 'Multimatic shocks', 'Titanium springs', 'eDrive electric mode', 'Head-up display'],
                'images' => self::img('1592198084033-aade902d1aae'),
            ],
            [
                'brand' => 'McLaren', 'model' => 'Artura', 'trim' => null, 'year' => 2024, 'price' => 247500, 'mileage' => 900,
                'body_type' => 'coupe', 'condition' => 'new', 'fuel_type' => 'hybrid', 'transmission' => 'dual_clutch', 'drivetrain' => 'rwd',
                'engine' => '3.0L Twin-Turbo V6 Hybrid', 'horsepower' => 690, 'torque' => 531, 'zero_to_sixty' => 3.0, 'top_speed' => 205,
                'exterior_color' => 'Ember Orange', 'vin' => 'SBM16AEA2RW001934',
                'description' => 'McLaren\'s new-generation high-performance hybrid. Lighter than you expect, quicker than you need, silent in town.',
                'features' => ['Clubsport seats', 'Bowers & Wilkins audio', 'Electric mode', 'Vehicle lift', 'Carbon fibre interior upgrade'],
                'images' => self::img('1631295868223-63265b40d9e4'),
            ],
            [
                'brand' => 'Porsche', 'model' => '911', 'trim' => 'Turbo S Cabriolet', 'year' => 2022, 'price' => 239900, 'mileage' => 6100,
                'body_type' => 'convertible', 'condition' => 'pre_owned', 'fuel_type' => 'petrol', 'transmission' => 'dual_clutch', 'drivetrain' => 'awd',
                'engine' => '3.8L Twin-Turbo Flat-6', 'horsepower' => 640, 'torque' => 590, 'zero_to_sixty' => 2.7, 'top_speed' => 205,
                'exterior_color' => 'GT Silver Metallic', 'vin' => 'WP0CD2A99NS261145',
                'description' => 'All-weather, all-wheel-drive, open-top hypercar pace. The sun\'s out — and so is 640 horsepower.',
                'features' => ['Sport Chrono package', 'PDCC active roll stabilisation', 'Burmester audio', 'Lift system', 'Wind deflector'],
                'images' => self::img('1611821064430-0d40291d0f0b'),
            ],
            [
                'brand' => 'Aston Martin', 'model' => 'Vantage', 'trim' => 'Roadster', 'year' => 2021, 'price' => 129500, 'mileage' => 11400,
                'body_type' => 'convertible', 'condition' => 'pre_owned', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'drivetrain' => 'rwd',
                'engine' => '4.0L Twin-Turbo V8', 'horsepower' => 503, 'torque' => 505, 'zero_to_sixty' => 3.6, 'top_speed' => 190,
                'exterior_color' => 'Aston Martin Racing Green', 'vin' => 'SCFSMGBW3MGN05582',
                'description' => 'The fastest-folding roof in the business wrapped around a snarling AMG-sourced V8. Long-legged, charismatic, and very British.',
                'features' => ['6.8-second roof', 'Sports Plus seats', 'Premium audio', 'Parking assist', 'Carbon fibre trim'],
                'images' => self::img('1552519507-da3b142c6e3d'),
            ],
            [
                'brand' => 'Rolls-Royce', 'model' => 'Ghost', 'trim' => null, 'year' => 2022, 'price' => 319000, 'mileage' => 5400,
                'body_type' => 'sedan', 'condition' => 'certified', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'drivetrain' => 'awd',
                'engine' => '6.75L Twin-Turbo V12', 'horsepower' => 563, 'torque' => 627, 'zero_to_sixty' => 4.6, 'top_speed' => 155,
                'exterior_color' => 'Arctic White', 'vin' => 'SCA663S02NU212093',
                'description' => 'Post-opulence defined: understated, near-silent and built around a Planar suspension system that reads the road ahead.',
                'features' => ['Illuminated fascia', 'Starlight headliner', 'Rear massage seats', 'Bespoke audio', 'Champagne cooler'],
                'images' => self::img('1606664515524-ed2f786a0bd6'),
            ],
        ];
    }
}
