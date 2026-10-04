<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Honeypot + per-visitor rate limiting for public lead forms.
 */
trait GuardsPublicForms
{
    /** Bots fill every field; humans never see this one. */
    public string $website = '';

    protected function prefillContactDetails(): void
    {
        if ($user = Auth::user()) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = (string) $user->phone;
        }
    }

    protected function isSpam(): bool
    {
        return $this->website !== '';
    }

    /**
     * @throws ValidationException
     */
    protected function throttle(string $form, int $maxAttempts = 5, int $decaySeconds = 3600): void
    {
        $key = $form.'|'.(Auth::id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            throw ValidationException::withMessages([
                'form' => "Too many requests. Please try again in {$minutes} minutes or call us directly.",
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
