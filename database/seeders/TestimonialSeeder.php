<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            ['Marcus Reid', 'Founder, Reid Capital', 'Flew in from Dallas for a test drive and drove home in the 720S the same afternoon. Paperwork done in under an hour — that is how buying a supercar should feel.', 'McLaren 720S'],
            ['Sofia Alvarez', 'Architect', 'They found me a Shark Blue GT3 RS when every other dealer had a two-year waiting list. Transparent pricing, no games.', 'Porsche 911 GT3 RS'],
            ['James Okafor', 'Surgeon', 'The trade-in estimate online was within two thousand dollars of the final offer. Rare honesty in this business.', 'Rolls-Royce Cullinan'],
            ['Priya Nair', 'Tech Executive', 'Booking the test drive took thirty seconds and the car was detailed and waiting when I arrived. Genuinely premium service.', 'Porsche Taycan Turbo S'],
            ['Daniel Kim', 'Restaurateur', 'Third car I have bought from Beast Mode. The after-sales team treat you like family.', 'Lamborghini Urus Performante'],
        ];

        foreach ($testimonials as [$name, $title, $quote, $vehicle]) {
            Testimonial::create(compact('name', 'title', 'quote', 'vehicle') + ['rating' => 5]);
        }
    }
}
