<?php

namespace App\Models;

use App\Enums\OdometerStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An owner handing the passport to someone they sold the car to outside the marketplace.
 */
class PassportTransfer extends Model
{
    protected $fillable = [
        'vehicle_id', 'from_user_id', 'to_user_id', 'token_hash', 'sale_mileage', 'odometer_status',
        'expires_at', 'accepted_at', 'declined_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'odometer_status' => OdometerStatus::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id')->withDefault(['name' => User::DELETED]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Links nobody has used, declined or cancelled yet (expired ones included).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('accepted_at')->whereNull('declined_at')->whereNull('cancelled_at');
    }

    /**
     * @return 'pending'|'accepted'|'declined'|'cancelled'|'expired'
     */
    public function status(): string
    {
        return match (true) {
            $this->accepted_at !== null => 'accepted',
            $this->declined_at !== null => 'declined',
            $this->cancelled_at !== null => 'cancelled',
            $this->expires_at->isPast() => 'expired',
            default => 'pending',
        };
    }

    public function isPending(): bool
    {
        return $this->status() === 'pending';
    }
}
