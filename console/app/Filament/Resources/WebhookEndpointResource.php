<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookEndpointResource\Pages;
use App\Models\WebhookEndpoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookEndpointResource extends Resource
{
    protected static ?string $model = WebhookEndpoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-webhook';

    protected static ?string $navigationGroup = 'Webhooks';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('url')
                    ->required()
                    ->maxLength(2048)
                    ->url(),

                Forms\Components\TagsInput::make('events')
                    ->placeholder('payment.succeeded')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('active')
                    ->default(true),

                Forms\Components\TextInput::make('max_retries')
                    ->numeric()
                    ->default(3)
                    ->minValue(1)
                    ->maxValue(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('url')
                    ->limit(50)
                    ->copyable(),

                Tables\Columns\IconColumn::make('active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('deliveries_count')
                    ->label('Deliveries')
                    ->counts('deliveries')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active'),
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
            'index' => Pages\ListWebhookEndpoints::route('/'),
            'create' => Pages\CreateWebhookEndpoint::route('/create'),
            'view' => Pages\ViewWebhookEndpoint::route('/{record}'),
            'edit' => Pages\EditWebhookEndpoint::route('/{record}/edit'),
        ];
    }
}
