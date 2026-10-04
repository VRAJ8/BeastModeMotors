<?php

namespace App\Filament\Resources\Deals;

use App\Enums\DealStatus;
use App\Filament\Resources\Deals\Pages\ManageDeals;
use App\Models\Deal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vehicle', 'buyer', 'seller'])
                ->withCount(['messages', 'messages as flagged_count' => fn ($m) => $m->whereNotNull('risk_flags')]))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('vehicle.model')->label('Car')->formatStateUsing(fn (Deal $record) => $record->vehicle->title()),
                TextColumn::make('seller.name')->label('Seller')->searchable(),
                TextColumn::make('buyer.name')->label('Buyer')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('agreed_price_cents')->label('Agreed')->formatStateUsing(fn ($state) => $state ? money($state) : null)->placeholder('—'),
                TextColumn::make('messages_count')->label('Messages'),
                TextColumn::make('flagged_count')->label('Flagged')->badge()->color(fn (int $state) => $state ? 'danger' : 'gray')->sortable(),
                TextColumn::make('updated_at')->label('Activity')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(DealStatus::class)->multiple(),
                TernaryFilter::make('flagged')->label('Has flagged messages')->queries(
                    true: fn (Builder $query) => $query->whereHas('messages', fn ($m) => $m->whereNotNull('risk_flags')),
                    false: fn (Builder $query) => $query->whereDoesntHave('messages', fn ($m) => $m->whereNotNull('risk_flags')),
                ),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDeals::route('/')];
    }
}
