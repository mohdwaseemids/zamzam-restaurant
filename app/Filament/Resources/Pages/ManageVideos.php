<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\VideoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVideos extends ManageRecords
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
