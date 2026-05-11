<?php
define('YUGA_ROOT', __DIR__);
$config = file_exists(YUGA_ROOT . '/config.php') ? require YUGA_ROOT . '/config.php' : [];
$brand  = $config['platform_name'] ?? 'Yuga';
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>500 — Server error · <?= htmlspecialchars($brand) ?></title>
<meta name="robots" content="noindex">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,sans-serif;background:#0a0f1e;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.orbs{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0}
.orb{position:absolute;border-radius:50%;filter:blur(80px);opacity:.12;animation:drift 14s ease-in-out infinite}
.o1{width:400px;height:400px;background:#ef4444;top:-100px;right:-100px}
.o2{width:300px;height:300px;background:#f59e0b;bottom:-80px;left:-80px;animation-delay:-5s}
@keyframes drift{0%,100%{transform:translate(0,0)}50%{transform:translate(30px,20px)}}
.box{position:relative;z-index:1;text-align:center;max-width:480px}
.num{font-size:120px;font-weight:800;background:linear-gradient(135deg,#fca5a5,#fcd34d);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;margin-bottom:16px}
h1{font-size:24px;font-weight:600;margin-bottom:10px}
p{font-size:15px;color:#94a3b8;margin-bottom:32px;line-height:1.6}
.btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
.btn{padding:11px 24px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;transition:.15s;display:inline-block;cursor:pointer;border:none;font-family:inherit}
.bp{background:#6366f1;color:#fff}.bp:hover{background:#4f46e5}
.bg{border:1px solid #1e293b;color:#94a3b8;background:transparent}.bg:hover{border-color:#a5b4fc;color:#a5b4fc}
.logo{display:inline-flex;align-items:center;gap:8px;margin-bottom:32px;font-size:16px;font-weight:700;color:#e2e8f0;text-decoration:none}
.ly{width:32px;height:32px;background:#6366f1;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px}
</style>
</head>
<body>
<div class="orbs"><div class="orb o1"></div><div class="orb o2"></div></div>
<div class="box">
  <a href="/" class="logo"><div class="ly">Y</div><?= htmlspecialchars($brand) ?></a>
  <div class="num">500</div>
  <h1>Something went wrong</h1>
  <p>The server ran into an unexpected error. This is not your fault.<br>Please try again in a moment — the issue has been logged.</p>
  <div class="btns">
    <button class="btn bp" onclick="location.reload()">Try again</button>
    <button class="btn bg" onclick="history.back()">Go back</button>
  </div>
</div>
</body>
</html>
