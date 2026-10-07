<?php

namespace App\Models;

use App\Enums\ListingStatus;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A private-sale advert. Every listing is backed by the car's passport.
 */
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'seller_id', 'share_link_id', 'slug', 'status', 'price_cents', 'previous_price_cents', 'price_dropped_at', 'mileage', 'city', 'state', 'zip',
        'description', 'score', 'views', 'removed_reason', 'published_at', 'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ListingStatus::class,
            'published_at' => 'datetime',
            'sold_at' => 'datetime',
            'price_dropped_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withDefault(['name' => User::DELETED]);
    }

    /**
     * @return BelongsTo<ShareLink, $this>
     */
    public function shareLink(): BelongsTo
    {
        return $this->belongsTo(ShareLink::class);
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class)->latest();
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function savers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_listings')->withTimestamps();
    }

    /**
     * @param  Builder<Listing>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->whereIn('status', [ListingStatus::Active, ListingStatus::Pending]);
    }

    public function isPublic(): bool
    {
        return $this->status->isPublic();
    }

    /**
     * The price before a cut in the last 30 days, for showing "was $X". Null when there hasn't been one.
     */
    public function recentDropFrom(): ?int
    {
        return $this->previous_price_cents > $this->price_cents && $this->price_dropped_at?->gt(now()->subDays(30))
            ? $this->previous_price_cents
            : null;
    }

    public function location(): string
    {
        return "{$this->city}, {$this->state}";
    }

    protected static function booted(): void
    {
        static::creating(function (Listing $listing) {
            $listing->slug ??= Str::slug($listing->vehicle->title()).'-'.Str::lower(Str::random(5));
        });

        // A cut on a live listing is news to people watching it. A rise wipes the "was" price, so a later
        // small cut can't be dressed up against an old, higher one.
        static::updating(function (Listing $listing) {
            $old = $listing->getOriginal('price_cents');

            if (! $listing->isDirty('price_cents') || ! $old || ! $listing->isPublic()) {
                return;
            }

            if ($listing->price_cents < $old) {
                $listing->previous_price_cents = $old;
                $listing->price_dropped_at = now();
            } else {
                $listing->previous_price_cents = null;
                $listing->price_dropped_at = null;
            }
        });
    }
}
