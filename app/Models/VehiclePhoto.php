<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
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
        return str_starts_with($this->path, 'http') ? $this->path : static::disk()->url($this->path);
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(config('passport.disks.photos'));
    }

    protected static function booted(): void
    {
        static::deleted(function (VehiclePhoto $photo) {
            if (! str_starts_with($photo->path, 'http')) {
                static::disk()->delete($photo->path);
            }
        });
    }
}
