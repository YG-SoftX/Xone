<?php

namespace App\Filament\Resources\CollectSubmissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form.title')
                    ->label('Form')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('metadata.ip')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('data.farm_loc')
                    ->label('GPS')
                    ->placeholder('N/A')
                    ->copyable(),
                \Filament\Tables\Columns\ImageColumn::make('data.rx_photo')
                    ->label('Capture')
                    ->circular(),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('form_id')
                    ->label('Filter by Form')
                    ->relationship('form', 'title'),
            ])
            ->recordActions([
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\Actions\Action::make('download_pdf')
                    ->label('Official PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($record) {
                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.submission', ['submission' => $record]);
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, "Submission_{$record->id}.pdf");
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
