<?php

namespace App\Filament\Resources\Worker;

use App\Filament\Resources\Worker\Pages;
use App\Models\Worker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkerResource extends Resource
{
    protected static ?string $model = Worker::class;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-wrench-screwdriver';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'People';
    }

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()->preload()->required(),
            Select::make('job_type_id')
                ->relationship('jobType', 'name')
                ->searchable()->preload()->required(),
            Toggle::make('is_available')->default(true),
            Toggle::make('is_verified')->default(false),
            TextInput::make('rating')
                ->numeric()->minValue(0)->maxValue(5)->default(0),
            CheckboxList::make('working_days')
                ->options([
                    'saturday'  => 'Saturday',
                    'sunday'    => 'Sunday',
                    'monday'    => 'Monday',
                    'tuesday'   => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday'  => 'Thursday',
                    'friday'    => 'Friday',
                ])
                ->columns(3),
            TextInput::make('avg_price')
                ->numeric()->minValue(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')->searchable()->sortable()->label('Name'),
                TextColumn::make('user.phone')->label('Phone'),
                TextColumn::make('jobType.name')->label('Job Type')->sortable(),
                IconColumn::make('is_available')->boolean()->label('Available'),
                IconColumn::make('is_verified')->boolean()->label('Verified'),
                TextColumn::make('rating')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('avg_price')->label('Price')
            ])
            ->filters([
                SelectFilter::make('job_type_id')
                    ->relationship('jobType', 'name')->label('Job Type'),
                TernaryFilter::make('is_verified')->label('Verified'),
                TernaryFilter::make('is_available')->label('Available'),
                TrashedFilter::make(),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope(SoftDeletingScope::class);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWorkers::route('/'),
            'create' => Pages\CreateWorker::route('/create'),
            'edit'   => Pages\EditWorker::route('/{record}/edit'),
        ];
    }
}