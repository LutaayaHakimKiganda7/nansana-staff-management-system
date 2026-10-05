<form class="filters" method="get"><input type="hidden" name="r" value="<?=e($route)?>">
<input name="q" value="<?=e($f['q'])?>" placeholder="Search name, reg. no, NIN, IPPS, file no">
<select name="school"><option value="">All schools</option><?php foreach($schools as $s): ?><option value="<?=$s['id']?>" <?=$f['school']!==''&&$f['school']==$s['id']?'selected':''?>><?=e($s['name'])?></option><?php endforeach; ?></select>
<select name="type"><option value="">Primary and secondary</option><option value="primary" <?=$f['type']==='primary'?'selected':''?>>Primary</option><option value="secondary" <?=$f['type']==='secondary'?'selected':''?>>Secondary</option></select>
<select name="payroll"><option value="">Any payroll status</option><option value="on_payroll" <?=$f['payroll']==='on_payroll'?'selected':''?>>On payroll</option><option value="off_payroll" <?=$f['payroll']==='off_payroll'?'selected':''?>>Off payroll</option></select>
<select name="status"><option value="">Any employment status</option><?php foreach(['active','retired','terminated','deceased'] as $x): ?><option value="<?=$x?>" <?=$f['status']===$x?'selected':''?>><?=ucfirst($x)?></option><?php endforeach; ?></select>
<select name="verified"><option value="">Verified or not</option><option value="1" <?=$f['verified']==='1'?'selected':''?>>Verified</option><option value="0" <?=$f['verified']==='0'?'selected':''?>>Not verified</option></select>
<button class="btn">Filter</button></form>
