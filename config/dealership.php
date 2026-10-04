<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Showroom details
    |--------------------------------------------------------------------------
    |
    | Contact details shown across the storefront and in emails.
    |
    */

    'name' => env('APP_NAME', 'Beast Mode Motors'),
    'email' => env('DEALERSHIP_EMAIL', 'sales@beastmodemotors.test'),
    'phone' => env('DEALERSHIP_PHONE', '+1 (305) 555-0199'),
    'address' => env('DEALERSHIP_ADDRESS', '1200 Biscayne Blvd, Miami, FL 33132'),
    'currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Test drives
    |--------------------------------------------------------------------------
    |
    | Opening hours per ISO weekday (1 = Monday ... 7 = Sunday). A null entry
    | means the showroom is closed. Slots are generated every `slot_minutes`.
    |
    */

    'test_drives' => [
        'slot_minutes' => 60,
        'booking_window_days' => 30,
        'min_notice_hours' => 2,
        'hours' => [
            1 => ['10:00', '19:00'],
            2 => ['10:00', '19:00'],
            3 => ['10:00', '19:00'],
            4 => ['10:00', '19:00'],
            5 => ['10:00', '19:00'],
            6 => ['10:00', '17:00'],
            7 => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Finance defaults
    |--------------------------------------------------------------------------
    */

    'finance' => [
        'apr' => 6.9,
        'term_months' => 60,
        'deposit_percent' => 20,
    ],

];
