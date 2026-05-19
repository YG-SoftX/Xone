<?php

namespace App\Filament\Resources\SupportArticles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(60)
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'faq' => 'warning',
                        'article' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('category')
                    ->formatStateUsing(fn ($record) => $record?->categoryLabel() ?? 'Uncategorized')
                    ->toggleable(),
                IconColumn::make('published')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'article' => 'Articles',
                        'faq' => 'FAQs',
                    ]),
                SelectFilter::make('published')
                    ->options([
                        '1' => 'Published',
                        '0' => 'Draft',
                    ]),
                SelectFilter::make('category')
                    ->options([
                        'getting-started' => 'Getting Started',
                        'account' => 'Account & Billing',
                        'mail' => 'YG Mail',
                        'xcel' => 'YG Xcel',
                        'docx' => 'YG DocX',
                        'troubleshooting' => 'Troubleshooting',
                        'security' => 'Security & Privacy',
                    ]),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
