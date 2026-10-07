<?php

namespace App\Livewire;

use App\Models\Listing;
use App\Models\SavedSearch;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * A buyer's saved marketplace searches: what matches now, and whether to email new matches.
 */
class SavedSearches extends Component
{
    public function toggleAlerts(int $id): void
    {
        $search = $this->find($id);
        $search->update(['email_alerts' => ! $search->email_alerts, 'notified_through' => now()]);
    }

    public function delete(int $id): void
    {
        $this->find($id)->delete();
        $this->dispatch('toast', message: 'Search deleted.');
    }

    public function render()
    {
        return view('livewire.saved-searches', [
            'searches' => Auth::user()->savedSearches()->get()->map(fn (SavedSearch $search) => [
                'search' => $search,
                'criteria' => $search->criteria(),
                'matches' => $search->criteria()->apply(Listing::public())->count(),
            ]),
        ]);
    }

    private function find(int $id): SavedSearch
    {
        return Auth::user()->savedSearches()->findOrFail($id);
    }
}
