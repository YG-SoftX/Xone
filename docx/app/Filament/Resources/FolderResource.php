<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FolderResource\Pages;
use App\Models\Folder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class FolderResource extends Resource
{
    protected static ?string $model = Folder::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static string|UnitEnum|null $navigationGroup = 'Organization';
    protected static ?int $navigationSort = 1;
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
            Forms\Components\ColorPicker::make('color')->nullable(),
            Forms\Components\TextInput::make('icon')->maxLength(50)->default('folder')->placeholder('heroicon name'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->searchable(),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent')->default('Root'),
                Tables\Columns\TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label('Documents')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Owner'),
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
            'index'  => Pages\ListFolders::route('/'),
            'create' => Pages\CreateFolder::route('/create'),
            'edit'   => Pages\EditFolder::route('/{record}/edit'),
        ];
    }
}
