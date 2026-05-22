<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiUsageLogResource\Pages;
use App\Models\AiUsageLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class AiUsageLogResource extends Resource
{
    protected static ?string $model = AiUsageLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-robot';

    protected static string|UnitEnum|null $navigationGroup = 'AI Services';

    protected static ?int $navigationSort = 1;

    protected static bool $isReadOnly = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->disabled(),

                Forms\Components\TextInput::make('model')
                    ->disabled(),

                Forms\Components\TextInput::make('prompt_tokens')
                    ->numeric()
                    ->disabled(),

                Forms\Components\TextInput::make('completion_tokens')
                    ->numeric()
                    ->disabled(),

                Forms\Components\TextInput::make('cost')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
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

                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('prompt_tokens')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('completion_tokens')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('cost')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model')
                    ->options([
                        'gpt-4' => 'GPT-4',
                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                        'claude-3-opus' => 'Claude 3 Opus',
                        'gemini-pro' => 'Gemini Pro',
                    ]),
                    
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($q) => $q->whereDate('created_at', '>=', $data['created_from']),
                            )
                            ->when(
                                $data['created_until'],
                                fn ($q) => $q->whereDate('created_at', '<=', $data['created_until']),
                            );
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiUsageLogs::route('/'),
            'view' => Pages\ViewAiUsageLog::route('/{record}'),
        ];
    }
}
