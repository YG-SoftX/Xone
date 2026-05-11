<?php
define('YUGA_ROOT', __DIR__);
$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$brand  = $config['platform_name'] ?? 'Yuga';
$site   = rtrim($config['site_url'] ?? '', '/');
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>404 — Page not found · <?= htmlspecialchars($brand) ?></title>
<meta name="robots" content="noindex">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#0a0f1e;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.orbs{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0}
.orb{position:absolute;border-radius:50%;filter:blur(80px);opacity:.12;animation:drift 14s ease-in-out infinite}
.o1{width:400px;height:400px;background:#6366f1;top:-100px;left:-100px}
.o2{width:300px;height:300px;background:#14b8a6;bottom:-80px;right:-80px;animation-delay:-5s}
@keyframes drift{0%,100%{transform:translate(0,0)}50%{transform:translate(30px,20px)}}
.box{position:relative;z-index:1;text-align:center;max-width:480px}
.num{font-size:120px;font-weight:800;background:linear-gradient(135deg,#a5b4fc,#14b8a6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;margin-bottom:16px}
h1{font-size:24px;font-weight:600;margin-bottom:10px}
p{font-size:15px;color:#94a3b8;margin-bottom:32px;line-height:1.6}
.btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn{padding:11px 24px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;transition:.15s;display:inline-block}
.bp{background:#6366f1;color:#fff}.bp:hover{background:#4f46e5}
.bg{border:1px solid #1e293b;color:#94a3b8}.bg:hover{border-color:#a5b4fc;color:#a5b4fc}
.logo{display:inline-flex;align-items:center;gap:8px;margin-bottom:32px;font-size:16px;font-weight:700;color:#e2e8f0;text-decoration:none}
.ly{width:32px;height:32px;background:#6366f1;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px}
</style>
</head>
<body>
<div class="orbs"><div class="orb o1"></div><div class="orb o2"></div></div>
<div class="box">
  <a href="<?= htmlspecialchars($site) ?>/portal/" class="logo">
    <div class="ly">Y</div><?= htmlspecialchars($brand) ?>
  </a>
  <div class="num">404</div>
  <h1>Page not found</h1>
  <p>The page you're looking for doesn't exist or may have moved.<br>Check the URL or head back home.</p>
  <div class="btns">
    <a href="<?= htmlspecialchars($site) ?>/portal/" class="btn bp">Go to portal</a>
    <a href="javascript:history.back()" class="btn bg">Go back</a>
  </div>
</div>
</body>
</html>
