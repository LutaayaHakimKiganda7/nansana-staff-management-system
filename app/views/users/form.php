<?php $title=$user?'Edit user':'Add user'; $v=fn($k)=>isset($_SESSION['old'][$k])?old($k):e($user[$k]??''); $role=$_SESSION['old']['role']??($user['role']??'viewer'); ?>
<div class="card narrow"><h2><?=$title?></h2>
<form method="post" action="<?=url('users/save')?>" class="form"><?=Csrf::field()?><input type="hidden" name="id" value="<?=e($user['id']??0)?>">
<label>Full name<input name="name" value="<?=$v('name')?>" required></label>
<label>Email<input type="email" name="email" value="<?=$v('email')?>" required></label>
<label>Phone<input name="phone" value="<?=$v('phone')?>" placeholder="07XX XXX XXX"></label>
<label>Role<select name="role"><?php foreach(Auth::ROLES as $k=>$n): ?><option value="<?=$k?>" <?=$role===$k?'selected':''?>><?=e($n)?></option><?php endforeach; ?></select></label>
<?php if($user): ?><label>Status<select name="status"><option value="active" <?=$user['status']==='active'?'selected':''?>>Active</option><option value="disabled" <?=$user['status']==='disabled'?'selected':''?>>Disabled</option></select></label>
<?php else: ?><p class="muted">A temporary password is generated when you save.</p><?php endif; ?>
<div class="row"><button class="btn primary">Save changes</button><a class="btn" href="<?=url('users/index')?>">Cancel</a></div></form></div>
