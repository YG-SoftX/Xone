<?php

namespace App\Filament\Resources\SupportUsers;

use App\Filament\Resources\SupportUsers\Pages\ListSupportUsers;
use App\Filament\Resources\SupportUsers\Tables\SupportUsersTable;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupportUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Ecosystem Management';

    protected static ?string $navigationLabel = 'Support Users';

    protected static ?int $navigationSort = 12;

    public static function table(Table $table): Table
    {
        return SupportUsersTable::configure($table);
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
            'index' => ListSupportUsers::route('/'),
        ];
    }
}
