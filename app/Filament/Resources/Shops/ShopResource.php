<?php

namespace App\Filament\Resources\Shops;

use App\Enums\VerificationStatus;
use App\Filament\Resources\Shops\Pages\ManageShops;
use App\Models\Shop;
use App\Support\UsStates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShopResource extends Resource
{
    protected static ?string $model = Shop::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Passports';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('email')->email()->disabled()->helperText('The shop\'s identity — it can\'t be changed.'),
                TextInput::make('city')->maxLength(80),
                Select::make('state')->options(UsStates::ALL),
                TextInput::make('phone')->maxLength(32),
                TextInput::make('website')->url()->maxLength(255),
                Textarea::make('about')->columnSpanFull()->maxLength(1000),
                Toggle::make('is_listed')->label('Shown in the public directory'),
                Toggle::make('vetted_at')->label('Vetted by staff')
                    ->helperText('Unvetted shops are listed once owners of '.Shop::MIN_CUSTOMERS.' different accounts have had work confirmed.')
                    ->formatStateUsing(fn ($state) => $state !== null)
                    ->dehydrateStateUsing(fn (bool $state, ?Shop $record) => $state ? ($record?->vetted_at ?? now()) : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'verifications as confirmed_count' => fn ($v) => $v->where('status', VerificationStatus::Confirmed),
                'verifications as disputed_count' => fn ($v) => $v->where('status', VerificationStatus::Disputed),
                'verifications as requests_count',
            ])->withCount(['verifications as customers_count' => fn ($v) => $v->where('status', VerificationStatus::Confirmed)->select(DB::raw('count(distinct requested_by)'))]))
            ->defaultSort('confirmed_count', 'desc')
            ->columns([
                TextColumn::make('name')->description(fn (Shop $record) => $record->email)->searchable(['name', 'email']),
                TextColumn::make('location')->state(fn (Shop $record) => $record->location())->placeholder('—'),
                TextColumn::make('confirmed_count')->label('Confirmed')->sortable(),
                TextColumn::make('disputed_count')->label('Disputed')->sortable(),
                TextColumn::make('requests_count')->label('Requests')->sortable(),
                TextColumn::make('customers_count')->label('Owners')->tooltip('Different accounts with confirmed work')->sortable(),
                TextColumn::make('vetted_at')->label('Vetted')->since()->placeholder('—'),
                TextColumn::make('profile_completed_at')->label('Profile')->since()->placeholder('Not completed'),
                ToggleColumn::make('is_listed')->label('Listed'),
            ])
            ->filters([TernaryFilter::make('is_listed')->label('Listed')])
            ->recordActions([
                Action::make('view')->icon('heroicon-o-arrow-top-right-on-square')->iconButton()
                    ->url(fn (Shop $record) => route('shops.show', $record), shouldOpenInNewTab: true),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageShops::route('/')];
    }
}
