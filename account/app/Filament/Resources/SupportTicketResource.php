<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Support';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ticket')->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()->preload()->required(),
                Forms\Components\TextInput::make('subject')->required()->maxLength(255),
                Forms\Components\Select::make('status')->options([
                    'open'        => 'Open',
                    'in_progress' => 'In Progress',
                    'resolved'    => 'Resolved',
                    'closed'      => 'Closed',
                ])->required()->default('open'),
                Forms\Components\Select::make('priority')->options([
                    'low'      => 'Low',
                    'medium'   => 'Medium',
                    'high'     => 'High',
                    'critical' => 'Critical',
                ])->required()->default('medium'),
                Forms\Components\Textarea::make('message')->required()->rows(6)->columnSpanFull(),
                Forms\Components\Textarea::make('admin_notes')->rows(4)->columnSpanFull()->label('Internal Notes'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(50),
                Tables\Columns\BadgeColumn::make('priority')->colors([
                    'secondary' => 'low',
                    'primary'   => 'medium',
                    'warning'   => 'high',
                    'danger'    => 'critical',
                ]),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'open',
                    'primary' => 'in_progress',
                    'success' => 'resolved',
                    'secondary' => 'closed',
                ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'open' => 'Open', 'in_progress' => 'In Progress',
                    'resolved' => 'Resolved', 'closed' => 'Closed',
                ]),
                Tables\Filters\SelectFilter::make('priority')->options([
                    'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn(SupportTicket $record) => $record->update(['status' => 'resolved']))
                    ->visible(fn(SupportTicket $record) => !in_array($record->status, ['resolved', 'closed'])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'open')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'edit'   => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
