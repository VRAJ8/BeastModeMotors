<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Removes EXIF/XMP/text metadata (GPS position, device serials, timestamps) from uploaded images
 * without re-encoding them, so pixels and quality are untouched. Unknown formats pass through.
 */
final class ImageMetadata
{
    /**
     * Store an upload on a disk with its metadata stripped. Returns the stored path.
     *
     * Throws when the disk refuses the write (the storage error itself is logged by the disk), so no photo or
     * receipt row is ever saved pointing at a file that doesn't exist.
     */
    public static function storeClean(UploadedFile $file, string $directory, string $disk): string
    {
        $path = trim($directory, '/').'/'.$file->hashName();

        if (! Storage::disk($disk)->put($path, self::strip((string) file_get_contents($file->getRealPath())))) {
            throw new RuntimeException("Could not store the upload on the [{$disk}] disk.");
        }

        return $path;
    }

    public static function strip(string $bytes): string
    {
        return match (true) {
            str_starts_with($bytes, "\xFF\xD8") => self::jpeg($bytes),
            str_starts_with($bytes, "\x89PNG\r\n\x1A\n") => self::png($bytes),
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => self::webp($bytes),
            default => $bytes,
        };
    }

    /**
     * Drop APP1 (EXIF/XMP), APP12/APP13 (Photoshop/IPTC) and comment segments; keep everything else.
     */
    private static function jpeg(string $b): string
    {
        $out = "\xFF\xD8";
        $i = 2;
        $len = strlen($b);

        while ($i + 4 <= $len && $b[$i] === "\xFF") {
            $marker = ord($b[$i + 1]);

            if ($marker === 0xDA) { // start of scan: the rest is image data
                return $out.substr($b, $i);
            }

            if ($marker === 0xD8 || $marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $out .= substr($b, $i, 2);
                $i += 2;

                continue;
            }

            $size = unpack('n', substr($b, $i + 2, 2))[1];
            $segment = substr($b, $i, $size + 2);

            if (! in_array($marker, [0xE1, 0xEC, 0xED, 0xFE], true)) {
                $out .= $segment;
            }

            $i += $size + 2;
        }

        return $out.substr($b, $i);
    }

    private static function png(string $b): string
    {
        $out = substr($b, 0, 8);
        $i = 8;
        $len = strlen($b);

        while ($i + 8 <= $len) {
            $size = unpack('N', substr($b, $i, 4))[1];
            $type = substr($b, $i + 4, 4);
            $chunk = substr($b, $i, $size + 12);

            if (! in_array($type, ['eXIf', 'tEXt', 'zTXt', 'iTXt', 'tIME'], true)) {
                $out .= $chunk;
            }

            $i += $size + 12;
        }

        return $out;
    }

    private static function webp(string $b): string
    {
        $body = 'WEBP';
        $i = 12;
        $len = strlen($b);

        while ($i + 8 <= $len) {
            $type = substr($b, $i, 4);
            $size = unpack('V', substr($b, $i + 4, 4))[1];
            $chunk = substr($b, $i, 8 + $size + ($size % 2));

            if ($type === 'VP8X') {
                $chunk[8] = chr(ord($chunk[8]) & ~0x0C); // clear the EXIF and XMP flags
            }

            if (! in_array($type, ['EXIF', 'XMP '], true)) {
                $body .= $chunk;
            }

            $i += 8 + $size + ($size % 2);
        }

        return 'RIFF'.pack('V', strlen($body)).$body;
    }
}
