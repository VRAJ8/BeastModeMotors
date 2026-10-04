<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $vehicle->title() }} — Vehicle passport</title>
    <style>
        @page { margin: 42px 48px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #121417; }
        h1 { font-size: 24px; margin: 0; }
        h2 { font-size: 13px; margin: 22px 0 8px; text-transform: uppercase; letter-spacing: 1px; color: #6a707b; }
        .muted { color: #6a707b; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 6px 6px; border-bottom: 1px solid #e3dfd5; text-align: left; vertical-align: top; }
        th { font-size: 9px; text-transform: uppercase; color: #6a707b; letter-spacing: .5px; }
        .stats td { border: 1px solid #e3dfd5; padding: 10px; width: 20%; }
        .big { font-size: 16px; font-weight: bold; }
        .v { color: #0d7a4b; font-weight: bold; } .d { color: #1f5cbd; } .x { color: #be2a2f; font-weight: bold; }
        .header { border-bottom: 3px solid #121417; padding-bottom: 12px; margin-bottom: 14px; }
        .accent { color: #ff5b14; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 8.5px; color: #6a707b; }
    </style>
</head>
<body>
    <div class="footer">Generated {{ now()->format('M j, Y g:i a') }} by {{ config('passport.name') }} · Verify online: {{ $link->url() }}</div>

    <div class="header">
        <span class="accent mono" style="font-size: 9px; letter-spacing: 2px">VEHICLE PASSPORT</span>
        <h1>{{ $vehicle->fullTitle() }}</h1>
        <span class="mono">{{ $link->show_full_vin ? $vehicle->vin : $vehicle->maskedVin() }}</span>
        <span class="muted"> · VIN check digit {{ $vehicle->vin_valid ? 'valid' : 'INVALID' }}</span>
    </div>

    <table class="stats">
        <tr>
            <td><span class="muted">Passport Score</span><br><span class="big">{{ $score['total'] }}/100</span><br>{{ $score['label'] }}</td>
            <td><span class="muted">Odometer</span><br><span class="big">{{ number_format($vehicle->current_mileage) }}</span> mi</td>
            <td><span class="muted">Owners</span><br><span class="big">{{ $vehicle->ownerCount() }}</span></td>
            <td><span class="muted">Records</span><br><span class="big">{{ $vehicle->records->count() }}</span></td>
            <td><span class="muted">Open recalls</span><br><span class="big">{{ $vehicle->recalls->filter->isOpen()->count() }}</span></td>
        </tr>
    </table>

    <h2>Score breakdown</h2>
    <table>
        @foreach ($score['components'] as $c)
            <tr><td style="width: 30%">{{ $c['label'] }}</td><td style="width: 12%" class="mono">{{ $c['points'] }}/{{ $c['max'] }}</td><td class="muted">{{ $c['detail'] }}</td></tr>
        @endforeach
    </table>

    <h2>Odometer</h2>
    @if ($anomalies)
        <p class="x">Warning: {{ count($anomalies) }} reading(s) lower than an earlier reading.</p>
    @else
        <p class="v">Readings only ever go up ({{ $vehicle->readings->count() }} readings{{ $milesPerYear ? ', avg '.number_format($milesPerYear).' mi/year' : '' }}).</p>
    @endif

    <h2>Ownership</h2>
    <table>
        @foreach ($vehicle->ownerships as $o)
            <tr><td>{{ $o->label() }}</td><td>{{ $o->period() }}</td><td>{{ $o->acquired_via->getLabel() }}</td><td class="mono">{{ number_format($o->start_mileage) }} → {{ number_format($o->end_mileage ?? $vehicle->current_mileage) }} mi</td></tr>
        @endforeach
    </table>

    <h2>Service history</h2>
    <table>
        <tr><th>Date</th><th>Miles</th><th>Work</th><th>By</th>@if ($link->show_costs)<th>Cost</th>@endif<th>Evidence</th></tr>
        @foreach ($vehicle->records as $r)
            <tr>
                <td class="mono">{{ $r->performed_on->format('Y-m-d') }}</td>
                <td class="mono">{{ number_format($r->mileage) }}</td>
                <td><strong>{{ $r->title }}</strong><br><span class="muted">{{ $r->category->getLabel() }}{{ $r->tasks ? ' · '.implode(', ', $r->tasks) : '' }}</span></td>
                <td>{{ $r->provider_name ?: $r->provider_type->getLabel() }}</td>
                @if ($link->show_costs)<td class="mono">{{ $r->cost_cents ? money($r->cost_cents) : '—' }}</td>@endif
                <td>
                    @switch($r->evidence())
                        @case('verified') <span class="v">SHOP VERIFIED</span> @break
                        @case('documented') <span class="d">Receipt</span> @break
                        @case('disputed') <span class="x">Disputed</span> @break
                        @default <span class="muted">Self-reported</span>
                    @endswitch
                    @if ($r->isBackfilled() && $r->evidence() !== 'verified')<br><span class="muted">logged later</span>@endif
                </td>
            </tr>
        @endforeach
    </table>

    <h2>Safety recalls</h2>
    @forelse ($vehicle->recalls as $recall)
        <p>{!! $recall->isOpen() ? '<span class="x">OPEN</span>' : '<span class="v">Fixed</span>' !!} · {{ $recall->component }} <span class="muted mono">(NHTSA {{ $recall->campaign_number }})</span></p>
    @empty
        <p class="muted">No recalls on file.</p>
    @endforelse

    <p class="muted" style="margin-top: 26px">This report reflects records logged by the car's owners. "Shop verified" records were confirmed by the business that performed the work via a signed link. It is not a substitute for an independent pre-purchase inspection.</p>
</body>
</html>
