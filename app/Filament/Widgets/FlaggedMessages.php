<?php

namespace App\Filament\Widgets;

use App\Models\DealMessage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FlaggedMessages extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recently flagged by scam shield';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DealMessage::query()->with(['user', 'deal.vehicle'])->whereNotNull('risk_flags')->latest())
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('created_at')->label('Sent')->since(),
                TextColumn::make('user.name')->label('From')->description(fn (DealMessage $m) => $m->user?->email),
                TextColumn::make('body')->limit(120)->wrap()->extraAttributes(['style' => 'min-width: 22rem']),
                TextColumn::make('risk_flags')->label('Flags')->badge()->color('danger')->listWithLineBreaks()
                    ->state(fn (DealMessage $m) => collect($m->risk_flags)->pluck('label')->all()),
                TextColumn::make('deal.vehicle.model')->label('Deal')->formatStateUsing(fn (DealMessage $m) => $m->deal->vehicle->title()),
            ]);
    }
}
