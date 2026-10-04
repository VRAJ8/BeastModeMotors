<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => LeadType::General,
            'status' => LeadStatus::New,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->randomElement([
                'Is this car still available? I can view it this weekend.',
                'Could you send over the service history and any paint meter readings?',
                'Interested in financing over 48 months — what rates are available?',
                'Do you ship to California? Looking for enclosed transport.',
                'Would you consider a part-exchange with my current car?',
                'Cash buyer, ready to move quickly for the right car.',
                'Are there any other colour options coming in soon?',
                'Can I arrange an independent pre-purchase inspection?',
            ]),
        ];
    }
}
