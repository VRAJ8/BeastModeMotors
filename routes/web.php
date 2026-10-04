<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/inventory', [VehicleController::class, 'index'])->name('vehicles.index');
Route::get('/inventory/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');

Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/compare', [CompareController::class, 'index'])->name('compare');
Route::delete('/compare', [CompareController::class, 'clear'])->name('compare.clear');
Route::delete('/compare/{vehicle}', [CompareController::class, 'destroy'])->name('compare.remove');

Route::view('/sell', 'sell')->name('sell');
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /admin\nDisallow: /garage\n\nSitemap: ".route('sitemap')."\n")
    ->header('Content-Type', 'text/plain'))->name('robots');

Route::middleware('auth')->group(function () {
    Route::get('/garage', [GarageController::class, 'index'])->name('garage');
    Route::patch('/garage/test-drives/{testDrive}/cancel', [GarageController::class, 'cancelTestDrive'])->name('garage.test-drives.cancel');
    Route::post('/garage/notifications/read', [GarageController::class, 'markNotificationsRead'])->name('garage.notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
