<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\ReservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageReservations extends ManageRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
