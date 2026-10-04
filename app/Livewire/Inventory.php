<?php

namespace App\Livewire;

use App\Enums\BodyType;
use App\Enums\Condition;
use App\Enums\FuelType;
use App\Enums\VehicleStatus;
use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Inventory extends Component
{
    use WithPagination;

    public const SORTS = [
        'newest' => 'Newest listings',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'hp_desc' => 'Most powerful',
        'mileage_asc' => 'Lowest mileage',
        'year_desc' => 'Model year: newest',
    ];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<int, string> */
    #[Url(except: [])]
    public array $brands = [];

    #[Url(except: '')]
    public string $body = '';

    #[Url(except: '')]
    public string $condition = '';

    #[Url(except: '')]
    public string $fuel = '';

    #[Url(as: 'min_price', except: null)]
    public ?int $minPrice = null;

    #[Url(as: 'max_price', except: null)]
    public ?int $maxPrice = null;

    #[Url(as: 'min_year', except: null)]
    public ?int $minYear = null;

    #[Url(as: 'min_hp', except: null)]
    public ?int $minHp = null;

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    #[Url(as: 'sold', except: false)]
    public bool $includeSold = false;

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'brands', 'body', 'condition', 'fuel', 'minPrice', 'maxPrice', 'minYear', 'minHp', 'sort', 'includeSold');
        $this->resetPage();
    }

    public function removeBrand(string $slug): void
    {
        $this->brands = array_values(array_diff($this->brands, [$slug]));
        $this->resetPage();
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        return collect([$this->search, $this->body, $this->condition, $this->fuel, $this->minPrice, $this->maxPrice, $this->minYear, $this->minHp])
            ->filter(fn ($v) => filled($v))
            ->count() + count($this->brands) + ($this->includeSold ? 1 : 0);
    }

    #[Computed]
    public function brandOptions()
    {
        return Brand::query()
            ->withCount(['vehicles' => fn (Builder $q) => $q->published()])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    protected function query(): Builder
    {
        return Vehicle::query()
            ->with('brand')
            ->published()
            ->when(! $this->includeSold, fn (Builder $q) => $q->where('status', '!=', VehicleStatus::Sold))
            ->when($this->search !== '', function (Builder $q) {
                $terms = preg_split('/\s+/', trim($this->search));
                foreach ($terms as $term) {
                    $q->where(fn (Builder $q) => $q
                        ->where('model', 'like', "%{$term}%")
                        ->orWhere('trim', 'like', "%{$term}%")
                        ->orWhere('engine', 'like', "%{$term}%")
                        ->orWhere('exterior_color', 'like', "%{$term}%")
                        ->orWhere('year', $term)
                        ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$term}%")));
                }
            })
            ->when($this->brands, fn (Builder $q) => $q->whereHas('brand', fn (Builder $b) => $b->whereIn('slug', $this->brands)))
            ->when(BodyType::tryFrom($this->body), fn (Builder $q, BodyType $body) => $q->where('body_type', $body))
            ->when(Condition::tryFrom($this->condition), fn (Builder $q, Condition $c) => $q->where('condition', $c))
            ->when(FuelType::tryFrom($this->fuel), fn (Builder $q, FuelType $f) => $q->where('fuel_type', $f))
            ->when($this->minPrice, fn (Builder $q) => $q->where('price', '>=', $this->minPrice))
            ->when($this->maxPrice, fn (Builder $q) => $q->where('price', '<=', $this->maxPrice))
            ->when($this->minYear, fn (Builder $q) => $q->where('year', '>=', $this->minYear))
            ->when($this->minHp, fn (Builder $q) => $q->where('horsepower', '>=', $this->minHp))
            ->tap(fn (Builder $q) => match ($this->sort) {
                'price_asc' => $q->orderBy('price'),
                'price_desc' => $q->orderByDesc('price'),
                'hp_desc' => $q->orderByDesc('horsepower'),
                'mileage_asc' => $q->orderBy('mileage'),
                'year_desc' => $q->orderByDesc('year'),
                default => $q->orderByDesc('published_at'),
            })
            ->orderBy('id');
    }

    public function paginationView(): string
    {
        return 'livewire.pagination';
    }

    public function render()
    {
        return view('livewire.inventory', [
            'vehicles' => $this->query()->paginate(9),
        ]);
    }
}
