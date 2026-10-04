<?php

namespace App\Http\Controllers;

use App\Enums\TestDriveStatus;
use App\Models\TestDrive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('garage', [
            'favorites' => $user->favorites()->with('brand')->latest('favorites.created_at')->get(),
            'upcoming' => $user->testDrives()->with('vehicle.brand')->upcoming()->orderBy('scheduled_at')->get(),
            'past' => $user->testDrives()->with('vehicle.brand')
                ->where(fn ($q) => $q->where('scheduled_at', '<', now())->orWhereIn('status', [TestDriveStatus::Cancelled, TestDriveStatus::Completed]))
                ->latest('scheduled_at')->take(10)->get(),
            'leads' => $user->leads()->with('vehicle.brand')->latest()->take(10)->get(),
            'notifications' => $user->notifications()->take(8)->get(),
        ]);
    }

    public function cancelTestDrive(Request $request, TestDrive $testDrive): RedirectResponse
    {
        abort_unless($testDrive->user_id === $request->user()->id, 403);

        if (! $testDrive->isCancellable()) {
            return back()->with('status', 'This test drive can no longer be cancelled.');
        }

        $testDrive->update(['status' => TestDriveStatus::Cancelled]);

        return back()->with('status', "Test drive {$testDrive->reference} cancelled.");
    }

    public function markNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
