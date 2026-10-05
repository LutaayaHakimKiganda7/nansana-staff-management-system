<?php $title='Audit log'; $qs=fn($p)=>url('audit/index',array_filter($f+['page'=>$p])); ?>
<div class="row between"><h1>Audit log</h1><span class="muted"><?=number_format($total)?> entries</span></div>
<form class="filters" method="get"><input type="hidden" name="r" value="audit/index">
<input name="q" value="<?=e($f['q'])?>" placeholder="Search user, record or IP">
<select name="action"><option value="">All actions</option><?php foreach($actions as $a): ?><option <?=$f['action']===$a?'selected':''?> value="<?=e($a)?>"><?=e(str_replace('_',' ',$a))?></option><?php endforeach; ?></select>
<input type="date" name="from" value="<?=e($f['from'])?>" aria-label="From"><input type="date" name="to" value="<?=e($f['to'])?>" aria-label="To"><button class="btn">Filter</button></form>
<div class="card tablewrap"><table><thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Changes</th><th>IP</th></tr></thead><tbody>
<?php foreach($rows as $a): ?><tr><td data-l="When"><?=e($a['created_at'])?></td><td data-l="User"><?=e($a['user_name'])?></td>
<td data-l="Action"><span class="dot <?=e($a['action'])?>"></span> <?=e(str_replace('_',' ',$a['action']))?></td>
<td data-l="Record"><?=e($a['entity'])?> <?=e($a['entity_id'])?></td>
<td data-l="Changes"><?php if($a['new_values']): $o=json_decode($a['old_values']??'[]',true)?:[]; foreach(json_decode($a['new_values'],true) as $k=>$nv): ?><small><?=e($k)?>: <?=isset($o[$k])?'<s>'.e($o[$k]).'</s> → ':''?><b><?=e(is_array($nv)?json_encode($nv):$nv)?></b></small><?php endforeach; endif; ?></td>
<td data-l="IP"><?=e($a['ip'])?></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="6" class="empty">No entries match these filters.</td></tr><?php endif; ?></tbody></table></div>
<div class="pager"><?php if($page>1): ?><a class="btn" href="<?=$qs($page-1)?>">Previous</a><?php endif; ?><span>Page <?=$page?> of <?=$pages?></span><?php if($page<$pages): ?><a class="btn" href="<?=$qs($page+1)?>">Next</a><?php endif; ?></div>
