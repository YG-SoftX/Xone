<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IsolatedPaymentSecurityResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * IsolatedPaymentSecurity Resource
 * 
 * Admin panel interface for managing isolated payment module security settings.
 * All configuration changes are validated and logged.
 */
class IsolatedPaymentSecurityResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Payment Security';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'isolated-payment-security';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Module Status')
                    ->description('Control the isolated payment module status')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('Enable Isolated Payment Module')
                            ->helperText('⚠️ WARNING: This is a temporary solution. Plan migration to official APIs.')
                            ->default(false)
                            ->required(),
                        
                        Forms\Components\Select::make('security_level')
                            ->label('Security Level')
                            ->options([
                                'STRICT' => 'Strict (Maximum Security)',
                                'MODERATE' => 'Moderate (Balanced)',
                                'PERMISSIVE' => 'Permissive (Minimal Restrictions)',
                            ])
                            ->default('STRICT')
                            ->helperText('Higher security levels may block legitimate transactions with suspicious patterns')
                            ->required(),
                        
                        Forms\Components\Toggle::make('emergency_disable')
                            ->label('Emergency Disable (Kill Switch)')
                            ->helperText('Immediately halt all payment processing')
                            ->default(false)
                            ->disabled(fn () => !auth()->user()->hasRole('super_admin')),
                        
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('Maintenance Mode')
                            ->default(false),
                        
                        Forms\Components\Textarea::make('maintenance_message')
                            ->label('Maintenance Message')
                            ->placeholder('Payment processing is temporarily unavailable. Please try again later.')
                            ->maxLength(500)
                            ->visible(fn (callable $get) => $get('maintenance_mode')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Rate Limiting')
                    ->description('Control request rate limits to prevent abuse')
                    ->schema([
                        Forms\Components\TextInput::make('payment_rate_limit')
                            ->label('Max Requests Per Hour')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(1000)
                            ->default(100)
                            ->helperText('Maximum payment requests per IP address per hour')
                            ->required(),
                        
                        Forms\Components\TextInput::make('payment_rate_window')
                            ->label('Rate Limit Window (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1440)
                            ->default(60)
                            ->required(),
                        
                        Forms\Components\TextInput::make('max_failed_attempts')
                            ->label('Max Failed Attempts Before Block')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(50)
                            ->default(10)
                            ->helperText('Number of consecutive failures before temporary IP block')
                            ->required(),
                        
                        Forms\Components\TextInput::make('block_duration_minutes')
                            ->label('Block Duration (minutes)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(1440)
                            ->default(30)
                            ->helperText('How long to block an IP after max failed attempts')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Transaction Limits')
                    ->description('Set minimum and maximum transaction amounts')
                    ->schema([
                        Forms\Components\TextInput::make('max_transaction_amount')
                            ->label('Maximum Transaction Amount')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9999999.99)
                            ->default(999999.99)
                            ->prefix(config('app.currency', 'NPR'))
                            ->required(),
                        
                        Forms\Components\TextInput::make('min_transaction_amount')
                            ->label('Minimum Transaction Amount')
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(1000)
                            ->default(0.01)
                            ->prefix(config('app.currency', 'NPR'))
                            ->required(),
                        
                        Forms\Components\MultiSelect::make('allowed_currencies')
                            ->label('Allowed Currencies')
                            ->options([
                                'NPR' => 'Nepalese Rupee (NPR)',
                                'USD' => 'US Dollar (USD)',
                                'EUR' => 'Euro (EUR)',
                                'INR' => 'Indian Rupee (INR)',
                                'GBP' => 'British Pound (GBP)',
                            ])
                            ->default(['NPR', 'USD'])
                            ->helperText('Select currencies to accept for payments')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Input Validation')
                    ->description('Configure input validation and sanitization')
                    ->schema([
                        Forms\Components\Toggle::make('strict_input_validation')
                            ->label('Strict Input Validation')
                            ->default(true)
                            ->helperText('Reject any input that does not match expected format exactly'),
                        
                        Forms\Components\Toggle::make('sanitize_inputs')
                            ->label('Sanitize All Inputs')
                            ->default(true)
                            ->helperText('Remove potentially dangerous characters from all string inputs'),
                        
                        Forms\Components\Toggle::make('block_sql_injection')
                            ->label('Block SQL Injection Patterns')
                            ->default(true)
                            ->helperText('Detect and block SQL injection attempts'),
                        
                        Forms\Components\Toggle::make('block_xss')
                            ->label('Block XSS Patterns')
                            ->default(true)
                            ->helperText('Detect and block cross-site scripting attempts'),
                        
                        Forms\Components\Toggle::make('block_command_injection')
                            ->label('Block Command Injection')
                            ->default(true)
                            ->helperText('Detect and block OS command injection attempts'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Logging & Monitoring')
                    ->description('Configure security logging and alerting')
                    ->schema([
                        Forms\Components\Toggle::make('enable_security_logging')
                            ->label('Enable Security Logging')
                            ->default(true)
                            ->helperText('Log all security events for audit trail'),
                        
                        Forms\Components\Select::make('log_level')
                            ->label('Log Level')
                            ->options([
                                'debug' => 'Debug (Most Verbose)',
                                'info' => 'Info (Recommended)',
                                'warning' => 'Warning',
                                'error' => 'Error',
                                'critical' => 'Critical Only',
                            ])
                            ->default('info')
                            ->required(),
                        
                        Forms\Components\TextInput::make('log_retention_days')
                            ->label('Log Retention (days)')
                            ->numeric()
                            ->minValue(7)
                            ->maxValue(365)
                            ->default(90)
                            ->helperText('How long to keep security logs')
                            ->required(),
                        
                        Forms\Components\Toggle::make('alert_on_suspicious')
                            ->label('Send Alerts on Suspicious Activity')
                            ->default(true)
                            ->helperText('Email administrators when suspicious activity detected'),
                        
                        Forms\Components\TagsInput::make('alert_emails')
                            ->label('Alert Email Addresses')
                            ->placeholder('admin@ygxone.com')
                            ->helperText('Email addresses to receive security alerts')
                            ->separator(',')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('File Integrity Monitoring')
                    ->description('Monitor isolated module files for unauthorized changes')
                    ->schema([
                        Forms\Components\Toggle::make('enable_integrity_check')
                            ->label('Enable File Integrity Checking')
                            ->default(true)
                            ->helperText('Regularly verify file hashes to detect tampering'),
                        
                        Forms\Components\TextInput::make('integrity_check_interval')
                            ->label('Check Interval (seconds)')
                            ->numeric()
                            ->minValue(300)
                            ->maxValue(86400)
                            ->default(3600)
                            ->helperText('How often to check file integrity (3600 = 1 hour)')
                            ->required(),
                        
                        Forms\Components\Toggle::make('block_on_integrity_violation')
                            ->label('Auto-Block on Integrity Violation')
                            ->default(true)
                            ->helperText('Automatically disable module if file tampering detected'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('SSL/TLS Configuration')
                    ->description('Configure encryption settings')
                    ->schema([
                        Forms\Components\Toggle::make('enforce_https')
                            ->label('Enforce HTTPS')
                            ->default(true)
                            ->helperText('Require HTTPS for all payment requests'),
                        
                        Forms\Components\Toggle::make('verify_ssl')
                            ->label('Verify SSL Certificates')
                            ->default(true)
                            ->helperText('Validate SSL certificates on all connections'),
                        
                        Forms\Components\Select::make('min_tls_version')
                            ->label('Minimum TLS Version')
                            ->options([
                                '1.0' => 'TLS 1.0 (Not Recommended)',
                                '1.1' => 'TLS 1.1 (Not Recommended)',
                                '1.2' => 'TLS 1.2 (Recommended)',
                                '1.3' => 'TLS 1.3 (Most Secure)',
                            ])
                            ->default('1.2')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Advanced Security Features')
                    ->description('Additional security measures')
                    ->schema([
                        Forms\Components\Toggle::make('enable_honeypot')
                            ->label('Enable Honeypot Fields')
                            ->default(true)
                            ->helperText('Add hidden fields to detect bot submissions'),
                        
                        Forms\Components\Toggle::make('enable_captcha')
                            ->label('Enable CAPTCHA for High-Value Transactions')
                            ->default(false)
                            ->helperText('Require CAPTCHA verification for large transactions'),
                        
                        Forms\Components\TextInput::make('captcha_threshold')
                            ->label('CAPTCHA Threshold Amount')
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(1000000)
                            ->default(10000)
                            ->visible(fn (callable $get) => $get('enable_captcha'))
                            ->helperText('Transactions above this amount require CAPTCHA'),
                        
                        Forms\Components\Toggle::make('enable_device_fingerprint')
                            ->label('Enable Device Fingerprinting')
                            ->default(true)
                            ->helperText('Track and identify devices for fraud detection'),
                        
                        Forms\Components\Toggle::make('enable_behavioral_analysis')
                            ->label('Enable Behavioral Analysis')
                            ->default(false)
                            ->helperText('Analyze user behavior patterns for anomalies (resource intensive)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Compliance Settings')
                    ->description('Configure compliance-related settings')
                    ->schema([
                        Forms\Components\Toggle::make('pci_compliance_mode')
                            ->label('PCI DSS Compliance Mode')
                            ->default(false)
                            ->helperText('⚠️ WARNING: Isolated module cannot achieve full PCI compliance'),
                        
                        Forms\Components\TextInput::make('gdpr_retention_days')
                            ->label('GDPR Data Retention (days)')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(730)
                            ->default(365)
                            ->helperText('How long to retain personal data per GDPR requirements'),
                        
                        Forms\Components\Toggle::make('anonymize_old_logs')
                            ->label('Anonymize Logs After Retention Period')
                            ->default(true)
                            ->helperText('Remove personal identifiers from old logs'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('setting_name')
                    ->label('Setting')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('current_value')
                    ->label('Current Value')
                    ->getStateUsing(fn ($record) => self::getCurrentValue($record->setting_name)),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Active',
                        'warning' => 'Warning',
                        'danger' => 'Disabled',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->modalHeading(fn ($record) => $record->setting_name)
                    ->modalContent(fn ($record) => view('filament.pages.isolated-payment-security-details', [
                        'setting' => $record,
                    ])),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIsolatedPaymentSecurity::route('/'),
        ];
    }

    /**
     * Get current value for a setting
     */
    private static function getCurrentValue(string $settingName): string
    {
        return config("isolated_payment_security.{$settingName}", 'Not configured');
    }

    /**
     * Check if user can access this resource
     */
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }
}
