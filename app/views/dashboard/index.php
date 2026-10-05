<?php $title='Dashboard'; $card=fn($l,$n,$sub='',$cls='',$href=null)=>'<'.($href?'a href="'.$href.'"':'div').' class="stat '.$cls.'"><span>'.$l.'</span><b>'.number_format($n).'</b><small>'.$sub.'</small></'.($href?'a':'div').'>'; ?>
<h1>Welcome, <?=e(explode(' ',Auth::user()['name'])[0])?></h1><p class="muted"><?=e(setting('municipality'))?>, <?=e(setting('district'))?></p>
<div class="stats">
<?=$card('Schools',$s['schools'],$s['primary'].' primary · '.$s['secondary'].' secondary','',url('schools/index'))?>
<?=$card('Teachers',$s['teachers'],$s['on_payroll'].' on payroll · '.$s['off_payroll'].' off','',url('ledger/index'))?>
<?=$card('Verified teachers',$s['verified'],$s['unverified'].' awaiting verification','',url('ledger/index',['verified'=>1]))?>
<?=$card('Retiring in '.$y,$s['retire_year'],$s['retire_next'].' more in '.($y+1),$s['retire_year']?'warn':'',url('ledger/index',['sort'=>'retire']))?>
</div>
<div class="cols"><section class="card"><div class="row between"><h2>Retiring soon</h2><a href="<?=url('ledger/index',['sort'=>'retire'])?>">See all</a></div>
<?php if(!$retiring): ?><p class="empty">No teachers retire in <?=$y?> or <?=$y+1?>.</p><?php else: ?><ul class="feed"><?php foreach($retiring as $t): ?><li><?=avatar($t)?><div><a href="<?=url('ledger/profile',['id'=>$t['id']])?>"><b><?=e(fullname($t))?></b></a><small><?=e($t['school_name'])?> · retires <?=e($t['retirement_year'])?></small></div></li><?php endforeach; ?></ul><?php endif; ?></section>
<?php if(Auth::can('audit.view')): ?><section class="card"><div class="row between"><h2>Recent activity</h2><a href="<?=url('audit/index')?>">Audit log</a></div>
<?php if(!$recent): ?><p class="empty">No activity yet.</p><?php else: ?><ul class="feed"><?php foreach($recent as $a): ?><li><span class="dot <?=e($a['action'])?>"></span><div><b><?=e($a['user_name'])?></b> <?=e(str_replace('_',' ',$a['action']))?><small><?=e($a['created_at'])?></small></div></li><?php endforeach; ?></ul><?php endif; ?></section><?php endif; ?></div>
<?php if(Auth::can('audit.view') && $s['failed']): ?><div class="alert warn"><?=$s['failed']?> failed sign-in attempt(s) in the last 24 hours. <a href="<?=url('audit/index',['action'=>'login_failed'])?>">Review</a></div><?php endif; ?>
<?php if(Auth::can('movements.approve') && $s['pending']): ?><div class="alert warn"><?=$s['pending']?> request(s) waiting for approval. <a href="<?=url('movements/index')?>">Review</a></div><?php endif; ?>
<?php if(Auth::can('biometrics.manage') && $s['awaiting']): ?><div class="alert warn"><?=$s['awaiting']?> teacher(s) have biometrics captured and are awaiting verification. <a href="<?=url('biometrics/index')?>">Verify now</a></div><?php endif; ?>
