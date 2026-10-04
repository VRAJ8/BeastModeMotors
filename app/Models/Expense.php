<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A running cost. Expenses are private to the owner who logged them and never transfer on sale.
 */
class Expense extends Model
{
    protected $fillable = ['vehicle_id', 'ownership_id', 'category', 'amount_cents', 'spent_on', 'odometer', 'volume', 'notes'];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'spent_on' => 'date',
            'volume' => 'float',
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
