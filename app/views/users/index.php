<?php $title='Users'; ?>
<div class="row between"><h1>Users</h1><a class="btn primary" href="<?=url('users/form')?>">Add user</a></div>
<form class="filters" method="get"><input type="hidden" name="r" value="users/index">
<input name="q" value="<?=e($q)?>" placeholder="Search name or email">
<select name="role"><option value="">All roles</option><?php foreach(Auth::ROLES as $k=>$v): ?><option value="<?=$k?>" <?=$role===$k?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select>
<button class="btn">Filter</button></form>
<div class="card tablewrap"><table><thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead><tbody>
<?php foreach($users as $x): ?><tr><td data-l="Name"><b><?=e($x['name'])?></b><small><?=e($x['email'])?><?=$x['phone']?' · '.e($x['phone']):''?></small></td>
<td data-l="Role"><?=e(Auth::ROLES[$x['role']])?></td><td data-l="Status"><span class="pill <?=$x['status']?>"><?=$x['status']==='active'?'Active':'Disabled'?></span></td>
<td data-l="Last sign-in"><?=$x['last_login']?e($x['last_login']):'Never'?></td>
<td class="act"><a href="<?=url('users/form',['id'=>$x['id']])?>">Edit</a>
<form method="post" action="<?=url('users/reset')?>" data-confirm="Reset this user's password? A new temporary password will be shown once."><?=Csrf::field()?><input type="hidden" name="id" value="<?=$x['id']?>"><button class="link">Reset password</button></form></td></tr>
<?php endforeach; if(!$users): ?><tr><td colspan="5" class="empty">No users match your search.</td></tr><?php endif; ?></tbody></table></div>
