<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class EnvironmentConfigController extends Controller
{
    /**
     * Display environment configuration page
     */
    public function index()
    {
        $envPath = base_path('.env');
        $envContent = File::exists($envPath) ? File::get($envPath) : '';
        
        // Parse .env file into sections
        $config = $this->parseEnvFile($envContent);
        
        return view('admin.environment-config.index', compact('config'));
    }
    
    /**
     * Update environment configuration
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'app_debug' => 'required|boolean',
            'db_connection' => 'required|string',
            'db_host' => 'nullable|string',
            'db_port' => 'nullable|integer',
            'db_database' => 'nullable|string',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
            'mail_mailer' => 'required|string',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|integer',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_from_address' => 'nullable|email',
            'queue_connection' => 'required|string',
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'paypal_client_id' => 'nullable|string',
            'paypal_secret' => 'nullable|string',
            'razorpay_key' => 'nullable|string',
            'razorpay_secret' => 'nullable|string',
            // YG Pay Configuration
            'ygpay_api_key' => 'nullable|string',
            'ygpay_api_url' => 'nullable|url',
            'payment_ygpay_enabled' => 'nullable|boolean',
            // YG AI Configuration
            'yg_ai_url' => 'nullable|url',
            'yg_ai_api_key' => 'nullable|string',
            'yg_ai_timeout' => 'nullable|integer|min:1|max:30',
        ]);
        
        try {
            $envPath = base_path('.env');
            $envContent = File::get($envPath);
            
            // Update values in .env file
            $updates = [
                'APP_NAME' => $validated['app_name'],
                'APP_URL' => $validated['app_url'],
                'APP_DEBUG' => $validated['app_debug'] ? 'true' : 'false',
                'DB_CONNECTION' => $validated['db_connection'],
                'DB_HOST' => $validated['db_host'] ?? '127.0.0.1',
                'DB_PORT' => $validated['db_port'] ?? 3306,
                'DB_DATABASE' => $validated['db_database'] ?? '',
                'DB_USERNAME' => $validated['db_username'] ?? '',
                'DB_PASSWORD' => $validated['db_password'] ?? '',
                'MAIL_MAILER' => $validated['mail_mailer'],
                'MAIL_HOST' => $validated['mail_host'] ?? '',
                'MAIL_PORT' => $validated['mail_port'] ?? 587,
                'MAIL_USERNAME' => $validated['mail_username'] ?? '',
                'MAIL_PASSWORD' => $validated['mail_password'] ?? '',
                'MAIL_FROM_ADDRESS' => $validated['mail_from_address'] ?? '',
                'QUEUE_CONNECTION' => $validated['queue_connection'],
                'STRIPE_KEY' => $validated['stripe_key'] ?? '',
                'STRIPE_SECRET' => $validated['stripe_secret'] ?? '',
                'PAYPAL_CLIENT_ID' => $validated['paypal_client_id'] ?? '',
                'PAYPAL_SECRET' => $validated['paypal_secret'] ?? '',
                'RAZORPAY_KEY' => $validated['razorpay_key'] ?? '',
                'RAZORPAY_SECRET' => $validated['razorpay_secret'] ?? '',
                // YG Pay Configuration
                'YGPAY_API_KEY' => $validated['ygpay_api_key'] ?? '',
                'YGPAY_API_URL' => $validated['ygpay_api_url'] ?? 'https://pay.ygxone.com/api/v1',
                'PAYMENT_YGPAY_ENABLED' => ($validated['payment_ygpay_enabled'] ?? false) ? 'true' : 'false',
                // YG AI Configuration
                'YG_AI_URL' => $validated['yg_ai_url'] ?? 'https://ai.ygxone.com',
                'YG_AI_API_KEY' => $validated['yg_ai_api_key'] ?? '',
                'YG_AI_TIMEOUT' => $validated['yg_ai_timeout'] ?? 5,
            ];
            
            foreach ($updates as $key => $value) {
                $pattern = "/^{$key}=.*/m";
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$value}";
                }
            }
            
            File::put($envPath, $envContent);
            
            // Clear config cache
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            
            return redirect()->back()->with('success', 'Environment configuration updated successfully! Cache cleared.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update configuration: ' . $e->getMessage());
        }
    }
    
    /**
     * Test database connection
     */
    public function testDatabaseConnection()
    {
        try {
            \DB::connection()->getPdo();
            return response()->json(['status' => 'success', 'message' => 'Database connection successful!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration(Request $request)
    {
        $validated = $request->validate([
            'test_email' => 'required|email',
        ]);
        
        try {
            \Mail::raw('This is a test email from YG Account Admin Panel.', function ($message) use ($validated) {
                $message->to($validated['test_email'])
                        ->subject('Test Email - YG Account')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            return response()->json(['status' => 'success', 'message' => 'Test email sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Parse .env file into structured sections
     */
    protected function parseEnvFile($content)
    {
        $lines = explode("\n", $content);
        $sections = [
            'app' => [],
            'database' => [],
            'mail' => [],
            'queue' => [],
            'payment' => [],
            'ecosystem' => [],
        ];
        
        $currentSection = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Detect section headers
            if (strpos($line, '# ===') !== false || strpos($line, '# ---') !== false) {
                if (stripos($line, 'application') !== false || stripos($line, 'app') !== false) {
                    $currentSection = 'app';
                } elseif (stripos($line, 'database') !== false) {
                    $currentSection = 'database';
                } elseif (stripos($line, 'mail') !== false || stripos($line, 'email') !== false) {
                    $currentSection = 'mail';
                } elseif (stripos($line, 'queue') !== false) {
                    $currentSection = 'queue';
                } elseif (stripos($line, 'payment') !== false || stripos($line, 'stripe') !== false || stripos($line, 'paypal') !== false) {
                    $currentSection = 'payment';
                } elseif (stripos($line, 'ecosystem') !== false || stripos($line, 'yg_') !== false) {
                    $currentSection = 'ecosystem';
                }
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false && $currentSection) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                $sections[$currentSection][$key] = $value;
            }
        }
        
        return $sections;
    }
}
