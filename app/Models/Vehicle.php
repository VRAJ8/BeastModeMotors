<?php

namespace App\Models;

use App\Enums\BodyType;
use App\Enums\Condition;
use App\Enums\Drivetrain;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use App\Notifications\PriceDropped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory;

    public const PLACEHOLDER_IMAGE = '/images/placeholder-car.svg';

    protected $fillable = [
        'brand_id',
        'model',
        'trim',
        'slug',
        'year',
        'price',
        'previous_price',
        'mileage',
        'body_type',
        'condition',
        'status',
        'fuel_type',
        'transmission',
        'drivetrain',
        'engine',
        'horsepower',
        'torque',
        'zero_to_sixty',
        'top_speed',
        'exterior_color',
        'interior_color',
        'vin',
        'description',
        'features',
        'images',
        'is_featured',
        'published_at',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'body_type' => BodyType::class,
            'condition' => Condition::class,
            'status' => VehicleStatus::class,
            'fuel_type' => FuelType::class,
            'transmission' => Transmission::class,
            'drivetrain' => Drivetrain::class,
            'features' => 'array',
            'images' => 'array',
            'is_featured' => 'boolean',
            'zero_to_sixty' => 'float',
            'published_at' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle) {
            if (blank($vehicle->slug)) {
                $vehicle->slug = static::uniqueSlug($vehicle);
            }
        });

        static::saving(function (Vehicle $vehicle) {
            if ($vehicle->isDirty('status')) {
                $vehicle->sold_at = $vehicle->status === VehicleStatus::Sold ? ($vehicle->sold_at ?? now()) : null;
            }

            if ($vehicle->exists && $vehicle->isDirty('price')) {
                $original = (int) $vehicle->getOriginal('price');
                // Remember the old price only on a drop, so the storefront can show "was $X".
                $vehicle->previous_price = $vehicle->price < $original ? $original : null;
            }
        });

        static::updated(function (Vehicle $vehicle) {
            if ($vehicle->wasChanged('price') && $vehicle->previous_price && $vehicle->status !== VehicleStatus::Sold) {
                Notification::send($vehicle->favoritedBy()->get(), new PriceDropped($vehicle));
            }
        });
    }

    protected static function uniqueSlug(Vehicle $vehicle): string
    {
        $base = Str::slug(implode(' ', array_filter([
            $vehicle->year,
            $vehicle->brand?->name,
            $vehicle->model,
            $vehicle->trim,
        ])));

        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<TestDrive, $this>
     */
    public function testDrives(): HasMany
    {
        return $this->hasMany(TestDrive::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * Vehicles that are visible on the storefront (published, including sold cars).
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->published()->where('status', VehicleStatus::Available);
    }

    protected function title(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->year} {$this->brand?->name} {$this->model}"));
    }

    protected function imageUrls(): Attribute
    {
        return Attribute::get(function (): array {
            $urls = collect($this->images ?? [])
                ->filter()
                ->map(fn (string $path) => Str::startsWith($path, ['http://', 'https://', '/'])
                    ? $path
                    : Storage::disk('public')->url($path))
                ->values()
                ->all();

            return $urls ?: [self::PLACEHOLDER_IMAGE];
        });
    }

    protected function coverImage(): Attribute
    {
        return Attribute::get(fn (): string => $this->image_urls[0]);
    }

    protected function hasPriceDrop(): Attribute
    {
        return Attribute::get(fn (): bool => $this->previous_price !== null && $this->previous_price > $this->price);
    }

    public function isAvailable(): bool
    {
        return $this->status === VehicleStatus::Available;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSchemaOrg(): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Car',
            'name' => $this->title,
            'brand' => ['@type' => 'Brand', 'name' => $this->brand?->name],
            'model' => $this->model,
            'vehicleModelDate' => (string) $this->year,
            'bodyType' => $this->body_type?->getLabel(),
            'color' => $this->exterior_color,
            'fuelType' => $this->fuel_type?->getLabel(),
            'vehicleTransmission' => $this->transmission?->getLabel(),
            'vehicleIdentificationNumber' => $this->vin,
            'mileageFromOdometer' => ['@type' => 'QuantitativeValue', 'value' => $this->mileage, 'unitCode' => 'SMI'],
            'image' => $this->image_urls,
            'description' => $this->description,
            'offers' => [
                '@type' => 'Offer',
                'price' => $this->price,
                'priceCurrency' => 'USD',
                'availability' => $this->isAvailable() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
            ],
        ]);
    }
}
