<?php

namespace App\Filament\Resources\CollectForms\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CollectFormInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('project_id')
                    ->numeric(),
                TextEntry::make('title'),
                TextEntry::make('schema')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('settings')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
