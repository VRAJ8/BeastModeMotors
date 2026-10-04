<?php

namespace App\Filament\Widgets;

use App\Enums\LeadType;
use App\Models\Lead;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class LeadsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Leads per week';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $weeks = collect(range(9, 0))->map(fn (int $i) => CarbonImmutable::now()->startOfWeek()->subWeeks($i));

        // Grouped in PHP so the query stays portable across SQLite / MySQL / Postgres.
        $leads = Lead::where('created_at', '>=', $weeks->first())->get(['type', 'created_at']);

        $palette = [
            LeadType::General->value => '#9ca3af',
            LeadType::Vehicle->value => '#38bdf8',
            LeadType::Finance->value => '#d4a857',
            LeadType::Offer->value => '#f59e0b',
            LeadType::TradeIn->value => '#34d399',
        ];

        $datasets = collect(LeadType::cases())->map(fn (LeadType $type) => [
            'label' => $type->getLabel(),
            'data' => $weeks->map(fn (CarbonImmutable $week) => $leads
                ->filter(fn (Lead $lead) => $lead->type === $type && $lead->created_at->between($week, $week->endOfWeek()))
                ->count())->all(),
            'backgroundColor' => $palette[$type->value],
            'borderRadius' => 4,
        ])->all();

        return [
            'datasets' => $datasets,
            'labels' => $weeks->map(fn (CarbonImmutable $week) => $week->format('M j'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => ['stacked' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
