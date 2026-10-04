<?php

namespace App\Filament\Resources\TestDrives\Pages;

use App\Filament\Resources\TestDrives\TestDriveResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTestDrive extends EditRecord
{
    protected static string $resource = TestDriveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
