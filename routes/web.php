<?php

use App\Http\Controllers\DealController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopVerificationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// Numeric ids only (and small enough for a bigint): anything else is a 404, not a database error.
Route::pattern('vehicle', '[0-9]{1,18}');
Route::pattern('deal', '[0-9]{1,18}');
Route::pattern('document', '[0-9]{1,18}');
Route::pattern('record', '[0-9]{1,18}');
Route::pattern('verification', '[0-9]{1,18}');

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::view('/', 'pages.home')->name('home');
Route::view('/how-it-works', 'pages.how-it-works')->name('how-it-works');
Route::view('/safety', 'pages.safety')->name('safety');
Route::view('/vin-check', 'pages.vin-check')->name('vin-check');

Route::get('/cars', [MarketplaceController::class, 'index'])->name('marketplace');
Route::get('/cars/{listing}', [MarketplaceController::class, 'show'])->name('listings.show');

// Public passports, shared by link (privacy settings are per link).
Route::get('/p/{shareLink}', [PassportController::class, 'show'])->name('passport.show');
Route::get('/p/{shareLink}/report.pdf', [PassportController::class, 'pdf'])->name('passport.pdf');
Route::get('/p/{shareLink}/documents/{document}', [PassportController::class, 'document'])->name('passport.document');

Route::get('/shops', [ShopController::class, 'index'])->name('shops.index');
Route::get('/shops/{shop}', [ShopController::class, 'show'])->name('shops.show');

// Shops answer verification requests and edit their profile through signed, expiring links — no account needed.
Route::middleware(['signed', 'throttle:20,1'])->group(function () {
    Route::get('/verify/{verification}', [ShopVerificationController::class, 'show'])->name('verify.show');
    Route::post('/verify/{verification}', [ShopVerificationController::class, 'store'])->name('verify.store');
    Route::get('/shops/{shop:id}/profile', [ShopController::class, 'edit'])->name('shops.edit');
    Route::post('/shops/{shop:id}/profile', [ShopController::class, 'update'])->name('shops.update');
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /admin\nDisallow: /garage\nDisallow: /deals\nDisallow: /p/\nDisallow: /verify/\n\nSitemap: ".route('sitemap')."\n")
    ->header('Content-Type', 'text/plain'))->name('robots');

/*
|--------------------------------------------------------------------------
| Signed-in
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/garage', [GarageController::class, 'index'])->name('garage');
    Route::get('/garage/add', [GarageController::class, 'create'])->name('vehicles.create');

    Route::prefix('/garage/{vehicle}')->middleware('can:manage,vehicle')->controller(VehicleController::class)->group(function () {
        Route::get('/', 'overview')->name('vehicles.show');
        Route::get('/history', 'history')->name('vehicles.history');
        Route::get('/history/new', 'createRecord')->name('records.create');
        Route::get('/history/{record}/edit', 'editRecord')->name('records.edit')->scopeBindings();
        Route::get('/maintenance', 'maintenance')->name('vehicles.maintenance');
        Route::get('/documents', 'documents')->name('vehicles.documents');
        Route::get('/costs', 'costs')->name('vehicles.costs');
        Route::get('/recalls', 'recalls')->name('vehicles.recalls');
        Route::get('/share', 'share')->name('vehicles.share');
        Route::get('/share/sign', 'windowSign')->name('vehicles.sign');
        Route::get('/sell', 'sell')->name('vehicles.sell');
        Route::get('/settings', 'settings')->name('vehicles.settings');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show')->scopeBindings();
    });

    Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
    Route::get('/deals/{deal}', [DealController::class, 'show'])->name('deals.show')->middleware('can:view,deal');
    Route::get('/deals/{deal}/bill-of-sale.pdf', [DealController::class, 'billOfSale'])->name('deals.bill-of-sale')->middleware('can:view,deal');
    Route::get('/deals/{deal}/odometer-disclosure.pdf', [DealController::class, 'odometerDisclosure'])->name('deals.odometer-disclosure')->middleware('can:view,deal');

    Route::get('/saved', [MarketplaceController::class, 'saved'])->name('saved');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->name('notifications.open')->whereUuid('id');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
