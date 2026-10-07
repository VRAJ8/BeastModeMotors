<?php

use App\Enums\ListingStatus;
use App\Enums\ReportStatus;
use App\Filament\Resources\Listings\Pages\ManageListings;
use App\Filament\Resources\Reports\Pages\ManageReports;
use App\Models\User;
use Filament\FontProviders\LocalFontProvider;
use Livewire\Livewire;

beforeEach(fn () => $this->admin = User::factory()->admin()->create());

it('is only open to trust & safety staff', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    $this->actingAs($this->admin)->get('/admin')->assertOk();
});

it('loads every admin screen', function (string $path) {
    liveListing()->reports()->create(['reason' => 'scam']);
    deal()->messages()->create(['user_id' => null, 'body' => 'x', 'risk_flags' => [['label' => 'Test', 'severity' => 'high']]]);

    $this->actingAs($this->admin)->get($path)->assertOk();
})->with(['/admin', '/admin/listings', '/admin/reports', '/admin/deals', '/admin/shop-verifications', '/admin/vehicles', '/admin/users', '/admin/shops']);

it('removes a reported listing and closes the report', function () {
    $listing = liveListing();
    $listing->update(['share_link_id' => $listing->vehicle->shareLinks()->create(['label' => 'Listing'])->id]);
    $report = $listing->reports()->create(['reason' => 'scam']);

    Livewire::actingAs($this->admin)->test(ManageReports::class)
        ->callTableAction('removeListing', $report);

    expect($listing->fresh()->status)->toBe(ListingStatus::Removed)
        ->and($report->fresh()->status)->toBe(ReportStatus::Actioned)
        ->and($listing->shareLink->fresh()->isActive())->toBeFalse();

    $this->get(route('listings.show', $listing))->assertOk(); // admins can still look
    auth()->logout();
    $this->get(route('listings.show', $listing))->assertNotFound();
});

it('removes a listing with a reason', function () {
    $listing = liveListing();

    Livewire::actingAs($this->admin)->test(ManageListings::class)
        ->callTableAction('remove', $listing, ['reason' => 'Stolen photos']);

    expect($listing->fresh()->removed_reason)->toBe('Stolen photos');
});

it('keeps staff details and fonts on our own servers', function () {
    $panel = Filament\Facades\Filament::getPanel('admin');
    $staff = User::factory()->admin()->create(['name' => 'Morgan Hale']);

    expect($panel->getFontProvider())->toBe(LocalFontProvider::class)
        ->and(Filament\Facades\Filament::getUserAvatarUrl($staff))->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(str(Filament\Facades\Filament::getUserAvatarUrl($staff))->after('base64,')))->toContain('>MH<');
});
