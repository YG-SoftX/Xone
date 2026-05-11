<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financial';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->relationship('user', 'name')->searchable()->preload()->required(),
            Forms\Components\TextInput::make('amount')->numeric()->required()->prefix('NPR'),
            Forms\Components\Select::make('type')->options([
                'deposit'    => 'Deposit',
                'withdrawal' => 'Withdrawal',
                'transfer'   => 'Transfer',
                'payment'    => 'Payment',
                'refund'     => 'Refund',
            ])->required(),
            Forms\Components\Select::make('status')->options([
                'pending'   => 'Pending',
                'completed' => 'Completed',
                'failed'    => 'Failed',
                'cancelled' => 'Cancelled',
            ])->required()->default('pending'),
            Forms\Components\TextInput::make('reference')->maxLength(100),
            Forms\Components\Textarea::make('description')->maxLength(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('NPR')->sortable(),
                Tables\Columns\BadgeColumn::make('type')->colors([
                    'success' => 'deposit',
                    'danger'  => 'withdrawal',
                    'primary' => 'transfer',
                    'warning' => 'payment',
                    'secondary' => 'refund',
                ]),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'pending',
                    'success' => 'completed',
                    'danger'  => 'failed',
                    'secondary' => 'cancelled',
                ]),
                Tables\Columns\TextColumn::make('reference')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'deposit' => 'Deposit', 'withdrawal' => 'Withdrawal',
                    'transfer' => 'Transfer', 'payment' => 'Payment',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'completed' => 'Completed',
                    'failed' => 'Failed', 'cancelled' => 'Cancelled',
                ]),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }
}
