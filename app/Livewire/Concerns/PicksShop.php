<?php

namespace App\Livewire\Concerns;

use App\Models\Shop;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Lets owners choose a shop from the directory instead of typing its email.
 * The shop's address stays server-side; owners only ever see it masked.
 */
trait PicksShop
{
    public ?int $shopId = null;

    abstract protected function shopSearchTerm(): string;

    abstract protected function applyPickedShop(?Shop $shop): void;

    /**
     * @return Collection<int, Shop>
     */
    #[Computed]
    public function shopSuggestions(): Collection
    {
        $term = trim($this->shopSearchTerm());

        if ($this->shopId || mb_strlen($term) < 2) {
            return collect();
        }

        return Shop::directory()->whereLike('name', "%{$term}%")->orderBy('name')->take(5)->get();
    }

    #[Computed]
    public function pickedShop(): ?Shop
    {
        return $this->shopId ? Shop::directory()->find($this->shopId) : null;
    }

    public function pickShop(int $id): void
    {
        $shop = Shop::directory()->findOrFail($id);
        $this->shopId = $shop->getKey();
        $this->applyPickedShop($shop);
    }

    public function clearShop(): void
    {
        $this->shopId = null;
        $this->applyPickedShop(null);
    }
}
