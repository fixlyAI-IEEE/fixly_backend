<?php

namespace App\Filament\Resources\PaymentCycles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentCyclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('worker.id')
                    ->searchable(),
                TextColumn::make('cycle_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('completed_jobs')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_due')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                ImageColumn::make('proof_image'),
                TextColumn::make('proof_uploaded_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cycle_started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('cycle_ended_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
