<?php

namespace App\Filament\Resources;

use App\Models\Mail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MailResource extends Resource
{
    protected static ?string $model = Mail::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Communications';
    protected static ?string $navigationLabel = 'Inbox';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('folder', 'inbox')
            ->where('read', false)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getGloballySearchableAttributes(): array
    {
        return ['subject', 'body', 'from', 'to'];
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('template_id')
                    ->label('Use Template')
                    ->options(\App\Models\MailTemplate::where('user_id', \Illuminate\Support\Facades\Auth::id())->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $template = \App\Models\MailTemplate::find($state);
                            if ($template) {
                                $set('subject', $template->subject);
                                $set('body', $template->body);
                            }
                        }
                    })
                    ->placeholder('Select a template...'),
                Forms\Components\TextInput::make('to')
                    ->required()
                    ->email(),
                Forms\Components\TextInput::make('subject')
                    ->required(),
                Forms\Components\RichEditor::make('body')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Schedule For')
                    ->native(false)
                    ->suffixIcon('heroicon-m-clock')
                    ->helperText('Leave empty to send immediately.'),
                Forms\Components\Select::make('folder')
                    ->options([
                        'inbox' => 'Inbox',
                        'sent' => 'Sent',
                        'trash' => 'Trash',
                        'drafts' => 'Drafts',
                        'spam' => 'Spam',
                        'finance' => 'Finance',
                        'important' => 'Important',
                    ])
                    ->default('sent')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from')
                    ->searchable(),
                Tables\Columns\TextColumn::make('to')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sentiment')
                    ->label('Tone')
                    ->state(function (Mail $record) {
                        return app(\App\Services\AiService::class)->analyzeSentiment($record->body);
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Urgent' => 'danger',
                        'Frustrated' => 'warning',
                        'Friendly' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('read')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('folder')
                    ->options([
                        'inbox' => 'Inbox',
                        'sent' => 'Sent',
                        'trash' => 'Trash',
                        'drafts' => 'Drafts',
                        'spam' => 'Spam',
                        'finance' => 'Finance',
                        'important' => 'Important',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('aiSummary')
                    ->label('AI Summary')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->modalHeading('YG-AI Thread Summary')
                    ->modalContent(fn (Mail $record) => view('filament.components.ai-summary', [
                        'summary' => app(\App\Services\AiService::class)->summarize($record->body)
                    ])),
                Tables\Actions\Action::make('reply')
                    ->icon('heroicon-o-arrow-turn-down-left')
                    ->color('info')
                    ->form([
                        Forms\Components\ViewField::make('smart_replies')
                            ->view('filament.components.smart-replies')
                            ->viewData(fn (Mail $record) => [
                                'replies' => app(\App\Services\AiService::class)->suggestReplies($record->body)
                            ]),
                        Forms\Components\TextInput::make('to')
                            ->required()
                            ->email()
                            ->default(fn (Mail $record) => $record->from),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->default(fn (Mail $record) => str_starts_with($record->subject, 'Re: ') ? $record->subject : 'Re: ' . $record->subject),
                        Forms\Components\RichEditor::make('body')
                            ->required()
                            ->default(fn (Mail $record) => '<br><br>--- On ' . $record->created_at->format('d M Y') . ', ' . $record->from . ' wrote: <br><blockquote>' . $record->body . '</blockquote>'),
                    ])
                    ->action(function (array $data, Mail $record): void {
                        $user = Auth::user();
                        $newMail = Mail::create([
                            'user_id' => $user->id,
                            'from' => $user->email,
                            'to' => $data['to'],
                            'subject' => $data['subject'],
                            'body' => $data['body'],
                            'folder' => 'sent',
                            'read' => true,
                        ]);

                        // Append signature if exists
                        $settings = \App\Models\MailSetting::where('user_id', $user->id)->first();
                        if ($settings && $settings->signature) {
                            $newMail->update(['body' => $newMail->body . '<br><br>' . $settings->signature]);
                        }

                        \App\Jobs\SendEmail::dispatch(
                            to: $data['to'],
                            subject: $data['subject'],
                            body: $data['body'],
                            fromEmail: $user->email,
                            attachments: [],
                            mailRecordId: $newMail->id
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Reply Sent')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('markRead')
                    ->label('Mark Read')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Mail $record) => $record->update(['read' => true]))
                    ->visible(fn (Mail $record) => !$record->read),
                Tables\Actions\Action::make('markUnread')
                    ->label('Mark Unread')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->action(fn (Mail $record) => $record->update(['read' => false]))
                    ->visible(fn (Mail $record) => $record->read),
                Tables\Actions\Action::make('markSpam')
                    ->label('Spam')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->action(fn (Mail $record) => $record->update(['folder' => 'spam']))
                    ->visible(fn (Mail $record) => $record->folder !== 'spam'),
                Tables\Actions\Action::make('notSpam')
                    ->label('Not Spam')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->action(fn (Mail $record) => $record->update(['folder' => 'inbox']))
                    ->visible(fn (Mail $record) => $record->folder === 'spam'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('backup')
                        ->label('Backup to Drive')
                        ->icon('heroicon-o-cloud-arrow-up')
                        ->color('success')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $user = Auth::user();
                            $exportData = [
                                'user' => [
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'exported_at' => now()->toISOString(),
                                ],
                                'emails' => $records->map(fn($mail) => [
                                    'from' => $mail->from,
                                    'to' => $mail->to,
                                    'subject' => $mail->subject,
                                    'body' => $mail->body,
                                    'created_at' => $mail->created_at->toISOString(),
                                ]),
                            ];

                            $filename = 'yg_mail_backup_' . $user->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
                            \Illuminate\Support\Facades\Storage::put('backups/' . $user->id . '/' . $filename, json_encode($exportData, JSON_PRETTY_PRINT));

                            \Filament\Notifications\Notification::make()
                                ->title('Backup Successful')
                                ->body("Backed up {$records->count()} emails to Drive.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => MailResource\Pages\ListMails::route('/'),
            'create' => MailResource\Pages\CreateMail::route('/create'),
            'view' => MailResource\Pages\ViewMail::route('/{record}'),
            'edit' => MailResource\Pages\EditMail::route('/{record}/edit'),
        ];
    }
}
