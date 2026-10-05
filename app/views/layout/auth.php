<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · <?=e(setting('system_name','MGTM System'))?></title>
<meta name="color-scheme" content="light"><link rel="stylesheet" href="<?=asset('css/app.css')?>"></head>
<body class="auth"><section class="hero"><img class="brand-logo large" src="<?=asset('img/nmc.jpg')?>" alt="Nansana Municipal Council logo">
<h1><?=e(setting('system_name'))?></h1><p><?=e(setting('system_full_name'))?></p><small>Records, transfers, payroll status and retirement for government teachers.</small></section>
<main class="auth-card"><?php foreach(flash() as [$t,$m]): ?><div class="alert <?=e($t)?>"><?=$m?></div><?php endforeach; ?><?=$content?></main></body></html>
