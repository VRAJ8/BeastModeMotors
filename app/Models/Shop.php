<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * A repair shop, identified by the email address it answers verification requests from.
 *
 * Shops never sign up: a profile appears once they confirm their first record, and they edit it
 * through a signed link — the same proof of email ownership the verification itself relies on.
 */
class Shop extends Model
{
    public const FAST_HOURS = 24;

    public const FAST_MIN_ANSWERS = 3;

    protected $fillable = ['name', 'slug', 'email', 'city', 'state', 'phone', 'website', 'about', 'specialties', 'is_listed', 'profile_completed_at'];

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'is_listed' => 'boolean',
            'profile_completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<ShopVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(ShopVerification::class);
    }

    /**
     * Records this shop has confirmed.
     *
     * @return HasMany<ServiceRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(ServiceRecord::class)->whereNotNull('verified_at');
    }

    /**
     * Shops shown in the public directory: listed by staff and with at least one confirmation.
     *
     * @param  Builder<Shop>  $query
     */
    public function scopeDirectory(Builder $query): void
    {
        $query->where('is_listed', true)
            ->whereHas('verifications', fn (Builder $v) => $v->where('status', VerificationStatus::Confirmed));
    }

    public static function forEmail(string $email, string $name): self
    {
        $email = Str::lower(trim($email));

        return static::firstOrCreate(['email' => $email], [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
        ]);
    }

    public function location(): ?string
    {
        return $this->city ? trim("{$this->city}, {$this->state}", ', ') : null;
    }

    /**
     * Email with the local part hidden, for owners picking a shop from the directory.
     */
    public function maskedEmail(): string
    {
        [$local, $domain] = explode('@', $this->email) + [1 => ''];

        return Str::substr($local, 0, 1).'•••@'.$domain;
    }

    public function editUrl(): string
    {
        return URL::temporarySignedRoute('shops.edit', now()->addDays(7), ['shop' => $this->getKey()]);
    }

    /**
     * Track record built from answered verification requests.
     *
     * @return array{confirmed: int, disputed: int, answered: int, unanswered: int, response_rate: ?int, median_hours: ?float, fast: bool, cars: int, makes: Collection<int, string>}
     */
    public function stats(): array
    {
        $verifications = $this->relationLoaded('verifications') ? $this->verifications : $this->verifications()->get();
        $answered = $verifications->whereIn('status', [VerificationStatus::Confirmed, VerificationStatus::Disputed]);
        $unanswered = $verifications->where('status', VerificationStatus::Expired);

        $hours = $answered->map(fn (ShopVerification $v) => $v->created_at->diffInMinutes($v->responded_at) / 60)->sort()->values();
        $median = $hours->isEmpty() ? null : round($hours->median(), 1);
        $closed = $answered->count() + $unanswered->count();

        $vehicles = Vehicle::whereIn('id', ServiceRecord::where('shop_id', $this->getKey())->whereNotNull('verified_at')->select('vehicle_id'))->get(['id', 'make']);

        return [
            'confirmed' => $answered->where('status', VerificationStatus::Confirmed)->count(),
            'disputed' => $answered->where('status', VerificationStatus::Disputed)->count(),
            'answered' => $answered->count(),
            'unanswered' => $unanswered->count(),
            'response_rate' => $closed ? (int) round($answered->count() / $closed * 100) : null,
            'median_hours' => $median,
            'fast' => $median !== null && $median <= self::FAST_HOURS && $answered->count() >= self::FAST_MIN_ANSWERS,
            'cars' => $vehicles->count(),
            'makes' => $vehicles->countBy('make')->sortDesc()->keys()->take(5)->values(),
        ];
    }
}
