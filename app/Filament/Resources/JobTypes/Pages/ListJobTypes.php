<?php

namespace App\Filament\Resources\JobTypes\Pages;

use App\Filament\Resources\JobTypes\JobTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJobTypes extends ListRecords
{
    protected static string $resource = JobTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}