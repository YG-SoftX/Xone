<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OAuthApplicationResource\Pages;
use App\Models\OAuthApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OAuthApplicationResource extends Resource
{
    protected static ?string $model = OAuthApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'API Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TagsInput::make('redirect_uris')
                    ->placeholder('https://example.com/callback')
                    ->columnSpanFull(),

                Forms\Components\TagsInput::make('scopes')
                    ->placeholder('read')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_confidential')
                    ->default(true)
                    ->label('Confidential Client'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_id')
                    ->limit(15)
                    ->copyable(),

                Tables\Columns\IconColumn::make('is_confidential')
                    ->boolean()
                    ->label('Confidential'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_confidential')
                    ->label('Client Type'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOAuthApplications::route('/'),
            'create' => Pages\CreateOAuthApplication::route('/create'),
            'view' => Pages\ViewOAuthApplication::route('/{record}'),
            'edit' => Pages\EditOAuthApplication::route('/{record}/edit'),
        ];
    }
}
