<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('vehicle.brand'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('name')
                    ->description(fn (Lead $record) => $record->email)
                    ->searchable(['name', 'email', 'phone']),
                TextColumn::make('vehicle.model')
                    ->label('Vehicle')
                    ->formatStateUsing(fn (Lead $record) => $record->vehicle ? "{$record->vehicle->year} {$record->vehicle->brand->name} {$record->vehicle->model}" : null)
                    ->placeholder('—'),
                TextColumn::make('offer_amount')
                    ->label('Offer')
                    ->money('USD', decimalPlaces: 0)
                    ->description(fn (Lead $record) => $record->offer_amount && $record->vehicle
                        ? round($record->offer_amount / $record->vehicle->price * 100).'% of asking'
                        : null)
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('message')->limit(50)->toggleable()->placeholder('—'),
                SelectColumn::make('status')
                    ->options(LeadStatus::class)
                    ->selectablePlaceholder(false)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(LeadType::class)->multiple(),
                SelectFilter::make('status')->options(LeadStatus::class)->multiple(),
            ])
            ->recordActions([
                Action::make('email')
                    ->icon('heroicon-o-envelope')
                    ->iconButton()
                    ->tooltip('Reply by email')
                    ->url(fn (Lead $record) => 'mailto:'.$record->email.'?subject='.rawurlencode('Re: your '.strtolower($record->type->getLabel()).' enquiry — '.config('dealership.name'))),
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('setStatus')
                        ->label('Change status')
                        ->icon('heroicon-o-arrow-path')
                        ->schema([Select::make('status')->options(LeadStatus::class)->required()])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['status' => $data['status']]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
