<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriveFileResource\Pages;
use App\Models\DriveFile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DriveFileResource extends Resource
{
    protected static ?string $model = DriveFile::class;
    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationGroup = 'Files';
    protected static ?string $navigationLabel = 'All Files';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('File Details')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255)->label('Display Name'),
                Forms\Components\TextInput::make('original_name')->disabled()->label('Original Name'),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()->preload()->required()->label('Owner'),
                Forms\Components\Select::make('folder_id')
                    ->relationship('folder', 'name')
                    ->searchable()->preload()->nullable()->label('Folder'),
                Forms\Components\TextInput::make('mime_type')->disabled(),
                Forms\Components\TextInput::make('size')->disabled()->suffix('bytes'),
                Forms\Components\Toggle::make('is_starred')->label('Starred'),
                Forms\Components\Toggle::make('is_trashed')->label('Trashed'),
                Forms\Components\Textarea::make('description')->rows(3)->maxLength(500),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()->limit(50)
                    ->description(fn(DriveFile $r) => $r->size_for_humans),
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable()->label('Owner'),
                Tables\Columns\TextColumn::make('folder.name')->default('Root')->label('Folder'),
                Tables\Columns\TextColumn::make('mime_type')->label('Type')->searchable()->badge(),
                Tables\Columns\TextColumn::make('size_for_humans')->label('Size')->sortable(query: fn($q, $d) => $q->orderBy('size', $d)),
                Tables\Columns\IconColumn::make('is_starred')->boolean()->label('★'),
                Tables\Columns\IconColumn::make('is_trashed')->boolean()->label('Trash'),
                Tables\Columns\TextColumn::make('download_count')->label('Downloads')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Owner')
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_starred')->label('Starred'),
                Tables\Filters\TernaryFilter::make('is_trashed')->label('Trashed'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('ai_summarize')
                    ->label('AI Summarize')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('warning')
                    ->modalHeading('AI Intelligence Brief')
                    ->modalDescription('Generating insight using local YugaLM...')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->action(function (DriveFile $record, \App\Services\AiService $ai) {
                        // Logic handled by the modal content
                    })
                    ->modalContent(function (DriveFile $record, \App\Services\AiService $ai) {
                        $path = storage_path('app/' . $record->path);
                        if (!file_exists($path)) {
                            return view('filament.components.ai-brief', ['error' => 'File physical storage not found.']);
                        }
                        
                        $extension = pathinfo($record->name, PATHINFO_EXTENSION);
                        $content = "";
                        
                        if (in_array(strtolower($extension), ['txt', 'md', 'html', 'php', 'js'])) {
                            $content = file_get_contents($path);
                        } else {
                            $content = "AI Analysis: Meta-data search for " . $record->name . " (Mime: " . $record->mime_type . ")";
                        }

                        $summary = $ai->summarizeFile($content);
                        return view('filament.components.ai-brief', ['summary' => $summary, 'file' => $record]);
                    }),
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn(DriveFile $record) => route('drive.download', $record->id))
                    ->openUrlInNewTab(),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDriveFiles::route('/'),
            'create' => Pages\CreateDriveFile::route('/create'),
            'edit'   => Pages\EditDriveFile::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
