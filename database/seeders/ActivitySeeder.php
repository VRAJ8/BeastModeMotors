<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\TestDriveStatus;
use App\Models\Lead;
use App\Models\TestDrive;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Notification;

/**
 * Demo activity so the back-office dashboard and customer garage aren't empty.
 */
class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        // Seeding shouldn't spam the mail log.
        Notification::fake();

        $customer = User::where('email', 'customer@beastmodemotors.test')->firstOrFail();
        $vehicles = Vehicle::all();

        $customer->favorites()->attach($vehicles->random(4)->pluck('id'));

        TestDrive::create([
            'vehicle_id' => $vehicles[3]->id,
            'user_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => '+1 (305) 555-0142',
            'scheduled_at' => now()->addWeekday()->setTime(14, 0),
            'status' => TestDriveStatus::Confirmed,
        ]);

        TestDrive::create([
            'vehicle_id' => $vehicles[1]->id,
            'user_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'scheduled_at' => now()->subDays(12)->setTime(11, 0),
            'status' => TestDriveStatus::Completed,
        ]);

        foreach (range(1, 14) as $i) {
            TestDrive::factory()->create([
                'vehicle_id' => $vehicles->random()->id,
                'scheduled_at' => now()->addDays(random_int(-20, 14))->setTime(random_int(10, 17), 0),
                'status' => fake()->randomElement(TestDriveStatus::cases()),
            ]);
        }

        // ~10 weeks of leads for the dashboard chart.
        foreach (range(1, 60) as $i) {
            $type = fake()->randomElement(LeadType::cases());
            $vehicle = in_array($type, [LeadType::General, LeadType::TradeIn], true) ? null : $vehicles->random();

            Lead::factory()->create([
                'type' => $type,
                'status' => fake()->randomElement(LeadStatus::cases()),
                'vehicle_id' => $vehicle?->id,
                'offer_amount' => $type === LeadType::Offer ? (int) round($vehicle->price * 0.92, -3) : null,
                'meta' => $type === LeadType::TradeIn ? ['make' => 'Porsche', 'model' => 'Cayenne', 'year' => 2019, 'mileage' => 38000, 'condition' => 'good'] : null,
                'created_at' => now()->subDays(random_int(0, 70))->subMinutes(random_int(0, 600)),
            ]);
        }

        Lead::create([
            'type' => LeadType::Offer,
            'vehicle_id' => $vehicles[0]->id,
            'user_id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'offer_amount' => 560000,
            'message' => 'Cash buyer, can complete this week.',
        ]);

        // One sold car and one price drop to show off those states.
        $vehicles[16]->update(['status' => 'sold']);
        $favorite = $customer->favorites()->first();
        $favorite->update(['price' => $favorite->price - 15000]);
    }
}
