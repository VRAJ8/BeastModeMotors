<?php

namespace App\Models;

use App\Enums\TestDriveStatus;
use App\Notifications\TestDriveUpdated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class TestDrive extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'vehicle_id',
        'user_id',
        'name',
        'email',
        'phone',
        'scheduled_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => TestDriveStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TestDrive $testDrive) {
            $testDrive->reference ??= static::newReference();
            $testDrive->status ??= TestDriveStatus::Pending;
        });

        static::created(fn (TestDrive $testDrive) => $testDrive->notifyCustomer());

        static::updated(function (TestDrive $testDrive) {
            if ($testDrive->wasChanged('status')) {
                $testDrive->notifyCustomer();
            }
        });
    }

    public static function newReference(): string
    {
        do {
            $reference = 'TD-'.Str::upper(Str::random(6));
        } while (static::where('reference', $reference)->exists());

        return $reference;
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
     * Bookings that still occupy a slot.
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [TestDriveStatus::Pending, TestDriveStatus::Confirmed]);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->active()->where('scheduled_at', '>=', now());
    }

    public function notifyCustomer(): void
    {
        $notification = new TestDriveUpdated($this);

        $this->user
            ? $this->user->notify($notification)
            : Notification::route('mail', [$this->email => $this->name])->notify($notification);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [TestDriveStatus::Pending, TestDriveStatus::Confirmed], true)
            && $this->scheduled_at->isFuture();
    }
}
