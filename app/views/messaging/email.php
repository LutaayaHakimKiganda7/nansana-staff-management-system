<?php $title='Email setup'; $tab='email'; require __DIR__.'/tabs.php'; $v=fn($k,$d='')=>e($c[$k]??$d); $enc=$c['encryption']??'ssl'; ?>
<h1>Email setup</h1><p class="muted">Emails are sent through your SMTP mailbox with PHPMailer.</p>
<?php if(!$mailer): ?><div class="alert warn"><b>PHPMailer is not installed yet.</b> Run <code>composer require phpmailer/phpmailer</code> in the project folder, or download PHPMailer and copy its <code>src</code> folder to <code>app/lib/PHPMailer/src</code>.</div><?php endif; ?>
<div class="card narrow"><form method="post" action="<?=url('messaging/saveemail')?>" class="form"><?=Csrf::field()?>
<label class="check"><input type="checkbox" name="enabled" value="1" <?=!empty($c['enabled'])?'checked':''?>> Email sending is enabled</label>
<div class="row"><button type="button" class="btn" id="hostinger">Fill Hostinger settings</button><small>smtp.hostinger.com, port 465, SSL</small></div>
<label>SMTP host<input name="host" id="host" value="<?=$v('host')?>" placeholder="smtp.hostinger.com"></label>
<div class="grid"><label>Port<input type="number" name="port" id="port" value="<?=$v('port')?>" placeholder="465"></label>
<label>Encryption<select name="encryption" id="enc"><option value="ssl" <?=$enc==='ssl'?'selected':''?>>SSL/TLS (port 465)</option><option value="tls" <?=$enc==='tls'?'selected':''?>>STARTTLS (port 587)</option><option value="none" <?=$enc==='none'?'selected':''?>>None</option></select></label></div>
<label>Username<input name="username" value="<?=$v('username')?>" autocomplete="off" placeholder="the full mailbox address"></label>
<label>Password<input type="password" name="password" autocomplete="new-password" placeholder="<?=$hasPw?'Saved. Leave blank to keep it':''?>"></label>
<label>From address<input type="email" name="from_email" value="<?=$v('from_email')?>"><small>On Hostinger this should be the same mailbox as the username.</small></label>
<label>From name<input name="from_name" value="<?=$v('from_name',setting('system_name'))?>"></label>
<label>Reply-to (optional)<input type="email" name="reply_to" value="<?=$v('reply_to')?>"></label>
<button class="btn primary">Save email settings</button></form></div>
<div class="card narrow"><h2>Send a test email</h2><form method="post" action="<?=url('messaging/testemail')?>" class="form"><?=Csrf::field()?><label>Send to<input type="email" name="to" required></label><button class="btn">Send test</button></form></div>
