<?php

namespace App\Support;

/**
 * Studio-style side-profile illustrations used for the demo cars, so the public demo never
 * depends on third-party photos. Real users upload their own photos.
 */
final class CarIllustration
{
    /** Body outline, glasshouse, front and rear wheel centres (x) for each shape. */
    private const SHAPES = [
        'coupe' => [
            'M120 300 C120 272 150 256 192 251 L292 241 C334 201 382 171 452 169 C522 167 582 201 642 246 C672 251 692 266 697 291 L697 318 L120 318 Z',
            'M326 240 C360 206 404 189 452 188 C500 187 544 210 584 240 Z',
            226, 592,
        ],
        'sedan' => [
            'M110 300 C112 276 140 262 180 258 L270 252 L340 196 C360 182 385 176 420 176 L520 178 C550 180 570 190 595 210 L640 250 C675 256 695 270 698 292 L698 318 L110 318 Z',
            'M354 248 L396 202 C406 194 416 191 431 191 L515 192 C540 193 555 201 572 216 L600 248 Z',
            214, 598,
        ],
        'hatch' => [
            'M110 305 C110 275 135 262 175 258 L260 250 L330 185 C345 172 362 168 385 168 L560 170 C585 172 598 180 610 198 L650 255 C680 262 695 278 698 300 L698 318 L110 318 Z',
            'M344 246 L392 190 C400 184 408 183 420 183 L556 185 C574 186 584 192 592 204 L626 246 Z',
            214, 596,
        ],
        'suv' => [
            'M105 305 L105 262 C105 246 120 236 140 233 L232 226 L302 160 C314 150 327 146 347 146 L562 146 C587 146 602 152 617 168 L668 228 C690 234 700 250 700 270 L700 318 L105 318 Z',
            'M314 222 L362 170 L560 170 L610 222 Z',
            214, 600,
        ],
        'truck' => [
            'M100 318 L100 252 C100 240 110 233 125 233 L382 233 L382 160 C382 150 390 146 402 146 L522 146 C542 146 554 152 564 166 L612 226 C652 230 692 241 699 263 L701 318 Z',
            'M402 226 L402 168 L520 168 L568 226 Z',
            204, 604,
        ],
    ];

    private const COLORS = [
        'white' => '#f2f1ec', 'chalk' => '#e4e2da', 'silver' => '#bfc3c7', 'gray' => '#6f757c', 'grey' => '#6f757c',
        'black' => '#22252a', 'red' => '#c8332d', 'blue' => '#2d5fa8', 'green' => '#2f6b4f', 'yellow' => '#e7b52c',
        'orange' => '#ef6a21', 'brown' => '#6e4c35', 'beige' => '#cdbb98',
    ];

    public static function svg(string $shape, ?string $color): string
    {
        [$body, $glass, $front, $rear] = self::SHAPES[$shape] ?? self::SHAPES['sedan'];
        $paint = self::paint($color);

        $wheel = fn (int $x) => <<<SVG
            <g transform="translate({$x} 318)">
                <circle r="50" fill="#16181b"/>
                <circle r="31" fill="#c9ccd0"/>
                <circle r="31" fill="none" stroke="#9ea3a8" stroke-width="2"/>
                <g stroke="#8d9297" stroke-width="5" stroke-linecap="round">
                    <path d="M0 -24 V24"/><path d="M-24 0 H24"/><path d="M-17 -17 L17 17"/><path d="M-17 17 L17 -17"/>
                </g>
                <circle r="8" fill="#5b6066"/>
            </g>
            SVG;

        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 500">
                <defs>
                    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f7f5f0"/><stop offset="1" stop-color="#e6e1d6"/></linearGradient>
                    <linearGradient id="shine" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset=".45" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".18"/></linearGradient>
                </defs>
                <rect width="800" height="500" fill="url(#bg)"/>
                <rect y="368" width="800" height="132" fill="#ddd7ca"/>
                <ellipse cx="404" cy="370" rx="330" ry="18" fill="#000" opacity=".16"/>
                <path d="{$body}" fill="{$paint}"/>
                <path d="{$body}" fill="url(#shine)"/>
                <path d="{$glass}" fill="#2a3138" opacity=".88"/>
                <path d="M130 292 H690" stroke="#000" stroke-opacity=".12" stroke-width="3"/>
                {$wheel($front)}
                {$wheel($rear)}
            </svg>
            SVG;
    }

    private static function paint(?string $color): string
    {
        $color = strtolower((string) $color);

        foreach (self::COLORS as $name => $hex) {
            if (str_contains($color, $name)) {
                return $hex;
            }
        }

        return '#7b8189';
    }
}
