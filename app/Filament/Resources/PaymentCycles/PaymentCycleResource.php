<?php

namespace App\Filament\Resources\PaymentCycles;

use App\Filament\Resources\PaymentCycles\Pages;
use App\Models\PaymentCycle;
use App\Services\PaymentService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PaymentCycleResource extends Resource
{
    protected static ?string $model = PaymentCycle::class;
    protected static ?string $navigationLabel = 'Payment Cycles';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Payments';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('worker_id')
                ->relationship('worker', 'id')
                ->disabled(),
            TextInput::make('cycle_number')->disabled(),
            TextInput::make('completed_jobs')->disabled(),
            TextInput::make('amount_due')->disabled(),
            TextInput::make('amount_paid')->disabled(),
            Select::make('status')
                ->options([
                    'pending'        => 'Pending',
                    'proof_uploaded' => 'Proof Uploaded',
                    'paid'           => 'Paid',
                    'rejected'       => 'Rejected',
                ])->disabled(),
            DateTimePicker::make('proof_uploaded_at')->disabled(),
            DateTimePicker::make('cycle_started_at')->disabled(),
            DateTimePicker::make('cycle_ended_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('worker.user.name')
                    ->label('Worker')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cycle_number')
                    ->label('Cycle')
                    ->sortable(),
                TextColumn::make('completed_jobs')
                    ->label('Jobs'),
                TextColumn::make('amount_due')
                    ->label('Amount Due (EGP)')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Amount Paid (EGP)')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'        => 'warning',
                        'proof_uploaded' => 'primary',
                        'paid'           => 'success',
                        'rejected'       => 'danger',
                        default          => 'gray',
                    }),
                TextColumn::make('proof_uploaded_at')
                    ->label('Proof Uploaded')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('proof_image')
                    ->label('Proof')
                    ->formatStateUsing(fn ($state) => $state ? 'View' : 'No proof')
                    ->url(fn ($record) => $record->proof_image
                        ? asset('storage/' . $record->proof_image)
                        : null
                    )
                    ->openUrlInNewTab(),
                TextColumn::make('cycle_started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cycle_ended_at')
                    ->label('Ended')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'        => 'Pending',
                        'proof_uploaded' => 'Proof Uploaded',
                        'paid'           => 'Paid',
                        'rejected'       => 'Rejected',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->isProofUploaded())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        app(PaymentService::class)->approvePayment($record);
                        Notification::make()
                            ->title('Payment approved. Worker unblocked.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->isProofUploaded())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        app(PaymentService::class)->rejectPayment($record);
                        Notification::make()
                            ->title('Payment proof rejected. Worker must re-upload.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentCycles::route('/'),
        ];
    }
}