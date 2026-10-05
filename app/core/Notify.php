<?php
class Crypt {
    static function key(){
        if($k=cfg('app_key')) return hash('sha256',$k,true);
        $f=__DIR__.'/../../storage/app.key';
        if(!is_file($f)) file_put_contents($f,bin2hex(random_bytes(32)));
        return hex2bin(trim(file_get_contents($f)));
    }
    static function enc($plain){ $iv=random_bytes(12); $c=openssl_encrypt($plain,'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag); return 'enc:'.base64_encode($iv.$tag.$c); }
    static function dec($v){
        if(!is_string($v)||strncmp($v,'enc:',4)) return $v; $raw=base64_decode(substr($v,4));
        $r=openssl_decrypt(substr($raw,28),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16)); return $r===false?'':$r;
    }
}
class Integrations {
    static function raw($p){ $r=DB::val('SELECT config FROM integrations WHERE provider=?',[$p]); return $r?(json_decode($r,true)?:[]):[]; }
    static function get($p){ $c=self::raw($p); foreach($c as $k=>$v) $c[$k]=Crypt::dec($v); return $c; }
    static function has($p,$k){ return !empty(self::raw($p)[$k]); }
    static function enabled($p){ return !empty(self::raw($p)['enabled']); }
    static function save($p,$new,$secrets=[]){
        $old=self::raw($p);
        foreach($secrets as $s){ if(($new[$s]??'')==='') $new[$s]=$old[$s]??''; else $new[$s]=Crypt::enc($new[$s]); }
        DB::q('INSERT INTO integrations (provider,config,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE config=VALUES(config),updated_by=VALUES(updated_by)',[$p,json_encode($new),Auth::user()['id']??null]);
    }
}
class Notify {
    static function fill($text,$vars){ return preg_replace_callback('/\{(\w+)\}/',fn($m)=>$vars[$m[1]]??$m[0],(string)$text); }
    static function phone($n){
        $cc=preg_replace('/\D/','',Integrations::get('sms')['country_code']??'256')?:'256'; $d=preg_replace('/\D/','',$n);
        if(strncmp($d,'00',2)===0) $d=substr($d,2);
        if($d!==''&&$d[0]==='0') $d=$cc.substr($d,1); elseif(strncmp($d,$cc,strlen($cc))!==0 && strlen($d)<=9) $d=$cc.$d;
        return (Integrations::get('sms')['plus']??'')==='1'?'+'.$d:$d;
    }
    static function log($ch,$to,$subj,$body,$ok,$err,$meta){
        DB::q('INSERT INTO message_log (channel,recipient,subject,body,status,error,template_code,teacher_id,user_id) VALUES (?,?,?,?,?,?,?,?,?)',
            [$ch,$to,$subj,$body,$ok?'sent':'failed',$ok?null:substr((string)$err,0,255),$meta['template']??null,$meta['teacher_id']??null,Auth::user()['id']??null]);
    }
    static function loadMailer(){
        if(class_exists('PHPMailer\\PHPMailer\\PHPMailer')) return true;
        $a=__DIR__.'/../../vendor/autoload.php'; if(is_file($a)){ require_once $a; return class_exists('PHPMailer\\PHPMailer\\PHPMailer'); }
        $d=__DIR__.'/../lib/PHPMailer/src/';
        if(is_file($d.'PHPMailer.php')){ require_once $d.'Exception.php'; require_once $d.'PHPMailer.php'; require_once $d.'SMTP.php'; return true; }
        return false;
    }
    static function email($to,$subject,$body,$meta=[]){
        $c=Integrations::get('email');
        if(empty($c['enabled'])) return [false,'Email is not enabled.'];
        if(!self::loadMailer()) return [false,'PHPMailer is not installed.'];
        try{
            $m=new \PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP(); $m->Host=$c['host']; $m->Port=(int)$c['port']; $m->SMTPAuth=true; $m->Username=$c['username']; $m->Password=$c['password'];
            $m->SMTPSecure=['ssl'=>'ssl','tls'=>'tls'][$c['encryption']??'']??''; if(!$m->SMTPSecure) $m->SMTPAutoTLS=false;
            $m->Timeout=20; $m->CharSet='UTF-8';
            $m->setFrom($c['from_email'],$c['from_name']??''); if(!empty($c['reply_to'])) $m->addReplyTo($c['reply_to']);
            $m->addAddress($to); $m->Subject=$subject; $m->isHTML(true);
            $m->Body='<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.5">'.nl2br(htmlspecialchars($body,ENT_QUOTES,'UTF-8')).'</div>'; $m->AltBody=$body;
            $m->send(); self::log('email',$to,$subject,$body,true,null,$meta); return [true,null];
        }catch(Throwable $e){ $err=$e->getMessage(); self::log('email',$to,$subject,$body,false,$err,$meta); return [false,$err]; }
    }
    static function sms($to,$text,$meta=[]){
        $c=Integrations::get('sms');
        if(empty($c['enabled'])||empty($c['api_key'])||empty($c['base_url'])) return [false,'SMS is not configured.'];
        $num=self::phone($to); $data=[($c['field_recipient']?:'to')=>$num,($c['field_message']?:'message')=>$text];
        if(!empty($c['sender_id'])&&!empty($c['field_sender'])) $data[$c['field_sender']]=$c['sender_id'];
        if(!empty($c['extra_fields'])&&is_array($x=json_decode($c['extra_fields'],true))) $data=$x+$data;
        $url=rtrim($c['base_url'],'/').'/'.ltrim($c['send_path']??'','/'); $h=['Accept: application/json']; $an=$c['auth_name']?:'api_key';
        switch($c['auth_mode']??'bearer'){ case 'header': $h[]=$an.': '.$c['api_key']; break; case 'query': $url.=(strpos($url,'?')?'&':'?').rawurlencode($an).'='.rawurlencode($c['api_key']); break; case 'body': $data[$an]=$c['api_key']; break; default: $h[]='Authorization: Bearer '.$c['api_key']; }
        $ch=curl_init(); $get=strtoupper($c['http_method']??'POST')==='GET';
        if($get){ $url.=(strpos($url,'?')?'&':'?').http_build_query($data); }
        else{ if(($c['body_format']??'json')==='json'){ $h[]='Content-Type: application/json'; $b=json_encode($data); } else $b=http_build_query($data); curl_setopt($ch,CURLOPT_POST,true); curl_setopt($ch,CURLOPT_POSTFIELDS,$b); }
        curl_setopt_array($ch,[CURLOPT_URL=>$url,CURLOPT_HTTPHEADER=>$h,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false]);
        $resp=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $cerr=curl_error($ch); curl_close($ch);
        $ok=$resp!==false && $code>=200 && $code<300 && (($c['success_match']??'')===''||stripos((string)$resp,$c['success_match'])!==false);
        $err=$ok?null:($cerr?:'HTTP '.$code.': '.substr(preg_replace('/\s+/',' ',strip_tags((string)$resp)),0,160));
        self::log('sms',$num,null,$text,$ok,$err,$meta); return [$ok,$err];
    }
    static function vars($t,$extra=[]){ return $extra+['name'=>trim($t['first_name'].' '.$t['surname']),'school'=>$t['school_name']??'','municipality'=>setting('municipality'),'office_phone'=>setting('contact_phone')]; }
    static function event($code,$t,$extra=[]){
        $v=self::vars($t,$extra); $n=0;
        foreach(DB::all('SELECT * FROM message_templates WHERE code=? AND enabled=1',[$code]) as $tp){
            $meta=['template'=>$code,'teacher_id'=>$t['id']];
            if($tp['channel']==='sms' && !empty($t['contact']) && Integrations::enabled('sms')) $n+=(int)self::sms($t['contact'],self::fill($tp['body'],$v),$meta)[0];
            if($tp['channel']==='email' && !empty($t['email']) && Integrations::enabled('email')) $n+=(int)self::email($t['email'],self::fill($tp['subject'],$v),self::fill($tp['body'],$v),$meta)[0];
        } return $n;
    }
    static function movement($m){
        $map=['transfer'=>'transfer_approved','retirement'=>'retirement_approved']; if(!isset($map[$m['type']])) return;
        $t=DB::row('SELECT t.*,s.name school_name FROM teachers t LEFT JOIN schools s ON s.id=t.school_id WHERE t.id=?',[$m['teacher_id']]); if(!$t) return;
        self::event($map[$m['type']],$t,['date'=>fdate($m['effective_date']),'new_school'=>$t['school_name'],'old_school'=>DB::val('SELECT name FROM schools WHERE id=?',[$m['from_school_id']])?:'']);
    }
}
