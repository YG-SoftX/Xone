<div class="eco-nav-sidebar">
    <div class="eco-brand">
        <img src="https://pay.ygxone.com/assets/images/logo-icon.png" alt="YG">
    </div>
    <ul class="eco-list">
        <li>
            <a href="https://pay.ygxone.com" title="YG Pay">
                <i class="fas fa-wallet"></i>
            </a>
        </li>
        <li>
            <a href="https://mail.ygxone.com" title="YG Mail">
                <i class="fas fa-envelope"></i>
            </a>
        </li>
        <li>
            <a href="https://drive.ygxone.com" title="YG Drive">
                <i class="fas fa-hdd"></i>
            </a>
        </li>
        <li class="{{ request()->is('admin*') ? 'active' : '' }}">
            <a href="https://docx.ygxone.com" title="YG DocX">
                <i class="fas fa-file-word"></i>
            </a>
        </li>
        <li>
            <a href="https://ai.ygxone.com" title="YG AI">
                <i class="fas fa-robot"></i>
            </a>
        </li>
        <li>
            <a href="https://play.ygxone.com" title="YG Play Store">
                <i class="fas fa-play"></i>
            </a>
        </li>
    </ul>
    <div class="eco-footer">
        <a href="https://account.ygxone.com" title="My Account">
            <i class="fas fa-user-circle"></i>
        </a>
    </div>
</div>

<style>
    .eco-nav-sidebar {
        width: 70px;
        height: 100vh;
        background: #0f172a;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px 0;
        z-index: 10000;
        border-right: 1px solid rgba(255,255,255,0.05);
    }
    .eco-brand img {
        width: 35px;
        margin-bottom: 40px;
    }
    .eco-list {
        list-style: none;
        padding: 0;
        margin: 0;
        flex: 1;
    }
    .eco-list li {
        margin-bottom: 25px;
        text-align: center;
    }
    .eco-list li a {
        color: rgba(255,255,255,0.4);
        font-size: 1.4rem;
        transition: all 0.3s;
        display: block;
        padding: 10px;
        border-radius: 12px;
    }
    .eco-list li a:hover, .eco-list li.active a {
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
        transform: scale(1.1);
    }
    .eco-footer a {
        color: rgba(255,255,255,0.4);
        font-size: 1.4rem;
        transition: all 0.3s;
    }
    .eco-footer a:hover {
        color: white;
    }
    
    /* Adjust main layout for the sidebar in Filament */
    .fi-layout {
        padding-left: 70px !important;
    }
    @media (max-width: 768px) {
        .eco-nav-sidebar {
            width: 100%;
            height: 60px;
            bottom: 0;
            top: auto;
            flex-direction: row;
            padding: 0 20px;
            border-right: none;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .eco-brand { display: none; }
        .eco-list {
            flex-direction: row;
            display: flex;
            justify-content: space-around;
            width: 100%;
        }
        .eco-list li { margin-bottom: 0; }
        .fi-layout { padding-left: 0 !important; padding-bottom: 60px !important; }
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
