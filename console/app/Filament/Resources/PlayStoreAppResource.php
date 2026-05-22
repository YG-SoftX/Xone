<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlayStoreAppResource\Pages;
use App\Models\PlayStoreApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class PlayStoreAppResource extends Resource
{
    protected static ?string $model = PlayStoreApp::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|UnitEnum|null $navigationGroup = 'Applications';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('package_name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Format: com.example.app'),

                Forms\Components\TextInput::make('app_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('version')
                    ->required()
                    ->maxLength(50)
                    ->default('1.0.0'),

                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'review' => 'In Review',
                        'published' => 'Published',
                        'rejected' => 'Rejected',
                    ])
                    ->default('draft')
                    ->required(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),

                Forms\Components\Toggle::make('is_paid')
                    ->label('Paid App'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('app_name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('package_name')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('version')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'review' => 'In Review',
                        'published' => 'Published',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPlayStoreApps::route('/'),
            'create' => Pages\CreatePlayStoreApp::route('/create'),
            'view' => Pages\ViewPlayStoreApp::route('/{record}'),
            'edit' => Pages\EditPlayStoreApp::route('/{record}/edit'),
        ];
    }
}
