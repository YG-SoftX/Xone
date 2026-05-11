<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FALaravel\Google2FA;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'two_factor_code' => ['nullable', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->input('email');
        
        // If it doesn't look like an email (no @), append @ygxone.com
        if (!str_contains($login, '@')) {
            $login = strtolower(trim($login)) . '@ygxone.com';
        }

        if (!Auth::attempt(['email' => $login, 'password' => $this->password], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Check if user has 2FA enabled
        $user = Auth::user();
        if ($user && $user->two_factor_enabled) {
            // If no 2FA code provided, redirect to 2FA verification
            if (!$this->filled('two_factor_code')) {
                Auth::logout();

                Session::put('login_user_id', $user->id);

                throw ValidationException::withMessages([
                    'two_factor_code' => 'Two-factor authentication required.',
                ])->redirectTo(route('2fa'));
            }

            // Validate the 2FA code
            $google2fa = new Google2FA();
            $valid = $google2fa->verifyKey($user->google2fa_secret, $this->two_factor_code);

            if (!$valid) {
                // Check recovery codes
                if (!$user->recovery_codes) {
                    Auth::logout();
                    throw ValidationException::withMessages([
                        'two_factor_code' => 'Invalid verification code.',
                    ]);
                }

                $recoveryCodes = json_decode(decrypt($user->recovery_codes), true);
                $codeIndex = array_search($this->two_factor_code, $recoveryCodes);

                if ($codeIndex === false) {
                    Auth::logout();
                    throw ValidationException::withMessages([
                        'two_factor_code' => 'Invalid verification code.',
                    ]);
                }

                // Remove used recovery code
                unset($recoveryCodes[$codeIndex]);
                $user->recovery_codes = encrypt(array_values($recoveryCodes));
                $user->save();
            }
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
