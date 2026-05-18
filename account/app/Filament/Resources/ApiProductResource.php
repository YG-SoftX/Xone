<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiProductResource\Pages;
use App\Models\ApiProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApiProductResource extends Resource
{
    protected static ?string $model = ApiProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Sovereign Command';
    protected static ?int $navigationSort = 3;
    protected static ?string $label = 'Subscription Plans';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Plan Identity')->schema([
                Forms\Components\TextInput::make('display_name')->required()->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->helperText('Internal plan key (e.g. enterprise_v1)'),
                Forms\Components\Textarea::make('description')->rows(3),
                Forms\Components\TextInput::make('icon')->placeholder('heroicon-o-star'),
            ])->columns(1),

            Forms\Components\Section::make('Economic Strategy')->schema([
                Forms\Components\TextInput::make('price_per_1000_calls')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->label('Price Rate'),
                Forms\Components\Toggle::make('is_active')->label('Active Plan')->default(true),
                Forms\Components\Toggle::make('requires_approval')->label('Requires Admin Approval'),
            ])->columns(3),

            Forms\Components\Section::make('Usage Quotas')->schema([
                Forms\Components\TextInput::make('default_daily_quota')
                    ->numeric()
                    ->helperText('0 for unlimited'),
                Forms\Components\TextInput::make('default_monthly_quota')
                    ->numeric()
                    ->helperText('0 for unlimited'),
            ])->columns(2),

            Forms\Components\Section::make('Technical Boundaries')->schema([
                Forms\Components\KeyValue::make('endpoints')
                    ->label('Allowed API Endpoints')
                    ->helperText('Define which routes this plan can access.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('price_per_1000_calls')
                    ->money('USD')
                    ->label('Rate')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('default_monthly_quota')->label('Mo. Quota')->formatStateUsing(fn ($state) => $state == 0 ? 'Unlimited' : number_format($state)),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('Y-m-d H:i:s')->label('Modified'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListApiProducts::route('/'),
            'create' => Pages\CreateApiProduct::route('/create'),
            'edit' => Pages\EditApiProduct::route('/{record}/edit'),
        ];
    }
}
