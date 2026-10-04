<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\TestDrive;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShowroomStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $available = Vehicle::available();
        $leadsThisMonth = Lead::where('created_at', '>=', now()->startOfMonth())->count();
        $leadsLastMonth = Lead::whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->count();
        $closed = Lead::whereIn('status', [LeadStatus::Won, LeadStatus::Lost])->count();
        $won = Lead::where('status', LeadStatus::Won)->count();

        $dailyLeads = collect(range(13, 0))
            ->map(fn (int $daysAgo) => Lead::whereDate('created_at', now()->subDays($daysAgo)->toDateString())->count())
            ->all();

        return [
            Stat::make('Stock value', money((clone $available)->sum('price')))
                ->description((clone $available)->count().' cars available')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),
            Stat::make('Leads this month', $leadsThisMonth)
                ->description($leadsLastMonth ? sprintf('%+d%% vs last month', round(($leadsThisMonth - $leadsLastMonth) / $leadsLastMonth * 100)) : 'First month of data')
                ->descriptionIcon($leadsThisMonth >= $leadsLastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($dailyLeads)
                ->color($leadsThisMonth >= $leadsLastMonth ? 'success' : 'danger'),
            Stat::make('Upcoming test drives', TestDrive::upcoming()->count())
                ->description(TestDrive::upcoming()->where('status', 'pending')->count().' awaiting confirmation')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('Win rate', $closed ? round($won / $closed * 100).'%' : '—')
                ->description("{$won} won of {$closed} closed leads")
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),
        ];
    }
}
