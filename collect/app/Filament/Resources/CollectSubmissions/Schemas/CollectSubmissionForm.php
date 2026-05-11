<?php

namespace App\Filament\Resources\CollectSubmissions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class CollectSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('form_id')
                    ->relationship('form', 'title')
                    ->required(),
                \Filament\Forms\Components\KeyValue::make('data')
                    ->label('Submission Data')
                    ->columnSpanFull()
                    ->hidden(fn ($record) => $record?->metadata['is_encrypted'] ?? false),
                
                \Filament\Forms\Components\Section::make('🔒 End-to-End Encrypted Data')
                    ->description('This data is encrypted. Enter the master key to view the raw content.')
                    ->visible(fn ($record) => $record?->metadata['is_encrypted'] ?? false)
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('encrypted_blob')
                            ->label('Encrypted Blob')
                            ->content(fn ($record) => $record->data['e2e_blob'] ?? 'No blob found'),
                        \Filament\Forms\Components\TextInput::make('decryption_key')
                            ->label('Master Key')
                            ->password()
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('decrypt')
                                    ->label('Decrypt Now')
                                    ->icon('heroicon-m-lock-open')
                                    ->action(function () {
                                        // This is a placeholder for JS-based decryption
                                    })
                                    ->extraAttributes([
                                        'onclick' => 'handleAdminDecryption()',
                                        'type' => 'button',
                                    ])
                            ),
                        \Filament\Forms\Components\View::make('filament.components.decryption-js'),
                    ]),
                
                \Filament\Forms\Components\KeyValue::make('metadata')
                    ->label('Submission Metadata')
                    ->columnSpanFull(),
            ]);
    }
}
