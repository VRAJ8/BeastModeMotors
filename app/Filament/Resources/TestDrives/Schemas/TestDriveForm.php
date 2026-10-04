<?php

namespace App\Filament\Resources\TestDrives\Schemas;

use App\Enums\TestDriveStatus;
use App\Models\Vehicle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestDriveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Booking')
                    ->columns(2)
                    ->schema([
                        Select::make('vehicle_id')
                            ->relationship('vehicle', 'model', fn ($query) => $query->with('brand'))
                            ->getOptionLabelFromRecordUsing(fn (Vehicle $record) => "{$record->year} {$record->brand->name} {$record->model} {$record->trim}")
                            ->searchable(['model', 'trim'])
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        DateTimePicker::make('scheduled_at')
                            ->seconds(false)
                            ->minutesStep(15)
                            ->required(),
                        TextInput::make('reference')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        ToggleButtons::make('status')
                            ->options(TestDriveStatus::class)
                            ->default(TestDriveStatus::Pending)
                            ->inline()
                            ->required()
                            ->helperText('The customer is emailed whenever the status changes.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('phone')->tel()->maxLength(32),
                        TextInput::make('email')->email()->required()->columnSpanFull(),
                        Select::make('user_id')
                            ->label('Linked account')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->columnSpanFull(),
                        Textarea::make('notes')->rows(3)->columnSpanFull(),
                    ]),
            ]);
    }
}
