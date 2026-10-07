<?php $title='Retirement'; $over=fn($t)=>$t['retirement_year']<$GLOBALS['y0']; ?>
<div class="row between"><div><h1>Retirement</h1><p class="muted"><?=count($rows)?> active teachers due to retire by the end of <?=$year?></p></div>
<form method="get" class="filters" style="margin:0"><input type="hidden" name="r" value="retirement/index"><select name="year" data-autosubmit><?php for($y=$y0;$y<=$y0+10;$y++): ?><option <?=$y==$year?'selected':''?>><?=$y?></option><?php endfor; ?></select></form></div>
<div class="card tablewrap"><table><thead><tr><th>Teacher</th><th>School</th><th>Date of birth</th><th>Retirement date</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($rows as $t): ?><tr><td data-l="Teacher"><div class="who"><?=avatar($t)?><div><a href="<?=url('ledger/profile',['id'=>$t['id']])?>"><b><?=e(fullname($t))?></b><?=verified_tick($t)?></a><small><?=e($t['registration_no'])?></small></div></div></td>
<td data-l="School"><?=e($t['school_name'])?></td><td data-l="Date of birth"><?=fdate($t['date_of_birth'])?></td><td data-l="Retirement date"><?=fdate($t['retire_date'])?></td>
<td data-l="Status"><?php if($t['retire_date']<date('Y-m-d')): ?><span class="pill disabled">Overdue</span><?php else: ?><span class="pill warnp">Upcoming</span><?php endif; ?><?=$t['pending']?' <span class="pill">Request pending</span>':''?></td>
<td class="act"><?php if(can_request('retirement') && !$t['pending']): ?><a href="<?=url('movements/create',['type'=>'retirement','teacher_id'=>$t['id']])?>">Start retirement</a><?php endif; ?></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="6" class="empty">No teachers are due to retire by then.</td></tr><?php endif; ?></tbody></table></div>
