<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UniversalFooterItemsResource\Pages;
use App\Models\UniversalFooterItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UniversalFooterItemsResource extends Resource
{
    protected static ?string $model = UniversalFooterItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Ecosystem Configuration';

    protected static ?int $navigationSort = 80;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Footer Item Details')
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255)
                            ->label('Display Label')
                            ->helperText('Text shown in the footer link'),

                        Forms\Components\TextInput::make('url')
                            ->required()
                            ->maxLength(255)
                            ->label('URL')
                            ->helperText('Link destination (can be internal or external)'),

                        Forms\Components\Select::make('service_key')
                            ->options([
                                'global' => 'Global (All Services)',
                                'mail' => 'YG Mail',
                                'drive' => 'YG Drive',
                                'docx' => 'YG DocX',
                                'chat' => 'YG Chat',
                                'calendar' => 'YG Calendar',
                                'contacts' => 'YG Contacts',
                                'notes' => 'YG Notes',
                                'xcel' => 'YG Xcel',
                                'meet' => 'YG Meet',
                                'pay' => 'YG Pay',
                                'ai' => 'YG AI',
                                'account' => 'YG Account',
                                'appstore' => 'App Store',
                                'home' => 'Home/Browser',
                            ])
                            ->default('global')
                            ->required()
                            ->label('Service')
                            ->helperText('Which service this footer item belongs to'),

                        Forms\Components\TextInput::make('icon')
                            ->maxLength(255)
                            ->label('Icon Class')
                            ->placeholder('fas fa-link')
                            ->helperText('Font Awesome icon class (optional)'),

                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->label('Sort Order')
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active')
                            ->helperText('Show/hide this footer item'),

                        Forms\Components\Toggle::make('is_external')
                            ->default(false)
                            ->label('External Link')
                            ->helperText('Open in new tab'),

                        Forms\Components\Textarea::make('metadata')
                            ->rows(3)
                            ->label('Metadata (JSON)')
                            ->helperText('Additional configuration as JSON (optional)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable()
                    ->label('Label'),

                Tables\Columns\TextColumn::make('url')
                    ->searchable()
                    ->limit(50)
                    ->label('URL'),

                Tables\Columns\BadgeColumn::make('service_key')
                    ->colors([
                        'primary' => 'global',
                        'success' => fn ($state): bool => !in_array($state, ['global']),
                    ])
                    ->label('Service'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\IconColumn::make('is_external')
                    ->boolean()
                    ->label('External'),

                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label('Order'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_key')
                    ->options([
                        'global' => 'Global',
                        'mail' => 'Mail',
                        'drive' => 'Drive',
                        'docx' => 'DocX',
                        'chat' => 'Chat',
                        'calendar' => 'Calendar',
                        'contacts' => 'Contacts',
                        'notes' => 'Notes',
                        'xcel' => 'Xcel',
                        'meet' => 'Meet',
                        'pay' => 'Pay',
                        'ai' => 'AI',
                        'account' => 'Account',
                        'appstore' => 'App Store',
                        'home' => 'Home',
                    ])
                    ->label('Service'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUniversalFooterItems::route('/'),
            'create' => Pages\CreateUniversalFooterItem::route('/create'),
            'edit' => Pages\EditUniversalFooterItem::route('/{record}/edit'),
        ];
    }
}
