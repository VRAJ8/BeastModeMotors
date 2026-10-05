<?php

use App\Livewire\RecordForm;
use App\Livewire\Vehicle\Sell;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('stores receipts and photos on whichever disks are configured', function () {
    config(['passport.disks.documents' => 's3', 'passport.disks.photos' => 's3-public']);
    Storage::fake('s3');
    Storage::fake('s3-public');
    Storage::fake('local');
    Storage::fake('public');

    $vehicle = car(['current_mileage' => 30000]);

    Livewire::actingAs($vehicle->owner)->test(RecordForm::class, ['vehicle' => $vehicle])
        ->set('title', 'Brakes')->set('mileage', 30100)->set('provider_name', 'Shop')
        ->set('receipts', [UploadedFile::fake()->create('invoice.pdf', 50, 'application/pdf')])
        ->call('save')->assertHasNoErrors();

    Livewire::actingAs($vehicle->owner)->test(Sell::class, ['vehicle' => $vehicle])
        ->set('photos', [UploadedFile::fake()->image('front.jpg')]);

    $document = $vehicle->documents()->first();
    $photo = $vehicle->photos()->first();

    Storage::disk('s3')->assertExists($document->path);
    Storage::disk('s3-public')->assertExists($photo->path);
    expect(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);

    $this->actingAs($vehicle->owner)->get(route('documents.show', [$vehicle, $document]))->assertOk();

    $document->delete();
    Storage::disk('s3')->assertMissing($document->path);
});
