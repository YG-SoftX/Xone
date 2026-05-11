@extends('admin.master-layout')

@section('title', 'Environment Configuration')
@section('page-title', 'System Configuration')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
    @endif
    
    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <form action="{{ route('admin.environment.update') }}" method="POST" class="space-y-6">
        @csrf
        
        <!-- Application Settings -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-cog text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Application Settings</h2>
                    <p class="text-sm text-gray-500">Core application configuration</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">App Name</label>
                    <input type="text" name="app_name" value="{{ $config['app']['APP_NAME'] ?? 'Ygxone' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">App URL</label>
                    <input type="url" name="app_url" value="{{ $config['app']['APP_URL'] ?? 'http://localhost' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Debug Mode</label>
                    <select name="app_debug" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="1" {{ ($config['app']['APP_DEBUG'] ?? 'false') == 'true' ? 'selected' : '' }}>Enabled (Development)</option>
                        <option value="0" {{ ($config['app']['APP_DEBUG'] ?? 'false') == 'false' ? 'selected' : '' }}>Disabled (Production)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Disable in production for security</p>
                </div>
            </div>
        </div>

        <!-- Database Configuration -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-database text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Database Configuration</h2>
                        <p class="text-sm text-gray-500">Database connection settings</p>
                    </div>
                </div>
                <button type="button" onclick="testDatabaseConnection()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <i class="fas fa-plug mr-2"></i>Test Connection
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Database Connection</label>
                    <select name="db_connection" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="sqlite" {{ ($config['database']['DB_CONNECTION'] ?? '') == 'sqlite' ? 'selected' : '' }}>SQLite</option>
                        <option value="mysql" {{ ($config['database']['DB_CONNECTION'] ?? '') == 'mysql' ? 'selected' : '' }}>MySQL</option>
                        <option value="pgsql" {{ ($config['database']['DB_CONNECTION'] ?? '') == 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Database Host</label>
                    <input type="text" name="db_host" value="{{ $config['database']['DB_HOST'] ?? '127.0.0.1' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Database Port</label>
                    <input type="number" name="db_port" value="{{ $config['database']['DB_PORT'] ?? '3306' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                    <input type="text" name="db_database" value="{{ $config['database']['DB_DATABASE'] ?? '' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Database Username</label>
                    <input type="text" name="db_username" value="{{ $config['database']['DB_USERNAME'] ?? '' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Database Password</label>
                    <input type="password" name="db_password" value="{{ $config['database']['DB_PASSWORD'] ?? '' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
        </div>

        <!-- Email Configuration -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-envelope text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Email Configuration</h2>
                        <p class="text-sm text-gray-500">SMTP and notification settings</p>
                    </div>
                </div>
                <button type="button" onclick="openTestEmailModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <i class="fas fa-paper-plane mr-2"></i>Send Test Email
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mail Driver</label>
                    <select name="mail_mailer" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="smtp" {{ ($config['mail']['MAIL_MAILER'] ?? '') == 'smtp' ? 'selected' : '' }}>SMTP</option>
                        <option value="mailgun" {{ ($config['mail']['MAIL_MAILER'] ?? '') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                        <option value="ses" {{ ($config['mail']['MAIL_MAILER'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                        <option value="postmark" {{ ($config['mail']['MAIL_MAILER'] ?? '') == 'postmark' ? 'selected' : '' }}>Postmark</option>
                        <option value="log" {{ ($config['mail']['MAIL_MAILER'] ?? '') == 'log' ? 'selected' : '' }}>Log Only</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host</label>
                    <input type="text" name="mail_host" value="{{ $config['mail']['MAIL_HOST'] ?? '' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                    <input type="number" name="mail_port" value="{{ $config['mail']['MAIL_PORT'] ?? '587' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
                    <input type="text" name="mail_username" value="{{ $config['mail']['MAIL_USERNAME'] ?? '' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
                    <input type="password" name="mail_password" value="{{ $config['mail']['MAIL_PASSWORD'] ?? '' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Address</label>
                    <input type="email" name="mail_from_address" value="{{ $config['mail']['MAIL_FROM_ADDRESS'] ?? '' }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                </div>
            </div>
        </div>

        <!-- Queue Configuration -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tasks text-yellow-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Queue Configuration</h2>
                    <p class="text-sm text-gray-500">Background job processing</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Queue Connection</label>
                    <select name="queue_connection" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500">
                        <option value="database" {{ ($config['queue']['QUEUE_CONNECTION'] ?? '') == 'database' ? 'selected' : '' }}>Database</option>
                        <option value="redis" {{ ($config['queue']['QUEUE_CONNECTION'] ?? '') == 'redis' ? 'selected' : '' }}>Redis</option>
                        <option value="sync" {{ ($config['queue']['QUEUE_CONNECTION'] ?? '') == 'sync' ? 'selected' : '' }}>Sync (Development)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Use Redis for production, Database for simplicity</p>
                </div>
            </div>
        </div>

        <!-- Payment Gateway Configuration -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-credit-card text-indigo-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Payment Gateways</h2>
                    <p class="text-sm text-gray-500">Configure payment processors</p>
                </div>
            </div>
            
            <div class="space-y-6">
                <!-- Stripe -->
                <div class="border rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-3"><i class="fab fa-stripe text-indigo-600 mr-2"></i>Stripe</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Publishable Key</label>
                            <input type="text" name="stripe_key" value="{{ $config['payment']['STRIPE_KEY'] ?? '' }}" 
                                   placeholder="pk_live_xxxxx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                            <input type="password" name="stripe_secret" value="{{ $config['payment']['STRIPE_SECRET'] ?? '' }}" 
                                   placeholder="sk_live_xxxxx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
                
                <!-- PayPal -->
                <div class="border rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-3"><i class="fab fa-paypal text-blue-600 mr-2"></i>PayPal</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client ID</label>
                            <input type="text" name="paypal_client_id" value="{{ $config['payment']['PAYPAL_CLIENT_ID'] ?? '' }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Secret</label>
                            <input type="password" name="paypal_secret" value="{{ $config['payment']['PAYPAL_SECRET'] ?? '' }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                
                <!-- Razorpay -->
                <div class="border rounded-lg p-4">
                    <h3 class="font-medium text-gray-800 mb-3"><i class="fas fa-rupee-sign text-orange-600 mr-2"></i>Razorpay</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Key ID</label>
                            <input type="text" name="razorpay_key" value="{{ $config['payment']['RAZORPAY_KEY'] ?? '' }}" 
                                   placeholder="rzp_live_xxxxx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Secret</label>
                            <input type="password" name="razorpay_secret" value="{{ $config['payment']['RAZORPAY_SECRET'] ?? '' }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                        </div>
                    </div>
                </div>
                
                <!-- YG Pay (Primary) -->
                <div class="border-2 border-blue-500 rounded-lg p-4 bg-blue-50">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-medium text-gray-800">
                            <i class="fas fa-wallet text-blue-600 mr-2"></i>YG Pay (Primary Payment Service)
                            <span class="ml-2 px-2 py-1 bg-blue-600 text-white text-xs rounded">RECOMMENDED</span>
                        </h3>
                        <div class="flex items-center">
                            <input type="checkbox" name="payment_ygpay_enabled" id="payment_ygpay_enabled" value="1" 
                                   {{ ($config['ecosystem']['PAYMENT_YGPAY_ENABLED'] ?? 'true') == 'true' ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <label for="payment_ygpay_enabled" class="ml-2 text-sm font-medium text-gray-700">Enable YG Pay</label>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                        YG Pay is the unified payment service for the entire YG Ecosystem. All payment gateways (Stripe, PayPal, etc.) are managed through YG Pay.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                API Key
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="ygpay_api_key" 
                                   value="{{ $config['ecosystem']['YGPAY_API_KEY'] ?? '' }}" 
                                   placeholder="Enter API key from YG Pay Admin" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">
                                Get this from YG Pay Admin → Developer Portal → API Keys
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">API URL</label>
                            <input type="url" name="ygpay_api_url" 
                                   value="{{ $config['ecosystem']['YGPAY_API_URL'] ?? 'https://pay.ygxone.com/api/v1' }}" 
                                   placeholder="https://pay.ygxone.com/api/v1" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">
                                Default: https://pay.ygxone.com/api/v1
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-white rounded border border-blue-200">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-lightbulb text-yellow-500 mt-1"></i>
                            <div class="text-sm text-gray-700">
                                <strong>Benefits of YG Pay:</strong>
                                <ul class="list-disc list-inside mt-1 space-y-1 text-gray-600">
                                    <li>Single integration point for all payment gateways</li>
                                    <li>Automatic gateway selection based on region/currency</li>
                                    <li>Centralized PCI DSS compliance</li>
                                    <li>Unified analytics and reporting</li>
                                    <li>Simplified maintenance (no SDK updates needed)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- YG AI Configuration -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-robot text-white text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">YG AI Service</h2>
                    <p class="text-sm text-gray-500">Artificial Intelligence integration settings</p>
                </div>
            </div>
            
            <div class="space-y-6">
                <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg border border-purple-200">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-purple-600 mt-1"></i>
                        <div class="text-sm text-gray-700">
                            <strong>YG AI Features:</strong>
                            <ul class="list-disc list-inside mt-2 space-y-1 text-gray-600">
                                <li>Smart email reply suggestions</li>
                                <li>Automatic email categorization (Primary/Social/Promotions)</li>
                                <li>Document writing assistance</li>
                                <li>Natural language calendar events</li>
                                <li>Contact enrichment</li>
                                <li>Text summarization & sentiment analysis</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            AI Service URL
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="url" name="yg_ai_url" 
                               value="{{ $config['ecosystem']['YG_AI_URL'] ?? 'https://ai.ygxone.com' }}" 
                               placeholder="https://ai.ygxone.com" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">
                            Default: https://ai.ygxone.com
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            API Key (Optional)
                        </label>
                        <input type="password" name="yg_ai_api_key" 
                               value="{{ $config['ecosystem']['YG_AI_API_KEY'] ?? '' }}" 
                               placeholder="Leave blank if not required" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">
                            Required only if your AI service uses authentication
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Request Timeout (seconds)</label>
                        <input type="number" name="yg_ai_timeout" 
                               value="{{ $config['ecosystem']['YG_AI_TIMEOUT'] ?? '5' }}" 
                               min="1" max="30"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <p class="text-xs text-gray-500 mt-1">
                            Recommended: 5 seconds (max 30)
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <div class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <i class="fas fa-check-circle text-green-600"></i>
                            <span class="text-sm text-green-700 font-medium">Service Active</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            AI features will be available across all YG services
                        </p>
                    </div>
                </div>
                
                <div class="border-t pt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Integration Status</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="p-3 bg-gray-50 rounded-lg text-center">
                            <i class="fas fa-envelope text-blue-500 mb-1"></i>
                            <p class="text-xs font-medium text-gray-700">YG Mail</p>
                            <span class="text-[10px] text-gray-500">Smart Reply</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg text-center">
                            <i class="fas fa-file-alt text-green-500 mb-1"></i>
                            <p class="text-xs font-medium text-gray-700">YG DocX</p>
                            <span class="text-[10px] text-gray-500">Writing Help</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg text-center">
                            <i class="fas fa-calendar text-orange-500 mb-1"></i>
                            <p class="text-xs font-medium text-gray-700">Calendar</p>
                            <span class="text-[10px] text-gray-500">NLP Events</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg text-center">
                            <i class="fas fa-address-book text-purple-500 mb-1"></i>
                            <p class="text-xs font-medium text-gray-700">Contacts</p>
                            <span class="text-[10px] text-gray-500">Enrichment</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-4">
            <button type="submit" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                <i class="fas fa-save mr-2"></i>Save Configuration
            </button>
            <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>

<!-- Test Email Modal -->
<div id="testEmailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl max-w-md w-full mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Send Test Email</h3>
            <button onclick="closeTestEmailModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Recipient Email</label>
                <input type="email" id="test_email" placeholder="admin@example.com" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeTestEmailModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" onclick="sendTestEmail()" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Send Test
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function testDatabaseConnection() {
    fetch('{{ route("admin.environment.test-database") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}

function openTestEmailModal() {
    document.getElementById('testEmailModal').classList.remove('hidden');
}

function closeTestEmailModal() {
    document.getElementById('testEmailModal').classList.add('hidden');
}

function sendTestEmail() {
    const testEmail = document.getElementById('test_email').value;
    
    if (!testEmail) {
        alert('Please enter a recipient email address');
        return;
    }
    
    fetch('{{ route("admin.environment.test-email") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ test_email: testEmail })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        closeTestEmailModal();
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
}
</script>
@endsection
