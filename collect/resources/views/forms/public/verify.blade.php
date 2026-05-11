<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Submission | YG Collect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; color: #0f172a; }
        .glass { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
        .success-glow { box-shadow: 0 20px 40px rgba(34, 197, 94, 0.1); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full">
        <div class="glass p-10 rounded-3xl text-center success-glow">
            <div class="w-20 h-20 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 border border-green-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold mb-2">Authentic Data</h1>
            <p class="text-slate-500 mb-8">This submission has been verified as untampered and original.</p>
            
            <div class="space-y-4 text-left">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <div class="text-xs text-slate-400 uppercase font-bold mb-1">Submission ID</div>
                    <div class="font-mono text-sm text-slate-700">#SUB-{{ str_pad($submission->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
                
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <div class="text-xs text-slate-400 uppercase font-bold mb-1">Verification Hash</div>
                    <div class="font-mono text-[10px] break-all text-blue-600">{{ $submission->metadata['verification_hash'] }}</div>
                </div>
                
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <div class="text-xs text-slate-400 uppercase font-bold mb-1">Submission Time</div>
                    <div class="text-sm text-slate-700">{{ $submission->created_at->toDayDateTimeString() }}</div>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-slate-100">
                <a href="https://collect.ygxone.com" class="text-slate-400 text-xs hover:text-slate-600 transition-colors">
                    Verified by YG Collect Transparency Engine
                </a>
            </div>
        </div>
    </div>
</body>
</html>
