<?php

namespace App\Livewire;

use App\Support\CompareList;
use Livewire\Attributes\On;
use Livewire\Component;

class CompareBadge extends Component
{
    #[On('compare-updated')]
    public function refresh(): void {}

    public function render(CompareList $compare)
    {
        return view('livewire.compare-badge', ['count' => $compare->count()]);
    }
}
