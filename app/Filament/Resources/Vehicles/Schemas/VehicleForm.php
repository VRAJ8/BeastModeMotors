<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\BodyType;
use App\Enums\Condition;
use App\Enums\Drivetrain;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Vehicle')
                        ->columns(3)
                        ->schema([
                            Select::make('brand_id')
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('model')->required()->maxLength(255),
                            TextInput::make('trim')->maxLength(255),
                            TextInput::make('year')->required()->numeric()->minValue(1950)->maxValue((int) date('Y') + 1),
                            TextInput::make('mileage')->required()->numeric()->minValue(0)->suffix('mi')->default(0),
                            TextInput::make('vin')->label('VIN')->length(17)->unique(ignoreRecord: true),
                            Select::make('body_type')->options(BodyType::class)->required(),
                            Select::make('condition')->options(Condition::class)->required(),
                            TextInput::make('exterior_color'),
                            Textarea::make('description')->rows(4)->columnSpanFull(),
                            TagsInput::make('features')->placeholder('Add a highlight')->columnSpanFull(),
                        ]),

                    Section::make('Performance & drivetrain')
                        ->columns(3)
                        ->schema([
                            TextInput::make('engine')->columnSpan(2),
                            Select::make('fuel_type')->options(FuelType::class)->required(),
                            TextInput::make('horsepower')->numeric()->suffix('hp'),
                            TextInput::make('torque')->numeric()->suffix('lb-ft'),
                            TextInput::make('zero_to_sixty')->label('0–60 mph')->numeric()->step(0.1)->suffix('s'),
                            TextInput::make('top_speed')->numeric()->suffix('mph'),
                            Select::make('transmission')->options(Transmission::class)->required(),
                            Select::make('drivetrain')->options(Drivetrain::class)->required(),
                            TextInput::make('interior_color'),
                        ]),

                    Section::make('Photos')
                        ->description('Upload photos (drag to reorder) and/or paste external image URLs. Uploaded photos are shown first.')
                        ->schema([
                            FileUpload::make('uploaded_images')
                                ->label('Uploads')
                                ->image()
                                ->multiple()
                                ->reorderable()
                                ->disk('public')
                                ->directory('vehicles')
                                ->maxSize(8192)
                                ->panelLayout('grid'),
                            TagsInput::make('external_images')
                                ->label('External image URLs')
                                ->placeholder('https://…')
                                ->nestedRecursiveRules(['url']),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Pricing')
                        ->schema([
                            TextInput::make('price')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->helperText('Lowering the price emails everyone who saved this car.'),
                            TextInput::make('previous_price')
                                ->label('Was')
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($record) => filled($record?->previous_price)),
                        ]),

                    Section::make('Listing')
                        ->schema([
                            ToggleButtons::make('status')
                                ->options(VehicleStatus::class)
                                ->default(VehicleStatus::Available)
                                ->inline()
                                ->required(),
                            Toggle::make('is_featured')->label('Feature on home page'),
                            DateTimePicker::make('published_at')
                                ->label('Publish at')
                                ->helperText('Leave empty to keep as a draft.')
                                ->default(now()),
                            Grid::make(2)->schema([
                                TextInput::make('views')->disabled()->dehydrated(false),
                                DateTimePicker::make('sold_at')->disabled()->dehydrated(false),
                            ])->visibleOn('edit'),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /**
     * Split the stored `images` array into uploads and external URLs for the form.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function splitImages(array $data): array
    {
        $images = collect($data['images'] ?? []);
        $isUrl = fn (string $path) => str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/');

        $data['uploaded_images'] = $images->reject($isUrl)->values()->all();
        $data['external_images'] = $images->filter($isUrl)->values()->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mergeImages(array $data): array
    {
        $data['images'] = array_values(array_filter([
            ...array_values((array) ($data['uploaded_images'] ?? [])),
            ...array_values((array) ($data['external_images'] ?? [])),
        ]));

        unset($data['uploaded_images'], $data['external_images']);

        return $data;
    }
}
