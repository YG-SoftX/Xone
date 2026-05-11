<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * PaymentProviderPerformanceWidget
 * 
 * Displays performance metrics for each payment provider.
 * Shows success rates, response times, and transaction volumes.
 */
class PaymentProviderPerformanceWidget extends TableWidget
{
    protected static ?string $heading = 'Payment Provider Performance';
    protected static ?int $sort = 3;

    public function query(): Builder
    {
        return DB::table('payment_transactions')
            ->select(
                'provider',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed'),
                DB::raw('ROUND(SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate'),
                DB::raw('ROUND(AVG(processing_time_ms), 0) as avg_response_time'),
                DB::raw('SUM(amount) as total_volume'),
                DB::raw('MAX(created_at) as last_transaction')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('provider')
            ->orderBy('total_transactions', 'desc');
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->query($this->query())
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('provider')
                    ->label('Provider')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                \Filament\Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Transactions')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state)),

                \Filament\Tables\Columns\TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->color(fn ($state) => $state >= 99 ? 'success' : ($state >= 95 ? 'warning' : 'danger')),

                \Filament\Tables\Columns\TextColumn::make('avg_response_time')
                    ->label('Avg Response')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "{$state}ms")
                    ->color(fn ($state) => $state < 200 ? 'success' : ($state < 500 ? 'warning' : 'danger')),

                \Filament\Tables\Columns\TextColumn::make('total_volume')
                    ->label('Volume (NPR)')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'NPR ' . number_format($state, 2)),

                \Filament\Tables\Columns\TextColumn::make('last_transaction')
                    ->label('Last Transaction')
                    ->dateTime('M d, H:i')
                    ->sortable(),
            ])
            ->defaultSort('total_transactions', 'desc')
            ->paginated(false);
    }
}
