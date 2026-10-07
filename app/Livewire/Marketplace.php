<?php

namespace App\Livewire;

use App\Enums\FuelType;
use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\Vehicle;
use App\Support\ListingFilters;
use App\Support\UsStates;
use Illuminate\Support\Facades\Auth;
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
     * Keep this search and email the buyer when new cars match it.
     */
    public function saveSearch()
    {
        $filters = $this->filters();

        if (! Auth::check()) {
            redirect()->setIntendedUrl($filters->url());

            return $this->redirectRoute('login');
        }

        $user = Auth::user();

        if ($filters->isEmpty() || $user->savedSearches()->where('filters_hash', $filters->hash())->exists()) {
            return null;
        }

        if ($user->savedSearches()->count() >= SavedSearch::PER_USER) {
            $this->addError('saveSearch', 'You can save up to '.SavedSearch::PER_USER.' searches. Delete one on your Saved page first.');

            return null;
        }

        $user->savedSearches()->create([
            'filters' => $filters->toArray(),
            'filters_hash' => $filters->hash(),
            'notified_through' => now(),
        ]);

        $this->dispatch('toast', message: 'Search saved. We\'ll email you when new cars match.');

        return null;
    }

    private function filters(): ListingFilters
    {
        return ListingFilters::from([
            'q' => $this->q, 'make' => $this->make, 'max_price' => $this->maxPrice, 'min_year' => $this->minYear,
            'max_miles' => $this->maxMiles, 'min_score' => $this->minScore, 'state' => $this->state, 'fuel' => $this->fuel,
        ]);
    }

    public function render()
    {
        $filters = $this->filters();
        $query = $filters->apply(Listing::public()->with('vehicle.photos'));

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
            // Keep the chosen make in the list even with nothing for sale, as when a saved search is reopened.
            'makes' => Vehicle::whereHas('listings', fn ($q) => $q->whereIn('status', [ListingStatus::Active, ListingStatus::Pending]))->distinct()->pluck('make')
                ->push($filters->make)->filter()->unique()->sort()->values(),
            'states' => UsStates::ALL,
            'fuels' => FuelType::options(),
            'filtered' => $this->q || $this->make || $this->maxPrice || $this->minYear || $this->maxMiles || $this->minScore || $this->state || $this->fuel,
            'searchable' => ! $filters->isEmpty(),
            'searchSaved' => Auth::check() && Auth::user()->savedSearches()->where('filters_hash', $filters->hash())->exists(),
        ]);
    }
}
