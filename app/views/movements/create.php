<?php $title=MOVE_TYPES[$type]; ?>
<div class="card narrow"><h2><?=e(MOVE_TYPES[$type])?> request</h2>
<?php if(!$t): ?><p class="muted">Find the teacher first.</p>
<form method="get" class="filters"><input type="hidden" name="r" value="movements/create"><input type="hidden" name="type" value="<?=e($type)?>"><input name="q" value="<?=e($q)?>" placeholder="Name, registration number or NIN" autofocus><button class="btn">Search</button></form>
<ul class="feed"><?php foreach($results as $r): ?><li><?=avatar($r)?><div><a href="<?=url('movements/create',['type'=>$type,'teacher_id'=>$r['id']])?>"><b><?=e(fullname($r))?></b><?=verified_tick($r)?></a><small><?=e($r['registration_no'])?> · <?=e($r['school_name'])?></small></div></li><?php endforeach; if($q!==''&&!$results): ?><li class="empty">No active teacher found.</li><?php endif; ?></ul>
<?php else: ?>
<div class="who" style="margin-bottom:1rem"><?=avatar($t)?><div><b><?=e(fullname($t))?></b><?=verified_tick($t)?><small><?=e($t['registration_no'])?> · <?=e($t['school_name'])?> · <?=$t['payroll_status']==='on_payroll'?'On payroll':'Off payroll'?></small></div></div>
<?php if($problem): ?><div class="alert warn"><?=e($problem)?></div><a class="btn" href="<?=url('ledger/profile',['id'=>$t['id']])?>">Back to profile</a>
<?php else: $eff=$type==='retirement'?date('Y-m-d',strtotime($t['date_of_birth'].' +'.$t['retirement_age'].' years')):date('Y-m-d'); ?>
<form method="post" action="<?=url('movements/save')?>" class="form"><?=Csrf::field()?><input type="hidden" name="type" value="<?=e($type)?>"><input type="hidden" name="teacher_id" value="<?=$t['id']?>">
<?php if($type==='transfer'): ?><label>Move to school<select name="to_school_id" required><option value="">Choose school</option><?php foreach($schools as $s): if($s['id']!=$t['school_id']): ?><option value="<?=$s['id']?>"><?=e($s['name'])?> (<?=$s['type']?>)</option><?php endif; endforeach; ?></select></label>
<label>New designation <small>Leave empty to keep "<?=e($t['designation'])?>"</small><input name="new_designation"></label><?php endif; ?>
<label>Effective date<input type="date" name="effective_date" value="<?=e($eff)?>" required><?php if($type==='retirement'): ?><small>Set to the retirement age birthday. Change it if the official date differs.</small><?php endif; ?></label>
<label>Reason<?=in_array($type,['transfer','retirement'])?' (optional)':''?><input name="reason" <?=in_array($type,['transfer','retirement'])?'':'required'?>></label>
<div class="row"><button class="btn primary">Submit for approval</button><a class="btn" href="<?=url('ledger/profile',['id'=>$t['id']])?>">Cancel</a></div></form><?php endif; ?><?php endif; ?></div>
