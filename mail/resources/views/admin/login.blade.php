<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — YG Mail</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="bg-white border border-gray-200 rounded-2xl p-8 w-full max-w-md shadow-lg">
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-red-600 rounded-2xl flex items-center justify-center text-white mx-auto mb-4 shadow-sm">
                <i class="fas fa-envelope text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">YG Mail Admin</h1>
            <p class="text-sm text-gray-500 mt-1">Sign in to manage the mail service.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">
                {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:border-red-400 focus:ring-2 focus:ring-red-100 outline-none transition">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:border-red-400 focus:ring-2 focus:ring-red-100 outline-none transition">
            </div>

            <button type="submit"
                    class="w-full py-3 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all">
                <i class="fas fa-lock-open mr-2"></i> Sign In
            </button>
        </form>
    </div>
</body>
</html>
