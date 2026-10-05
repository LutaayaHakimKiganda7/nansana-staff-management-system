<?php $title='Settings'; ?>
<div class="card narrow"><h2>System settings</h2>
<form method="post" class="form"><?=Csrf::field()?>
<label>System name<input name="system_name" value="<?=e(setting('system_name'))?>" required></label>
<label>Municipality<input name="municipality" value="<?=e(setting('municipality'))?>"></label>
<label>District<input name="district" value="<?=e(setting('district'))?>"></label>
<label>Office email<input type="email" name="contact_email" value="<?=e(setting('contact_email'))?>"></label>
<label>Office phone<input name="contact_phone" value="<?=e(setting('contact_phone'))?>"></label>
<button class="btn primary">Save settings</button></form></div>
