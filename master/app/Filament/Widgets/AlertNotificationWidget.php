<?php

namespace App\Filament\Widgets;

use App\Models\AppModule;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * AlertNotificationWidget
 * 
 * Displays critical alerts and service issues requiring attention.
 */
class AlertNotificationWidget extends TableWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AppModule::query()
                    ->whereIn('status', ['degraded', 'unhealthy'])
                    ->orWhere('consecutive_failures', '>=', 3)
                    ->orderBy('consecutive_failures', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Service')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'degraded',
                        'danger' => 'unhealthy',
                    ])
                    ->icons([
                        'heroicon-o-exclamation-triangle' => 'degraded',
                        'heroicon-o-x-circle' => 'unhealthy',
                    ]),

                Tables\Columns\TextColumn::make('consecutive_failures')
                    ->label('Failures')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('health_error')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->health_error),

                Tables\Columns\TextColumn::make('last_health_check')
                    ->label('Last Check')
                    ->dateTime('M d, H:i')
                    ->sortable(),
            ])
            ->defaultSort('consecutive_failures', 'desc')
            ->paginated(false);
    }

    protected function getTableHeading(): string
    {
        return 'Critical Alerts';
    }
}
