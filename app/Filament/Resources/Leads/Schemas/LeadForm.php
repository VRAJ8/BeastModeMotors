<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Vehicle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Enquiry')
                        ->columns(2)
                        ->schema([
                            Select::make('type')->options(LeadType::class)->required()->live(),
                            Select::make('vehicle_id')
                                ->label('Vehicle')
                                ->relationship('vehicle', 'model', fn ($query) => $query->with('brand'))
                                ->getOptionLabelFromRecordUsing(fn (Vehicle $record) => "{$record->year} {$record->brand->name} {$record->model} {$record->trim}")
                                ->searchable(['model', 'trim'])
                                ->preload(),
                            TextInput::make('offer_amount')
                                ->numeric()
                                ->prefix('$')
                                ->visible(fn ($get) => $get('type') === LeadType::Offer || $get('type') === LeadType::Offer->value),
                            Textarea::make('message')->rows(4)->columnSpanFull(),
                            KeyValue::make('meta')
                                ->label('Details')
                                ->columnSpanFull()
                                ->visible(fn ($state) => filled($state)),
                        ]),
                ])->columnSpan(['lg' => 2]),
                Group::make([
                    Section::make('Pipeline')->schema([
                        ToggleButtons::make('status')
                            ->options(LeadStatus::class)
                            ->default(LeadStatus::New)
                            ->required(),
                    ]),
                    Section::make('Contact')->schema([
                        TextInput::make('name')->required(),
                        TextInput::make('email')->email()->required(),
                        TextInput::make('phone')->tel(),
                    ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }
}
