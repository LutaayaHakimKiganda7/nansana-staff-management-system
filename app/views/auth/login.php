<h2>Sign in</h2><p class="muted">Use the account issued by your system administrator.</p>
<form method="post" class="form" autocomplete="on"><?=Csrf::field()?>
<label>Email<input type="email" name="email" value="<?=old('email')?>" required autofocus></label>
<label>Password<input type="password" name="password" required></label>
<button class="btn primary wide">Sign in</button></form>
