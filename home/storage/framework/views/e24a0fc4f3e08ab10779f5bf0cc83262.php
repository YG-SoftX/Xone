<?php $__env->startSection('title', 'YGXONE Search - Home'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .home-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    header {
        height: 70px;
        padding: 0 40px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        z-index: 50;
    }
    .nav-links { display: flex; gap: 25px; align-items: center; }
    .nav-link { color: var(--text-dim); text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.2s; }
    .nav-link:hover { color: var(--yg-accent-blue); }
    .profile-btn { width: 36px; height: 36px; border-radius: 10px; background: var(--yg-accent-blue); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; cursor: pointer; text-decoration: none; }

    main { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding-bottom: 100px; width: 100%; max-width: 800px; margin: 0 auto; }
    .logo { font-size: 80px; font-weight: 900; letter-spacing: -3px; margin-bottom: 30px; color: #0f172a; font-family: 'Outfit', sans-serif; }
    .logo span { background: linear-gradient(135deg, #2563eb, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .search-container { width: 90%; max-width: 600px; position: relative; }
    .search-bar { width: 100%; background: #ffffff; border: 1px solid #cbd5e1; padding: 18px 24px 18px 56px; border-radius: 35px; color: #0f172a; font-size: 16px; outline: none; transition: all 0.3s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); font-family: inherit; }
    .search-bar:focus { border-color: var(--yg-accent-blue); box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.1); }
    .search-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 18px; }
    .ai-badge { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; font-size: 10px; font-weight: 900; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; }

    .action-buttons { display: flex; gap: 15px; margin-top: 35px; }
    .btn { background: #f1f5f9; border: none; color: #475569; padding: 12px 24px; font-size: 14px; font-weight: 600; border-radius: 10px; cursor: pointer; transition: all 0.2s; font-family: inherit; }
    .btn:hover { background: #e2e8f0; color: #0f172a; }
    .btn-ai { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }

    footer { padding: 25px 40px; display: flex; justify-content: space-between; color: var(--text-dim); font-size: 12px; border-top: 1px solid #f1f5f9; width: 100%; }
</style>

<div class="home-wrapper">
    <header>
        <div class="nav-links">
            <a href="https://mail.ygxone.com" class="nav-link">YG Mail</a>
            <a href="https://drive.ygxone.com" class="nav-link">YG Drive</a>
            <a href="https://ai.ygxone.com" class="nav-link">YG AI</a>
            <?php if(auth()->check()): ?>
                <a href="https://account.ygxone.com" class="profile-btn">
                    <i class="fas fa-user"></i>
                </a>
            <?php else: ?>
                <a href="https://account.ygxone.com/login" class="nav-link">Sign In</a>
                <a href="https://account.ygxone.com/register" class="profile-btn">
                    <i class="fas fa-user-plus"></i>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <div class="logo">YGX<span>ONE</span></div>

        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <form id="search-form" action="<?php echo e(route('search.index')); ?>" method="GET">
                <input type="text" name="q" class="search-bar" placeholder="Search the ecosystem or ask Yuga AI..." autofocus autocomplete="off">
            </form>
            <div class="ai-badge">Yuga LLM</div>
        </div>

        <div class="action-buttons">
            <button class="btn" onclick="document.getElementById('search-form').submit()">Ecosystem Search</button>
            <button class="btn btn-ai" onclick="location.href='https://ai.ygxone.com'">Ask Yuga AI</button>
        </div>
    </main>

    <footer>
        <div class="nav-links">
            <a href="#" class="nav-link">About</a>
            <a href="#" class="nav-link">Privacy</a>
            <a href="#" class="nav-link">Terms</a>
        </div>
        <div>
            Sovereign Intelligence Platform © 2026
        </div>
    </footer>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home4/ygmarket/ygxone.com/home/resources/views/search/home.blade.php ENDPATH**/ ?>