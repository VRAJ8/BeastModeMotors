<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message in a deal room. Messages without a user are system events.
 */
class DealMessage extends Model
{
    protected $fillable = ['deal_id', 'user_id', 'body', 'risk_flags', 'read_at'];

    protected function casts(): array
    {
        return [
            'risk_flags' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Deal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSystem(): bool
    {
        return $this->user_id === null;
    }
}
