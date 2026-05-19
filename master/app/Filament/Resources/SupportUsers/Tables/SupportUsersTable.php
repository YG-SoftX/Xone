<?php

namespace App\Filament\Resources\SupportUsers\Tables;

use App\Models\Support\Ticket;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupportUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->getStateUsing(function ($record) {
                        return Ticket::where('user_id', $record->id)->count();
                    })
                    ->sortable(false),
                TextColumn::make('open_tickets_count')
                    ->label('Open')
                    ->getStateUsing(function ($record) {
                        return Ticket::where('user_id', $record->id)
                            ->whereIn('status', ['open', 'pending'])
                            ->count();
                    })
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }
}
