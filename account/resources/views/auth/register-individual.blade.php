@extends('layouts.app')
@section('title', 'Create Individual Account')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/zxcvbn@4.4.2/dist/zxcvbn.js"></script>
<!-- intl-tel-input: main library MUST load before Alpine -->
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/intlTelInput.min.js"></script>
@endpush

@section('content')
<div class="google-card" x-data="{ 
    step: 1, 
    totalSteps: 4,
    fullName: '',
    email: '',
    emailStatus: 'idle',
    dob: '',
    phone: '',
    phoneFocused: false,
    password: '',
    passwordScore: 0,
    iti: null,
    
    init() {
        this.$nextTick(() => {
            if (typeof window.intlTelInput === 'undefined') {
                console.error('intl-tel-input library not loaded!');
                return;
            }
            this.iti = window.intlTelInput(this.$refs.phoneInput, {
                utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/utils.js',
                separateDialCode: true,
                initialCountry: 'auto',
                geoIpLookup: function(callback) {
                    fetch('https://ipapi.co/json')
                        .then(res => res.json())
                        .then(data => callback(data.country_code))
                        .catch(() => callback('np'));
                }
            });
        });
    },

    async checkEmail() {
        if (this.email.length < 3) { this.emailStatus = 'idle'; return; }
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
        <h1 class="text-[24px] font-normal text-[#202124] mb-1">Create your YG Account</h1>
        <p class="text-[14px] text-google-gray" x-text="'Step ' + step + ' of ' + totalSteps"></p>

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

    <form method="POST" action="{{ route('register.individual') }}" class="space-y-1">
        @csrf
        
        <!-- Step 1: Basics -->
        <div x-show="step === 1" x-transition.opacity>
            <div class="google-input-wrapper">
                <input type="text" name="full_name" x-model="fullName" id="name" placeholder=" " class="google-input" required autofocus>
                <label for="name" class="google-label">Full Name</label>
            </div>
            <div class="google-input-wrapper">
                <input type="date" name="date_of_birth" x-model="dob" id="dob" placeholder=" " class="google-input" required>
                <label for="dob" class="google-label">Birth Date</label>
            </div>
            <div class="phone-field-wrapper mb-10">
                <input type="tel" name="phone" x-ref="phoneInput" id="phone"
                    placeholder=" "
                    @focus="phoneFocused = true"
                    @blur="phoneFocused = false"
                    @input="phone = $event.target.value"
                    required>
                <label for="phone" class="phone-label"
                    :class="{ 'phone-label--float': phoneFocused || phone }"
                >Phone Number</label>
            </div>
        </div>

        <!-- Step 2: Username -->
        <div x-show="step === 2" x-cloak x-transition.opacity>
            <div class="google-input-wrapper relative">
                <input type="text" name="email_username" x-model="email" id="username" @input.debounce.500ms="checkEmail()" placeholder=" " class="google-input pr-32" required>
                <label for="username" class="google-label">Choose your username</label>
                <span class="absolute right-4 top-4 text-google-gray font-medium">@ygxone.com</span>
            </div>
            <div class="mt-[-16px] mb-10 min-h-[20px] text-[12px]">
                <span x-show="emailStatus === 'checking'" class="text-google-blue animate-pulse">Checking availability...</span>
                <span x-show="emailStatus === 'available'" class="text-google-green">Username available!</span>
                <span x-show="emailStatus === 'taken'" class="text-google-red">That username is taken.</span>
                <span x-show="emailStatus === 'idle'" class="text-google-gray">You can use letters, numbers & periods</span>
            </div>
        </div>

        <!-- Step 3: Password -->
        <div x-show="step === 3" x-cloak x-transition.opacity>
            <div class="google-input-wrapper">
                <input type="password" name="password" x-model="password" id="pass" @input="updateStrength()" placeholder=" " class="google-input" required>
                <label for="pass" class="google-label">Create password</label>
            </div>
            <div class="mt-[-16px] mb-10">
                <div class="w-full bg-gray-100 h-1 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-500" :class="{'bg-google-red': passwordScore < 2, 'bg-google-yellow': passwordScore === 2, 'bg-google-green': passwordScore > 2}" :style="'width: ' + (passwordScore + 1) * 20 + '%'"></div>
                </div>
                <p class="mt-2 text-[12px] text-google-gray">Use 8 or more characters with a mix of letters, numbers & symbols</p>
            </div>
        </div>

        <!-- Step 4: Finalize -->
        <div x-show="step === 4" x-cloak x-transition.opacity>
            <div class="p-6 bg-google-surface border border-google-border rounded-lg mb-10">
                <h3 class="text-sm font-medium text-google-blue mb-2">Terms & Privacy</h3>
                <p class="text-[12px] text-google-gray leading-relaxed">
                    By clicking "Claim Identity", you agree to the YGXONE Terms of Service and Privacy Policy. We'll use your phone number for account security.
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
                Claim Identity
            </button>
        </div>
    </form>
</div>
@endsection
