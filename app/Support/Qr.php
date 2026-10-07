<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class Qr
{
    public static function svg(string $data, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        // Drop the XML prolog so the SVG can be inlined in HTML.
        return trim(preg_replace('/^<\?xml.*?\?>/', '', $writer->writeString($data)));
    }
}
