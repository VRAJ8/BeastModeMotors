<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'People';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('phone')->tel(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave blank to keep the current password.' : null),
                Toggle::make('is_admin')
                    ->label('Trust & safety access')
                    ->disabled(fn (?User $record) => $record?->is(Auth::user())),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->description(fn (User $record) => static::demo() && ! static::isDemoAccount($record) ? static::maskEmail($record->email) : $record->email)->searchable(['name', 'email']),
                TextColumn::make('location')->state(fn (User $record) => $record->city ? "{$record->city}, {$record->state}" : null)->placeholder('—'),
                TextColumn::make('vehicles_count')->counts('vehicles')->label('Cars')->sortable(),
                TextColumn::make('listings_count')->counts('listings')->label('Listings')->sortable(),
                IconColumn::make('is_admin')->label('Staff')->boolean(),
                TextColumn::make('created_at')->label('Joined')->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_admin')->label('Staff')])
            ->recordActions([
                // The demo publishes its staff login, so accounts are read-only there.
                EditAction::make()->using(fn (User $record, array $data) => static::save($record, $data))->hidden(fn () => static::demo()),
                DeleteAction::make()->hidden(fn (User $record) => static::demo() || $record->is(Auth::user())),
            ]);
    }

    /**
     * is_admin is deliberately not mass-assignable, so staff access is saved explicitly here.
     *
     * @param  array<string, mixed>  $data
     */
    public static function save(User $user, array $data): User
    {
        $user->fill(collect($data)->except('is_admin')->all());

        if (array_key_exists('is_admin', $data) && ! $user->is(Auth::user())) {
            $user->is_admin = (bool) $data['is_admin'];
        }

        $user->save();

        return $user;
    }

    public static function demo(): bool
    {
        return (bool) config('passport.demo');
    }

    public static function isDemoAccount(User $user): bool
    {
        return str_ends_with($user->email, '@beastmodemotors.test') || str_ends_with($user->email, '@example.com');
    }

    public static function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email) + [1 => ''];

        return mb_substr($local, 0, 1).'•••@'.$domain;
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
