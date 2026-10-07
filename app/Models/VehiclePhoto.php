<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
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
        // The file goes once the delete has committed: a rolled-back transaction must not leave a row without its file.
        static::deleted(function (VehiclePhoto $photo) {
            if (! str_starts_with($photo->path, 'http')) {
                DB::afterCommit(fn () => static::disk()->delete($photo->path));
            }
        });
    }
}
