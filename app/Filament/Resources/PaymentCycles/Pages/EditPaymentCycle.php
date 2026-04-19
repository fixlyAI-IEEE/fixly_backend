<?php

namespace App\Filament\Resources\PaymentCycles\Pages;

use App\Filament\Resources\PaymentCycles\PaymentCycleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentCycle extends EditRecord
{
    protected static string $resource = PaymentCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
