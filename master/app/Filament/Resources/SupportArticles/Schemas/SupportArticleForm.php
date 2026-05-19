<?php

namespace App\Filament\Resources\SupportArticles\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SupportArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set, $context) {
                        if ($context === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'article' => 'Article',
                        'faq' => 'FAQ',
                    ])
                    ->required()
                    ->default('article'),
                Select::make('category')
                    ->options([
                        'getting-started' => 'Getting Started',
                        'account' => 'Account & Billing',
                        'mail' => 'YG Mail',
                        'xcel' => 'YG Xcel',
                        'docx' => 'YG DocX',
                        'troubleshooting' => 'Troubleshooting',
                        'security' => 'Security & Privacy',
                    ])
                    ->nullable(),
                Textarea::make('content')
                    ->label('Content (supports plain text and basic HTML)')
                    ->required()
                    ->rows(12)
                    ->columnSpanFull(),
                Toggle::make('published')
                    ->label('Published')
                    ->default(false),
                TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first'),
            ]);
    }
}
