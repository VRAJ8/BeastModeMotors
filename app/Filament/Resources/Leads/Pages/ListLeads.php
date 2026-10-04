<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New lead'),
        ];
    }

    public function getTabs(): array
    {
        $open = [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified];

        return [
            'open' => Tab::make('Open')
                ->badge(Lead::whereIn('status', $open)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $open)),
            'new' => Tab::make('New')
                ->badge(Lead::where('status', LeadStatus::New)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::New)),
            'won' => Tab::make('Won')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::Won)),
            'lost' => Tab::make('Lost')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::Lost)),
            'all' => Tab::make('All'),
        ];
    }
}
