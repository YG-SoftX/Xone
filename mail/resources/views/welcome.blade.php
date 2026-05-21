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
        .icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
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
                <a href="{{ route('sso.initiate') }}" class="gradient-primary px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-md hover:shadow-lg transition-all hover:scale-[1.02]">
                    Login with YG Account
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
                    {!! optional($settings)->hero_title ?? 'Sovereign<br>Communication' !!}
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
                    <span class="text-gray-400 text-sm">Authenticated via YG Account • Encrypted End-to-End</span>
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
            <!-- End-to-End Encryption -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-red-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-red-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">End-to-End Encryption</h3>
                <p class="text-gray-500 text-sm leading-relaxed">RSA-4096 + AES-256-CBC hybrid encryption. Your emails stay private, even from us.</p>
            </div>

            <!-- AI-Powered -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-blue-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-blue-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">AI-Powered</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Smart categorization, sentiment analysis, and AI-suggested replies save you time.</p>
            </div>

            <!-- Spam Protection -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-green-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-green-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Spam Protection</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Multi-layer spam filtering with domain blacklists, keyword scanning, and rate limits.</p>
            </div>

            <!-- Smart Rules -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-purple-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-purple-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Smart Rules</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Auto-sort, forward, label, and archive emails with custom rule engine.</p>
            </div>

            <!-- Unified Drive -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-yellow-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-yellow-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Unified Drive</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Attachments auto-sync to YG Drive. Access your files anywhere.</p>
            </div>

            <!-- SSO Integration -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-indigo-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-indigo-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Centralized Authentication</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Single sign-on via YG Account. One identity across the entire YGXONE ecosystem.</p>
            </div>

            <!-- Custom Domains -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-orange-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-orange-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Custom Domains</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Use your own domain with DKIM, SPF, and DMARC support.</p>
            </div>

            <!-- Push Notifications -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-teal-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-teal-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Push Notifications</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Real-time push notifications for new emails and important updates.</p>
            </div>

            <!-- Full-Text Search -->
            <div class="feature-card bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="icon-wrapper bg-pink-50">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-pink-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
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
    <footer class="bg-gray-50 border-t border-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <!-- Dynamic Footer Links -->
                <div class="flex flex-wrap justify-center gap-6 text-sm">
                    @php
                        $footerService = config('app.footer_service', 'mail');
                        $masterApiUrl = config('services.master_api.url', env('MASTER_API_URL', 'http://localhost:8001'));
                        
                        try {
                            $response = \Illuminate\Support\Facades\Http::timeout(3)
                                ->get("{$masterApiUrl}/api/footer/{$footerService}");
                            $footerItems = $response->successful() ? $response->json('items', []) : [];
                        } catch (\Exception $e) {
                            $footerItems = [];
                        }
                    @endphp
                    
                    @forelse($footerItems as $item)
                        <a href="{{ $item['url'] }}" 
                           target="{{ $item['target'] ?? '_self' }}"
                           rel="{{ $item['rel'] ?? '' }}"
                           class="text-gray-500 hover:text-red-600 transition-colors font-medium">
                            {{ $item['label'] }}
                        </a>
                    @empty
                        <!-- Fallback links if API fails -->
                        <a href="/privacy" class="text-gray-500 hover:text-red-600 transition-colors font-medium">Privacy</a>
                        <a href="/terms" class="text-gray-500 hover:text-red-600 transition-colors font-medium">Terms</a>
                        <a href="/support" class="text-gray-500 hover:text-red-600 transition-colors font-medium">Support</a>
                    @endforelse
                </div>
                
                <!-- Copyright -->
                <p class="text-sm text-gray-400">
                    &copy; {{ date('Y') }} YGXONE Systems • Sovereign Communication
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
