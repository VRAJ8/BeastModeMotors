<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A manufacturer safety recall published by NHTSA for this car's make, model and year.
 */
class Recall extends Model
{
    protected $fillable = [
        'vehicle_id', 'service_record_id', 'campaign_number', 'component', 'summary', 'consequence', 'remedy',
        'reported_on', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_on' => 'date',
            'resolved_at' => 'datetime',
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
     * @return BelongsTo<ServiceRecord, $this>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(ServiceRecord::class, 'service_record_id');
    }

    public function isOpen(): bool
    {
        return $this->resolved_at === null;
    }
}
