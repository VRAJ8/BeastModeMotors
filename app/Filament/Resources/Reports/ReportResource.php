<?php

namespace App\Filament\Resources\Reports;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Filament\Resources\Listings\ListingResource;
use App\Filament\Resources\Reports\Pages\ManageReports;
use App\Models\Report;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|\UnitEnum|null $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $open = Report::where('status', ReportStatus::Open)->count();

        return $open ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['listing.vehicle', 'listing.seller', 'reporter']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Reported')->since()->sortable(),
                TextColumn::make('reason')->badge(),
                TextColumn::make('listing.slug')->label('Listing')
                    ->formatStateUsing(fn (Report $record) => $record->listing->vehicle->title())
                    ->description(fn (Report $record) => 'Seller: '.$record->listing->seller->name)
                    ->url(fn (Report $record) => route('listings.show', $record->listing), shouldOpenInNewTab: true),
                TextColumn::make('details')->limit(60)->wrap()->placeholder('—'),
                TextColumn::make('reporter.name')->placeholder('Deleted user'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ReportStatus::class)->default(ReportStatus::Open->value),
                SelectFilter::make('reason')->options(ReportReason::class),
            ])
            ->recordActions([
                Action::make('removeListing')->label('Remove listing')->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (Report $record) => $record->status === ReportStatus::Open && $record->listing->isPublic())
                    ->requiresConfirmation()
                    ->action(fn (Report $record) => ListingResource::remove($record->listing, 'Removed after a report: '.$record->reason->getLabel())),
                Action::make('dismiss')->icon('heroicon-o-x-mark')->color('gray')
                    ->visible(fn (Report $record) => $record->status === ReportStatus::Open)
                    ->action(fn (Report $record) => $record->update(['status' => ReportStatus::Dismissed])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReports::route('/')];
    }
}
