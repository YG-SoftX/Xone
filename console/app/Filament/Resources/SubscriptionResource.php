<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('plan_id')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('provider')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('provider_subscription_id')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('status')
                    ->options([
                        'trialing' => 'Trialing',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                    ])
                    ->default('trialing')
                    ->required(),

                Forms\Components\DateTimePicker::make('current_period_start')
                    ->required(),

                Forms\Components\DateTimePicker::make('current_period_end')
                    ->required(),

                Forms\Components\DateTimePicker::make('trial_ends_at'),

                Forms\Components\DateTimePicker::make('canceled_at'),
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

                Tables\Columns\TextColumn::make('plan_id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('provider')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled' => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_period_end')
                    ->dateTime()
                    ->sortable()
                    ->label('Renews'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trialing' => 'Trialing',
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
