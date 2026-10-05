<?php $title='Schools'; ?>
<div class="row between"><div><h1>Schools</h1><p class="muted"><?=count($schools)?> shown</p></div><?php if(Auth::can('schools.manage')): ?><a class="btn primary" href="<?=url('schools/form')?>">Register school</a><?php endif; ?></div>
<form class="filters" method="get"><input type="hidden" name="r" value="schools/index"><input name="q" value="<?=e($q)?>" placeholder="Search name, location, EMIS or UNEB number">
<select name="type"><option value="">All types</option><option value="primary" <?=$type==='primary'?'selected':''?>>Primary</option><option value="secondary" <?=$type==='secondary'?'selected':''?>>Secondary</option></select>
<select name="status"><option value="">Any status</option><option value="active" <?=$st==='active'?'selected':''?>>Active</option><option value="inactive" <?=$st==='inactive'?'selected':''?>>Inactive</option></select><button class="btn">Filter</button></form>
<div class="card tablewrap"><table><thead><tr><th>School</th><th>Type</th><th>EMIS</th><th>UNEB centre</th><th>P.O. Box</th><th>Teachers</th><th></th></tr></thead><tbody>
<?php foreach($schools as $s): ?><tr><td data-l="School"><b><?=e($s['name'])?></b><small><?=e($s['location'])?></small></td>
<td data-l="Type"><?=ucfirst($s['type'])?></td><td data-l="EMIS"><?=e($s['emis_code']?:'—')?></td><td data-l="UNEB centre"><?=e($s['uneb_centre_no']?:'—')?></td><td data-l="P.O. Box"><?=e($s['po_box']?:'—')?></td>
<td data-l="Teachers"><a href="<?=url('ledger/index',['school'=>$s['id']])?>"><?=$s['n']?></a></td>
<td class="act"><span class="pill <?=$s['status']?>"><?=ucfirst($s['status'])?></span><?php if(Auth::can('schools.manage')): ?> <a href="<?=url('schools/form',['id'=>$s['id']])?>">Edit</a><?php endif; ?></td></tr>
<?php endforeach; if(!$schools): ?><tr><td colspan="7" class="empty">No schools yet. Register the first one to start posting teachers.</td></tr><?php endif; ?></tbody></table></div>
