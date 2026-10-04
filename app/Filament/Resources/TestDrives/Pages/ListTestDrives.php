<?php

namespace App\Filament\Resources\TestDrives\Pages;

use App\Enums\TestDriveStatus;
use App\Filament\Resources\TestDrives\TestDriveResource;
use App\Models\TestDrive;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTestDrives extends ListRecords
{
    protected static string $resource = TestDriveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New booking'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'upcoming' => Tab::make('Upcoming')
                ->badge(TestDrive::upcoming()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->upcoming()),
            'pending' => Tab::make('Needs confirmation')
                ->badge(TestDrive::upcoming()->where('status', TestDriveStatus::Pending)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->upcoming()->where('status', TestDriveStatus::Pending)),
            'past' => Tab::make('Past')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('scheduled_at', '<', now())->reorder('scheduled_at', 'desc')),
            'all' => Tab::make('All'),
        ];
    }
}
