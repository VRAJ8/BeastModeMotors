<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Beast Mode Motors'),
    'support_email' => env('SUPPORT_EMAIL', 'support@beastmodemotors.test'),

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    |
    | Shows the demo credentials on sign-in screens and rebuilds the database
    | every night so visitors can try everything freely.
    |
    */

    'demo' => (bool) env('DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | NHTSA (US Department of Transportation) open data
    |--------------------------------------------------------------------------
    |
    | Free, keyless APIs used for VIN decoding and safety recalls. When they
    | can't be reached the app falls back to an offline VIN decoder.
    |
    */

    'nhtsa' => [
        'enabled' => (bool) env('NHTSA_ENABLED', true),
        'vin_url' => 'https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValuesExtended',
        'recalls_url' => 'https://api.nhtsa.gov/recalls/recallsByVehicle',
        'timeout' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Shop verification
    |--------------------------------------------------------------------------
    */

    'verification' => [
        'link_valid_days' => 14,
        'max_requests_per_record' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Records
    |--------------------------------------------------------------------------
    |
    | A record entered more than `backfill_days` after the work was done is
    | marked as "logged later" and carries less weight in the Passport Score.
    |
    */

    'backfill_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Receipts and paperwork are private (streamed through authorised routes);
    | car photos are public. Point these at "s3" / "s3-public" in production
    | (works with AWS S3, Cloudflare R2 and other S3-compatible stores).
    |
    */

    'disks' => [
        'documents' => env('DOCUMENTS_DISK', 'local'),
        'photos' => env('PHOTOS_DISK', 'public'),
    ],
    'max_upload_kb' => 10240,

    /*
    |--------------------------------------------------------------------------
    | Maintenance schedules
    |--------------------------------------------------------------------------
    |
    | Default reminders created for a new car. Owners can edit them freely.
    | [task, every N miles, every N months]
    |
    */

    'maintenance' => [
        'combustion' => [
            ['Engine oil & filter', 7500, 12],
            ['Tire rotation', 7500, null],
            ['Engine air filter', 15000, 24],
            ['Cabin air filter', 15000, 12],
            ['Brake fluid', null, 24],
            ['Spark plugs', 60000, null],
            ['Coolant', 60000, 60],
            ['Transmission fluid', 60000, null],
            ['State inspection', null, 12],
        ],
        'electric' => [
            ['Tire rotation', 7500, null],
            ['Cabin air filter', null, 24],
            ['Brake fluid', null, 24],
            ['Battery coolant check', null, 48],
            ['State inspection', null, 12],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pre-purchase inspection checklist
    |--------------------------------------------------------------------------
    */

    'inspection' => [
        'Paperwork' => [
            'vin_match' => 'VIN on dash, door jamb and title matches the passport',
            'title_clean' => 'Title is in the seller\'s name with no liens or brands',
            'odometer_match' => 'Odometer agrees with the latest passport reading',
        ],
        'Exterior' => [
            'panels' => 'Panel gaps even, no signs of respray or filler',
            'glass' => 'Glass and lights free of cracks and moisture',
            'tires' => 'Tires matched, even wear, adequate tread',
            'rust' => 'No structural rust on sills, arches or subframe',
        ],
        'Mechanical' => [
            'cold_start' => 'Cold start is clean, no smoke or warning lights',
            'leaks' => 'No fluid leaks under the car or in the engine bay',
            'scan' => 'OBD scan shows no stored or pending codes',
            'brakes' => 'Brakes straight and quiet, pads and discs healthy',
            'suspension' => 'No knocks, clunks or uneven ride height',
        ],
        'Interior' => [
            'electrics' => 'Windows, locks, A/C, infotainment all work',
            'wear' => 'Wear matches the mileage (seat, wheel, pedals)',
            'water' => 'No damp carpets, musty smell or water marks',
        ],
        'Test drive' => [
            'drive_shift' => 'Gear changes smooth, no slipping or flare',
            'drive_track' => 'Tracks straight, no vibration under braking',
            'drive_noise' => 'No unusual noises at speed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Handover checklist
    |--------------------------------------------------------------------------
    |
    | Who is responsible for each step of handing a car over. A sale can only
    | be completed when every required step is ticked by its owner.
    |
    */

    'handover' => [
        'bill_of_sale' => ['label' => 'Bill of sale signed by both parties', 'by' => 'seller', 'required' => true],
        'payment_sent' => ['label' => 'Payment sent using a traceable method', 'by' => 'buyer', 'required' => true],
        'payment_cleared' => ['label' => 'Payment received and cleared', 'by' => 'seller', 'required' => true],
        'vin_checked' => ['label' => 'VIN on the car checked against the title', 'by' => 'buyer', 'required' => true],
        'title_signed' => ['label' => 'Title signed over to the buyer', 'by' => 'seller', 'required' => true],
        'keys' => ['label' => 'All keys, fobs and codes handed over', 'by' => 'seller', 'required' => true],
        'insurance' => ['label' => 'Insurance arranged before driving away', 'by' => 'buyer', 'required' => true],
        'plates' => ['label' => 'Plates and toll tags removed (where required)', 'by' => 'seller', 'required' => false],
        'manuals' => ['label' => 'Manuals, spare parts and paper records handed over', 'by' => 'seller', 'required' => false],
    ],

];
