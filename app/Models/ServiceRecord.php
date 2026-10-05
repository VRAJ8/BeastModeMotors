<?php

namespace App\Models;

use App\Enums\OdometerSource;
use App\Enums\ProviderType;
use App\Enums\ServiceCategory;
use App\Enums\VerificationStatus;
use Database\Factories\ServiceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A piece of work done on the car: maintenance, a repair, a modification…
 */
class ServiceRecord extends Model
{
    /** @use HasFactory<ServiceRecordFactory> */
    use HasFactory;

    public const EVIDENCE_VERIFIED = 'verified';

    public const EVIDENCE_DOCUMENTED = 'documented';

    public const EVIDENCE_SELF = 'self';

    public const EVIDENCE_DISPUTED = 'disputed';

    protected $fillable = [
        'vehicle_id', 'ownership_id', 'logged_by', 'category', 'title', 'description', 'performed_on', 'mileage',
        'cost_cents', 'line_items', 'provider_type', 'provider_name', 'provider_email', 'shop_id', 'tasks', 'verified_at',
        'disputed_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ServiceCategory::class,
            'provider_type' => ProviderType::class,
            'performed_on' => 'date',
            'line_items' => 'array',
            'tasks' => 'array',
            'verified_at' => 'datetime',
            'disputed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Ownership, $this>
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class);
    }

    /**
     * The shop that confirmed this record.
     *
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<ShopVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(ShopVerification::class)->latest();
    }

    /**
     * @return HasOne<ShopVerification, $this>
     */
    public function pendingVerification(): HasOne
    {
        return $this->hasOne(ShopVerification::class)
            ->where('status', VerificationStatus::Pending)
            ->where('expires_at', '>', now())
            ->latestOfMany();
    }

    /**
     * @return HasOne<OdometerReading, $this>
     */
    public function reading(): HasOne
    {
        return $this->hasOne(OdometerReading::class);
    }

    /**
     * How strong the proof behind this record is.
     */
    public function evidence(): string
    {
        return match (true) {
            $this->disputed_at !== null => self::EVIDENCE_DISPUTED,
            $this->verified_at !== null => self::EVIDENCE_VERIFIED,
            $this->hasReceipt() => self::EVIDENCE_DOCUMENTED,
            default => self::EVIDENCE_SELF,
        };
    }

    public function hasReceipt(): bool
    {
        if (array_key_exists('documents_count', $this->attributes)) {
            return $this->attributes['documents_count'] > 0;
        }

        return $this->relationLoaded('documents') ? $this->documents->isNotEmpty() : $this->documents()->exists();
    }

    /**
     * Logged long after the work was done (memory, not a contemporaneous record).
     */
    public function isBackfilled(): bool
    {
        $loggedAt = $this->created_at ?? now();

        return $this->performed_on->diffInDays($loggedAt, false) > config('passport.backfill_days');
    }

    /**
     * A shop-confirmed record can't have its facts changed, or the confirmation would be meaningless.
     */
    public function isLocked(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Records logged by earlier owners are part of the car's history: readable, never editable,
     * and their costs stay private to the owner who paid them.
     */
    public function isFromOwnership(?int $ownershipId): bool
    {
        return $ownershipId !== null && $this->ownership_id === $ownershipId;
    }

    public function canRequestVerification(): bool
    {
        return $this->provider_type !== ProviderType::Diy
            && $this->verified_at === null
            && $this->disputed_at === null
            && $this->verifications()->count() < config('passport.verification.max_requests_per_record');
    }

    protected static function booted(): void
    {
        // Every record doubles as an odometer reading, so mileage history stays in step.
        static::saved(function (ServiceRecord $record) {
            $record->reading()->updateOrCreate([], [
                'vehicle_id' => $record->vehicle_id,
                'ownership_id' => $record->ownership_id,
                'reading' => $record->mileage,
                'recorded_on' => $record->performed_on,
                'source' => OdometerSource::Service,
            ]);
            $record->vehicle->refreshMileage();
        });

        // Receipts go with the record (before the foreign key unlinks them).
        static::deleting(fn (ServiceRecord $record) => $record->documents()->get()->each->delete());

        static::deleted(function (ServiceRecord $record) {
            $record->reading()->delete();
            $record->vehicle?->refreshMileage();
        });
    }
}
