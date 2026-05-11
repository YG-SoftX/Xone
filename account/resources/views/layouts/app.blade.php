<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Sign in - YG Accounts')</title>
    
    <!-- Core Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- intl-tel-input (country code picker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/css/intlTelInput.css">
    
    <!-- AlpineJS for interaction -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
    
    <style>
        /* FOOLPROOF BASE STYLES */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background-color: #fff;
            color: #202124;
            display: flex;
            flex-direction: column;
        }

        [x-cloak] { display: none !important; }

        /* CENTERED CONTAINER */
        .page-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }

        /* GOOGLE CARD */
        .google-card {
            width: 100%;
            max-width: 448px;
            min-height: 500px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 48px 40px 36px;
            box-sizing: border-box;
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 600px) {
            .google-card {
                border: none;
                padding: 24px 24px;
                min-height: auto;
            }
        }

        /* INPUTS */
        .google-input-wrapper {
            position: relative;
            margin-bottom: 24px;
            width: 100%;
        }

        .google-input {
            width: 100%;
            height: 56px;
            padding: 13px 15px;
            font-size: 16px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            outline: none;
            box-sizing: border-box;
            background: transparent;
            transition: border-color 0.2s;
        }

        .google-input:focus {
            border: 2px solid #1a73e8;
            padding: 12px 14px;
        }

        .google-label {
            position: absolute;
            left: 15px;
            top: 16px;
            color: #5f6368;
            font-size: 16px;
            pointer-events: none;
            transition: transform 0.2s, font-size 0.2s, color 0.2s;
            transform-origin: left top;
            background: #fff;
            padding: 0 4px;
        }

        .google-input:focus + .google-label,
        .google-input:not(:placeholder-shown) + .google-label {
            transform: translate(-4px, -28px) scale(0.75);
            color: #1a73e8;
        }

        /* BUTTONS */
        .btn-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
        }

        .btn-primary,
        .btn-google-primary {
            background-color: #1a73e8;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            letter-spacing: 0.25px;
            transition: background-color 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover,
        .btn-google-primary:hover {
            background-color: #1557b0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .btn-secondary,
        .btn-google-secondary {
            background: transparent;
            color: #1a73e8;
            border: none;
            border-radius: 4px;
            padding: 10px 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary:hover,
        .btn-google-secondary:hover {
            background-color: rgba(26,115,232,0.08);
        }

        /* ===== PHONE FIELD (intl-tel-input) ===== */
        .phone-field-wrapper {
            position: relative;
            margin-bottom: 24px;
        }

        /* iti fills the wrapper */
        .phone-field-wrapper .iti {
            display: block !important;
            width: 100% !important;
        }

        /* Style the raw input inside iti to match google-input */
        .phone-field-wrapper input[type="tel"] {
            width: 100% !important;
            height: 56px;
            font-size: 16px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            outline: none;
            box-sizing: border-box;
            background: transparent;
            transition: border-color 0.2s;
            color: #202124;
        }

        .phone-field-wrapper input[type="tel"]:focus {
            border: 2px solid #1a73e8;
        }

        /* Match the flag container height */
        .phone-field-wrapper .iti__flag-container,
        .phone-field-wrapper .iti__selected-flag {
            height: 56px;
        }

        .phone-field-wrapper .iti__selected-flag {
            border-right: 1px solid #dadce0;
            padding: 0 8px 0 12px;
            border-radius: 4px 0 0 4px;
        }

        /* Country list dropdown z-index */
        .iti__country-list { z-index: 9999; }

        /*
         * Floating label for phone field.
         * NOTE: iti wraps the <input> in its own <div>, so the CSS
         * sibling selector ":not(:placeholder-shown) + label" stops working.
         * We control floating via Alpine: :class="{'phone-label--float': phoneFocused || phone}"
         */
        .phone-label {
            position: absolute;
            /* ~96px = flag (32px) + dial-code (~48px) + gap (16px) */
            left: 96px;
            top: 16px;
            color: #5f6368;
            font-size: 16px;
            pointer-events: none;
            transition: transform 0.15s ease, font-size 0.15s ease, color 0.15s ease;
            transform-origin: left top;
            background: #fff;
            padding: 0 4px;
            z-index: 2;
        }

        .phone-label--float {
            transform: translate(-4px, -28px) scale(0.75);
            color: #1a73e8;
        }

        /* Utility classes used by templates */
        .flex { display: flex; }
        .flex-1 { flex: 1; }
        .justify-between { justify-content: space-between; }
        .items-center { align-items: center; }
        .space-y-1 > * + * { margin-top: 4px; }
        .pt-4 { padding-top: 16px; }
        .mb-10 { margin-bottom: 40px; }
        .relative { position: relative; }
        .absolute { position: absolute; }
        .w-full { width: 100%; }
        .min-h-\[20px\] { min-height: 20px; }
        .mt-\[-16px\] { margin-top: -16px; }
        .pr-32 { padding-right: 8rem; }
        .right-4 { right: 1rem; }
        .top-4 { top: 1rem; }
        .text-\[14px\] { font-size: 14px; }
        .text-\[12px\] { font-size: 12px; }
        .text-\[24px\] { font-size: 24px; }
        .font-normal { font-weight: 400; }
        .font-medium { font-weight: 500; }
        .leading-relaxed { line-height: 1.625; }
        .animate-pulse { animation: pulse 1.5s cubic-bezier(0.4,0,0.6,1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .text-google-blue { color: #1a73e8; }
        .text-google-gray { color: #5f6368; }
        .text-google-green { color: #0f9d58; }
        .text-google-red  { color: #d93025; }
        .text-google-yellow { color: #f9ab00; }
        .text-sm { font-size: 14px; }
        .p-6 { padding: 24px; }
        .bg-google-surface { background: #f8f9fa; }
        .border { border-width: 1px; border-style: solid; }
        .border-google-border { border-color: #dadce0; }
        .rounded-lg { border-radius: 8px; }
        .mb-2 { margin-bottom: 8px; }
        .mt-2 { margin-top: 8px; }
        .bg-gray-100 { background: #f1f3f4; }
        .h-1 { height: 4px; }
        .h-full { height: 100%; }
        .rounded-full { border-radius: 9999px; }
        .overflow-hidden { overflow: hidden; }
        .transition-all { transition: all 0.3s; }
        .duration-500 { transition-duration: 500ms; }
        .bg-google-red { background-color: #d93025; }
        .bg-google-yellow { background-color: #f9ab00; }
        .bg-google-green { background-color: #0f9d58; }
        .-ml-4 { margin-left: -1rem; }

        /* LOGO */
        .logo-box {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 24px;
            letter-spacing: -0.5px;
        }

        .logo-text span { color: #1a73e8; }

        /* FOOTER */
        .footer {
            width: 100%;
            max-width: 1024px;
            margin: 0 auto;
            padding: 24px;
            box-sizing: border-box;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #5f6368;
        }

        .footer a {
            color: #5f6368;
            text-decoration: none;
            margin-left: 24px;
        }

        .footer a:hover { text-decoration: underline; }

        .lang-selector {
            border: none;
            background: transparent;
            font-size: 12px;
            color: #5f6368;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .lang-selector:hover { background: #f1f3f4; }

        /* UTILS */
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 8px; }
        .mb-8 { margin-bottom: 32px; }
        .mt-2 { margin-top: 8px; }
    </style>
    @stack('head')
</head>
<body>
    <div class="page-wrapper">
        @yield('content')
    </div>

    <footer class="footer">
        <select class="lang-selector">
            <option>English (United States)</option>
            <option>English (United Kingdom)</option>
            <option>Español</option>
        </select>
        <div class="footer-links">
            <a href="https://support.ygxone.com" target="_blank">Help</a>
            <a href="https://ygxone.com/privacy" target="_blank">Privacy</a>
            <a href="https://ygxone.com/terms" target="_blank">Terms</a>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
