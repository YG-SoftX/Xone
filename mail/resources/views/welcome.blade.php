<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ optional($settings)->brand_name ?? config('app.name') }} - Sovereign Communication</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            overflow-x: hidden;
        }
        .glass {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0,0,0,0.08);
        }
        .gradient-primary {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        }
        .gradient-bg {
            background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
        }
        .hero-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">

    <!-- Top Navigation -->
    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-red-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900">{{ optional($settings)->brand_name ?? config('app.name') }}</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('sso.initiate') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">
                    Sign In
                </a>
                <a href="{{ route('sso.initiate') . '&register=1' }}" class="gradient-primary px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all hover:scale-[1.02]">
                    Get Started
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 pt-24 pb-32 text-center relative">
            <!-- Background decoration -->
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[600px] bg-gradient-to-b from-red-100/40 to-transparent rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative">
                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium bg-red-50 text-red-700 border border-red-100 mb-8">
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                    {{ optional($settings)->site_description ?? 'Encrypted & Sovereign Communication' }}
                </span>

                <h1 class="text-5xl md:text-7xl lg:text-8xl font-bold tracking-tight leading-[1.1] text-gray-900 mb-6">
                    {{ optional($settings)->hero_title ?? 'Sovereign<br>Communication' }}
                </h1>

                <p class="text-lg md:text-xl text-gray-500 max-w-2xl mx-auto leading-relaxed mb-12">
                    {{ optional($settings)->hero_subtitle ?? 'End-to-end encrypted email with AI-powered features, unified across your YGXONE ecosystem.' }}
                </p>

                <div class="flex flex-col md:flex-row gap-4 justify-center items-center">
                    <a href="{{ route('sso.initiate') }}"
                       class="gradient-primary px-8 py-4 rounded-2xl text-lg font-bold text-white shadow-lg hover:shadow-xl transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center gap-3">
                        {{ optional($settings)->cta_text ?? 'Start Mailing' }}
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <span class="text-gray-400 text-sm">Secured by YGXONE SSO • Encrypted End-to-End</span>
                </div>
            </div>
        </div>

        <!-- Feature preview cards floating -->
        <div class="hidden lg:block absolute left-8 top-40 glass p-5 rounded-2xl w-56 hero-float shadow-lg">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">AI</div>
                <span class="text-sm font-semibold text-gray-800">Smart Inbox</span>
            </div>
            <div class="space-y-2">
                <div class="h-2 w-full rounded bg-gray-100"></div>
                <div class="h-2 w-3/4 rounded bg-gray-100"></div>
                <div class="h-2 w-5/6 rounded bg-red-100"></div>
            </div>
        </div>
        <div class="hidden lg:block absolute right-8 bottom-0 glass p-5 rounded-2xl w-56 hero-float shadow-lg" style="animation-delay: 2s;">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-white text-xs font-bold">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                </div>
                <span class="text-sm font-semibold text-gray-800">E2E Encryption</span>
            </div>
            <div class="h-2 w-full rounded bg-gray-100 mb-1"></div>
            <div class="h-2 w-4/5 rounded bg-gray-100"></div>
        </div>
    </section>

    <!-- Features Bento Grid -->
    <section class="max-w-7xl mx-auto px-6 pb-32">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Everything you need for secure email</h2>
            <p class="text-gray-500 max-w-xl mx-auto">Enterprise-grade email with the simplicity you expect.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-shield-alt text-red-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">End-to-End Encryption</h3>
                <p class="text-gray-500 text-sm leading-relaxed">RSA-4096 + AES-256-CBC hybrid encryption. Your emails stay private, even from us.</p>
            </div>

            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-robot text-blue-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">AI-Powered</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Smart categorization, sentiment analysis, and AI-suggested replies save you time.</p>
            </div>

            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-filter text-green-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Spam Protection</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Multi-layer spam filtering with domain blacklists, keyword scanning, and rate limits.</p>
            </div>

            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-rules text-purple-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Smart Rules</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Auto-sort, forward, label, and archive emails with custom rule engine.</p>
            </div>

            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-cloud text-yellow-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Unified Drive</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Attachments auto-sync to YG Drive. Access your files anywhere.</p>
            </div>

            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-fingerprint text-indigo-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">SSO Integration</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Seamless single sign-on across the YGXONE ecosystem with YG Account.</p>
            </div>

            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-custom-domain text-orange-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Custom Domains</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Use your own domain with DKIM, SPF, and DMARC support.</p>
            </div>

            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-bell text-teal-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Push Notifications</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Real-time push notifications for new emails and important updates.</p>
            </div>

            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="w-12 h-12 bg-pink-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-search text-pink-500 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Full-Text Search</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Search across all folders including subject, body, sender, and attachments.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-white border-t border-gray-100">
        <div class="max-w-4xl mx-auto px-6 py-24 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ready to take control?</h2>
            <p class="text-gray-500 mb-8">Join YGXONE Mail and experience sovereign, intelligent email.</p>
            <a href="{{ route('sso.initiate') }}"
               class="gradient-primary px-8 py-4 rounded-2xl text-lg font-bold text-white shadow-lg hover:shadow-xl transition-all hover:scale-[1.02] active:scale-[0.98] inline-flex items-center gap-3">
                Get Started Free
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-50 border-t border-gray-100 py-12">
        <div class="max-w-7xl mx-auto px-6 text-center text-sm text-gray-400">
            &copy; {{ date('Y') }} YGXONE Systems &bull; Built with Laravel & Filament
        </div>
    </footer>

</body>
</html>
