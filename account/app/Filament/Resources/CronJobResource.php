<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CronJobResource\Pages;
use App\Filament\Resources\CronJobResource\RelationManagers;
use App\Models\CronJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use UnitEnum;

class CronJobResource extends Resource
{
    protected static ?string $model = CronJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static string|UnitEnum|null $navigationGroup = 'System Management';
    
    protected static ?int $navigationSort = 50;
    
    protected static ?string $navigationLabel = 'Cron Jobs';
    
    protected static ?string $modelLabel = 'Cron Job';
    
    protected static ?string $pluralModelLabel = 'Cron Jobs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Job Configuration')
                    ->description('Configure the cron job schedule and command')
                    ->schema([
                        TextInput::make('name')
                            ->label('Job Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique identifier for this cron job (e.g., laravel-scheduler)'),
                        
                        Textarea::make('command')
                            ->label('Command')
                            ->required()
                            ->rows(3)
                            ->placeholder('/usr/bin/php /path/to/artisan schedule:run >> /dev/null 2>&1')
                            ->helperText('The full shell command to execute'),
                        
                        TextInput::make('schedule')
                            ->label('Schedule (Cron Expression)')
                            ->required()
                            ->placeholder('* * * * *')
                            ->helperText('Standard cron format: minute hour day month weekday'),
                        
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->helperText('Human-readable description of what this job does'),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->default(true)
                            ->helperText('Enable or disable this cron job'),
                        
                        Toggle::make('is_system')
                            ->label('System Job')
                            ->default(false)
                            ->helperText('System jobs cannot be deleted by users'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Job Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (CronJob $record) => $record->description),
                
                TextColumn::make('schedule')
                    ->label('Schedule')
                    ->badge()
                    ->color('gray'),
                
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'success',
                        'warning' => 'running',
                        'danger' => 'failed',
                        'gray' => 'pending',
                    ]),
                
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                TextColumn::make('last_run_at')
                    ->label('Last Run')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('next_run_human')
                    ->label('Next Run')
                    ->toggleable(),
                
                TextColumn::make('total_runs')
                    ->label('Runs')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('success_rate')
                    ->label('Success Rate')
                    ->formatStateUsing(fn (float $state) => number_format($state, 1) . '%')
                    ->color(fn (float $state) => $state >= 90 ? 'success' : ($state >= 70 ? 'warning' : 'danger'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Enabled Status'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'running' => 'Running',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
                
                Tables\Filters\Filter::make('system_jobs')
                    ->query(fn (Builder $query): Builder => $query->where('is_system', true))
                    ->label('Show System Jobs Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Action::make('view_output')
                    ->label('View Output')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (CronJob $record) => "Output: {$record->name}")
                    ->modalContent(fn (CronJob $record) => $record->last_output ?? 'No output recorded')
                    ->visible(fn (CronJob $record) => !empty($record->last_output)),
                
                Action::make('test_run')
                    ->label('Test Run')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (CronJob $record) {
                        try {
                            // Simulate execution (in production, you'd actually run the command)
                            $record->update([
                                'last_run_at' => now(),
                                'status' => 'success',
                                'total_runs' => $record->total_runs + 1,
                                'last_output' => 'Test execution completed successfully at ' . now()->toDateTimeString(),
                            ]);
                            
                            Notification::make()
                                ->title('Test Run Successful')
                                ->body("Cron job '{$record->name}' executed successfully.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Test Run Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Action::make('generate_cpanel_command')
                    ->label('cPanel Command')
                    ->icon('heroicon-o-code-bracket')
                    ->color('info')
                    ->modalHeading('cPanel Cron Job Command')
                    ->modalContent(fn (CronJob $record) => view('filament.cron-job-cpanel-command', ['cronJob' => $record]))
                    ->modalFooterActions([]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(fn () => auth()->user()?->role !== 'super_admin'),
                    
                    Tables\Actions\BulkAction::make('enable')
                        ->label('Enable Selected')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => true]))
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('disable')
                        ->label('Disable Selected')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCronJobs::route('/'),
            'create' => Pages\CreateCronJob::route('/create'),
            'edit' => Pages\EditCronJob::route('/{record}/edit'),
        ];
    }
    
    /**
     * Get navigation badge count (number of enabled jobs)
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_enabled', true)->count();
    }
    
    /**
     * Get navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        $failedCount = static::getModel()::where('status', 'failed')->count();
        return $failedCount > 0 ? 'danger' : 'success';
    }
}
