<?php

namespace App\Filament\Resources\JobTypes;

use App\Filament\Resources\JobTypes\JobTypeResource\Pages;
use App\Models\JobType;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;


class JobTypeResource extends Resource
{
    protected static ?string $model = JobType::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-briefcase';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Catalog';
    }

    public static function getNavigationLabel(): string
    {
        return 'Job Types';
    }


    public static function form(Schema $schema): Schema   
     {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('workers_count')
                    ->counts('workers')
                    ->label('Workers')
                    ->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListJobTypes::route('/'),
            'create' => Pages\CreateJobType::route('/create'),
            'edit'   => Pages\EditJobType::route('/{record}/edit'),
        ];
    }
}