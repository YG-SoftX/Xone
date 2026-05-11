@extends('support.layout')

@section('support_content')
    {{-- Category: YG Account --}}
    <a href="#" class="help-card bg-white p-8 rounded-[2.5rem] text-center">
        <div class="w-20 h-20 bg-blue-50 rounded-3xl flex items-center justify-center text-blue-600 mx-auto mb-6">
            <i class="fas fa-user-circle text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3 font-google">YG Account</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Manage your profile, security, and two-factor authentication.</p>
    </a>

    {{-- Category: YG Mail --}}
    <a href="#" class="help-card bg-white p-8 rounded-[2.5rem] text-center">
        <div class="w-20 h-20 bg-purple-50 rounded-3xl flex items-center justify-center text-purple-600 mx-auto mb-6">
            <i class="fas fa-envelope text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3 font-google">YG Mail</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Sending, receiving, and troubleshooting your encryption.</p>
    </a>

    {{-- Category: YG Pay --}}
    <a href="#" class="help-card bg-white p-8 rounded-[2.5rem] text-center">
        <div class="w-20 h-20 bg-green-50 rounded-3xl flex items-center justify-center text-green-600 mx-auto mb-6">
            <i class="fas fa-wallet text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3 font-google">YG Pay</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Digital wallet, transactions, and merchant services.</p>
    </a>

    {{-- Category: YG Drive --}}
    <a href="#" class="help-card bg-white p-8 rounded-[2.5rem] text-center">
        <div class="w-20 h-20 bg-amber-50 rounded-3xl flex items-center justify-center text-amber-600 mx-auto mb-6">
            <i class="fas fa-cloud-upload-alt text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3 font-google">YG Drive</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Storage plans, file sharing, and synchronization.</p>
    </a>

    {{-- Category: YG Meet --}}
    <a href="#" class="help-card bg-white p-8 rounded-[2.5rem] text-center">
        <div class="w-20 h-20 bg-red-50 rounded-3xl flex items-center justify-center text-red-600 mx-auto mb-6">
            <i class="fas fa-video text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3 font-google">YG Meet</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Video conferencing, screen sharing, and recording.</p>
    </a>

    {{-- Category: YG Docx --}}
    <a href="#" class="help-card bg-white p-8 rounded-[2.5rem] text-center">
        <div class="w-20 h-20 bg-indigo-50 rounded-3xl flex items-center justify-center text-indigo-600 mx-auto mb-6">
            <i class="fas fa-file-alt text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3 font-google">YG Docx</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Document creation, editing, and collaborative writing.</p>
    </a>

    {{-- Category: YG Playstore --}}
    <a href="#" class="help-card bg-white p-8 rounded-[2.5rem] text-center">
        <div class="w-20 h-20 bg-cyan-50 rounded-3xl flex items-center justify-center text-cyan-600 mx-auto mb-6">
            <i class="fas fa-shopping-bag text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3 font-google">YG Playstore</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Discovering apps, updates, and developer accounts.</p>
    </a>

    {{-- Category: Security --}}
    <a href="#" class="help-card bg-white p-8 rounded-[2.5rem] text-center">
        <div class="w-20 h-20 bg-rose-50 rounded-3xl flex items-center justify-center text-rose-600 mx-auto mb-6">
            <i class="fas fa-shield-alt text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3 font-google">Privacy & Safety</h3>
        <p class="text-gray-500 text-sm leading-relaxed">Our commitment to your security and data protection.</p>
    </a>
@endsection
