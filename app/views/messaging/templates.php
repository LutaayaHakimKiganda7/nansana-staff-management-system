<?php $title='Templates'; $tab='templates'; require __DIR__.'/tabs.php'; ?>
<h1>Message templates</h1><p class="muted">Automatic messages sent when requests are approved. Placeholders: <code>{name}</code> <code>{school}</code> <code>{old_school}</code> <code>{new_school}</code> <code>{date}</code> <code>{municipality}</code> <code>{office_phone}</code></p>
<div class="card narrow"><h2>Retirement reminders</h2><form method="post" action="<?=url('messaging/savenotice')?>" class="row"><?=Csrf::field()?><label>Remind teachers <input type="number" name="days" value="<?=e($days)?>" min="1" max="365" style="width:90px"> days before retirement</label><button class="btn">Save</button></form>
<small>Reminders go out when the daily cron job runs: <code>php cron/retirement_alerts.php</code></small></div>
<?php foreach($rows as $t): ?><details class="card tpl"><summary><b><?=e($t['name'])?></b> <span class="pill"><?=strtoupper($t['channel'])?></span> <?=$t['enabled']?'':'<span class="pill disabled">Off</span>'?></summary>
<form method="post" action="<?=url('messaging/savetemplate')?>" class="form"><?=Csrf::field()?><input type="hidden" name="id" value="<?=$t['id']?>">
<?php if($t['channel']==='email'): ?><label>Subject<input name="subject" value="<?=e($t['subject'])?>"></label><?php endif; ?>
<label>Message<textarea name="body" rows="<?=$t['channel']==='sms'?3:6?>"><?=e($t['body'])?></textarea></label>
<label class="check"><input type="checkbox" name="enabled" value="1" <?=$t['enabled']?'checked':''?>> Send automatically</label><button class="btn primary">Save template</button></form></details>
<?php endforeach; ?>
