<?php $title=$t?'Edit teacher':'Register teacher'; $O=$_SESSION['old']??[];
$v=fn($k,$d='')=>e($O[$k]??($t[$k]??$d));
$kinRows=[]; for($i=0;$i<3;$i++){ $kinRows[]=isset($O['kin_name'])?['name'=>$O['kin_name'][$i]??'','relationship'=>$O['kin_rel'][$i]??'','contact'=>$O['kin_contact'][$i]??'','nin'=>$O['kin_nin'][$i]??'']:($kin[$i]??['name'=>'','relationship'=>'','contact'=>'','nin'=>'']); } ?>
<h1><?=$title?></h1><?php if($t): ?><p class="muted"><?=e(fullname($t))?> · <?=e($t['registration_no'])?></p><?php endif; ?>
<form method="post" action="<?=url('teachers/save')?>" enctype="multipart/form-data" class="tform"><?=Csrf::field()?><input type="hidden" name="id" value="<?=e($t['id']??0)?>"><input type="hidden" name="base_updated" value="<?=e($t['updated_at']??'')?>">
<div class="tabs" role="tablist"><button type="button" class="on" data-tab="a">Personal</button><button type="button" data-tab="b">Employment</button><button type="button" data-tab="c">IDs</button><button type="button" data-tab="d">Next of kin</button><button type="button" data-tab="e">Teaching</button></div>
<section class="card tab on" data-pane="a"><div class="grid">
<label>Surname<input name="surname" value="<?=$v('surname')?>" required></label><label>First name<input name="first_name" value="<?=$v('first_name')?>" required></label>
<label>Date of birth<input type="date" id="dob" name="date_of_birth" value="<?=$v('date_of_birth')?>" required></label><label>Contact<input name="contact" value="<?=$v('contact')?>" placeholder="07XX XXX XXX"></label><label>Email<input type="email" name="email" value="<?=$v('email')?>"></label>
<label>Health status<input name="health_status" value="<?=$v('health_status')?>"></label>
<label>Profile picture<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG or WebP, up to 3 MB.</small></label></div></section>
<section class="card tab" data-pane="b"><div class="grid">
<label>Registration number<input name="registration_no" value="<?=$v('registration_no')?>" required></label><label>File number<input name="file_no" value="<?=$v('file_no')?>"></label>
<label>Date file opened<input type="date" name="date_opened" value="<?=$v('date_opened')?>"></label><label>Date employed<input type="date" name="date_employed" value="<?=$v('date_employed')?>" required></label>
<label>Department<input name="department" value="<?=$v('department')?>" list="dept"><datalist id="dept"><option>Primary</option><option>Secondary</option></datalist></label>
<label>Designation<input name="designation" value="<?=$v('designation')?>" list="desig" required><datalist id="desig"><option>Head Teacher</option><option>Deputy Head Teacher</option><option>Director of Studies</option><option>Senior Teacher</option><option>Teacher</option></datalist></label>
<label>Retirement age<input type="number" id="rage" name="retirement_age" min="50" max="70" value="<?=$v('retirement_age','60')?>"></label>
<label>Retirement year<input id="ryear" value="<?=$v('retirement_year')?>" readonly><small>Calculated from date of birth and retirement age.</small></label>
<label>Salary scale<input name="salary_scale" value="<?=$v('salary_scale')?>"></label>
<?php if(!$t): ?><label>Posted to school<select name="school_id" required><option value="">Choose school</option><?php foreach($schools as $s): ?><option value="<?=$s['id']?>" <?=($O['school_id']??'')==$s['id']?'selected':''?>><?=e($s['name'])?> (<?=$s['type']?>)</option><?php endforeach; ?></select></label>
<label>Date posted to this school<input type="date" name="school_since" value="<?=e($O['school_since']??'')?>" required></label>
<label>Payroll status<select name="payroll_status"><option value="on_payroll">On payroll</option><option value="off_payroll" <?=($O['payroll_status']??'')==='off_payroll'?'selected':''?>>Off payroll</option></select></label>
<?php else: ?><p class="muted wide2">School, payroll and employment status change through transfers, payroll and retirement actions (Phase 5).</p><?php endif; ?></div></section>
<section class="card tab" data-pane="c"><div class="grid"><label>NSSF number<input name="nssf_no" value="<?=$v('nssf_no')?>"></label><label>IPPS number<input name="ipps_no" value="<?=$v('ipps_no')?>"></label>
<label>NIN<input name="nin" value="<?=$v('nin')?>" maxlength="14" style="text-transform:uppercase"></label><label>TIN<input name="tin" value="<?=$v('tin')?>"></label></div>
<p class="muted">Biometrics and verification are captured by the System Admin in Phase 7.</p></section>
<section class="card tab" data-pane="d"><p class="muted">At least one next of kin is required.</p><?php foreach($kinRows as $i=>$k): ?><div class="grid kin"><b class="wide2">Next of kin <?=$i+1?></b>
<label>Name<input name="kin_name[]" value="<?=e($k['name'])?>"></label><label>Relationship<input name="kin_rel[]" value="<?=e($k['relationship'])?>"></label>
<label>Contact<input name="kin_contact[]" value="<?=e($k['contact'])?>"></label><label>NIN<input name="kin_nin[]" value="<?=e($k['nin'])?>" maxlength="14" style="text-transform:uppercase"></label></div><?php endforeach; ?></section>
<section class="card tab" data-pane="e"><div class="grid"><label>Subjects<input name="subjects" value="<?=$v('subjects')?>" placeholder="e.g. Mathematics, Physics"></label><label>Classes<input name="classes" value="<?=$v('classes')?>" placeholder="e.g. P5, P6"></label></div></section>
<div class="row" style="margin-top:1.25rem"><button class="btn primary">Save teacher</button><a class="btn" href="<?=$t?url('ledger/profile',['id'=>$t['id']]):url('teachers/index')?>">Cancel</a></div></form>
