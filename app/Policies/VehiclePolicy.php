<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Only the current owner can see or change a car's private garage view.
     */
    public function manage(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->user_id === $user->getKey();
    }
}
