<?php $title='Change password'; ?>
<div class="card narrow"><h2>Change password</h2>
<?php if(!empty($forced)): ?><p class="muted">Set a new password before you continue.</p><?php endif; ?>
<form method="post" class="form"><?=Csrf::field()?>
<label>Current password<input type="password" name="current" required></label>
<label>New password<input type="password" name="new" minlength="10" required><small>At least 10 characters.</small></label>
<label>Confirm new password<input type="password" name="confirm" minlength="10" required></label>
<button class="btn primary">Save password</button></form></div>
