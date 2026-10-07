<?php

namespace App\Models;

use App\Enums\FuelType;
use App\Enums\ListingStatus;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A physical car, identified by its VIN. Its history outlives any one owner.
 */
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'vin', 'vin_valid', 'decode_source', 'decoded', 'year', 'make', 'model', 'trim', 'body',
        'engine', 'drivetrain', 'transmission', 'fuel_type', 'exterior_color', 'nickname', 'current_mileage',
        'recalls_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'vin_valid' => 'boolean',
            'decoded' => 'array',
            'fuel_type' => FuelType::class,
            'recalls_checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Ownership, $this>
     */
    public function ownerships(): HasMany
    {
        return $this->hasMany(Ownership::class)->orderBy('owner_number');
    }

    /**
     * @return HasOne<Ownership, $this>
     */
    public function currentOwnership(): HasOne
    {
        return $this->hasOne(Ownership::class)->whereNull('ended_on')->latestOfMany('owner_number');
    }

    /**
     * @return HasMany<ServiceRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(ServiceRecord::class)->orderByDesc('performed_on')->orderByDesc('id');
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest();
    }

    /**
     * @return HasMany<OdometerReading, $this>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(OdometerReading::class)->orderBy('recorded_on')->orderBy('id');
    }

    /**
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class)->orderBy('id');
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class)->orderByDesc('spent_on')->orderByDesc('id');
    }

    /**
     * @return HasMany<Recall, $this>
     */
    public function recalls(): HasMany
    {
        return $this->hasMany(Recall::class)->orderByDesc('reported_on');
    }

    /**
     * @return HasMany<VehiclePhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<ShareLink, $this>
     */
    public function shareLinks(): HasMany
    {
        return $this->hasMany(ShareLink::class)->latest();
    }

    /**
     * @return HasMany<Listing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class)->latest();
    }

    /**
     * The listing the current owner is working on (draft, live or under offer).
     *
     * @return HasOne<Listing, $this>
     */
    public function openListing(): HasOne
    {
        return $this->hasOne(Listing::class)
            ->whereIn('status', [ListingStatus::Draft, ListingStatus::Active, ListingStatus::Pending])
            ->latestOfMany();
    }

    public function title(): string
    {
        return trim("{$this->year} {$this->make} {$this->model}");
    }

    public function displayName(): string
    {
        return $this->nickname ?: $this->title();
    }

    public function fullTitle(): string
    {
        return trim($this->title().' '.$this->trim);
    }

    /**
     * Hide the serial number (last six characters) for public views.
     */
    public function maskedVin(): string
    {
        return substr($this->vin, 0, 11).'••••••';
    }

    public function coverPhotoUrl(): ?string
    {
        $photo = $this->relationLoaded('photos') ? $this->photos->first() : $this->photos()->first();

        return $photo?->url();
    }

    public function ownerCount(): int
    {
        return $this->relationLoaded('ownerships') ? $this->ownerships->count() : $this->ownerships()->count();
    }

    /**
     * Recompute the cached mileage from the highest trusted reading.
     */
    public function refreshMileage(): void
    {
        $this->forceFill(['current_mileage' => (int) $this->readings()->max('reading')])->saveQuietly();
    }

    protected static function booted(): void
    {
        static::deleting(function (Vehicle $vehicle) {
            $vehicle->photos->each->delete();
            $vehicle->documents()->get()->each->delete();
        });
    }
}
