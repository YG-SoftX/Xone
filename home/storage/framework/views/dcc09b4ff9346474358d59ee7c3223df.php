<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    <title><?php echo $__env->yieldContent('title', 'YGXONE — Sovereign Intelligence Portal'); ?></title>
    
    <!-- Fonts: High-Fidelity Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    
    <!-- Icons: FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- UI: Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --yg-bg: #ffffff;
            --yg-glass: rgba(0, 0, 0, 0.02);
            --yg-border: #e2e8f0;
            --yg-accent-blue: #2563eb;
            --yg-accent-purple: #7c3aed;
            --text-main: #0f172a;
            --text-dim: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--yg-bg);
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Clean Minimalist Background */
        .bg-pattern {
            position: fixed;
            inset: 0;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.3;
            z-index: -1;
        }

        [x-cloak] { display: none !important; }
    </style>
    
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="antialiased">
    <div class="bg-pattern"></div>
    
    <!-- Admin Quick Access -->
    <?php if(auth()->check() && (in_array(auth()->user()->email, explode(',', env('ADMIN_EMAILS', ''))) || in_array(auth()->id(), array_filter(explode(',', env('ADMIN_USER_IDS', '')))))): ?>
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white px-4 py-2 text-xs font-semibold">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <span><i class="fas fa-shield-alt mr-2"></i>Admin Mode</span>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="hover:underline">Dashboard</a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php echo $__env->yieldContent('content'); ?>
    
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\YG SoftX\Xone\home\resources\views\layouts\app.blade.php ENDPATH**/ ?>