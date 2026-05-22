<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string|UnitEnum|null $navigationGroup = 'Users';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('User Info')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('username')->maxLength(50)->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')->options([
                    'user'       => 'User',
                    'admin'      => 'Admin',
                    'super_admin' => 'Super Admin',
                ])->required()->default('user'),
                Forms\Components\Select::make('status')->options([
                    'active'    => 'Active',
                    'suspended' => 'Suspended',
                    'banned'    => 'Banned',
                ])->required()->default('active'),
                Forms\Components\TextInput::make('storage_used')->numeric()->disabled()->suffix('bytes'),
            ])->columns(2),
            Forms\Components\Section::make('Storage Quota')->relationship('storageQuota')->schema([
                Forms\Components\TextInput::make('quota_bytes')->numeric()->required()->default(15368709120)->suffix('bytes')->helperText('15 GB = 15368709120'),
                Forms\Components\Select::make('plan')->options([
                    'free'       => 'Free (15 GB)',
                    'pro'        => 'Pro (100 GB)',
                    'business'   => 'Business (1 TB)',
                    'enterprise' => 'Enterprise (Unlimited)',
                ])->default('free'),
            ])->columns(2),
            Forms\Components\Section::make('Password')->schema([
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context) => $context === 'create'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')->circular()->defaultImageUrl(fn($r) => 'https://ui-avatars.com/api/?name='.urlencode($r->name).'&color=1a73e8&background=e8f0fe'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\BadgeColumn::make('role')->colors([
                    'primary' => 'user',
                    'warning' => 'admin',
                    'danger'  => 'super_admin',
                ]),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'success'   => 'active',
                    'warning'   => 'suspended',
                    'danger'    => 'banned',
                ]),
                Tables\Columns\TextColumn::make('storage_used_for_humans')->label('Used'),
                Tables\Columns\TextColumn::make('storage_quota_for_humans')->label('Quota'),
                Tables\Columns\TextColumn::make('drive_files_count')->counts('driveFiles')->label('Files'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->options([
                    'user' => 'User', 'admin' => 'Admin', 'super_admin' => 'Super Admin',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'active' => 'Active', 'suspended' => 'Suspended', 'banned' => 'Banned',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn(User $r) => $r->update(['status' => 'suspended']))
                    ->visible(fn(User $r) => $r->status === 'active'),
                Tables\Actions\Action::make('activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn(User $r) => $r->update(['status' => 'active']))
                    ->visible(fn(User $r) => $r->status !== 'active'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
