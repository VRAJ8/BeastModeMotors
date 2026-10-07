<?php

namespace App\Models;

use App\Enums\DealStatus;
use App\Enums\OdometerStatus;
use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A buyer and a seller working through one car: talk, agree a price, inspect, hand over.
 */
class Deal extends Model
{
    protected $fillable = [
        'listing_id', 'vehicle_id', 'buyer_id', 'seller_id', 'status', 'agreed_price_cents', 'sale_mileage', 'odometer_status', 'handover',
        'agreed_at', 'buyer_confirmed_at', 'seller_confirmed_at', 'completed_at', 'cancelled_at', 'cancelled_by',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => DealStatus::class,
            'odometer_status' => OdometerStatus::class,
            'handover' => 'array',
            'agreed_at' => 'datetime',
            'buyer_confirmed_at' => 'datetime',
            'seller_confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Listing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id')->withDefault(['name' => User::DELETED]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withDefault(['name' => User::DELETED]);
    }

    /**
     * @return HasMany<DealMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(DealMessage::class)->orderBy('id');
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class)->latest('id');
    }

    /**
     * @return HasOne<Offer, $this>
     */
    public function pendingOffer(): HasOne
    {
        // Past its deadline an offer is dead, whether or not housekeeping has marked it expired yet.
        return $this->hasOne(Offer::class)->where('status', OfferStatus::Pending)->where('expires_at', '>', now())->latestOfMany();
    }

    /**
     * @return HasOne<Inspection, $this>
     */
    public function inspection(): HasOne
    {
        return $this->hasOne(Inspection::class);
    }

    /**
     * @param  Builder<Deal>  $query
     */
    public function scopeInvolving(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q->where('buyer_id', $user->getKey())->orWhere('seller_id', $user->getKey()));
    }

    public function isParticipant(?User $user): bool
    {
        return $user !== null && in_array($user->getKey(), [$this->buyer_id, $this->seller_id], true);
    }

    /**
     * @return 'buyer'|'seller'|null
     */
    public function roleOf(?User $user): ?string
    {
        return match ($user?->getKey()) {
            $this->buyer_id => 'buyer',
            $this->seller_id => 'seller',
            default => null,
        };
    }

    public function counterparty(User $user): User
    {
        return $this->roleOf($user) === 'buyer' ? $this->seller : $this->buyer;
    }

    /**
     * Handover checklist merged with who ticked what.
     *
     * @return array<string, array{label: string, by: string, required: bool, done_at: ?string}>
     */
    public function handoverItems(): array
    {
        $state = $this->handover ?? [];

        return collect(config('passport.handover'))
            ->map(fn (array $item, string $key) => $item + ['done_at' => $state[$key] ?? null])
            ->all();
    }

    public function handoverComplete(): bool
    {
        return collect($this->handoverItems())->every(fn (array $item) => ! $item['required'] || $item['done_at']);
    }

    /**
     * 0 talk · 1 agreed · 2 inspection done · 3 handover ready · 4 completed.
     */
    public function stage(): int
    {
        return match (true) {
            $this->status === DealStatus::Completed => 4,
            $this->status !== DealStatus::Agreed => 0,
            $this->handoverComplete() => 3,
            $this->inspection?->completed_at !== null => 2,
            default => 1,
        };
    }
}
