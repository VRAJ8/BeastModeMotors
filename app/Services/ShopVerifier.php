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
    public const WEBMAIL = ['gmail.com', 'googlemail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'live.com', 'icloud.com', 'me.com', 'aol.com', 'proton.me', 'protonmail.com', 'gmx.com', 'mail.com'];

    public function request(ServiceRecord $record, User $owner, string $shopName, string $shopEmail): ShopVerification
    {
        if (! $record->canRequestVerification()) {
            throw ValidationException::withMessages(['shop_email' => 'This record can\'t be sent for verification.']);
        }

        if ($record->pendingVerification()->exists()) {
            throw ValidationException::withMessages(['shop_email' => 'A request is already waiting for the shop to answer.']);
        }

        if (self::sameMailbox($shopEmail, $owner->email)) {
            throw ValidationException::withMessages(['shop_email' => 'Use the shop\'s email address, not your own.']);
        }

        if (self::sameOrganisation($shopEmail, $owner->email)) {
            throw ValidationException::withMessages(['shop_email' => 'That address is on your own email domain. Ask the shop for their address.']);
        }

        // Verification emails go to addresses we don't control: cap how many any owner, or any inbox, can trigger.
        foreach (['verify-owner:'.$owner->getKey() => 10, 'verify-shop:'.strtolower($shopEmail) => 5] as $key => $perDay) {
            if (RateLimiter::tooManyAttempts($key, $perDay)) {
                throw ValidationException::withMessages(['shop_email' => 'Too many verification requests today. Try again tomorrow.']);
            }
        }

        RateLimiter::hit('verify-owner:'.$owner->getKey(), 86400);
        RateLimiter::hit('verify-shop:'.strtolower($shopEmail), 86400);

        $verification = DB::transaction(function () use ($record, $owner, $shopName, $shopEmail) {
            // Two requests at once must not both go out: re-check under a lock on the record.
            $locked = ServiceRecord::whereKey($record->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->canRequestVerification() || $locked->pendingVerification()->exists()) {
                throw ValidationException::withMessages(['shop_email' => 'A request is already waiting for the shop to answer.']);
            }

            return $locked->verifications()->create([
                'requested_by' => $owner->getKey(),
                'shop_id' => Shop::forEmail($shopEmail, $shopName)->getKey(),
                'shop_name' => $shopName,
                'shop_email' => $shopEmail,
                'status' => VerificationStatus::Pending,
                'expires_at' => now()->addDays(config('passport.verification.link_valid_days')),
            ]);
        });

        $record->update(['provider_name' => $record->provider_name ?: $shopName, 'provider_email' => $shopEmail]);

        Notification::route('mail', [$shopEmail => $shopName])->notify(new VerifyServiceRecord($verification));

        return $verification;
    }

    /**
     * Same inbox, ignoring case, +tags and (for Gmail) dots.
     */
    public static function sameMailbox(string $a, string $b): bool
    {
        return self::canonical($a) === self::canonical($b);
    }

    /**
     * Both addresses on the same custom domain (webmail domains are shared by millions, so they don't count).
     */
    public static function sameOrganisation(string $a, string $b): bool
    {
        $domain = fn (string $email) => strtolower(substr(strrchr($email, '@') ?: '', 1));

        return $domain($a) !== '' && $domain($a) === $domain($b) && ! in_array($domain($a), self::WEBMAIL, true);
    }

    private static function canonical(string $email): string
    {
        [$local, $domain] = explode('@', strtolower(trim($email))) + [1 => ''];
        $local = explode('+', $local)[0];

        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = str_replace('.', '', $local);
            $domain = 'gmail.com';
        }

        return $local.'@'.$domain;
    }

    /**
     * @param  ?string  $businessName  The shop's own name for itself, the first time it confirms: until then it's
     *                                 known by whatever the first owner to ask typed in.
     */
    public function answer(ShopVerification $verification, bool $confirmed, string $responderName, ?string $note, ?string $ip, ?string $businessName = null): void
    {
        abort_unless($verification->isAnswerable(), 410, 'This verification link has expired or was already used.');

        DB::transaction(function () use ($verification, $confirmed, $responderName, $note, $ip, $businessName) {
            $record = ServiceRecord::whereKey($verification->service_record_id)->lockForUpdate()->firstOrFail();

            // A double-submitted form must not answer twice.
            abort_unless($verification->fresh()->isAnswerable(), 410, 'This verification link has expired or was already used.');

            $verification->update([
                'status' => $confirmed ? VerificationStatus::Confirmed : VerificationStatus::Disputed,
                'responder_name' => $responderName,
                'response_note' => $note,
                'responder_ip' => $ip,
                'responded_at' => now(),
            ]);

            // A dispute always wins: a confirmation never clears one, and a dispute clears a confirmation.
            if (! $confirmed) {
                $record->update(['disputed_at' => now(), 'verified_at' => null, 'shop_id' => null]);
            } elseif ($record->disputed_at === null) {
                $record->update(['verified_at' => now(), 'shop_id' => $verification->shop_id]);
            }

            $shop = $verification->shop;

            if ($confirmed && $shop && ! $shop->hasConfirmedName() && filled($businessName)) {
                $shop->update(['name' => trim($businessName), 'name_confirmed_at' => now()]);
            }
        });

        $verification->requester?->notify(new VerificationAnswered($verification));
    }
}
