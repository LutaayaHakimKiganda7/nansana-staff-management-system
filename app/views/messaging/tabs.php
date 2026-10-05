<?php $tabs=[]; if(Auth::can('messaging.send')) $tabs['compose']='Send message'; if(Auth::can('messaging.log')) $tabs['log']='Message log';
if(Auth::can('messaging.configure')){ $tabs['templates']='Templates'; $tabs['email']='Email setup'; $tabs['sms']='SMS setup'; } ?>
<div class="tabs" style="margin-bottom:1.25rem"><?php foreach($tabs as $k=>$l): ?><a class="tablink <?=$tab===$k?'on':''?>" href="<?=url('messaging/'.$k)?>"><?=e($l)?></a><?php endforeach; ?></div>
