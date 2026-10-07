<?php
$quarterly=$quarterly??false; $period=$period??null; $cycle=$cycle??null; $title=$quarterly?'Quarterly biometric check':'Capture biometrics';
$S=BiometricsController::SLOTS; $img=fn($id)=>url($quarterly?'biometrics/quarterlyimage':'biometrics/image',['id'=>$id]);
$imagePath=url($quarterly?'biometrics/quarterlyimage':'biometrics/image').'&id=';
$fp=count(array_diff_key($samples,['face'=>1])); $locked=$quarterly&&!empty($cycle['verified_at']);
$hasConsent=$quarterly?!empty($cycle['consented_at']):!empty($t['biometric_consent_at']);
?>
<div class="row between"><a href="<?=url($quarterly?'biometrics/quarterly':'biometrics/index')?>">← Back</a>
<?php if($quarterly&&$fp>=BiometricsController::MIN_FP&&isset($samples['face'])): ?><a class="btn primary" href="<?=url('biometrics/quarterlyverify',['id'=>$t['id']])?>">Review quarterly verification</a>
<?php elseif(!$quarterly&&$t['biometrics_status']==='captured'): ?><a class="btn primary" href="<?=url('biometrics/verify',['id'=>$t['id']])?>">Go to verification</a><?php endif; ?></div>
<h1><?=e(fullname($t))?><?=verified_tick($t)?></h1><p class="muted"><?=e($t['registration_no'])?> · <?=e($t['school_name'])?><?php if($quarterly): ?> · Quarter <?=$period['quarter']?>, <?=$period['year']?><?php endif; ?></p>
<?php if($locked): ?><div class="alert ok">Quarter <?=$period['quarter']?> <?=$period['year']?> was verified on <?=fdate($cycle['verified_at'])?>. These captures are locked for the quarter.</div>
<?php else: ?>
<div id="bio" data-teacher="<?=$t['id']?>" data-csrf="<?=e(Csrf::token())?>" data-save="<?=url($quarterly?'biometrics/quarterlysave':'biometrics/save')?>" data-remove="<?=url($quarterly?'biometrics/quarterlyremove':'biometrics/remove')?>" data-image="<?=$imagePath?>" data-min="<?=BiometricsController::MIN_FP?>">
<section class="card"><?php if($hasConsent): ?><p class="ok">Consent recorded for <?= $quarterly?'Q'.$period['quarter'].' '.$period['year']:fdate($t['biometric_consent_at']) ?>.</p><input type="checkbox" id="consent" checked hidden>
<?php else: ?><label class="check"><input type="checkbox" id="consent"> The teacher has been told why their fingerprints and photo are collected and has agreed to this <?= $quarterly?'quarterly':'biometric' ?> check.</label><?php endif; ?>
<p class="muted" id="summary">Fingerprints: <b id="nfp"><?=$fp?></b> of 10 (at least <?=BiometricsController::MIN_FP?> needed) · Face: <b id="nface"><?=isset($samples['face'])?'captured':'missing'?></b></p><div id="msg" class="alert" hidden></div></section>
<section class="card"><h2>Face</h2><div class="faceblk"><div><video id="cam" autoplay playsinline muted hidden></video><img id="faceimg" class="facepic" alt="" <?=isset($samples['face'])?'src="'.$img($samples['face']['id']).'"':'hidden'?>></div>
<div class="row"><button type="button" class="btn" id="camstart">Start camera</button><button type="button" class="btn primary" id="snap" hidden>Take photo</button><label class="btn">Upload photo<input type="file" id="facefile" accept="image/jpeg,image/png" hidden></label></div></div></section>
<section class="card"><h2>Fingerprints</h2><p class="muted" id="scannernote">No scanner connected. Upload a fingerprint image for each finger. The desktop app (Phase 8) adds direct scanner capture.</p>
<div class="fingers"><?php foreach($S as $k=>$l): $has=isset($samples[$k]); ?><div class="finger <?=$has?'done':''?>" data-slot="<?=$k?>"><b><?=e($l)?></b><img alt="" <?=$has?'src="'.$img($samples[$k]['id']).'"':'hidden'?>><small class="st"><?=$has?'Captured':'Empty'?></small>
<div class="row"><button type="button" class="btn scan" hidden>Scan</button><label class="btn">Upload<input type="file" accept="image/jpeg,image/png,image/bmp" hidden></label><button type="button" class="link del" <?=$has?'':'hidden'?>>Remove</button></div></div><?php endforeach; ?></div></section></div>
<script src="<?=asset('js/biometrics.js')?>"></script><?php endif; ?>
