<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

/**
 * A request for the shop that did the work to confirm a record, answered through a signed link.
 */
class ShopVerification extends Model
{
    protected $fillable = [
        'service_record_id', 'requested_by', 'shop_name', 'shop_email', 'status', 'responder_name', 'response_note',
        'responder_ip', 'responded_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VerificationStatus::class,
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceRecord, $this>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(ServiceRecord::class, 'service_record_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isAnswerable(): bool
    {
        return $this->status === VerificationStatus::Pending && $this->expires_at->isFuture();
    }

    public function signedUrl(): string
    {
        return URL::temporarySignedRoute('verify.show', $this->expires_at, ['verification' => $this->getKey()]);
    }
}
