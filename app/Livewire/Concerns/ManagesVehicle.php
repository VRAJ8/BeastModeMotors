<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Gate;

/**
 * Re-checks ownership on the first render and on every later request, not just at the page route.
 */
trait ManagesVehicle
{
    public function renderingManagesVehicle(): void
    {
        Gate::authorize('manage', $this->vehicle);
    }

    public function hydrateManagesVehicle(): void
    {
        Gate::authorize('manage', $this->vehicle);
    }
}
