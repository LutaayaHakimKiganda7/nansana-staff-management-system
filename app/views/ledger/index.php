<?php $title='Teacher ledger'; $route='ledger/index'; $S=fn($l,$c)=>sort_link($l,$c,$sort,$dir,'ledger/index',$f); ?>
<div class="row between"><div><h1>Teacher ledger</h1><p class="muted"><?=number_format($total)?> teachers</p></div>
<a class="btn" href="<?=url('ledger/export',array_filter($f))?>">Export CSV</a></div>
<?php require __DIR__.'/../partials/tfilters.php'; ?>
<div class="card tablewrap"><table><thead><tr><th><?=$S('Teacher','name')?></th><th><?=$S('Designation','designation')?></th><th><?=$S('Current school','school')?></th><th><?=$S('Time at school','tenure')?></th><th><?=$S('Retires','retire')?></th><th><?=$S('Payroll','payroll')?></th></tr></thead><tbody>
<?php foreach($rows as $t): ?><tr><td data-l="Teacher"><div class="who"><?=avatar($t)?><div><a href="<?=url('ledger/profile',['id'=>$t['id']])?>"><b><?=e(fullname($t))?></b></a><small><?=e($t['registration_no'])?><?=$t['verified']?' · Verified':''?></small></div></div></td>
<td data-l="Designation"><?=e($t['designation'])?></td><td data-l="School"><?=e($t['school_name']?:'—')?><small><?=e(ucfirst((string)$t['school_type']))?></small></td>
<td data-l="Time at school"><?=e(tenure($t['school_since']))?></td><td data-l="Retires"><?=e($t['retirement_year'])?></td>
<td data-l="Payroll"><span class="pill <?=$t['payroll_status']==='on_payroll'?'active':'disabled'?>"><?=$t['payroll_status']==='on_payroll'?'On':'Off'?></span></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="6" class="empty">No teachers match these filters.</td></tr><?php endif; ?></tbody></table></div>
<?=pager($page,$pages,$route,$f)?>
