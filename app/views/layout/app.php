<?php
$u=Auth::user(); $r=$_GET['r']??'dashboard/index'; $sec=explode('/',$r)[0];
function ico($n){ $p=['home'=>'M3 11l9-8 9 8M5 10v10h5v-6h4v6h5V10','users'=>'M16 11a4 4 0 100-8 4 4 0 000 8zM3 21v-1a6 6 0 0112 0v1M17 14a5 5 0 014 5v2','log'=>'M5 3h11l3 3v15H5zM9 9h7M9 13h7M9 17h4','gear'=>'M12 15a3 3 0 100-6 3 3 0 000 6zM19 12l2-1-2-4-2 1-2-1-1-2H9L8 7 6 8 4 7 2 11l2 1v2l-2 1 2 4 2-1 2 1 1 2h4l1-2 2-1 2 1 2-4-2-1z','out'=>'M9 4H5v16h4M16 8l4 4-4 4M20 12H9','school'=>'M3 10l9-6 9 6M5 10v9h14v-9M9 19v-5h6v5','person'=>'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0','book'=>'M5 4h12a2 2 0 012 2v14H7a2 2 0 01-2-2zM5 18a2 2 0 012-2h12','menu'=>'M3 6h18M3 12h18M3 18h18','key'=>'M15 7a4 4 0 11-3 6l-8 8H3v-3h3v-3h3l3-3a4 4 0 013-5z'];
 return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="'.$p[$n].'"/></svg>'; }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(setting('system_name','MGTM System'))?></title><meta name="color-scheme" content="light"><meta name="csrf-token" content="<?=e(Csrf::token())?>"><meta name="app-base" content="<?=e(base())?>"><link rel="manifest" href="<?=base()?>/manifest.webmanifest"><meta name="theme-color" content="#f4f6f8"><link rel="stylesheet" href="<?=asset('css/app.css')?>"></head>
<body><div class="shell">
<aside class="side" id="side"><div class="brand"><img class="brand-logo" src="<?=asset('img/nmc.jpg')?>" alt="Nansana Municipal Council logo"><div><b><?=e(setting('system_name'))?></b><small><?=e(setting('municipality'))?></small></div></div>
<nav>
<a href="<?=url('dashboard/index')?>" class="<?=$sec==='dashboard'?'on':''?>"><?=ico('home')?>Dashboard</a>
<?php if(Auth::can('teachers.view')): ?><a href="<?=url('teachers/index')?>" class="<?=$sec==='teachers'?'on':''?>"><?=ico('person')?>Teachers</a>
<a href="<?=url('ledger/index')?>" class="<?=$sec==='ledger'?'on':''?>"><?=ico('book')?>Teacher ledger</a>
<a href="<?=url('schools/index')?>" class="<?=$sec==='schools'?'on':''?>"><?=ico('school')?>Schools</a>
<a href="<?=url('movements/index')?>" class="<?=$sec==='movements'?'on':''?>"><?=ico('log')?>Approvals</a>
<a href="<?=url('retirement/index')?>" class="<?=$sec==='retirement'?'on':''?>"><?=ico('key')?>Retirement</a><?php endif; ?>
<?php if(Auth::can('biometrics.manage')): ?><a href="<?=url('biometrics/index')?>" class="<?=$sec==='biometrics'?'on':''?>"><?=ico('key')?>Verification</a><?php endif; ?>
<?php if(Auth::can('messaging.send')||Auth::can('messaging.log')): ?><a href="<?=url('messaging/index')?>" class="<?=$sec==='messaging'?'on':''?>"><?=ico('menu')?>Messaging</a><?php endif; ?>
<?php if(Auth::can('users.manage')): ?><a href="<?=url('users/index')?>" class="<?=$sec==='users'?'on':''?>"><?=ico('users')?>Users</a><?php endif; ?>
<?php if(Auth::can('audit.view')): ?><a href="<?=url('audit/index')?>" class="<?=$sec==='audit'?'on':''?>"><?=ico('log')?>Audit log</a><?php endif; ?>
<?php if(Auth::can('system.manage')): ?><a href="<?=url('system/backups')?>" class="<?=$sec==='system'?'on':''?>"><?=ico('log')?>Backups</a><?php endif; ?>
<?php if(Auth::can('settings.manage')): ?><a href="<?=url('settings/index')?>" class="<?=$sec==='settings'?'on':''?>"><?=ico('gear')?>Settings</a><?php endif; ?>
</nav>
<div class="me"><div class="av"><?=e(strtoupper(substr($u['name'],0,1)))?></div><div><b><?=e($u['name'])?></b><small><?=e(Auth::ROLES[$u['role']])?></small></div></div>
<button type="button" class="sidelink" id="offopen"><?=ico('menu')?>Work offline<span id="offcount" hidden></span></button>
<a class="sidelink" href="<?=url('auth/password')?>"><?=ico('key')?>Change password</a>
<form method="post" action="<?=url('auth/logout')?>"><?=Csrf::field()?><button class="sidelink"><?=ico('out')?>Sign out</button></form>
</aside>
<div class="scrim" id="scrim"></div>
<main class="main"><header class="top"><button class="burger" id="burger" aria-label="Menu"><?=ico('menu')?></button><span class="crumb"><?=e($title??'')?></span></header>
<div class="page">
<?php foreach(flash() as [$t,$m]): ?><div class="alert <?=e($t)?>"><?=$m?></div><?php endforeach; ?>
<?=$content?></div></main></div>
<script src="<?=asset('js/app.js')?>"></script><script src="<?=asset('js/queue.js')?>"></script><script src="<?=asset('js/offline.js')?>"></script></body></html>
