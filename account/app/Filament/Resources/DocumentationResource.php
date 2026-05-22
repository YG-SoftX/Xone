<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentationResource\Pages;
use App\Models\Documentation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class DocumentationResource extends Resource
{
    protected static ?string $model = Documentation::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static string|UnitEnum|null $navigationGroup = 'Knowledge Base';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->lazy()
                                    ->afterStateUpdated(fn (string $context, $state, callable $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->unique(Documentation::class, 'slug', ignoreRecord: true),
                            ]),
                        Forms\Components\Select::make('category')
                            ->options([
                                'account' => 'YG Account',
                                'mail' => 'YG Mail',
                                'pay' => 'YG Pay',
                                'drive' => 'YG Drive',
                                'meet' => 'YG Meet',
                                'docx' => 'YG Docx',
                                'playstore' => 'YG Playstore',
                                'security' => 'Privacy & Safety',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('summary')
                            ->rows(3)
                            ->required(),
                        Forms\Components\RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                    ])->columnSpan(2),
                
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->default(true),
                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(0),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('category')
                    ->colors([
                        'primary' => 'account',
                        'success' => 'pay',
                        'warning' => 'mail',
                    ]),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Status'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'account' => 'YG Account',
                        'mail' => 'YG Mail',
                        'pay' => 'YG Pay',
                    ]),
            ])
            ->actions([
                Tables\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentations::route('/'),
            'create' => Pages\CreateDocumentation::route('/create'),
            'edit' => Pages\EditDocumentation::route('/{record}/edit'),
        ];
    }
}
