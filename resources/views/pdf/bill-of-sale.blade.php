<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill of sale</title>
    <style>
        @page { margin: 54px 60px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #121417; line-height: 1.5; }
        h1 { font-size: 26px; margin: 0 0 4px; }
        h2 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #6a707b; margin: 22px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 7px 8px; border: 1px solid #cbc5b6; vertical-align: top; }
        .label { font-size: 8.5px; text-transform: uppercase; color: #6a707b; letter-spacing: .5px; display: block; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .sign td { border: none; border-top: 1px solid #121417; padding-top: 6px; width: 45%; }
        .muted { color: #6a707b; }
    </style>
</head>
<body>
    <h1>Motor Vehicle Bill of Sale</h1>
    <p class="muted">Reference BMM-{{ str_pad($deal->id, 6, '0', STR_PAD_LEFT) }} · Prepared {{ now()->format('F j, Y') }}</p>

    <h2>Vehicle</h2>
    <table>
        <tr>
            <td colspan="2"><span class="label">VIN</span><span class="mono">{{ $deal->vehicle->vin }}</span></td>
            <td><span class="label">Year</span>{{ $deal->vehicle->year }}</td>
            <td><span class="label">Make</span>{{ $deal->vehicle->make }}</td>
        </tr>
        <tr>
            <td><span class="label">Model</span>{{ $deal->vehicle->model }}</td>
            <td><span class="label">Body / trim</span>{{ $deal->vehicle->trim ?: $deal->vehicle->body ?: '—' }}</td>
            <td><span class="label">Colour</span>{{ $deal->vehicle->exterior_color ?: '—' }}</td>
            <td><span class="label">Odometer at sale</span><span class="mono">{{ $deal->sale_mileage ? number_format($deal->sale_mileage) : '________' }}</span> mi</td>
        </tr>
    </table>

    <h2>Sale</h2>
    <table>
        <tr>
            <td><span class="label">Purchase price</span><strong style="font-size: 15px">{{ money($deal->agreed_price_cents) }}</strong> (USD)</td>
            <td><span class="label">Date of sale</span>{{ ($deal->completed_at ?? $deal->agreed_at)->format('F j, Y') }}</td>
        </tr>
    </table>

    <h2>Parties</h2>
    <table>
        <tr>
            <td style="width: 50%"><span class="label">Seller</span><strong>{{ $deal->seller->name }}</strong><br>{{ $deal->seller->email }}<br>Address: ____________________________________</td>
            <td><span class="label">Buyer</span><strong>{{ $deal->buyer->name }}</strong><br>{{ $deal->buyer->email }}<br>Address: ____________________________________</td>
        </tr>
    </table>

    <h2>Terms</h2>
    <p>The seller confirms they are the legal owner of the vehicle described above, that it is free of all liens and encumbrances except as stated here: ______________________, and that they have the right to sell it. The seller certifies that, to the best of their knowledge, the odometer reading above reflects the actual mileage of the vehicle.</p>
    <p>The vehicle is sold <strong>as is</strong>, without warranty of any kind, express or implied. The buyer acknowledges having had the opportunity to inspect the vehicle{{ $deal->inspection?->completed_at ? ' and recorded a pre-purchase inspection on '.$deal->inspection->completed_at->format('F j, Y') : '' }}.</p>
    <p>The vehicle's service history ({{ $deal->vehicle->records()->count() }} records) transfers to the buyer through Beast Mode Motors upon completion.</p>

    <table class="sign" style="margin-top: 50px">
        <tr><td>Seller signature &amp; date</td><td style="width: 10%; border: none"></td><td>Buyer signature &amp; date</td></tr>
    </table>

    <p class="muted" style="margin-top: 40px; font-size: 9px">Some states require their own bill of sale form, notarisation or an odometer disclosure statement. Check your state's motor vehicle agency before registering. Beast Mode Motors is not a party to this sale and never holds funds.</p>
</body>
</html>
