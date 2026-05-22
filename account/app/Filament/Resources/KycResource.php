<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KycResource\Pages;
use App\Models\KYC;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class KycResource extends Resource
{
    protected static ?string $model = KYC::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'KYC Verifications';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('KYC Details')->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')->options([
                    'pending'  => 'Pending',
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                ])->required()->default('pending'),
                Forms\Components\TextInput::make('document_type')->maxLength(100),
                Forms\Components\TextInput::make('document_number')->maxLength(100),
                Forms\Components\FileUpload::make('document_front')->image()->directory('kyc/front'),
                Forms\Components\FileUpload::make('document_back')->image()->directory('kyc/back'),
                Forms\Components\Textarea::make('rejection_reason')->maxLength(500)->visible(fn($get) => $get('status') === 'rejected'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable()->label('User'),
                Tables\Columns\TextColumn::make('user.email')->searchable()->label('Email'),
                Tables\Columns\TextColumn::make('document_type'),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'pending',
                    'success' => 'verified',
                    'danger'  => 'rejected',
                ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime('Y-m-d H:i:s')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (KYC $record) {
                        $record->update(['status' => 'verified']);
                        $record->user->update(['kyc_status' => 'verified']);
                    })
                    ->visible(fn(KYC $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')->required()->label('Reason'),
                    ])
                    ->action(function (KYC $record, array $data) {
                        $record->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);
                        $record->user->update(['kyc_status' => 'rejected']);
                    })
                    ->visible(fn(KYC $record) => $record->status === 'pending'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKycs::route('/'),
            'create' => Pages\CreateKyc::route('/create'),
            'edit'   => Pages\EditKyc::route('/{record}/edit'),
        ];
    }
}
