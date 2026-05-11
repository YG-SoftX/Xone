<?php

namespace App\Filament\Resources\PaymentProfiles;

use App\Filament\Resources\PaymentProfiles\Pages\CreatePaymentProfile;
use App\Filament\Resources\PaymentProfiles\Pages\EditPaymentProfile;
use App\Filament\Resources\PaymentProfiles\Pages\ListPaymentProfiles;
use App\Filament\Resources\PaymentProfiles\Schemas\PaymentProfileForm;
use App\Filament\Resources\PaymentProfiles\Tables\PaymentProfilesTable;
use App\Models\PaymentProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentProfileResource extends Resource
{
    protected static ?string $model = PaymentProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PaymentProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentProfilesTable::configure($table);
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
            'index' => ListPaymentProfiles::route('/'),
            'create' => CreatePaymentProfile::route('/create'),
            'edit' => EditPaymentProfile::route('/{record}/edit'),
        ];
    }
}
