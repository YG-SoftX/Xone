<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MailRuleResource\Pages;
use App\Models\MailRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MailRuleResource extends Resource
{
    protected static ?string $model = MailRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-variable';

    protected static string|UnitEnum|null $navigationGroup = 'Automation';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rule Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder('e.g., Sort Invoices'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Trigger')
                    ->schema([
                        Forms\Components\Select::make('trigger_type')
                            ->options([
                                'subject' => 'Subject Contains',
                                'sender' => 'Sender Email',
                                'body' => 'Body Contains',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('trigger_value')
                            ->required()
                            ->placeholder('Keyword or email'),
                    ])->columns(2),

                Forms\Components\Section::make('Action')
                    ->schema([
                        Forms\Components\Select::make('action_type')
                            ->options([
                                'move_to' => 'Move to Folder',
                                'mark_read' => 'Mark as Read',
                                'delete' => 'Delete Permanently',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('action_value')
                            ->options([
                                'inbox' => 'Inbox',
                                'finance' => 'Finance',
                                'important' => 'Important',
                                'spam' => 'Spam',
                                'trash' => 'Trash',
                            ])
                            ->required(fn (Forms\Get $get) => $get('action_type') === 'move_to')
                            ->visible(fn (Forms\Get $get) => $get('action_type') === 'move_to'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trigger_type')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('trigger_value'),
                Tables\Columns\TextColumn::make('action_type')
                    ->badge()
                    ->color('success'),
                Tables\Columns\ToggleColumn::make('is_active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMailRules::route('/'),
        ];
    }
}
