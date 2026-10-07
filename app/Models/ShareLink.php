<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A revocable public link to a car's passport, with per-link privacy settings.
 */
class ShareLink extends Model
{
    protected $fillable = [
        'vehicle_id', 'created_by', 'token', 'label', 'show_costs', 'show_full_vin', 'show_documents', 'views',
        'last_viewed_at', 'expires_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'show_costs' => 'boolean',
            'show_full_vin' => 'boolean',
            'show_documents' => 'boolean',
            'last_viewed_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function url(): string
    {
        return route('passport.show', $this);
    }

    public function recordView(): void
    {
        $this->forceFill(['views' => $this->views + 1, 'last_viewed_at' => now()])->saveQuietly();
    }

    protected static function booted(): void
    {
        static::creating(fn (ShareLink $link) => $link->token ??= Str::lower(Str::random(24)));
    }
}
