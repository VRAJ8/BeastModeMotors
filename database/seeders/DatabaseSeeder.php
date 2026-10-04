<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Showroom Admin',
            'email' => 'admin@beastmodemotors.test',
            'password' => 'password',
        ]);

        User::factory()->create([
            'name' => 'Demo Customer',
            'email' => 'customer@beastmodemotors.test',
            'password' => 'password',
        ]);

        $this->call([
            ShowroomSeeder::class,
            TestimonialSeeder::class,
            ActivitySeeder::class,
        ]);
    }
}
