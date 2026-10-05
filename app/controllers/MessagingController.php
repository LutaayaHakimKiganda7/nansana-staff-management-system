<?php
class MessagingController extends Controller {
    function index(){ $this->needLogin(); $this->redirect(Auth::can('messaging.send')?'messaging/compose':(Auth::can('messaging.log')?'messaging/log':'dashboard/index')); }
    private function str($k,$max=190){ return substr(trim($_POST[$k]??''),0,$max); }
    // ---------- Email setup ----------
    function email(){ $this->need('messaging.configure'); $c=Integrations::get('email'); $mailer=Notify::loadMailer(); $hasPw=Integrations::has('email','password'); $this->view('messaging/email',compact('c','mailer','hasPw')); }
    function saveemail(){
        $this->need('messaging.configure'); $this->post();
        $d=['enabled'=>isset($_POST['enabled'])?1:0,'host'=>$this->str('host'),'port'=>(int)$this->str('port'),'encryption'=>in_array($_POST['encryption']??'',['ssl','tls','none'])?$_POST['encryption']:'ssl',
            'username'=>$this->str('username'),'password'=>$_POST['password']??'','from_email'=>strtolower($this->str('from_email')),'from_name'=>$this->str('from_name'),'reply_to'=>strtolower($this->str('reply_to'))];
        $err=null;
        if($d['enabled']){
            if(!$d['host']||$d['port']<1||$d['port']>65535||!$d['username']) $err='Enter the SMTP host, port and username.';
            elseif(!filter_var($d['from_email'],FILTER_VALIDATE_EMAIL)) $err='Enter a valid "from" email address.';
            elseif($d['reply_to']&&!filter_var($d['reply_to'],FILTER_VALIDATE_EMAIL)) $err='The reply-to address is not valid.';
            elseif($d['password']===''&&!Integrations::has('email','password')) $err='Enter the SMTP password.';
        }
        if($err){ flash('danger',$err); $this->redirect('messaging/email'); }
        $changedPw=$d['password']!==''; Integrations::save('email',$d,['password']);
        Audit::log('email_settings_updated','integration','email',null,['host'=>$d['host'],'port'=>$d['port'],'encryption'=>$d['encryption'],'username'=>$d['username'],'from_email'=>$d['from_email'],'enabled'=>$d['enabled'],'password_changed'=>$changedPw]);
        flash('ok','Email settings saved. Send a test email to confirm they work.'); $this->redirect('messaging/email');
    }
    function testemail(){
        $this->need('messaging.configure'); $this->post(); $to=trim($_POST['to']??'');
        if(!filter_var($to,FILTER_VALIDATE_EMAIL)){ flash('danger','Enter a valid address to send the test to.'); $this->redirect('messaging/email'); }
        [$ok,$err]=Notify::email($to,'MGTM System test email','This is a test message from '.setting('system_name').'. Your email settings work.',['template'=>'test']);
        flash($ok?'ok':'danger',$ok?'Test email sent to '.e($to).'.':'Test failed: '.e($err)); $this->redirect('messaging/email');
    }
    // ---------- SMS setup ----------
    function sms(){ $this->need('messaging.configure'); $c=Integrations::get('sms'); $hasKey=Integrations::has('sms','api_key'); $this->view('messaging/sms',compact('c','hasKey')); }
    function savesms(){
        $this->need('messaging.configure'); $this->post();
        $x=trim($_POST['extra_fields']??'');
        $d=['enabled'=>isset($_POST['enabled'])?1:0,'api_key'=>trim($_POST['api_key']??''),'base_url'=>$this->str('base_url'),'send_path'=>$this->str('send_path'),'http_method'=>($_POST['http_method']??'')==='GET'?'GET':'POST',
            'auth_mode'=>in_array($_POST['auth_mode']??'',['bearer','header','query','body'])?$_POST['auth_mode']:'bearer','auth_name'=>$this->str('auth_name',60),'body_format'=>($_POST['body_format']??'')==='form'?'form':'json',
            'field_recipient'=>$this->str('field_recipient',40),'field_message'=>$this->str('field_message',40),'field_sender'=>$this->str('field_sender',40),'sender_id'=>$this->str('sender_id',20),
            'extra_fields'=>$x,'success_match'=>$this->str('success_match',80),'country_code'=>preg_replace('/\D/','',$_POST['country_code']??'256')?:'256','plus'=>isset($_POST['plus'])?'1':'0'];
        $err=null;
        if($d['enabled']){
            if(!preg_match('~^https://[^\s/]+~i',$d['base_url'])) $err='The API base URL must start with https://';
            elseif($d['api_key']===''&&!Integrations::has('sms','api_key')) $err='Enter the API key.';
        }
        if(!$err&&$x!==''&&!is_array(json_decode($x,true))) $err='Extra fields must be valid JSON, e.g. {"type":"plain"}.';
        if($err){ flash('danger',$err); $this->redirect('messaging/sms'); }
        $changed=$d['api_key']!==''; Integrations::save('sms',$d,['api_key']); $log=$d; unset($log['api_key']); $log['api_key_changed']=$changed;
        Audit::log('sms_settings_updated','integration','sms',null,$log);
        flash('ok','SMS settings saved. Send a test SMS to confirm they work.'); $this->redirect('messaging/sms');
    }
    function testsms(){
        $this->need('messaging.configure'); $this->post(); $to=trim($_POST['to']??'');
        if(strlen(preg_replace('/\D/','',$to))<9){ flash('danger','Enter a valid phone number.'); $this->redirect('messaging/sms'); }
        [$ok,$err]=Notify::sms($to,'Test message from '.setting('system_name').'.',['template'=>'test']);
        flash($ok?'ok':'danger',$ok?'Test SMS accepted by the gateway.':'Test failed: '.e($err)); $this->redirect('messaging/sms');
    }
    // ---------- Templates ----------
    function templates(){
        $this->need('messaging.configure'); $rows=DB::all('SELECT * FROM message_templates ORDER BY code,channel'); $days=setting('retirement_notice_days','90'); $this->view('messaging/templates',compact('rows','days'));
    }
    function savetemplate(){
        $this->need('messaging.configure'); $this->post(); $id=(int)$_POST['id']; $old=DB::row('SELECT * FROM message_templates WHERE id=?',[$id]);
        if(!$old||trim($_POST['body']??'')===''){ flash('danger','The message text cannot be empty.'); $this->redirect('messaging/templates'); }
        $n=['subject'=>$old['channel']==='email'?trim($_POST['subject']??''):null,'body'=>trim($_POST['body']),'enabled'=>isset($_POST['enabled'])?1:0];
        DB::q('UPDATE message_templates SET subject=?,body=?,enabled=? WHERE id=?',[$n['subject'],$n['body'],$n['enabled'],$id]);
        [$o,$nn]=Audit::diff($old,$n); if($nn) Audit::log('template_updated','template',$old['code'].'/'.$old['channel'],$o,$nn);
        flash('ok','Template saved.'); $this->redirect('messaging/templates');
    }
    function savenotice(){
        $this->need('messaging.configure'); $this->post(); $d=max(1,min(365,(int)$_POST['days']));
        DB::q('INSERT INTO settings (k,v) VALUES ("retirement_notice_days",?) ON DUPLICATE KEY UPDATE v=VALUES(v)',[$d]); Audit::log('settings_updated','settings',null,null,['retirement_notice_days'=>$d]);
        flash('ok','Reminder period saved.'); $this->redirect('messaging/templates');
    }
    // ---------- Log ----------
    function log(){
        $this->need('messaging.log'); $ch=$_GET['channel']??''; $st=$_GET['status']??''; $q=trim($_GET['q']??''); $w=' WHERE 1'; $p=[];
        if(in_array($ch,['sms','email'])){ $w.=' AND l.channel=?'; $p[]=$ch; } if(in_array($st,['sent','failed'])){ $w.=' AND l.status=?'; $p[]=$st; }
        if($q!==''){ $w.=' AND (l.recipient LIKE ? OR t.surname LIKE ? OR t.first_name LIKE ?)'; array_push($p,"%$q%","%$q%","%$q%"); }
        $page=max(1,(int)($_GET['page']??1)); $per=30; $total=(int)DB::val('SELECT COUNT(*) FROM message_log l LEFT JOIN teachers t ON t.id=l.teacher_id'.$w,$p);
        $rows=DB::all('SELECT l.*,t.surname,t.first_name,u.name sender FROM message_log l LEFT JOIN teachers t ON t.id=l.teacher_id LEFT JOIN users u ON u.id=l.user_id'.$w.' ORDER BY l.id DESC LIMIT '.$per.' OFFSET '.(($page-1)*$per),$p);
        $f=['channel'=>$ch,'status'=>$st,'q'=>$q]; $pages=max(1,(int)ceil($total/$per)); $this->view('messaging/log',compact('rows','f','page','pages','total'));
    }
    // ---------- Compose / bulk send ----------
    function compose(){
        $this->need('messaging.send'); $schools=DB::all('SELECT id,name FROM schools WHERE status="active" ORDER BY name');
        $ready=['sms'=>Integrations::enabled('sms'),'email'=>Integrations::enabled('email')]; $this->view('messaging/compose',compact('schools','ready'));
    }
    function send(){
        $this->need('messaging.send'); $this->post(); set_time_limit(180);
        $chs=array_values(array_intersect($_POST['channel']??[],['sms','email'])); $body=trim($_POST['body']??''); $subj=trim($_POST['subject']??''); $sid=(int)($_POST['school_id']??0);
        if(!$chs||$body===''||(in_array('email',$chs)&&$subj==='')){ flash('danger','Choose a channel and enter the message (and a subject for email).'); $this->redirect('messaging/compose'); }
        foreach($chs as $c) if(!Integrations::enabled($c)){ flash('danger',strtoupper($c).' is not set up yet. An administrator must configure it first.'); $this->redirect('messaging/compose'); }
        $w='WHERE t.termination_status="active"'; $p=[]; if($sid){ $w.=' AND t.school_id=?'; $p[]=$sid; }
        $teachers=DB::all('SELECT t.*,s.name school_name FROM teachers t LEFT JOIN schools s ON s.id=t.school_id '.$w.' ORDER BY t.id LIMIT 301',$p);
        if(count($teachers)>300){ flash('danger','That is more than 300 recipients. Send by school instead.'); $this->redirect('messaging/compose'); }
        $ok=$bad=$skip=0;
        foreach($teachers as $t){ $v=Notify::vars($t); $meta=['template'=>'broadcast','teacher_id'=>$t['id']];
            foreach($chs as $c){
                if($c==='sms'){ if(!$t['contact']){ $skip++; continue; } [$r]=Notify::sms($t['contact'],Notify::fill($body,$v),$meta); }
                else { if(!$t['email']){ $skip++; continue; } [$r]=Notify::email($t['email'],Notify::fill($subj,$v),Notify::fill($body,$v),$meta); }
                $r?$ok++:$bad++; } }
        Audit::log('broadcast_sent','message',null,null,['channels'=>$chs,'school_id'=>$sid?:'all','sent'=>$ok,'failed'=>$bad,'skipped'=>$skip]);
        flash($bad?'warn':'ok',"Done: $ok sent, $bad failed, $skip skipped (no phone or email on file). See the message log for details."); $this->redirect('messaging/log');
    }
}
