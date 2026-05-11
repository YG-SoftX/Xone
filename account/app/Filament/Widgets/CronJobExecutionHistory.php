<?php

namespace App\Filament\Widgets;

use App\Models\CronJob;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CronJobExecutionHistory extends TableWidget
{
    protected int|string|array $columnSpan = 'full';
    
    protected static ?string $heading = 'Recent Job Executions';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CronJob::query()
                    ->whereNotNull('last_run_at')
                    ->orderBy('last_run_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Job Name')
                    ->searchable()
                    ->description(fn (CronJob $record) => $record->description),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'success',
                        'warning' => 'running',
                        'danger' => 'failed',
                        'gray' => 'pending',
                    ]),
                
                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Executed At')
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('last_output')
                    ->label('Output')
                    ->limit(100)
                    ->tooltip(fn (CronJob $record) => $record->last_output),
                
                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->formatStateUsing(fn (float $state) => number_format($state, 1) . '%')
                    ->color(fn (float $state) => $state >= 90 ? 'success' : ($state >= 70 ? 'warning' : 'danger')),
            ])
            ->paginated(false);
    }
}
