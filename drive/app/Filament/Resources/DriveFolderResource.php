<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriveFolderResource\Pages;
use App\Models\DriveFolder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DriveFolderResource extends Resource
{
    protected static ?string $model = DriveFolder::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Files';
    protected static ?string $navigationLabel = 'Folders';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()->preload()->required()->label('Owner'),
            Forms\Components\Select::make('parent_id')
                ->relationship('parent', 'name')
                ->searchable()->preload()->nullable()->label('Parent Folder'),
            Forms\Components\ColorPicker::make('color')->default('#4a86e8'),
            Forms\Components\Toggle::make('is_starred')->label('Starred'),
            Forms\Components\Textarea::make('description')->rows(3)->maxLength(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->searchable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent')->default('Root'),
                Tables\Columns\TextColumn::make('files_count')->counts('files')->label('Files'),
                Tables\Columns\TextColumn::make('children_count')->counts('children')->label('Subfolders'),
                Tables\Columns\IconColumn::make('is_starred')->boolean()->label('★'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Owner'),
                Tables\Filters\TernaryFilter::make('is_starred')->label('Starred'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDriveFolders::route('/'),
            'create' => Pages\CreateDriveFolder::route('/create'),
            'edit'   => Pages\EditDriveFolder::route('/{record}/edit'),
        ];
    }
}
