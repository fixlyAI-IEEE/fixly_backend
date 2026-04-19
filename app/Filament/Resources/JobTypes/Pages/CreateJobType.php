<?php

namespace App\Filament\Resources\JobTypes\JobTypeResource\Pages;

use App\Filament\Resources\JobTypes\JobTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobType extends CreateRecord
{
    protected static string $resource = JobTypeResource::class;
}