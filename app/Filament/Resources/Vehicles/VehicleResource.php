<?php

namespace App\Filament\Resources\Vehicles;

use App\Enums\DealStatus;
use App\Enums\FuelType;
use App\Filament\Resources\Vehicles\Pages\ManageVehicles;
use App\Models\Deal;
use App\Models\Vehicle;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
            ])
            ->recordActions([
                Action::make('release')
                    ->label('Release VIN')
                    ->icon('heroicon-o-lock-open')
                    ->color('danger')
                    ->visible(fn (Vehicle $record) => self::canRelease($record))
                    ->requiresConfirmation()
                    ->modalDescription('Deletes this passport so the car\'s real owner can register the VIN. Only do this after checking their title or registration.')
                    ->action(function (Vehicle $record) {
                        abort_unless(self::canRelease($record), 422, 'This passport has history from other people and can\'t be released.');
                        $record->delete();
                        Notification::make()->title('VIN released')->success()->send();
                    }),
            ]);
    }

    /**
     * A passport that only ever had one account behind it, with no live or finished sale, can be released to the
     * VIN's real owner. Anything with other people's history in it needs an engineer, not a button.
     */
    public static function canRelease(Vehicle $vehicle): bool
    {
        return $vehicle->ownerships()->count() <= 1
            && ! Deal::where('vehicle_id', $vehicle->getKey())->whereIn('status', [DealStatus::Open, DealStatus::Agreed, DealStatus::Completed])->exists();
    }

    public static function getPages(): array
    {
        return ['index' => ManageVehicles::route('/')];
    }
}
