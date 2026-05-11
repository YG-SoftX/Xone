<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name', 'Ygxone') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@300;400;500;700&family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    @routes(['nonce' => app('csp-nonce')])
    @viteReactRefresh
    @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
    @inertiaHead

    <style nonce="{{ app('csp-nonce') }}">
        /* Google-style light theme defaults */
        {!! $theme_css ?? ':root{--color-primary:#4285F4;--color-secondary:#34A853;--color-background:#FFFFFF;--color-surface:#F8F9FA;--color-text:#202124;--color-accent:#EA4335;--font-heading:"Google Sans",system-ui,sans-serif;--font-body:"Roboto",system-ui,sans-serif;--border-radius:8px}' !!}

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-body);
            color: var(--color-text);
            background: var(--color-background);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: var(--font-heading);
        }

        a {
            color: var(--color-primary);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        button,
        .btn {
            border-radius: var(--border-radius);
        }
    </style>
</head>

<body class="antialiased">
    @inertia
</body>

</html>