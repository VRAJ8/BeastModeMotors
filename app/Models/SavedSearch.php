<?php

namespace App\Models;

use App\Support\ListingFilters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A marketplace search a buyer saved, with a daily email when new cars match it.
 */
class SavedSearch extends Model
{
    /** Enough for real shopping; keeps the daily alert run cheap. */
    public const PER_USER = 10;

    protected $fillable = ['user_id', 'filters', 'filters_hash', 'email_alerts', 'notified_through'];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'email_alerts' => 'boolean',
            'notified_through' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function criteria(): ListingFilters
    {
        return ListingFilters::from($this->filters ?? []);
    }
}
