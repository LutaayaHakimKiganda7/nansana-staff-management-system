<?php $title='Message log'; $tab='log'; require __DIR__.'/tabs.php'; ?>
<div class="row between"><h1>Message log</h1><span class="muted"><?=number_format($total)?> messages</span></div>
<form class="filters" method="get"><input type="hidden" name="r" value="messaging/log"><input name="q" value="<?=e($f['q'])?>" placeholder="Search recipient or teacher">
<select name="channel"><option value="">SMS and email</option><option value="sms" <?=$f['channel']==='sms'?'selected':''?>>SMS</option><option value="email" <?=$f['channel']==='email'?'selected':''?>>Email</option></select>
<select name="status"><option value="">Any status</option><option value="sent" <?=$f['status']==='sent'?'selected':''?>>Sent</option><option value="failed" <?=$f['status']==='failed'?'selected':''?>>Failed</option></select><button class="btn">Filter</button></form>
<div class="card tablewrap"><table><thead><tr><th>When</th><th>To</th><th>Message</th><th>Status</th></tr></thead><tbody>
<?php foreach($rows as $m): ?><tr><td data-l="When"><?=e(substr($m['created_at'],0,16))?><small><?=e($m['sender']?:'System')?></small></td>
<td data-l="To"><?=e($m['recipient'])?><small><?=e(trim(($m['first_name']??'').' '.($m['surname']??'')))?> · <?=strtoupper($m['channel'])?></small></td>
<td data-l="Message"><?=$m['subject']?'<b>'.e($m['subject']).'</b><br>':''?><?=e(mb_strimwidth((string)$m['body'],0,140,'…'))?></td>
<td data-l="Status"><span class="pill <?=$m['status']==='sent'?'active':'disabled'?>"><?=ucfirst($m['status'])?></span><?=$m['error']?'<small>'.e($m['error']).'</small>':''?></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="4" class="empty">No messages yet.</td></tr><?php endif; ?></tbody></table></div><?=pager($page,$pages,'messaging/log',$f)?>
