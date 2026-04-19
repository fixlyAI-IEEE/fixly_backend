<?php

namespace App\Filament\Resources\Chats;

use App\Filament\Resources\Chats\Pages;
use App\Models\Chat;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChatResource extends Resource
{
    protected static ?string $model = Chat::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Operation';
    }

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()->preload()->required(),
            Select::make('job_type_id')
                ->relationship('jobType', 'name')
                ->searchable()->preload()->required(),
            Textarea::make('message')->required()->rows(3),
            Textarea::make('response')->nullable()->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('jobType.name')->label('Job Type'),
                TextColumn::make('message')->limit(50),
                TextColumn::make('response')->limit(50),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([ViewAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChats::route('/'),
            'create' => Pages\CreateChat::route('/create'),
            'edit'   => Pages\EditChat::route('/{record}/edit'),
        ];
    }
}