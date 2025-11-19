<?php

namespace App\Filament\Resources\ReasonForVisitResource\Pages;

use App\Filament\Resources\ReasonForVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReasonForVisits extends ListRecords
{
    protected static string $resource = ReasonForVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
