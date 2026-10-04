<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Notifications\NewLeadReceived;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'vehicle_id',
        'user_id',
        'name',
        'email',
        'phone',
        'message',
        'offer_amount',
        'meta',
    ];

    protected $attributes = [
        'status' => 'new',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Lead $lead) {
            Notification::route('mail', config('dealership.email'))->notify(new NewLeadReceived($lead));
        });
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
}
