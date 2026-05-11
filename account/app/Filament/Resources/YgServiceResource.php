<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YgServiceResource\Pages;
use App\Models\YgService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class YgServiceResource extends Resource
{
    protected static ?string $model = YgService::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'Sovereign Command';
    protected static ?int $navigationSort = 1;
    protected static ?string $label = 'Ecosystem Services';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Service Identity')->schema([
                Forms\Components\TextInput::make('service_name')->required()->maxLength(255),
                Forms\Components\TextInput::make('service_key')->required()->maxLength(50)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('url')->required()->url()->maxLength(255),
                Forms\Components\TextInput::make('port')->numeric()->placeholder('e.g. 80 or 3000'),
            ])->columns(2),

            Forms\Components\Section::make('Availability Control')->schema([
                Forms\Components\Toggle::make('is_active')->label('Operational')->default(true),
                Forms\Components\Toggle::make('is_maintenance')->label('Maintenance Mode')->default(false),
                Forms\Components\Select::make('status')
                    ->options([
                        'operational' => 'Operational',
                        'down' => 'Down',
                        'maintenance' => 'Maintenance',
                        'degraded' => 'Degraded',
                    ])->required()->default('operational'),
            ])->columns(3),

            Forms\Components\Section::make('Intelligence & Monitoring')->schema([
                Forms\Components\TextInput::make('health_check_url')->url()->label('Health Check Endpoint'),
                Forms\Components\KeyValue::make('metadata')->label('Service Metadata (JSON)'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('service_key')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('url')->limit(30),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\IconColumn::make('is_maintenance')
                    ->boolean()
                    ->label('Maint.')
                    ->trueIcon('heroicon-o-wrench-screwdriver')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'operational',
                        'danger' => 'down',
                        'warning' => 'maintenance',
                        'gray' => 'degraded',
                    ]),
                Tables\Columns\TextColumn::make('last_health_check')->dateTime()->label('Last Check'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_maintenance'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('check_health')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(fn (YgService $record) => $record->checkHealth()),
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
            'index' => Pages\ListYgServices::route('/'),
            'create' => Pages\CreateYgService::route('/create'),
            'edit' => Pages\EditYgService::route('/{record}/edit'),
        ];
    }
}
