<?php

namespace App\Filament\Resources\JobTypes\Pages;

use App\Filament\Resources\JobTypes\JobTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJobType extends ViewRecord
{
    protected static string $resource = JobTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}