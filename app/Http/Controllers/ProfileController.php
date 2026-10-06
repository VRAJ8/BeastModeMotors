<?php

namespace App\Http\Controllers;

use App\Enums\DealStatus;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Deal;
use App\Services\DealFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, DealFlow $deals): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // An agreed sale involves someone else's money and plans: finish or cancel it first.
        if (Deal::involving($user)->where('status', DealStatus::Agreed)->exists()) {
            return back()->withErrors(['password' => 'You have an agreed sale in progress. Complete or cancel it in your deal room before deleting your account.'], 'userDeletion');
        }

        // Tell everyone mid-conversation, rather than letting their deals silently disappear.
        Deal::involving($user)->where('status', DealStatus::Open)->get()
            ->each(fn (Deal $deal) => $deals->cancel($deal, $user, 'Account deleted'));

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
