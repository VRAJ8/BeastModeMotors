<?php

namespace App\Http\Controllers;

use App\Enums\VerificationStatus;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Support\UsStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $search = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $state = is_string($request->query('state')) ? $request->query('state') : '';

        $shops = Shop::directory()
            ->withCount(['verifications as confirmed_count' => fn ($q) => $q->where('status', VerificationStatus::Confirmed)])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->whereLike('name', "%{$search}%")->orWhereLike('city', "%{$search}%")))
            ->when(isset(UsStates::ALL[$state]), fn ($q) => $q->where('state', $state))
            ->orderByDesc('confirmed_count')
            ->paginate(12)
            ->withQueryString();

        return view('shops.index', ['shops' => $shops, 'search' => $search, 'state' => $state, 'states' => UsStates::ALL]);
    }

    public function show(Shop $shop): View
    {
        abort_unless(Shop::directory()->whereKey($shop->getKey())->exists(), 404);

        return view('shops.show', [
            'shop' => $shop->load('verifications'),
            'stats' => $shop->stats(),
            'recent' => ServiceRecord::where('shop_id', $shop->getKey())
                ->whereNotNull('verified_at')
                ->with('vehicle')
                ->latest('performed_on')
                ->take(8)
                ->get(),
        ]);
    }

    public function edit(Request $request, Shop $shop): View
    {
        return view('shops.edit', ['shop' => $shop, 'states' => UsStates::ALL, 'action' => $request->fullUrl()]);
    }

    public function update(Request $request, Shop $shop): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', Rule::in(array_keys(UsStates::ALL))],
            'phone' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'about' => ['nullable', 'string', 'max:1000'],
            'specialties' => ['nullable', 'string', 'max:200'],
        ]);

        $data['specialties'] = collect(explode(',', (string) ($data['specialties'] ?? '')))
            ->map(fn ($s) => trim($s))->filter()->take(8)->values()->all() ?: null;

        $shop->update($data + ['profile_completed_at' => now()]);

        return back()->with('toast', 'Profile saved.');
    }
}
