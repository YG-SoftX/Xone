<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Activity Logs';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable()->label('User'),
                Tables\Columns\TextColumn::make('action')->searchable()->badge(),
                Tables\Columns\TextColumn::make('description')->limit(80)->searchable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options(fn() => ActivityLog::distinct()->pluck('action', 'action')->toArray()),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
