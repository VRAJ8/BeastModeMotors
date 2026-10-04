<?php

namespace App\Filament\Resources\Listings;

use App\Enums\ListingStatus;
use App\Filament\Resources\Listings\Pages\ManageListings;
use App\Models\Listing;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingResource extends Resource
{
    protected static ?string $model = Listing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vehicle', 'seller'])->withCount(['reports', 'deals']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('vehicle.model')->label('Car')
                    ->formatStateUsing(fn (Listing $record) => $record->vehicle->title())
                    ->description(fn (Listing $record) => $record->vehicle->vin)
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('vehicle', fn ($v) => $v->where('vin', 'like', "%{$search}%")->orWhere('make', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%"))),
                TextColumn::make('seller.name')->description(fn (Listing $record) => $record->seller->email)->searchable(),
                TextColumn::make('price_cents')->label('Price')->formatStateUsing(fn ($state) => money($state))->sortable(),
                TextColumn::make('score')->label('Score')->badge()->color(fn (int $state) => $state >= 85 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('reports_count')->label('Reports')->badge()->color(fn (int $state) => $state ? 'danger' : 'gray')->sortable(),
                TextColumn::make('deals_count')->label('Deals')->sortable(),
                TextColumn::make('location')->state(fn (Listing $record) => $record->location())->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ListingStatus::class)->multiple(),
                TernaryFilter::make('reported')->queries(
                    true: fn (Builder $query) => $query->has('reports'),
                    false: fn (Builder $query) => $query->doesntHave('reports'),
                ),
            ])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->iconButton()
                    ->url(fn (Listing $record) => route('listings.show', $record), shouldOpenInNewTab: true),
                Action::make('remove')->label('Remove')->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (Listing $record) => $record->status->isPublic())
                    ->schema([Textarea::make('reason')->label('Reason (shown to the seller)')->required()->maxLength(255)])
                    ->action(fn (Listing $record, array $data) => static::remove($record, $data['reason'])),
                Action::make('restore')->icon('heroicon-o-arrow-uturn-left')->color('gray')
                    ->visible(fn (Listing $record) => $record->status === ListingStatus::Removed)
                    ->requiresConfirmation()
                    ->action(fn (Listing $record) => $record->update(['status' => ListingStatus::Active, 'removed_reason' => null])),
            ]);
    }

    public static function remove(Listing $listing, string $reason): void
    {
        $listing->update(['status' => ListingStatus::Removed, 'removed_reason' => $reason]);
        $listing->shareLink?->update(['revoked_at' => now()]);
        $listing->reports()->where('status', 'open')->update(['status' => 'actioned']);
    }

    public static function getPages(): array
    {
        return ['index' => ManageListings::route('/')];
    }
}
