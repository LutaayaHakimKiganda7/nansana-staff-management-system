<?php $title='Verification'; $names=['awaiting'=>'Awaiting verification','pending'=>'Biometrics needed','verified'=>'Verified']; ?>
<h1>Teacher verification</h1><p class="muted">Only the System Admin captures biometrics and verifies teachers.</p>
<p><a class="btn" href="<?=url('biometrics/quarterly')?>">Quarterly biometric checks</a></p>
<div class="tabs" style="margin-bottom:1rem"><?php foreach($names as $k=>$n): ?><a class="tablink <?=$stage===$k?'on':''?>" href="<?=url('biometrics/index',['stage'=>$k,'q'=>$q])?>"><?=e($n)?> (<?=$counts[$k]?>)</a><?php endforeach; ?></div>
<form class="filters" method="get"><input type="hidden" name="r" value="biometrics/index"><input type="hidden" name="stage" value="<?=e($stage)?>"><input name="q" value="<?=e($q)?>" placeholder="Search name or registration number"><button class="btn">Search</button></form>
<div class="card tablewrap"><table><thead><tr><th>Teacher</th><th>School</th><th>Fingerprints</th><th>Face</th><th></th></tr></thead><tbody>
<?php foreach($rows as $t): ?><tr><td data-l="Teacher"><div class="who"><?=avatar($t)?><div><a href="<?=url('ledger/profile',['id'=>$t['id']])?>"><b><?=e(fullname($t))?></b><?=verified_tick($t)?></a><small><?=e($t['registration_no'])?></small></div></div></td>
<td data-l="School"><?=e($t['school_name'])?></td><td data-l="Fingerprints"><?=$t['fp']?>/10</td><td data-l="Face"><?=$t['face']?'Captured':'Missing'?></td>
<td class="act"><a href="<?=url('biometrics/capture',['id'=>$t['id']])?>">Capture</a> <?php if($t['biometrics_status']==='captured'): ?><a href="<?=url('biometrics/verify',['id'=>$t['id']])?>"><?=$t['verified']?'View':'Verify'?></a><?php endif; ?></td></tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="5" class="empty">No teachers in this list.</td></tr><?php endif; ?></tbody></table></div><?=pager($page,$pages,'biometrics/index',$f)?>
