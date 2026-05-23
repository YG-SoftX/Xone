<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformFeatureResource\Pages;
use App\Models\PlatformFeature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlatformFeatureResource extends Resource
{
    protected static ?string $model = PlatformFeature::class;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Sovereign Command';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'System Toggles';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Feature Authority')->schema([
                Forms\Components\TextInput::make('feature_name')->required()->maxLength(255),
                Forms\Components\TextInput::make('feature_key')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->helperText('e.g. business_registration, public_search, ai_training'),
                Forms\Components\Textarea::make('description')->rows(3),
            ])->columns(1),

            Forms\Components\Section::make('State Control')->schema([
                Forms\Components\Toggle::make('is_enabled')
                    ->label('Feature Enabled')
                    ->onColor('success')
                    ->default(true),
                Forms\Components\Toggle::make('is_public')
                    ->label('Publicly Accessible')
                    ->helperText('If off, feature is only available to verified empire members.'),
            ])->columns(2),

            Forms\Components\Section::make('Feature Intelligence')->schema([
                Forms\Components\KeyValue::make('config')
                    ->label('Feature Configuration (JSON)')
                    ->helperText('Define specific parameters for this feature.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('feature_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('feature_key')->badge()->color('info'),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->boolean()
                    ->label('Enabled')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean()
                    ->label('Public'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('Y-m-d H:i:s')->label('Last Modified'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled'),
                Tables\Filters\TernaryFilter::make('is_public'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle')
                    ->icon('heroicon-o-power')
                    ->color(fn (PlatformFeature $record) => $record->is_enabled ? 'danger' : 'success')
                    ->action(fn (PlatformFeature $record) => $record->update(['is_enabled' => !$record->is_enabled])),
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
            'index' => Pages\ListPlatformFeatures::route('/'),
            'create' => Pages\CreatePlatformFeature::route('/create'),
            'edit' => Pages\EditPlatformFeature::route('/{record}/edit'),
        ];
    }
}
