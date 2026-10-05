<?php $title='Send message'; $tab='compose'; require __DIR__.'/tabs.php'; ?>
<h1>Send message</h1><p class="muted">Send an SMS or email to teachers. Teachers without a phone number or email are skipped.</p>
<?php foreach(['sms'=>'SMS','email'=>'Email'] as $k=>$l) if(!$ready[$k]): ?><div class="alert warn"><?=$l?> is not set up yet.<?=Auth::can('messaging.configure')?' <a href="'.url('messaging/'.$k).'">Set it up</a>':' Ask an administrator.'?></div><?php endif; ?>
<div class="card narrow"><form method="post" action="<?=url('messaging/send')?>" class="form" data-confirm="Send this message to the selected teachers now?"><?=Csrf::field()?>
<label>Recipients<select name="school_id"><option value="0">All active teachers</option><?php foreach($schools as $s): ?><option value="<?=$s['id']?>">Teachers at <?=e($s['name'])?></option><?php endforeach; ?></select></label>
<div class="row"><label class="check"><input type="checkbox" name="channel[]" value="sms" checked> SMS</label><label class="check"><input type="checkbox" name="channel[]" value="email"> Email</label></div>
<label>Subject (email only)<input name="subject"></label>
<label>Message<textarea name="body" rows="5" required></textarea><small>You can use {name}, {school} and {municipality}. SMS over 160 characters costs more than one message.</small></label>
<button class="btn primary">Send</button></form></div>
