<?php

namespace App\Filament\Resources\Society;

use App\Filament\Resources\Society\PostResource\Pages;
use App\Models\Society\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationGroup = 'YG Society';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required(),
                    Forms\Components\TextInput::make('flair'),
                    Forms\Components\Textarea::make('content')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('media_url')
                        ->disk('public')
                        ->directory('society/media'),
                    Forms\Components\Toggle::make('is_public')
                        ->default(true),
                    Forms\Components\Toggle::make('is_hidden')
                        ->label('Hide from Feed'),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('media_url')->label('Media')->circular()->disk('public'),
                TextColumn::make('user.name')->label('Author')->sortable()->searchable(),
                TextColumn::make('flair')->badge(),
                TextColumn::make('content')->limit(50)->searchable(),
                TextColumn::make('trending_score')->label('Gravity')->sortable(),
                ToggleColumn::make('is_hidden')->label('Hidden'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_hidden'),
                Tables\Filters\SelectFilter::make('flair')
                    ->options([
                        'Announcement' => 'Announcement',
                        'Discussion' => 'Discussion',
                        'Media' => 'Media',
                        'Project' => 'Project',
                        'Question' => 'Question',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
