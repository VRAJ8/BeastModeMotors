<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestLeads extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'New leads';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Lead::query()->with('vehicle.brand')->where('status', LeadStatus::New)->latest())
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('created_at')->label('Received')->since(),
                TextColumn::make('type')->badge(),
                TextColumn::make('name')->description(fn (Lead $record) => $record->email),
                TextColumn::make('vehicle.model')
                    ->label('Vehicle')
                    ->formatStateUsing(fn (Lead $record) => $record->vehicle ? "{$record->vehicle->year} {$record->vehicle->brand->name} {$record->vehicle->model}" : null)
                    ->placeholder('—'),
                TextColumn::make('offer_amount')->label('Offer')->money('USD', decimalPlaces: 0)->placeholder('—'),
            ])
            ->recordActions([
                Action::make('open')
                    ->icon('heroicon-o-arrow-right')
                    ->url(fn (Lead $record) => LeadResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
