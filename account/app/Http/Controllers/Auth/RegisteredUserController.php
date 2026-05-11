<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SsoController;
use App\Models\Organization;
use App\Models\DeviceActivityLog;
use App\Models\User;
use App\Services\DeviceFingerprintService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display account type selection page
     */
    public function chooseAccountType()
    {
        return view('auth.choose-account-type');
    }

    /**
     * Check if email is available (AJAX)
     */
    public function checkEmail(Request $request)
    {
        $email = $request->input('email');
        $exists = User::where('email', $email)->exists();

        if ($exists) {
            // Generate some Google-style suggestions
            $base = explode('@', $email)[0];
            $suggestions = [
                $base . rand(10, 99) . '@' . config('app.domain', 'ygxone.com'),
                $base . '.' . date('Y') . '@' . config('app.domain', 'ygxone.com'),
                substr($base, 0, 1) . strrev($base) . '@' . config('app.domain', 'ygxone.com'),
            ];

            return response()->json([
                'available' => false,
                'suggestions' => $suggestions
            ]);
        }

        return response()->json(['available' => true]);
    }

    /**
     * Display individual registration form
     */
    public function showIndividualRegistration()
    {
        return view('auth.register-individual');
    }

    /**
     * Display business registration form
     */
    public function showBusinessRegistration()
    {
        if (!\App\Models\PlatformFeature::isEnabled('business_registration')) {
            return redirect()->route('register.individual')
                ->with('error', 'Business registration is currently closed. You can create an individual account instead.');
        }

        return view('auth.register-business', [
            'businessTypes' => $this->getBusinessTypes(),
            'industries' => $this->getIndustries(),
            'plans' => $this->getSubscriptionPlans(),
        ]);
    }

    /**
     * Handle individual user registration
     */
    public function registerIndividual(Request $request): RedirectResponse
    {
        // Build full email from the username field + @ygxone.com
        $username  = strtolower(trim($request->input('email_username', '')));
        $fullEmail = $username . '@ygxone.com';
        $request->merge(['email' => $fullEmail]);

        // Validate input
        $validator = Validator::make($request->all(), [
            'full_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:8'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:' . now()->subYears(13)->toDateString()],
            'country'       => ['nullable', 'string', 'max:2'],
            'referral_code' => ['nullable', 'string', 'max:50'],
        ], [
            'date_of_birth.before' => 'You must be at least 13 years old.',
            'email.unique'         => 'That username is already taken. Please choose a different one.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Collect device fingerprint
        $deviceFingerprint = app(DeviceFingerprintService::class)->capture($request);

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'account_type'       => 'individual',
                'name'               => $request->full_name,
                'email'              => $fullEmail,
                'phone'              => $request->phone,
                'password'           => Hash::make($request->password),
                'birthday'           => $request->date_of_birth,
                'country'            => $request->input('country', null),
                'language'           => $request->input('language', 'en'),
                'timezone'           => $request->input('timezone', config('app.timezone')),
                'status'             => 'active',
                'device_fingerprint' => $deviceFingerprint,
                'last_login_ip'      => $request->ip(),
                'refferal_user_id'   => $request->referral_code,
            ]);

            // Auto-create wallet
            \App\Models\PayWallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0.00, 'currency' => 'USD']
            );

            // Log device activity
            DeviceActivityLog::create([
                'user_id' => $user->id,
                'activity_type' => 'registration',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_fingerprint' => json_encode($deviceFingerprint),
                'occurred_at' => now(),
            ]);

            DB::commit();

            // Send verification email
            event(new Registered($user));

            // Log activity
            \App\Models\ActivityLog::record(
                $user->id,
                'Created individual account',
                'Register',
                '👤',
                $request->ip()
            );

            Auth::login($user);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Registration failed: ' . $e->getMessage()])
                ->withInput();
        }

        // SSO: if the user came from another YG app (e.g. mail, drive),
        // redirect back with an SSO token instead of going to the dashboard.
        if ($callback = session('sso_callback')) {
            session()->forget(['sso_callback', 'sso_service']);
            return app(SsoController::class)->issueTokenAndRedirect($user, $callback);
        }

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Account created! Welcome to your new YG Account.');
    }

    /**
     * Handle business registration
     */
    public function registerBusiness(Request $request): RedirectResponse
    {
        if (!\App\Models\PlatformFeature::isEnabled('business_registration')) {
            return redirect()->back()->with('error', 'Business registration is currently disabled.');
        }

        // Build full email if it's a new ygxone.com address
        $email = $request->input('email');
        if ($request->input('email_type') === 'new' && !str_contains($email, '@')) {
            $email = strtolower(trim($email)) . '@ygxone.com';
        }

        // Validate basic information
        $validator = Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'max:255'],
            'industry'     => ['required', 'string'],
            'email'        => ['required', 'string', 'max:255'], // We check uniqueness manually below
            'password'     => ['required', 'string', 'min:8'],
            'phone'        => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check email uniqueness
        if (User::where('email', $email)->exists()) {
            return redirect()->back()
                ->withErrors(['email' => 'This email address is already registered.'])
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Collect device fingerprint
            $deviceFingerprint = app(DeviceFingerprintService::class)->capture($request);

            // Create admin user
            $adminUser = User::create([
                'account_type'       => 'business_admin',
                'name'               => $request->company_name . ' Admin', // Placeholder
                'email'              => $email,
                'phone'              => $request->phone,
                'password'           => Hash::make($request->password),
                'country'            => $request->input('country', null),
                'language'           => $request->input('language', 'en'),
                'timezone'           => $request->input('timezone', config('app.timezone')),
                'status'             => 'active',
                'device_fingerprint' => $deviceFingerprint,
                'last_login_ip'      => $request->ip(),
            ]);

            // Create organization record with defaults
            $organization = Organization::create([
                'name'                     => $request->company_name,
                'owner_id'                 => $adminUser->id,
                'status'                   => 'active',
            ]);

            // Link user to organization
            $adminUser->update(['organization_id' => $organization->id]);

            // Auto-create wallet
            \App\Models\PayWallet::firstOrCreate(
                ['user_id' => $adminUser->id],
                ['balance' => 0.00, 'currency' => 'USD']
            );

            DB::commit();

            Auth::login($adminUser);

            // SSO: Handle redirect
            if ($callback = session('sso_callback')) {
                session()->forget(['sso_callback', 'sso_service']);
                return app(SsoController::class)->issueTokenAndRedirect($adminUser, $callback);
            }

            return redirect()->route('dashboard') // Redirect to dashboard since onboarding route might be missing
                ->with('success', 'Organization account created! Welcome to your new workspace.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Registration failed: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Verify business domain (DNS check)
     */
    public function verifyBusinessDomain(Request $request, Business $business)
    {
        $this->authorize('manage', $business);

        $verificationCode = $business->generateVerificationCode();
        
        // Check DNS TXT record
        $dnsRecords = dns_get_record($business->custom_domain, DNS_TXT);
        
        $verified = false;
        foreach ($dnsRecords as $record) {
            if (isset($record['txt']) && strpos($record['txt'], "ygxone-verification={$verificationCode}") !== false) {
                $verified = true;
                break;
            }
        }

        if ($verified) {
            $business->update([
                'domain_verified' => true,
                'verified_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Domain verified successfully!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Verification record not found. Please add the TXT record and try again.',
        ], 422);
    }

    /**
     * Helper methods
     */
    private function getBusinessTypes(): array
    {
        return [
            'startup' => 'Startup',
            'sme' => 'Small/Medium Enterprise',
            'enterprise' => 'Large Enterprise',
            'nonprofit' => 'Non-Profit Organization',
            'government' => 'Government Agency',
            'education' => 'Educational Institution',
            'freelancer' => 'Freelancer/Solo Entrepreneur',
        ];
    }

    private function getIndustries(): array
    {
        return [
            'technology' => 'Technology',
            'finance' => 'Finance & Banking',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'retail' => 'Retail & E-commerce',
            'manufacturing' => 'Manufacturing',
            'media' => 'Media & Entertainment',
            'consulting' => 'Consulting',
            'real_estate' => 'Real Estate',
            'other' => 'Other',
        ];
    }

    private function getSubscriptionPlans(): array
    {
        return [
            'starter' => [
                'name' => 'Starter',
                'price' => 9.99,
                'max_users' => 10,
                'storage_gb' => 100,
                'features' => ['Email', 'Drive', 'Chat', 'Meet (up to 10 participants)'],
            ],
            'business' => [
                'name' => 'Business',
                'price' => 29.99,
                'max_users' => 50,
                'storage_gb' => 1000,
                'features' => ['All Starter features', 'Advanced Meet (up to 100)', 'Admin Panel', 'Priority Support'],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 99.99,
                'max_users' => -1, // unlimited
                'storage_gb' => 10000,
                'features' => ['All Business features', 'Unlimited Meet', 'Custom Branding', 'SLA', 'Dedicated Support'],
            ],
        ];
    }

    private function getPlanMaxUsers(string $plan): int
    {
        return match($plan) {
            'starter' => 10,
            'business' => 50,
            'enterprise' => -1, // unlimited
            default => 10,
        };
    }

    private function getPlanStorageQuota(string $plan): int
    {
        return match($plan) {
            'starter' => 100,
            'business' => 1000,
            'enterprise' => 10000,
            default => 100,
        };
    }

    private function getDefaultFeaturesForPlan(string $plan): array
    {
        $baseFeatures = ['mail', 'drive', 'chat', 'meet'];
        
        if ($plan === 'business' || $plan === 'enterprise') {
            $baseFeatures[] = 'admin_panel';
            $baseFeatures[] = 'analytics';
            $baseFeatures[] = 'priority_support';
        }
        
        if ($plan === 'enterprise') {
            $baseFeatures[] = 'custom_branding';
            $baseFeatures[] = 'sso_integration';
            $baseFeatures[] = 'api_access';
            $baseFeatures[] = 'audit_logs';
        }
        
        return $baseFeatures;
    }
}
