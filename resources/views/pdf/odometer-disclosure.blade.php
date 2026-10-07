@php($vehicle = $deal->vehicle)
@php($chosen = $deal->odometer_status)
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Odometer disclosure statement</title>
    <style>
        @page { margin: 44px 56px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #121417; line-height: 1.45; }
        h1 { font-size: 24px; margin: 0 0 4px; }
        h2 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #6a707b; margin: 16px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 7px 8px; border: 1px solid #cbc5b6; vertical-align: top; }
        .label { font-size: 8.5px; text-transform: uppercase; color: #6a707b; letter-spacing: .5px; display: block; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .box { font-size: 14px; padding-right: 6px; }
        .cert td { border: none; padding: 3px 0; }
        .sign td { border: none; border-top: 1px solid #121417; padding-top: 6px; width: 45%; }
        .notice { border: 1px solid #121417; padding: 8px 10px; font-size: 10px; }
        .muted { color: #6a707b; }
    </style>
</head>
<body>
    <h1>Odometer Disclosure Statement</h1>
    <p class="muted">Reference BMM-{{ str_pad($deal->id, 6, '0', STR_PAD_LEFT) }} · Prepared {{ now()->format('F j, Y') }}</p>

    <p class="notice">Federal law (and State law, if applicable) requires that you state the mileage upon transfer of ownership. Failure to complete or providing a false statement may result in fines and/or imprisonment. (49 U.S.C. 32705; 49 CFR Part 580)</p>

    <h2>Vehicle</h2>
    <table>
        <tr>
            <td colspan="2"><span class="label">Vehicle identification number</span><span class="mono">{{ $vehicle->vin }}</span></td>
            <td><span class="label">Year</span>{{ $vehicle->year }}</td>
            <td><span class="label">Make</span>{{ $vehicle->make }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Model</span>{{ $vehicle->model }}</td>
            <td colspan="2"><span class="label">Body type</span>{{ $vehicle->body ?: '____________________' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Odometer reading (no tenths)</span><span class="mono" style="font-size: 15px">{{ $deal->sale_mileage ? number_format($deal->sale_mileage) : '____________' }}</span> miles</td>
            <td colspan="2"><span class="label">Date of transfer</span>{{ $deal->completed_at?->format('F j, Y') ?? '____________________' }}</td>
        </tr>
    </table>

    <h2>Seller's certification</h2>
    <p>I, the seller, state that the odometer now reads the mileage above and, to the best of my knowledge, that:</p>
    <table class="cert">
        @foreach (\App\Enums\OdometerStatus::cases() as $status)
            <tr><td style="width: 22px"><span class="box">{{ $chosen === $status ? '☒' : '☐' }}</span></td><td>{{ $status->certification() }}</td></tr>
        @endforeach
    </table>

    <h2>Parties</h2>
    <table>
        <tr>
            <td style="width: 50%"><span class="label">Seller (transferor)</span><strong>{{ $deal->seller->name }}</strong><br>Address: ____________________________________<br>________________________________________</td>
            <td><span class="label">Buyer (transferee)</span><strong>{{ $deal->buyer->name }}</strong><br>Address: ____________________________________<br>________________________________________</td>
        </tr>
    </table>

    <table class="sign" style="margin-top: 34px">
        <tr><td>Seller's signature &amp; date</td><td style="width: 10%; border: none"></td><td>Seller's printed name</td></tr>
    </table>
    <p style="margin-top: 18px">I, the buyer, acknowledge receiving this statement and the seller's certification of the odometer reading.</p>
    <table class="sign" style="margin-top: 30px">
        <tr><td>Buyer's signature &amp; date</td><td style="width: 10%; border: none"></td><td>Buyer's printed name</td></tr>
    </table>

    <p class="muted" style="margin-top: 22px; font-size: 9px">Most states take the odometer disclosure in the odometer section of the title itself: fill that in with the same figures when you sign the title over. Keep this signed statement with your records. Your state's motor vehicle agency can tell you whether it needs its own form. Beast Mode Motors is not a party to this sale.</p>
</body>
</html>
