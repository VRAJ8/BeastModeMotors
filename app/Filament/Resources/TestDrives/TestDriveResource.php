<?php

namespace App\Filament\Resources\TestDrives;

use App\Filament\Resources\TestDrives\Pages\CreateTestDrive;
use App\Filament\Resources\TestDrives\Pages\EditTestDrive;
use App\Filament\Resources\TestDrives\Pages\ListTestDrives;
use App\Filament\Resources\TestDrives\Schemas\TestDriveForm;
use App\Filament\Resources\TestDrives\Tables\TestDrivesTable;
use App\Models\TestDrive;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TestDriveResource extends Resource
{
    protected static ?string $model = TestDrive::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return TestDriveForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TestDrivesTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::upcoming()->where('status', 'pending')->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTestDrives::route('/'),
            'create' => CreateTestDrive::route('/create'),
            'edit' => EditTestDrive::route('/{record}/edit'),
        ];
    }
}
