<?php

namespace App\Filament\Resources\ShopVerifications;

use App\Enums\VerificationStatus;
use App\Filament\Resources\ShopVerifications\Pages\ManageShopVerifications;
use App\Models\ShopVerification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShopVerificationResource extends Resource
{
    protected static ?string $model = ShopVerification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|\UnitEnum|null $navigationGroup = 'Passports';

    protected static ?string $navigationLabel = 'Shop verifications';

    protected static ?int $navigationSort = 2;

    private const FREE_MAIL = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com', 'aol.com', 'proton.me'];

    /**
     * A shop email on the owner's own (non-webmail) domain deserves a second look.
     */
    public static function sameDomainAsOwner(ShopVerification $v): bool
    {
        $shop = strtolower(substr(strrchr($v->shop_email, '@') ?: '', 1));
        $owner = strtolower(substr(strrchr((string) $v->requester?->email, '@') ?: '', 1));

        return $shop !== '' && $shop === $owner && ! in_array($shop, self::FREE_MAIL, true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['record.vehicle', 'requester']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Requested')->since()->sortable(),
                TextColumn::make('record.title')->label('Record')->description(fn (ShopVerification $v) => $v->record->vehicle->title().' · '.$v->record->performed_on->format('M j, Y')),
                TextColumn::make('shop_name')->description(fn (ShopVerification $v) => $v->shop_email)->searchable(['shop_name', 'shop_email']),
                TextColumn::make('requester.name')->label('Owner')->description(fn (ShopVerification $v) => $v->requester?->email),
                IconColumn::make('same_domain')->label('Owner\'s domain')->state(fn (ShopVerification $v) => static::sameDomainAsOwner($v))
                    ->boolean()->trueIcon('heroicon-o-exclamation-triangle')->trueColor('danger')->falseIcon('heroicon-o-minus')->falseColor('gray'),
                TextColumn::make('status')->badge(),
                TextColumn::make('responder_name')->label('Answered by')->description(fn (ShopVerification $v) => $v->responder_ip)->placeholder('—'),
                TextColumn::make('responded_at')->since()->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status')->options(VerificationStatus::class)]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageShopVerifications::route('/')];
    }
}
