<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Inter', Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 40px; }
        .card { max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 32px; padding: 48px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); text-align: center; border: 1px solid #eef2f6; }
        .logo { width: 64px; height: 64px; background: #2563eb; border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 32px; box-shadow: 0 10px 20px rgba(37,99,235,0.2); }
        h1 { color: #0f172a; font-size: 24px; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.5px; }
        p { color: #64748b; font-size: 16px; line-height: 1.6; margin-bottom: 32px; }
        .otp-box { background: #f1f5f9; border-radius: 24px; padding: 24px; font-size: 42px; font-weight: 900; color: #1e293b; letter-spacing: 12px; margin-bottom: 32px; font-family: 'Courier New', Courier, monospace; }
        .footer { color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; }
        .divider { height: 1px; background: #e2e8f0; margin: 32px 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C12 22 20 18 20 12V5L12 2L4 5V12C4 18 12 22 12 22Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h1>Security Verification</h1>
        <p>A request was made to verify your identity for <strong>{{ $reason }}</strong>. Use the secure code below to proceed.</p>
        
        <div class="otp-box">
            {{ $code }}
        </div>
        
        <p style="font-size: 13px;">This code will expire in <strong>10 minutes</strong>. If you did not request this, please secure your account immediately.</p>
        
        <div class="divider"></div>
        <div class="footer">Secured by YG Guard Security System</div>
    </div>
</body>
</html>
