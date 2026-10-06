<?php

namespace App\Filament\Widgets;

use App\Enums\DealStatus;
use App\Enums\ReportStatus;
use App\Enums\VerificationStatus;
use App\Models\Deal;
use App\Models\DealMessage;
use App\Models\Listing;
use App\Models\Report;
use App\Models\ServiceRecord;
use App\Models\ShopVerification;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // These are trend numbers, not a live feed: refresh once a minute rather than Filament's default 5s.
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $records = ServiceRecord::count();
        $verified = ServiceRecord::whereNotNull('verified_at')->count();
        $answered = ShopVerification::whereIn('status', [VerificationStatus::Confirmed, VerificationStatus::Disputed])->count();
        $asked = ShopVerification::count();

        // One range query (index-friendly), bucketed by day in PHP, instead of fourteen date() scans.
        $since = now()->subDays(13)->startOfDay();
        $perDay = ServiceRecord::where('created_at', '>=', $since)->pluck('created_at')
            ->countBy(fn ($createdAt) => $createdAt->toDateString());
        $dailyRecords = collect(range(13, 0))
            ->map(fn (int $daysAgo) => $perDay->get(now()->subDays($daysAgo)->toDateString(), 0))
            ->all();

        return [
            Stat::make('Passports', Vehicle::count())
                ->description($records.' records logged')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart($dailyRecords)
                ->color('primary'),
            Stat::make('Shop-verified records', $records ? round($verified / $records * 100).'%' : '—')
                ->description($asked ? round($answered / $asked * 100).'% of requests answered by shops' : 'No requests yet')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Live listings', Listing::public()->count())
                ->description(($sold = Deal::where('status', DealStatus::Completed)->count()).' '.str('passport')->plural($sold).' transferred through sales')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color('info'),
            Stat::make('Needs review', Report::where('status', ReportStatus::Open)->count())
                ->description(($flagged = DealMessage::whereNotNull('risk_flags')->where('created_at', '>=', now()->subDays(7))->count()).' '.str('message')->plural($flagged).' flagged by scam shield this week')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color('danger'),
        ];
    }
}
