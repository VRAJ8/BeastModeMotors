<?php

namespace App\Filament\Resources\Vehicles\Tables;

use App\Enums\BodyType;
use App\Enums\Condition;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('brand'))
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('')
                    ->state(fn (Vehicle $record) => $record->cover_image)
                    ->imageWidth(96)
                    ->imageHeight(60),
                TextColumn::make('model')
                    ->label('Vehicle')
                    ->formatStateUsing(fn (Vehicle $record) => "{$record->year} {$record->brand->name} {$record->model} {$record->trim}")
                    ->description(fn (Vehicle $record) => $record->vin)
                    ->searchable(['model', 'trim', 'vin'])
                    ->sortable(),
                TextColumn::make('price')
                    ->money('USD', decimalPlaces: 0)
                    ->description(fn (Vehicle $record) => $record->has_price_drop ? 'was '.money($record->previous_price) : null)
                    ->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('condition')->badge()->toggleable(),
                TextColumn::make('body_type')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mileage')->numeric()->suffix(' mi')->sortable()->toggleable(),
                TextColumn::make('horsepower')->suffix(' hp')->sortable()->toggleable(),
                TextColumn::make('views')->numeric()->sortable()->toggleable(),
                TextColumn::make('favorited_by_count')->counts('favoritedBy')->label('Saves')->sortable()->toggleable(),
                ToggleColumn::make('is_featured')->label('Featured'),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->since()
                    ->placeholder('Draft')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(VehicleStatus::class),
                SelectFilter::make('brand')->relationship('brand', 'name')->multiple()->preload(),
                SelectFilter::make('body_type')->options(BodyType::class),
                SelectFilter::make('condition')->options(Condition::class),
                TernaryFilter::make('published_at')
                    ->label('Published')
                    ->nullable()
                    ->trueLabel('Published')
                    ->falseLabel('Drafts'),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('view')
                        ->label('View on site')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (Vehicle $record) => route('vehicles.show', $record), shouldOpenInNewTab: true)
                        ->visible(fn (Vehicle $record) => $record->published_at?->isPast()),
                    Action::make('reducePrice')
                        ->label('Reduce price')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->color('success')
                        ->visible(fn (Vehicle $record) => $record->status !== VehicleStatus::Sold)
                        ->schema([
                            TextInput::make('price')
                                ->label('New price')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->lt('current_price')
                                ->default(fn (Vehicle $record) => (int) round($record->price * 0.97, -3)),
                            TextInput::make('current_price')->hidden()->default(fn (Vehicle $record) => $record->price),
                        ])
                        ->modalDescription(fn (Vehicle $record) => 'Currently '.money($record->price).'. Everyone who saved this car ('.$record->favoritedBy()->count().') will be emailed.')
                        ->action(function (Vehicle $record, array $data) {
                            $record->update(['price' => (int) $data['price']]);
                            Notification::make()->title('Price reduced to '.money($record->price))->success()->send();
                        }),
                    Action::make('markSold')
                        ->label('Mark as sold')
                        ->icon('heroicon-o-check-badge')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Vehicle $record) => $record->status !== VehicleStatus::Sold)
                        ->action(fn (Vehicle $record) => $record->update(['status' => VehicleStatus::Sold])),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('feature')
                        ->label('Feature on home page')
                        ->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
