<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiKeyResource\Pages;
use App\Models\ApiKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'API Management';

    protected static ?int $navigationSort = 1;

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

                Forms\Components\TextInput::make('rate_limit')
                    ->numeric()
                    ->default(1000)
                    ->required(),

                Forms\Components\KeyValue::make('restrictions')
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('expires_at'),
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

                Tables\Columns\TextColumn::make('key')
                    ->limit(20)
                    ->copyable(),

                Tables\Columns\TextColumn::make('rate_limit')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->badge(fn ($record) => $record->isExpired() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->active()),
                    
                Tables\Filters\Filter::make('expired')
                    ->query(fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<', now())),
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
            'index' => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKey::route('/create'),
            'view' => Pages\ViewApiKey::route('/{record}'),
            'edit' => Pages\EditApiKey::route('/{record}/edit'),
        ];
    }
}
