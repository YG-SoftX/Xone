<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — YG Mail</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 40px; width: 400px; }
        .login-card h1 { font-size: 24px; margin-bottom: 8px; }
        .login-card p { color: #94a3b8; font-size: 14px; margin-bottom: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px 14px; background: #0f172a; border: 1px solid #475569; border-radius: 8px; color: #e2e8f0; font-size: 14px; margin-bottom: 16px; outline: none; }
        input:focus { border-color: #3b82f6; }
        .btn { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #2563eb; }
        .error { background: #7f1d1d; border: 1px solid #ef4444; color: #fca5a5; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>🛡️ YG Mail Admin</h1>
        <p>Sign in to manage the mail service.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first('email') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn">Sign In</button>
        </form>
    </div>
</body>
</html>
