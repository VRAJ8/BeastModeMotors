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

    public function render()
    {
        $query = Listing::public()
            ->with('vehicle.photos')
            ->whereHas('vehicle', function (Builder $v) {
                $v->when($this->make, fn ($q) => $q->where('make', $this->make))
                    ->when($this->fuel, fn ($q) => $q->where('fuel_type', $this->fuel))
                    ->when($this->minYear !== '', fn ($q) => $q->where('year', '>=', (int) $this->minYear))
                    ->when(trim($this->q) !== '', function ($q) {
                        foreach (preg_split('/\s+/', trim($this->q)) as $term) {
                            $q->where(fn ($w) => $w->where('make', 'like', "%{$term}%")
                                ->orWhere('model', 'like', "%{$term}%")
                                ->orWhere('trim', 'like', "%{$term}%")
                                ->orWhere('year', $term));
                        }
                    });
            })
            ->when($this->maxPrice !== '', fn ($q) => $q->where('price_cents', '<=', (int) $this->maxPrice * 100))
            ->when($this->maxMiles !== '', fn ($q) => $q->where('mileage', '<=', (int) $this->maxMiles))
            ->when($this->minScore > 0, fn ($q) => $q->where('score', '>=', $this->minScore))
            ->when($this->state, fn ($q) => $q->where('state', $this->state));

        match ($this->sort) {
            'newest' => $query->latest('published_at'),
            'price_asc' => $query->orderBy('price_cents'),
            'price_desc' => $query->orderByDesc('price_cents'),
            'miles' => $query->orderBy('mileage'),
            default => $query->orderByDesc('score')->latest('published_at'),
        };

        return view('livewire.marketplace', [
            'listings' => $query->paginate(12),
            'makes' => Vehicle::whereHas('listings', fn ($q) => $q->whereIn('status', [ListingStatus::Active, ListingStatus::Pending]))->distinct()->orderBy('make')->pluck('make'),
            'states' => UsStates::ALL,
            'fuels' => FuelType::options(),
            'filtered' => $this->q || $this->make || $this->maxPrice || $this->minYear || $this->maxMiles || $this->minScore || $this->state || $this->fuel,
        ]);
    }
}
