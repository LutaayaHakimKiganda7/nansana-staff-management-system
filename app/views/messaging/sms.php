<?php $title='SMS setup'; $tab='sms'; require __DIR__.'/tabs.php'; $v=fn($k,$d='')=>e($c[$k]??$d); $am=$c['auth_mode']??'bearer'; ?>
<h1>SMS setup (LANASMS)</h1><p class="muted">Paste your API key, then copy the endpoint, authentication method and field names from the LANASMS API documentation (docs.lanasms.com).</p>
<div class="card narrow"><form method="post" action="<?=url('messaging/savesms')?>" class="form"><?=Csrf::field()?>
<label class="check"><input type="checkbox" name="enabled" value="1" <?=!empty($c['enabled'])?'checked':''?>> SMS sending is enabled</label>
<label>API key<input type="password" name="api_key" autocomplete="new-password" placeholder="<?=$hasKey?'Saved. Leave blank to keep it':'Paste your API key'?>"></label>
<label>Sender ID (optional)<input name="sender_id" value="<?=$v('sender_id')?>" maxlength="20"></label>
<h2 style="margin-top:.5rem">API connection</h2>
<label>API base URL<input name="base_url" value="<?=$v('base_url')?>" placeholder="https://..."></label>
<label>Send-SMS path<input name="send_path" value="<?=$v('send_path')?>" placeholder="/..."></label>
<div class="grid"><label>Request method<select name="http_method"><option>POST</option><option <?=($c['http_method']??'')==='GET'?'selected':''?>>GET</option></select></label>
<label>Body format<select name="body_format"><option value="json">JSON</option><option value="form" <?=($c['body_format']??'')==='form'?'selected':''?>>Form fields</option></select></label>
<label>API key is sent as<select name="auth_mode"><option value="bearer" <?=$am==='bearer'?'selected':''?>>Authorization: Bearer header</option><option value="header" <?=$am==='header'?'selected':''?>>Custom header</option><option value="query" <?=$am==='query'?'selected':''?>>URL parameter</option><option value="body" <?=$am==='body'?'selected':''?>>Body field</option></select></label>
<label>Header or parameter name<input name="auth_name" value="<?=$v('auth_name')?>" placeholder="only if not Bearer"></label></div>
<div class="grid"><label>Recipient field name<input name="field_recipient" value="<?=$v('field_recipient')?>" placeholder="e.g. to"></label><label>Message field name<input name="field_message" value="<?=$v('field_message')?>" placeholder="e.g. message"></label>
<label>Sender field name<input name="field_sender" value="<?=$v('field_sender')?>" placeholder="only if Sender ID is used"></label></div>
<label>Extra fixed fields (JSON, optional)<input name="extra_fields" value="<?=$v('extra_fields')?>" placeholder='{"type":"plain"}'></label>
<label>Success text in the response (optional)<input name="success_match" value="<?=$v('success_match')?>"><small>Leave empty to treat any HTTP 2xx reply as success.</small></label>
<h2 style="margin-top:.5rem">Phone numbers</h2>
<div class="grid"><label>Country code<input name="country_code" value="<?=$v('country_code','256')?>"></label><label class="check" style="align-self:end"><input type="checkbox" name="plus" value="1" <?=($c['plus']??'')==='1'?'checked':''?>> Send numbers with a leading +</label></div>
<small>07XX numbers are converted to 2567XX before sending.</small>
<button class="btn primary">Save SMS settings</button></form></div>
<div class="card narrow"><h2>Send a test SMS</h2><form method="post" action="<?=url('messaging/testsms')?>" class="form"><?=Csrf::field()?><label>Phone number<input name="to" placeholder="07XX XXX XXX" required></label><button class="btn">Send test</button></form></div>
