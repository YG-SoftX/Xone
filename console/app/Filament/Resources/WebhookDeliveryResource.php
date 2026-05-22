<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookDeliveryResource\Pages;
use App\Models\WebhookDelivery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class WebhookDeliveryResource extends Resource
{
    protected static ?string $model = WebhookDelivery::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|UnitEnum|null $navigationGroup = 'Webhooks';

    protected static ?int $navigationSort = 2;

    protected static bool $isReadOnly = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('webhook_endpoint_id')
                    ->relationship('webhookEndpoint', 'url')
                    ->disabled(),

                Forms\Components\TextInput::make('event_type')
                    ->disabled(),

                Forms\Components\TextInput::make('status_code')
                    ->numeric()
                    ->disabled(),

                Forms\Components\TextInput::make('attempt')
                    ->numeric()
                    ->disabled(),

                Forms\Components\DateTimePicker::make('delivered_at')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('webhookEndpoint.url')
                    ->label('Endpoint')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('event_type')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status_code')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('attempt')
                    ->sortable(),

                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('successful')
                    ->query(fn ($query) => $query->successful()),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookDeliveries::route('/'),
            'view' => Pages\ViewWebhookDelivery::route('/{record}'),
        ];
    }
}
