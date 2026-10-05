<?php $title='Backups'; ?>
<div class="row between"><div><h1>Backups</h1><p class="muted">Database backups kept on the server (newest 14). Download copies and store them somewhere safe.</p></div>
<form method="post" action="<?=url('system/run')?>"><?=Csrf::field()?><button class="btn primary">Back up now</button></form></div>
<div class="alert warn">Also keep a separate copy of <code>storage/app.key</code>. Without it, encrypted passwords, API keys and biometric samples in a restored backup cannot be read. Do not store the key in the same place as the backups.</div>
<div class="card tablewrap"><table><thead><tr><th>File</th><th>Created</th><th>Size</th><th></th></tr></thead><tbody>
<?php foreach($files as $f): ?><tr><td data-l="File"><?=e($f['name'])?></td><td data-l="Created"><?=date('d M Y H:i',$f['time'])?></td><td data-l="Size"><?=number_format($f['size']/1024,0)?> KB</td><td class="act"><a href="<?=url('system/download',['file'=>$f['name']])?>">Download</a></td></tr>
<?php endforeach; if(!$files): ?><tr><td colspan="4" class="empty">No backups yet.</td></tr><?php endif; ?></tbody></table></div>
<p class="muted">To restore, import the file in phpMyAdmin (it accepts .sql.gz). Schedule a daily backup with the cron job <code>php cron/backup.php</code>.</p>
