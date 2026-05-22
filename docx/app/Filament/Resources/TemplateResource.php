<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemplateResource\Pages;
use App\Models\Template;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string|UnitEnum|null $navigationGroup = 'Documents';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()->preload()->required()->label('Creator'),
            Forms\Components\Select::make('category')->options([
                'business'   => 'Business',
                'education'  => 'Education',
                'personal'   => 'Personal',
                'legal'      => 'Legal',
                'marketing'  => 'Marketing',
                'other'      => 'Other',
            ])->required()->default('other'),
            Forms\Components\Toggle::make('is_public')->label('Public Template')->default(false),
            Forms\Components\Textarea::make('description')->maxLength(500)->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Creator')->searchable(),
                Tables\Columns\BadgeColumn::make('category')->colors([
                    'primary' => 'business', 'success' => 'education',
                    'warning' => 'personal', 'danger' => 'legal',
                ]),
                Tables\Columns\IconColumn::make('is_public')->boolean()->label('Public'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options([
                    'business' => 'Business', 'education' => 'Education',
                    'personal' => 'Personal', 'legal' => 'Legal',
                ]),
                Tables\Filters\TernaryFilter::make('is_public')->label('Public'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit'   => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
