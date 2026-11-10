<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\InstagramPostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInstagramPosts extends ManageRecords
{
    protected static string $resource = InstagramPostResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
