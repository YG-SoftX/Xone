<?php

namespace App\Filament\Pages;

use App\Models\MailSetting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MailSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.mail-settings';

    protected static ?string $navigationGroup = 'Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = MailSetting::firstOrCreate(['user_id' => Auth::id()]);
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Email Signature')
                    ->description('This signature will be automatically appended to your outgoing emails.')
                    ->schema([
                        RichEditor::make('signature')
                            ->label('Your Signature')
                            ->placeholder('e.g. Regards, John Doe'),
                    ]),

                Section::make('Vacation Responder')
                    ->description('Automatically reply to incoming emails while you are away.')
                    ->schema([
                        Toggle::make('vacation_mode')
                            ->label('Enable Vacation Responder')
                            ->live(),
                        Textarea::make('vacation_message')
                            ->label('Auto-Reply Message')
                            ->visible(fn ($get) => $get('vacation_mode')),
                        DatePicker::make('vacation_start')
                            ->label('Start Date')
                            ->visible(fn ($get) => $get('vacation_mode')),
                        DatePicker::make('vacation_end')
                            ->label('End Date')
                            ->visible(fn ($get) => $get('vacation_mode')),
                    ]),

                Section::make('UI Customization')
                    ->description('Personalize the look and feel of your dashboard.')
                    ->schema([
                        Forms\Components\TextInput::make('brand_name')
                            ->label('Dashboard Title')
                            ->placeholder('e.g. My Workspace'),
                        Forms\Components\ColorPicker::make('primary_color')
                            ->label('Primary Brand Color')
                            ->default('#9B1B30'),
                        Forms\Components\Select::make('sidebar_type')
                            ->label('Sidebar Aesthetic')
                            ->options([
                                'glass' => 'Modern Glass (Transparent)',
                                'solid' => 'Clean Solid',
                                'gradient' => 'High-End Gradient',
                            ])
                            ->default('glass'),
                    ])->columns(3),

                Section::make('Public Landing Page')
                    ->description('Customize the initial screen global users see before logging in.')
                    ->schema([
                        Toggle::make('show_landing_page')
                            ->label('Enable Landing Page')
                            ->helperText('If disabled, users will be redirected straight to the Login/SSO page.')
                            ->live(),
                        Forms\Components\TextInput::make('hero_title')
                            ->label('Hero Title')
                            ->visible(fn ($get) => $get('show_landing_page')),
                        Textarea::make('hero_subtitle')
                            ->label('Hero Subtitle')
                            ->visible(fn ($get) => $get('show_landing_page')),
                        Forms\Components\TextInput::make('cta_text')
                            ->label('CTA Button Text')
                            ->visible(fn ($get) => $get('show_landing_page')),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = MailSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            $data
        );

        Notification::make()
            ->title('Settings Saved')
            ->success()
            ->send();
    }
}
