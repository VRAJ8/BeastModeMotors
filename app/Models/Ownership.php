<?php

namespace App\Models;

use App\Enums\AcquiredVia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One owner's chapter in a car's life.
 */
class Ownership extends Model
{
    protected $fillable = [
        'vehicle_id', 'user_id', 'owner_number', 'acquired_via', 'started_on', 'ended_on',
        'start_mileage', 'end_mileage', 'purchase_price_cents',
    ];

    protected function casts(): array
    {
        return [
            'acquired_via' => AcquiredVia::class,
            'started_on' => 'date',
            'ended_on' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function isCurrent(): bool
    {
        return $this->ended_on === null;
    }

    public function label(): string
    {
        return 'Owner '.$this->owner_number;
    }

    public function period(): string
    {
        return $this->started_on->format('M Y').' – '.($this->ended_on?->format('M Y') ?? 'present');
    }

    public function months(): int
    {
        return max(1, (int) $this->started_on->diffInMonths($this->ended_on ?? now()));
    }
}
