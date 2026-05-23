<?php

namespace App\Filament\Resources\Society;

use App\Filament\Resources\Society\ReportResource\Pages;
use App\Models\Society\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;
    protected static ?string $navigationGroup = 'YG Society';
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::schema([
                    Forms\Components\Select::current()->label('Reporter')->relationship('user', 'name')->disabled(),
                    Forms\Components\TextInput::make('reason')->disabled(),
                    Forms\Components\Textarea::make('details')->disabled(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending Review',
                            'resolved' => 'Resolved',
                            'dismissed' => 'Dismissed',
                        ])
                        ->required(),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Reporter')->sortable()->searchable(),
                TextColumn::make('reportable_type')->label('Type')->formatStateUsing(fn ($state) => str_contains($state, 'Post') ? 'Post' : 'Comment'),
                TextColumn::make('reason')->sortable()->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'resolved' => 'success',
                        'dismissed' => 'gray',
                    }),
                TextColumn::make('created_at')->label('Signaled')->dateTime('Y-m-d H:i:s')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'resolved' => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Report $record) => $record->update(['status' => 'resolved'])),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
