<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Documents';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Document Info')->schema([
                Forms\Components\TextInput::make('title')->required()->maxLength(255),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()->preload()->required()->label('Owner'),
                Forms\Components\Select::make('folder_id')
                    ->relationship('folder', 'name')
                    ->searchable()->preload()->nullable()->label('Folder'),
                Forms\Components\Select::make('document_type')->options([
                    'document'     => 'Document',
                    'spreadsheet'  => 'Spreadsheet',
                    'presentation' => 'Presentation',
                    'template'     => 'Template',
                ])->required()->default('document'),
                Forms\Components\Select::make('status')->options([
                    'draft'     => 'Draft',
                    'published' => 'Published',
                    'archived'  => 'Archived',
                ])->required()->default('draft'),
            ])->columns(2),

            Forms\Components\Section::make('Editor')
                ->schema([
                    Forms\Components\RichEditor::make('content')
                        ->label('Document Content')
                        ->required()
                        ->columnSpanFull()
                        ->placeholder('Start writing your sovereign masterpiece...')
                        ->toolbarButtons([
                            'attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike', 'undo',
                        ]),
                ]),

            Forms\Components\Section::make('AI Writing Assistant')
                ->description('Harness the power of local YugaLM intelligence')
                ->schema([
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('ai_expand')
                            ->label('Expand Content')
                            ->icon('heroicon-o-sparkles')
                            ->color('warning')
                            ->action(function (Forms\Set $set, $state, \App\Services\AiService $ai) {
                                if (empty($state)) return;
                                $set('content', $ai->expandText($state));
                            }),
                        Forms\Components\Actions\Action::make('ai_proofread')
                            ->label('Proofread')
                            ->icon('heroicon-o-check-badge')
                            ->color('success')
                            ->action(function (Forms\Set $set, $state, \App\Services\AiService $ai) {
                                if (empty($state)) return;
                                $set('content', $ai->proofreadText($state));
                            }),
                    ]),
                ])->collapsed(),

            Forms\Components\Section::make('Stats')->schema([
                Forms\Components\TextInput::make('word_count')->numeric()->default(0)->disabled(),
                Forms\Components\TextInput::make('page_count')->numeric()->default(1)->disabled(),
            ])->columns(2)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(60),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('folder.name')->label('Folder')->default('—'),
                Tables\Columns\BadgeColumn::make('document_type')->colors([
                    'primary'   => 'document',
                    'success'   => 'spreadsheet',
                    'warning'   => 'presentation',
                    'secondary' => 'template',
                ]),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'secondary' => 'draft',
                    'success'   => 'published',
                    'warning'   => 'archived',
                ]),
                Tables\Columns\TextColumn::make('word_count')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('page_count')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->label('Last Modified'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')->options([
                    'document' => 'Document', 'spreadsheet' => 'Spreadsheet',
                    'presentation' => 'Presentation', 'template' => 'Template',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived',
                ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit'   => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
