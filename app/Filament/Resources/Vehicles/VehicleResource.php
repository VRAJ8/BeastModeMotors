<?php

namespace App\Filament\Resources\Vehicles;

use App\Enums\FuelType;
use App\Filament\Resources\Vehicles\Pages\ManageVehicles;
use App\Models\Vehicle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Passports';

    protected static ?string $navigationLabel = 'Cars';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'vin';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('owner')->withCount(['records', 'ownerships', 'recalls as open_recalls_count' => fn ($r) => $r->whereNull('resolved_at')]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('model')->label('Car')->formatStateUsing(fn (Vehicle $record) => $record->fullTitle())->searchable(['make', 'model', 'vin'])->description(fn (Vehicle $record) => $record->vin),
                IconColumn::make('vin_valid')->label('VIN ok')->boolean(),
                TextColumn::make('decode_source')->label('Decoded by')->badge()->color('gray'),
                TextColumn::make('owner.name')->label('Owner')->searchable(),
                TextColumn::make('current_mileage')->label('Miles')->numeric()->sortable(),
                TextColumn::make('records_count')->label('Records')->sortable(),
                TextColumn::make('ownerships_count')->label('Owners'),
                TextColumn::make('open_recalls_count')->label('Open recalls')->badge()->color(fn (int $state) => $state ? 'danger' : 'gray'),
                TextColumn::make('created_at')->label('Added')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('fuel_type')->options(FuelType::class),
                TernaryFilter::make('vin_valid')->label('VIN passes check digit'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVehicles::route('/')];
    }
}
