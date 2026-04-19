<?php

namespace App\Filament\Resources\Ratings;

use App\Filament\Resources\Ratings\Pages;
use App\Models\Rating;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RatingResource extends Resource
{
    protected static ?string $model = Rating::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-star';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Operation';
    }

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()->preload()->required(),
            Select::make('worker_id')
                ->relationship('worker.user', 'name')
                ->searchable()->preload()->required(),
            TextInput::make('rate')
                ->numeric()->required()->minValue(1)->maxValue(5),
            Textarea::make('comment')->nullable()->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('worker.user.name')->label('Worker')->searchable(),
                TextColumn::make('rate')->sortable(),
                TextColumn::make('comment')->limit(50),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('rate')->options([
                    1 => '1 ⭐', 2 => '2 ⭐', 3 => '3 ⭐', 4 => '4 ⭐', 5 => '5 ⭐',
                ]),
            ])
            ->actions([ViewAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRatings::route('/'),
            'create' => Pages\CreateRating::route('/create'),
            'edit'   => Pages\EditRating::route('/{record}/edit'),
        ];
    }
}