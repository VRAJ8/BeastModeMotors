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
        'vehicle_id', 'seller_id', 'share_link_id', 'slug', 'status', 'price_cents', 'mileage', 'city', 'state', 'zip',
        'description', 'score', 'views', 'removed_reason', 'published_at', 'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ListingStatus::class,
            'published_at' => 'datetime',
            'sold_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'seller_id');
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

    public function location(): string
    {
        return "{$this->city}, {$this->state}";
    }

    protected static function booted(): void
    {
        static::creating(function (Listing $listing) {
            $listing->slug ??= Str::slug($listing->vehicle->title()).'-'.Str::lower(Str::random(5));
        });
    }
}
