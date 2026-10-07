<?php

namespace App\Models;

use App\Enums\OdometerSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdometerReading extends Model
{
    protected $fillable = ['vehicle_id', 'ownership_id', 'service_record_id', 'reading', 'recorded_on', 'source'];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'source' => OdometerSource::class,
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
