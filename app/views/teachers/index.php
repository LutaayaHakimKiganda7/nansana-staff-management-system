<?php $title='Teachers'; $route='teachers/index'; ?>
<div class="row between"><div><h1>Teachers</h1><p class="muted"><?=number_format($total)?> records</p></div><div class="row"><?php if(Auth::can('import.manage')): ?><a class="btn" href="<?=url('import/index')?>">Import CSV</a><?php endif; ?><?php if(Auth::can('teachers.manage')): ?><a class="btn primary" href="<?=url('teachers/form')?>">Register teacher</a><?php endif; ?></div></div>
<?php require __DIR__.'/../partials/tfilters.php'; ?>
<div class="card tablewrap"><table><thead><tr><th>Teacher</th><th>Reg. no</th><th>School</th><th>Designation</th><th>Payroll</th><th></th></tr></thead><tbody>
<?php foreach($rows as $t): ?><tr><td data-l="Teacher"><div class="who"><?=avatar($t)?><div><a href="<?=url('ledger/profile',['id'=>$t['id']])?>"><b><?=e(fullname($t))?></b><?=verified_tick($t)?></a><small><?=e($t['contact'])?></small></div></div></td>
<td data-l="Reg. no"><?=e($t['registration_no'])?></td><td data-l="School"><?=e($t['school_name']?:'—')?></td><td data-l="Designation"><?=e($t['designation'])?></td>
<td data-l="Payroll"><span class="pill <?=$t['payroll_status']==='on_payroll'?'active':'disabled'?>"><?=$t['payroll_status']==='on_payroll'?'On payroll':'Off payroll'?></span><?php if($t['termination_status']!=='active'): ?> <span class="pill"><?=ucfirst($t['termination_status'])?></span><?php endif; ?></td>
<td class="act"><a href="<?=url('ledger/profile',['id'=>$t['id']])?>">Open</a><?php if(Auth::can('teachers.manage')): ?> <a href="<?=url('teachers/form',['id'=>$t['id']])?>">Edit</a><?php endif; ?></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="6" class="empty">No teachers match. <?=Auth::can('teachers.manage')?'Register the first teacher to begin.':''?></td></tr><?php endif; ?></tbody></table></div>
<?=pager($page,$pages,$route,$f)?>
