<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('subject')
                    ->required()
                    ->disabled()
                    ->maxLength(255),
                Select::make('category')
                    ->options([
                        'billing' => 'Billing',
                        'technical' => 'Technical',
                        'account' => 'Account',
                        'feature' => 'Feature Request',
                        'other' => 'Other',
                    ])
                    ->disabled(),
                Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->required(),
                Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'pending' => 'Pending',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ])
                    ->required(),
                Textarea::make('_reply_message')
                    ->label('Reply as Support Agent')
                    ->helperText('Type your reply here. It will appear as an agent response in the ticket thread.')
                    ->rows(4),
            ]);
    }
}
