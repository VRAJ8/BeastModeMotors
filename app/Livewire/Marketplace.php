<?php

namespace App\Livewire;

use App\Enums\FuelType;
use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\Vehicle;
use App\Support\UsStates;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Marketplace extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $make = '';

    #[Url(as: 'max_price', except: '')]
    public string $maxPrice = '';

    #[Url(as: 'min_year', except: '')]
    public string $minYear = '';

    #[Url(as: 'max_miles', except: '')]
    public string $maxMiles = '';

    #[Url(as: 'min_score', except: 0)]
    public int $minScore = 0;

    #[Url(except: '')]
    public string $state = '';

    #[Url(except: '')]
    public string $fuel = '';

    #[Url(except: 'score')]
    public string $sort = 'score';

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clear(): void
    {
        $this->reset('q', 'make', 'maxPrice', 'minYear', 'maxMiles', 'minScore', 'state', 'fuel', 'sort');
        $this->resetPage();
    }

    /**
     * A whole number from a URL or form value, clamped to what the column can hold. Postgres rejects
     * out-of-range parameters outright, so a ZIP code typed into "max miles" must never reach it raw.
     */
    private static function bounded(string|int $value, int $min, int $max): ?int
    {
        $digits = preg_replace('/[\s,$]/', '', (string) $value);

        return ctype_digit($digits) ? (int) min(max((int) substr($digits, 0, 12), $min), $max) : null;
    }

    private static function looksLikeYear(string $term): bool
    {
        return strlen($term) === 4 && ctype_digit($term) && (int) $term >= 1900 && (int) $term <= (int) date('Y') + 2;
    }

    public function render()
    {
        $minYear = self::bounded($this->minYear, 1900, 2100);
        $maxPrice = self::bounded($this->maxPrice, 0, 100_000_000);
        $maxMiles = self::bounded($this->maxMiles, 0, 2_000_000);
        $minScore = self::bounded($this->minScore, 0, 100) ?? 0;

        $query = Listing::public()
            ->with('vehicle.photos')
            ->whereHas('vehicle', function (Builder $v) use ($minYear) {
                $v->when($this->make, fn ($q) => $q->where('make', $this->make))
                    ->when($this->fuel, fn ($q) => $q->where('fuel_type', $this->fuel))
                    ->when($minYear !== null, fn ($q) => $q->where('year', '>=', $minYear))
                    ->when(trim($this->q) !== '', function ($q) {
                        foreach (preg_split('/\s+/', trim($this->q)) as $term) {
                            // whereLike is case-insensitive on every database (ILIKE on Postgres).
                            $q->where(fn ($w) => $w->whereLike('make', "%{$term}%")
                                ->orWhereLike('model', "%{$term}%")
                                ->orWhereLike('trim', "%{$term}%")
                                ->when(self::looksLikeYear($term), fn ($y) => $y->orWhere('year', (int) $term)));
                        }
                    });
            })
            ->when($maxPrice !== null, fn ($q) => $q->where('price_cents', '<=', $maxPrice * 100))
            ->when($maxMiles !== null, fn ($q) => $q->where('mileage', '<=', $maxMiles))
            ->when($minScore > 0, fn ($q) => $q->where('score', '>=', $minScore))
            ->when($this->state, fn ($q) => $q->where('state', $this->state));

        match ($this->sort) {
            'newest' => $query->latest('published_at'),
            'price_asc' => $query->orderBy('price_cents'),
            'price_desc' => $query->orderByDesc('price_cents'),
            'miles' => $query->orderBy('mileage'),
            default => $query->orderByDesc('score')->latest('published_at'),
        };

        // Ties on price or mileage would otherwise shuffle between pages on Postgres.
        $query->orderByDesc('listings.id');

        return view('livewire.marketplace', [
            'listings' => $query->paginate(12),
            'makes' => Vehicle::whereHas('listings', fn ($q) => $q->whereIn('status', [ListingStatus::Active, ListingStatus::Pending]))->distinct()->orderBy('make')->pluck('make'),
            'states' => UsStates::ALL,
            'fuels' => FuelType::options(),
            'filtered' => $this->q || $this->make || $this->maxPrice || $this->minYear || $this->maxMiles || $this->minScore || $this->state || $this->fuel,
        ]);
    }
}
