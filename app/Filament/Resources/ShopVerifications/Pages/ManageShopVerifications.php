<?php

namespace App\Filament\Resources\ShopVerifications\Pages;

use App\Filament\Resources\ShopVerifications\ShopVerificationResource;
use Filament\Resources\Pages\ManageRecords;

class ManageShopVerifications extends ManageRecords
{
    protected static string $resource = ShopVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
