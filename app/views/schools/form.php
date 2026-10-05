<?php $title=$school?'Edit school':'Register school'; $v=fn($k)=>isset($_SESSION['old'][$k])?old($k):e($school[$k]??''); $type=$_SESSION['old']['type']??($school['type']??'primary'); ?>
<div class="card narrow"><h2><?=$title?></h2><form method="post" action="<?=url('schools/save')?>" class="form"><?=Csrf::field()?><input type="hidden" name="id" value="<?=e($school['id']??0)?>">
<label>School name<input name="name" value="<?=$v('name')?>" required></label>
<label>Type<select name="type"><option value="primary" <?=$type==='primary'?'selected':''?>>Primary</option><option value="secondary" <?=$type==='secondary'?'selected':''?>>Secondary</option></select></label>
<label>Location<input name="location" value="<?=$v('location')?>" placeholder="Parish / ward / division"></label>
<label>P.O. Box<input name="po_box" value="<?=$v('po_box')?>"></label>
<label>UNEB centre number<input name="uneb_centre_no" value="<?=$v('uneb_centre_no')?>"></label>
<label>EMIS code<input name="emis_code" value="<?=$v('emis_code')?>"></label>
<?php if($school): ?><label>Status<select name="status"><option value="active" <?=$school['status']==='active'?'selected':''?>>Active</option><option value="inactive" <?=$school['status']==='inactive'?'selected':''?>>Inactive</option></select></label><?php endif; ?>
<div class="row"><button class="btn primary">Save school</button><a class="btn" href="<?=url('schools/index')?>">Cancel</a></div></form></div>
