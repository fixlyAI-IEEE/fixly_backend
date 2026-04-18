<?php

namespace App\Filament\Resources\WorkerResource\Pages;
use App\Filament\Resources\Workers\WorkerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorker extends CreateRecord
{
    protected static string $resource = WorkerResource::class;
}
