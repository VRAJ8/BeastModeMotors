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

it('keeps photos in the main bucket when AWS_PUBLIC_BUCKET is left blank', function () {
    $disk = configWithEnv('filesystems.php', ['AWS_BUCKET' => 'one-bucket', 'AWS_PUBLIC_BUCKET' => '', 'AWS_URL' => '', 'AWS_PUBLIC_URL' => ''])['disks']['s3-public'];

    expect($disk['bucket'])->toBe('one-bucket')
        ->and($disk['url'])->toBeNull();

    $split = configWithEnv('filesystems.php', ['AWS_BUCKET' => 'receipts', 'AWS_PUBLIC_BUCKET' => 'photos', 'AWS_PUBLIC_URL' => 'https://photos.example.com'])['disks'];

    expect($split['s3']['bucket'])->toBe('receipts')
        ->and($split['s3-public']['bucket'])->toBe('photos')
        ->and($split['s3-public']['url'])->toBe('https://photos.example.com');
});

it('saves nothing when the storage service refuses an upload, so trying again is safe', function () {
    // An S3 disk with no bucket: the SDK rejects every write before sending anything.
    config([
        'filesystems.disks.refusing' => ['driver' => 's3', 'key' => 'k', 'secret' => 's', 'region' => 'us-east-1', 'bucket' => '', 'throw' => false],
        'passport.disks.photos' => 'refusing',
        'passport.disks.documents' => 'refusing',
    ]);
    $vehicle = car(['current_mileage' => 30000]);
    $records = $vehicle->records()->count();

    Livewire::actingAs($vehicle->owner)->test(Sell::class, ['vehicle' => $vehicle])
        ->set('photos', [UploadedFile::fake()->image('front.jpg')])
        ->assertHasErrors('photos')
        ->assertSet('photos', []);

    $form = Livewire::actingAs($vehicle->owner)->test(RecordForm::class, ['vehicle' => $vehicle])
        ->set('title', 'Brakes')->set('mileage', 30100)->set('provider_name', 'Shop')
        ->set('receipts', [UploadedFile::fake()->create('invoice.pdf', 50, 'application/pdf')]);
    $form->call('save')->assertHasErrors('receipts');
    $form->call('save')->assertHasErrors('receipts');

    expect($vehicle->photos()->count())->toBe(0)
        ->and($vehicle->records()->count())->toBe($records)
        ->and($vehicle->documents()->count())->toBe(0);
});
