<?php

namespace App\Filament\Resources\PaymentCycles\Pages;

use App\Filament\Resources\PaymentCycles\PaymentCycleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentCycles extends ListRecords
{
    protected static string $resource = PaymentCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
