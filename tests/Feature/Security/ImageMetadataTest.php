<?php

use App\Livewire\Vehicle\Sell;
use App\Support\ImageMetadata;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * A real JPEG with an EXIF segment carrying a GPS marker and a comment.
 */
function jpegWithGps(): string
{
    $image = imagecreatetruecolor(8, 8);
    ob_start();
    imagejpeg($image);
    $jpeg = ob_get_clean();

    $exif = "Exif\0\0".'GPSLatitude=25.7617N GPSLongitude=80.1918W';
    $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
    $comment = 'shot at 1200 Main St';
    $com = "\xFF\xFE".pack('n', strlen($comment) + 2).$comment;

    return substr($jpeg, 0, 2).$app1.$com.substr($jpeg, 2);
}

it('strips GPS and comments from JPEGs but keeps a valid image', function () {
    $clean = ImageMetadata::strip(jpegWithGps());

    expect($clean)->not->toContain('GPSLatitude')
        ->and($clean)->not->toContain('1200 Main St')
        ->and(imagecreatefromstring($clean))->not->toBeFalse();
});

it('strips text and EXIF chunks from PNGs', function () {
    $image = imagecreatetruecolor(4, 4);
    ob_start();
    imagepng($image);
    $png = ob_get_clean();
    $text = "Comment\0taken at home";
    $chunk = pack('N', strlen($text)).'tEXt'.$text.pack('N', crc32('tEXt'.$text));
    $dirty = substr($png, 0, 33).$chunk.substr($png, 33); // after IHDR

    $clean = ImageMetadata::strip($dirty);

    expect($clean)->not->toContain('taken at home')->and(imagecreatefromstring($clean))->not->toBeFalse();
});

it('publishes car photos without location metadata', function () {
    Storage::fake('public');
    $vehicle = car();
    $upload = UploadedFile::fake()->createWithContent('front.jpg', jpegWithGps());

    Livewire::actingAs($vehicle->owner)->test(Sell::class, ['vehicle' => $vehicle])->set('photos', [$upload]);

    $stored = Storage::disk('public')->get($vehicle->photos()->first()->path);
    expect($stored)->not->toContain('GPSLatitude')->and(imagecreatefromstring($stored))->not->toBeFalse();
});
