<?php

namespace App\Models;

use App\Enums\InspectionResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The buyer's pre-purchase inspection, recorded against a standard checklist.
 */
class Inspection extends Model
{
    protected $fillable = ['deal_id', 'scheduled_for', 'location', 'inspector', 'results', 'summary', 'completed_at'];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'results' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Deal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function resultFor(string $key): InspectionResult
    {
        return InspectionResult::tryFrom($this->results[$key]['result'] ?? '') ?? InspectionResult::NotChecked;
    }

    /**
     * @return array<string, int>
     */
    public function tally(): array
    {
        $keys = collect(config('passport.inspection'))->flatMap(fn (array $items) => array_keys($items));

        return $keys->countBy(fn (string $key) => $this->resultFor($key)->value)->all();
    }
}
