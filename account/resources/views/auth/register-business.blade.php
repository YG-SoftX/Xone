@extends('layouts.app')
@section('title', 'Register Organization')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/zxcvbn@4.4.2/dist/zxcvbn.js"></script>
<style>
    .iti { width: 100%; display: block; }
    .iti__country-list { z-index: 50; }
</style>
@endpush

@section('content')
<div class="google-card" x-data="{ 
    step: 1, 
    totalSteps: 4,
    companyName: '',
    industry: '',
    emailType: 'new',
    email: '',
    emailStatus: 'idle',
    phone: '',
    password: '',
    passwordScore: 0,
    iti: null,
    
    init() {
        this.iti = window.intlTelInput(this.$refs.phoneInput, {
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/utils.js',
            separateDialCode: true,
            initialCountry: 'auto',
            geoIpLookup: function(callback) {
                fetch('https://ipapi.co/json').then(res => res.json()).then(data => callback(data.country_code)).catch(() => callback('us'));
            }
        });
    },

    async checkEmail() {
        if (this.email.length < 3 || this.emailType === 'existing') { this.emailStatus = 'idle'; return; }
        this.emailStatus = 'checking';
        try {
            let res = await fetch('{{ route('register.check-email') }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ email: this.email + '@ygxone.com' })
            });
            let data = await res.json();
            this.emailStatus = data.available ? 'available' : 'taken';
        } catch (e) { this.emailStatus = 'idle'; }
    },
    
    updateStrength() {
        if (!this.password) { this.passwordScore = 0; return; }
        this.passwordScore = zxcvbn(this.password).score;
    },

    next() { if(this.step < this.totalSteps) this.step++ },
    back() { if(this.step > 1) this.step-- }
}">
    <!-- Header -->
    <div class="text-center mb-8">
        <div class="logo-text mb-2">YG<span>ONE</span></div>
        <h1 class="text-[24px] font-normal text-[#202124] mb-1">Register Organization</h1>
        <p class="text-[14px] text-google-gray" x-text="'Phase ' + step + ' of ' + totalSteps"></p>

        @if ($errors->any())
            <div class="mt-4 p-3 rounded-lg bg-[#fce8e6] text-[#d93025] text-[13px] flex items-start text-left">
                <i class="fas fa-exclamation-circle mt-0.5 mr-3"></i>
                <div>
                    <ul class="list-none p-0 m-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('register.business') }}" class="space-y-1">
        @csrf
        
        <!-- Step 1: Organization Details -->
        <div x-show="step === 1" x-transition.opacity>
            <div class="google-input-wrapper">
                <input type="text" name="company_name" x-model="companyName" id="company" placeholder=" " class="google-input" required autofocus>
                <label for="company" class="google-label">Company Name</label>
            </div>
            <div class="google-input-wrapper mb-6">
                <select name="industry" x-model="industry" id="industry" required class="google-input google-select">
                    <option value="" disabled selected></option>
                    <optgroup label="Technology & Media">
                        <option value="software">Software & SaaS</option>
                        <option value="ecommerce">E-commerce</option>
                        <option value="fintech">Fintech</option>
                        <option value="media">Media & Entertainment</option>
                        <option value="telecom">Telecommunications</option>
                    </optgroup>
                    <optgroup label="Professional Services">
                        <option value="legal">Legal Services</option>
                        <option value="accounting">Accounting & Tax</option>
                        <option value="consulting">Management Consulting</option>
                        <option value="marketing">Marketing & Advertising</option>
                        <option value="real_estate">Real Estate</option>
                    </optgroup>
                    <optgroup label="Healthcare & Science">
                        <option value="healthcare">Healthcare Providers</option>
                        <option value="pharma">Pharmaceuticals</option>
                        <option value="biotech">Biotechnology</option>
                    </optgroup>
                    <optgroup label="Industrial & Retail">
                        <option value="manufacturing">Manufacturing</option>
                        <option value="logistics">Logistics & Supply Chain</option>
                        <option value="retail">Retail & Consumer Goods</option>
                        <option value="construction">Construction</option>
                        <option value="energy">Energy & Utilities</option>
                    </optgroup>
                    <option value="other">Other Industry</option>
                </select>
                <label for="industry" class="google-label">Industry Sector</label>
            </div>
            <div class="google-input-wrapper mb-10">
                <input type="tel" name="phone" x-ref="phoneInput" id="phone" class="google-input !pl-16" required>
                <label for="phone" class="google-label !left-16">Business Phone</label>
            </div>
        </div>

        <!-- Step 2: Enterprise Email -->
        <div x-show="step === 2" x-cloak x-transition.opacity>
            <div class="mb-6">
                <label class="block text-sm font-medium text-google-gray mb-4">Choose how you'll sign in</label>
                <div class="space-y-3">
                    <label class="flex items-center p-3 border border-google-border rounded-lg cursor-pointer hover:bg-gray-50 transition" :class="{'border-google-blue bg-blue-50/30': emailType === 'new'}">
                        <input type="radio" name="email_type" value="new" x-model="emailType" class="w-4 h-4 text-google-blue border-google-border focus:ring-google-blue">
                        <span class="ml-3 text-sm text-[#202124]">Get a new @ygxone.com address</span>
                    </label>
                    <label class="flex items-center p-3 border border-google-border rounded-lg cursor-pointer hover:bg-gray-50 transition" :class="{'border-google-blue bg-blue-50/30': emailType === 'existing'}">
                        <input type="radio" name="email_type" value="existing" x-model="emailType" class="w-4 h-4 text-google-blue border-google-border focus:ring-google-blue">
                        <span class="ml-3 text-sm text-[#202124]">Use your existing email</span>
                    </label>
                </div>
            </div>

            <div class="google-input-wrapper relative">
                <input type="text" name="email" x-model="email" id="email" @input.debounce.500ms="checkEmail()" placeholder=" " class="google-input" :class="emailType === 'new' ? 'pr-32' : ''" required>
                <label for="email" class="google-label" x-text="emailType === 'new' ? 'Enter username' : 'Enter your email'"></label>
                <span x-show="emailType === 'new'" class="absolute right-4 top-4 text-google-gray font-medium">@ygxone.com</span>
            </div>
            <div class="mt-[-16px] mb-10 min-h-[20px] text-[12px]">
                <template x-if="emailType === 'new'">
                    <div>
                        <span x-show="emailStatus === 'checking'" class="text-google-blue animate-pulse">Checking availability...</span>
                        <span x-show="emailStatus === 'available'" class="text-google-green">Address available!</span>
                        <span x-show="emailStatus === 'taken'" class="text-google-red">Address taken.</span>
                        <span x-show="emailStatus === 'idle'" class="text-google-gray">Establish your primary corporate mail identity</span>
                    </div>
                </template>
                <template x-if="emailType === 'existing'">
                    <span class="text-google-gray">We'll use this for administrative recovery</span>
                </template>
            </div>
        </div>

        <!-- Step 3: Administrative Security -->
        <div x-show="step === 3" x-cloak x-transition.opacity>
            <div class="google-input-wrapper">
                <input type="password" name="password" x-model="password" id="pass" @input="updateStrength()" placeholder=" " class="google-input" required>
                <label for="pass" class="google-label">Admin password</label>
            </div>
            <div class="mt-[-16px] mb-10">
                <div class="w-full bg-gray-100 h-1 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-500" :class="{'bg-google-red': passwordScore < 3, 'bg-google-green': passwordScore >= 3}" :style="'width: ' + (passwordScore + 1) * 20 + '%'"></div>
                </div>
                <p class="mt-2 text-[12px] text-google-gray" x-text="'Security Level: ' + ['Basic', 'Weak', 'Standard', 'Enterprise', 'Fortress'][passwordScore]"></p>
            </div>
        </div>

        <!-- Step 4: Governance Finalization -->
        <div x-show="step === 4" x-cloak x-transition.opacity>
            <div class="p-6 bg-google-surface border border-google-border rounded-lg mb-10">
                <h3 class="text-sm font-medium text-google-blue mb-2">Corporate Governance</h3>
                <p class="text-[12px] text-google-gray leading-relaxed">
                    By initializing this organization, you agree to the YGXONE terms of corporate security and governance. This account will have administrative authority over linked modules.
                </p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-4">
            <button type="button" x-show="step > 1" @click="back()" class="btn-google-secondary -ml-4">
                Back
            </button>
            <div x-show="step === 1" class="flex-1"></div>

            <button type="button" x-show="step < totalSteps" @click="next()" class="btn-google-primary">
                Next
            </button>
            <button type="submit" x-show="step === totalSteps" x-cloak class="btn-google-primary">
                Initialize Organization
            </button>
        </div>
    </form>
</div>
@endsection
