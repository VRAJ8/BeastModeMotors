<?php

namespace App\Support;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

/**
 * Session-backed list of up to three vehicles the visitor wants to compare.
 */
class CompareList
{
    public const MAX = 3;

    protected const KEY = 'compare';

    /**
     * @return array<int, int>
     */
    public function ids(): array
    {
        return array_values(array_map('intval', session(self::KEY, [])));
    }

    public function has(Vehicle|int $vehicle): bool
    {
        return in_array($this->id($vehicle), $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function isFull(): bool
    {
        return $this->count() >= self::MAX;
    }

    /**
     * Toggle a vehicle in or out. Returns false when adding to a full list.
     */
    public function toggle(Vehicle|int $vehicle): bool
    {
        $id = $this->id($vehicle);

        if ($this->has($id)) {
            $this->remove($id);

            return true;
        }

        if ($this->isFull()) {
            return false;
        }

        session()->put(self::KEY, [...$this->ids(), $id]);

        return true;
    }

    public function remove(Vehicle|int $vehicle): void
    {
        $id = $this->id($vehicle);
        session()->put(self::KEY, array_values(array_diff($this->ids(), [$id])));
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function vehicles(): Collection
    {
        $ids = $this->ids();

        return Vehicle::with('brand')
            ->published()
            ->whereKey($ids)
            ->get()
            ->sortBy(fn (Vehicle $v) => array_search($v->id, $ids, true))
            ->values();
    }

    protected function id(Vehicle|int $vehicle): int
    {
        return $vehicle instanceof Vehicle ? $vehicle->id : $vehicle;
    }
}
