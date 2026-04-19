<?php

namespace App\Filament\Resources\PaymentCycles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentCycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('worker_id')
                    ->relationship('worker', 'id')
                    ->required(),
                TextInput::make('cycle_number')
                    ->required()
                    ->numeric(),
                TextInput::make('completed_jobs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('amount_due')
                    ->required()
                    ->numeric()
                    ->default(75.0),
                TextInput::make('amount_paid')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'proof_uploaded' => 'Proof uploaded',
            'paid' => 'Paid',
            'rejected' => 'Rejected',
        ])
                    ->default('pending')
                    ->required(),
                FileUpload::make('proof_image')
                    ->image(),
                DateTimePicker::make('proof_uploaded_at'),
                DateTimePicker::make('paid_at'),
                DateTimePicker::make('cycle_started_at'),
                DateTimePicker::make('cycle_ended_at'),
            ]);
    }
}
