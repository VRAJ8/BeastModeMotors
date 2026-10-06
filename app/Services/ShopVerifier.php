<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\ShopVerification;
use App\Models\User;
use App\Notifications\VerificationAnswered;
use App\Notifications\VerifyServiceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Lets the shop that did the work confirm (or dispute) a record through a signed, expiring link.
 */
class ShopVerifier
{
    public function request(ServiceRecord $record, User $owner, string $shopName, string $shopEmail): ShopVerification
    {
        if (! $record->canRequestVerification()) {
            throw ValidationException::withMessages(['shop_email' => 'This record can\'t be sent for verification.']);
        }

        if ($record->pendingVerification()->exists()) {
            throw ValidationException::withMessages(['shop_email' => 'A request is already waiting for the shop to answer.']);
        }

        if (strcasecmp($shopEmail, $owner->email) === 0) {
            throw ValidationException::withMessages(['shop_email' => 'Use the shop\'s email address, not your own.']);
        }

        // Verification emails go to addresses we don't control: cap how many any owner, or any inbox, can trigger.
        foreach (['verify-owner:'.$owner->getKey() => 10, 'verify-shop:'.strtolower($shopEmail) => 5] as $key => $perDay) {
            if (RateLimiter::tooManyAttempts($key, $perDay)) {
                throw ValidationException::withMessages(['shop_email' => 'Too many verification requests today. Try again tomorrow.']);
            }
        }

        RateLimiter::hit('verify-owner:'.$owner->getKey(), 86400);
        RateLimiter::hit('verify-shop:'.strtolower($shopEmail), 86400);

        $verification = $record->verifications()->create([
            'requested_by' => $owner->getKey(),
            'shop_id' => Shop::forEmail($shopEmail, $shopName)->getKey(),
            'shop_name' => $shopName,
            'shop_email' => $shopEmail,
            'status' => VerificationStatus::Pending,
            'expires_at' => now()->addDays(config('passport.verification.link_valid_days')),
        ]);

        $record->update(['provider_name' => $record->provider_name ?: $shopName, 'provider_email' => $shopEmail]);

        Notification::route('mail', [$shopEmail => $shopName])->notify(new VerifyServiceRecord($verification));

        return $verification;
    }

    public function answer(ShopVerification $verification, bool $confirmed, string $responderName, ?string $note, ?string $ip): void
    {
        abort_unless($verification->isAnswerable(), 410, 'This verification link has expired or was already used.');

        DB::transaction(function () use ($verification, $confirmed, $responderName, $note, $ip) {
            $verification->update([
                'status' => $confirmed ? VerificationStatus::Confirmed : VerificationStatus::Disputed,
                'responder_name' => $responderName,
                'response_note' => $note,
                'responder_ip' => $ip,
                'responded_at' => now(),
            ]);

            $verification->record->update($confirmed
                ? ['verified_at' => now(), 'disputed_at' => null, 'shop_id' => $verification->shop_id]
                : ['disputed_at' => now()]);
        });

        $verification->requester?->notify(new VerificationAnswered($verification));
    }
}
