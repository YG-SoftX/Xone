<?php

namespace App\Filament\Resources;

use App\Models\Attachment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';
    protected static ?string $navigationGroup = 'Communications';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('mail_id')
                    ->relationship('mail', 'subject')
                    ->required(),
                Forms\Components\TextInput::make('file_name')
                    ->required(),
                Forms\Components\TextInput::make('mime_type')
                    ->required(),
                Forms\Components\TextInput::make('file_size')
                    ->numeric()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mail.subject')
                    ->label('Email Subject')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mime_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size (Bytes)')
                    ->numeric(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('mail', fn ($query) => $query->where('user_id', Auth::id()));
    }

    public static function getPages(): array
    {
        return [
            'index' => AttachmentResource\Pages\ListAttachments::route('/'),
        ];
    }
}
