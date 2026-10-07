<?php

namespace App\Support;

use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * The marketplace's search filters, cleaned up once and applied the same way by the marketplace page and by
 * saved-search alerts. Keys match the marketplace's URL parameters.
 */
final class ListingFilters
{
    private function __construct(
        public readonly string $q,
        public readonly string $make,
        public readonly ?int $maxPrice,
        public readonly ?int $minYear,
        public readonly ?int $maxMiles,
        public readonly int $minScore,
        public readonly string $state,
        public readonly string $fuel,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function from(array $input): self
    {
        $state = strtoupper(trim((string) ($input['state'] ?? '')));

        return new self(
            q: Str::limit(trim((string) preg_replace('/\s+/', ' ', (string) ($input['q'] ?? ''))), 80, ''),
            make: Str::limit(trim((string) ($input['make'] ?? '')), 60, ''),
            maxPrice: self::bounded($input['max_price'] ?? '', 0, 100_000_000),
            minYear: self::bounded($input['min_year'] ?? '', 1900, 2100),
            maxMiles: self::bounded($input['max_miles'] ?? '', 0, 2_000_000),
            minScore: self::bounded($input['min_score'] ?? '', 0, 100) ?? 0,
            state: isset(UsStates::ALL[$state]) ? $state : '',
            fuel: FuelType::tryFrom((string) ($input['fuel'] ?? ''))?->value ?? '',
        );
    }

    /**
     * Only the filters in use, as marketplace URL parameters.
     *
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return array_filter([
            'q' => $this->q,
            'make' => $this->make,
            'max_price' => $this->maxPrice,
            'min_year' => $this->minYear,
            'max_miles' => $this->maxMiles,
            'min_score' => $this->minScore,
            'state' => $this->state,
            'fuel' => $this->fuel,
        ], fn ($value) => $value !== '' && $value !== null && $value !== 0);
    }

    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }

    /** Identifies the same search however it was typed, so nobody saves it twice. */
    public function hash(): string
    {
        return hash('sha256', json_encode([...$this->toArray(), 'q' => Str::lower($this->q)]));
    }

    public function url(): string
    {
        return route('marketplace', $this->toArray());
    }

    /**
     * A short label, e.g. "BMW · Under $60,000 · 2018 or newer · Score 70+".
     */
    public function describe(): string
    {
        $parts = array_filter([
            $this->q !== '' ? "“{$this->q}”" : null,
            $this->make ?: null,
            $this->fuel ? FuelType::from($this->fuel)->getLabel() : null,
            $this->maxPrice !== null ? 'Under '.Number::currency($this->maxPrice, 'USD', precision: 0) : null,
            $this->minYear !== null ? "{$this->minYear} or newer" : null,
            $this->maxMiles !== null ? 'Under '.number_format($this->maxMiles).' mi' : null,
            $this->minScore > 0 ? "Score {$this->minScore}+" : null,
            $this->state ? UsStates::ALL[$this->state] : null,
        ]);

        return $parts ? implode(' · ', $parts) : 'All cars';
    }

    /**
     * @template TModel of \App\Models\Listing
     *
     * @param  Builder<TModel>  $listings
     * @return Builder<TModel>
     */
    public function apply(Builder $listings): Builder
    {
        return $listings
            ->whereHas('vehicle', function (Builder $v) {
                $v->when($this->make, fn ($q) => $q->where('make', $this->make))
                    ->when($this->fuel, fn ($q) => $q->where('fuel_type', $this->fuel))
                    ->when($this->minYear !== null, fn ($q) => $q->where('year', '>=', $this->minYear))
                    ->when($this->q !== '', function ($q) {
                        foreach (explode(' ', $this->q) as $term) {
                            // whereLike is case-insensitive on every database (ILIKE on Postgres).
                            $q->where(fn ($w) => $w->whereLike('make', "%{$term}%")
                                ->orWhereLike('model', "%{$term}%")
                                ->orWhereLike('trim', "%{$term}%")
                                ->when(self::looksLikeYear($term), fn ($y) => $y->orWhere('year', (int) $term)));
                        }
                    });
            })
            ->when($this->maxPrice !== null, fn ($q) => $q->where('price_cents', '<=', $this->maxPrice * 100))
            ->when($this->maxMiles !== null, fn ($q) => $q->where('mileage', '<=', $this->maxMiles))
            ->when($this->minScore > 0, fn ($q) => $q->where('score', '>=', $this->minScore))
            ->when($this->state, fn ($q) => $q->where('state', $this->state));
    }

    /**
     * A whole number from a URL or form value, clamped to what the column can hold. Postgres rejects
     * out-of-range parameters outright, so a ZIP code typed into "max miles" must never reach it raw.
     */
    private static function bounded(mixed $value, int $min, int $max): ?int
    {
        $digits = preg_replace('/[\s,$]/', '', (string) $value);

        return ctype_digit($digits) ? (int) min(max((int) substr($digits, 0, 12), $min), $max) : null;
    }

    private static function looksLikeYear(string $term): bool
    {
        return strlen($term) === 4 && ctype_digit($term) && (int) $term >= 1900 && (int) $term <= (int) date('Y') + 2;
    }
}
