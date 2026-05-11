<?php

namespace App\Filament\Resources\InfrastructureNodes;

use App\Filament\Resources\InfrastructureNodes\Pages\CreateInfrastructureNode;
use App\Filament\Resources\InfrastructureNodes\Pages\EditInfrastructureNode;
use App\Filament\Resources\InfrastructureNodes\Pages\ListInfrastructureNodes;
use App\Filament\Resources\InfrastructureNodes\Schemas\InfrastructureNodeForm;
use App\Filament\Resources\InfrastructureNodes\Tables\InfrastructureNodesTable;
use App\Models\InfrastructureNode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InfrastructureNodeResource extends Resource
{
    protected static ?string $model = InfrastructureNode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InfrastructureNodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InfrastructureNodesTable::configure($table);
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
            'index' => ListInfrastructureNodes::route('/'),
            'create' => CreateInfrastructureNode::route('/create'),
            'edit' => EditInfrastructureNode::route('/{record}/edit'),
        ];
    }
}
