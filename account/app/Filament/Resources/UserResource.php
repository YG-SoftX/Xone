<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string|UnitEnum|null $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Info')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('username')->maxLength(50)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
                Forms\Components\DatePicker::make('birthday'),
                Forms\Components\Select::make('gender')->options([
                    'male' => 'Male', 'female' => 'Female', 'other' => 'Other',
                ]),
            ])->columns(2),

            Forms\Components\Section::make('Account Settings')->schema([
                Forms\Components\Select::make('role')->options([
                    'user' => 'User',
                    'admin' => 'Admin',
                    'super_admin' => 'Super Admin',
                ])->required()->default('user'),
                Forms\Components\Select::make('status')->options([
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'banned' => 'Banned',
                ])->required()->default('active'),
                Forms\Components\Select::make('account_type')->options([
                    'personal' => 'Personal',
                    'business' => 'Business',
                ])->default('personal'),
                Forms\Components\Select::make('kyc_status')->options([
                    'pending' => 'Pending',
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                ])->default('pending'),
                Forms\Components\Toggle::make('two_factor_enabled')->label('2FA Enabled'),
            ])->columns(2),

            Forms\Components\Section::make('Society Settings')->schema([
                Forms\Components\TextInput::make('stones')
                    ->numeric()
                    ->default(0)
                    ->label('Stone Balance (Karma)'),
                Forms\Components\Toggle::make('is_society_banned')
                    ->label('Ban from YG Society')
                    ->helperText('Prevents posting or commenting in the community.'),
            ])->columns(2),

            Forms\Components\Section::make('Password')->schema([
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context) => $context === 'create')
                    ->label(fn(string $context) => $context === 'edit' ? 'New Password (leave blank to keep)' : 'Password'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')->circular()->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&color=9B1B30&background=f3e8ea'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('username')->searchable(),
                Tables\Columns\BadgeColumn::make('role')->colors([
                    'warning' => 'admin',
                    'danger'  => 'super_admin',
                    'primary' => 'user',
                ]),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'warning' => 'suspended',
                    'danger'  => 'banned',
                ]),
                Tables\Columns\BadgeColumn::make('kyc_status')->colors([
                    'success' => 'verified',
                    'warning' => 'pending',
                    'danger'  => 'rejected',
                ]),
                Tables\Columns\TextColumn::make('stones')->label('Stones')->sortable(),
                Tables\Columns\IconColumn::make('is_society_banned')
                    ->boolean()
                    ->label('Soc. Ban')
                    ->trueIcon('heroicon-o-no-symbol')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\IconColumn::make('two_factor_enabled')->boolean()->label('2FA'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('Y-m-d H:i:s')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->options([
                    'user' => 'User', 'admin' => 'Admin', 'super_admin' => 'Super Admin',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active', 'suspended' => 'Suspended', 'banned' => 'Banned',
                ]),
                Tables\Filters\SelectFilter::make('kyc_status')->options([
                    'pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn(User $record) => $record->update(['status' => 'suspended']))
                    ->visible(fn(User $record) => $record->status === 'active'),
                Tables\Actions\Action::make('activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn(User $record) => $record->update(['status' => 'active']))
                    ->visible(fn(User $record) => $record->status !== 'active'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('suspend_selected')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-no-symbol')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 'suspended'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
