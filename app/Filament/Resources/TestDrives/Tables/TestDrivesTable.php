<?php

namespace App\Filament\Resources\TestDrives\Tables;

use App\Enums\TestDriveStatus;
use App\Models\TestDrive;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestDrivesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('vehicle.brand'))
            ->defaultSort('scheduled_at')
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label('When')
                    ->dateTime('D, M j · g:i A')
                    ->description(fn (TestDrive $record) => $record->scheduled_at->diffForHumans())
                    ->sortable(),
                TextColumn::make('vehicle.model')
                    ->label('Vehicle')
                    ->formatStateUsing(fn (TestDrive $record) => "{$record->vehicle->year} {$record->vehicle->brand->name} {$record->vehicle->model}")
                    ->url(fn (TestDrive $record) => route('vehicles.show', $record->vehicle), shouldOpenInNewTab: true),
                TextColumn::make('name')
                    ->label('Customer')
                    ->description(fn (TestDrive $record) => $record->email)
                    ->searchable(['name', 'email', 'reference']),
                TextColumn::make('phone')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('reference')->fontFamily('mono')->copyable()->toggleable(),
                TextColumn::make('created_at')->label('Requested')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(TestDriveStatus::class)->multiple(),
                Filter::make('scheduled_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('scheduled_at', '>=', $date))
                        ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('scheduled_at', '<=', $date))),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->button()
                    ->size('sm')
                    ->visible(fn (TestDrive $record) => $record->status === TestDriveStatus::Pending)
                    ->action(fn (TestDrive $record) => $record->update(['status' => TestDriveStatus::Confirmed])),
                ActionGroup::make([
                    Action::make('complete')
                        ->label('Mark completed')
                        ->icon('heroicon-o-flag')
                        ->color('success')
                        ->visible(fn (TestDrive $record) => $record->status === TestDriveStatus::Confirmed)
                        ->action(fn (TestDrive $record) => $record->update(['status' => TestDriveStatus::Completed])),
                    Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('The customer will be notified by email.')
                        ->visible(fn (TestDrive $record) => in_array($record->status, [TestDriveStatus::Pending, TestDriveStatus::Confirmed], true))
                        ->action(fn (TestDrive $record) => $record->update(['status' => TestDriveStatus::Cancelled])),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
