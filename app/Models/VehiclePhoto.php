<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VehiclePhoto extends Model
{
    protected $fillable = ['vehicle_id', 'path', 'caption', 'position'];

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function url(): string
    {
        return str_starts_with($this->path, 'http') ? $this->path : Storage::disk('public')->url($this->path);
    }

    protected static function booted(): void
    {
        static::deleted(function (VehiclePhoto $photo) {
            if (! str_starts_with($photo->path, 'http')) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }
}
