<?php
function cfg($k){ static $c; $c ??= require __DIR__.'/../config/config.php'; return $c[$k] ?? null; }
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function base(){ return rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/'); }
function url($r='dashboard/index',$q=[]){ return base().'/index.php?r='.$r.($q?'&'.http_build_query($q):''); }
function asset($p){ return base().'/assets/'.$p; }
function flash($type=null,$msg=null){
    if($type){ $_SESSION['flash'][]=[$type,$msg]; return; }
    $f=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $f;
}
function old($k,$d=''){ return e($_SESSION['old'][$k] ?? $d); }
function setting($k,$d=''){ static $s; $s ??= array_column(DB::all('SELECT k,v FROM settings'),'v','k'); return $s[$k] ?? $d; }

class DB {
    static $pdo;
    static function pdo(){
        if(!self::$pdo){
            $port=cfg('db_port'); $portPart=$port?';port='.$port:'';
            self::$pdo=new PDO('mysql:host='.cfg('db_host').$portPart.';dbname='.cfg('db_name').';charset=utf8mb4',cfg('db_user'),cfg('db_pass'),
              [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
        }
        return self::$pdo;
    }
    static function q($sql,$p=[]){ $s=self::pdo()->prepare($sql); $s->execute($p); return $s; }
    static function all($sql,$p=[]){ return self::q($sql,$p)->fetchAll(); }
    static function row($sql,$p=[]){ return self::q($sql,$p)->fetch() ?: null; }
    static function val($sql,$p=[]){ return self::q($sql,$p)->fetchColumn(); }
    static function insert($sql,$p=[]){ self::q($sql,$p); return self::pdo()->lastInsertId(); }
}

class Csrf {
    static function token(){ return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
    static function field(){ return '<input type="hidden" name="_csrf" value="'.self::token().'">'; }
    static function verify(){
        if(!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')){ http_response_code(419); exit('Your session expired. Go back, refresh the page and try again.'); }
    }
}

class Audit {
    static function log($action,$entity=null,$entityId=null,$old=null,$new=null,$user=null){
        $u = $user ?? Auth::user();
        DB::q('INSERT INTO audit_logs (user_id,user_name,action,entity,entity_id,old_values,new_values,ip,user_agent) VALUES (?,?,?,?,?,?,?,?,?)',[
            $u['id'] ?? null, $u['name'] ?? 'Guest', $action, $entity, $entityId,
            $old!==null?json_encode($old):null, $new!==null?json_encode($new):null,
            $_SERVER['REMOTE_ADDR'] ?? '', substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)]);
    }
    static function diff($old,$new,$skip=['password_hash']){
        $o=$n=[]; foreach($new as $k=>$v){ if(in_array($k,$skip)) continue; if(($old[$k]??null)!=$v){ $o[$k]=$old[$k]??null; $n[$k]=$v; } } return [$o,$n];
    }
}

class Auth {
    const ROLES=['admin'=>'System Admin','meo'=>'Municipal Education Officer','records'=>'Records Clerk','payroll'=>'Payroll Officer','viewer'=>'Viewer'];
    const PERMS=[
        'users.manage'=>['admin'],
        'audit.view'=>['admin','meo'],
        'settings.manage'=>['admin'],
        'schools.manage'=>['admin','meo','records'],
        'teachers.manage'=>['admin','meo','records'],
        'movements.approve'=>['admin','meo'],
        'messaging.configure'=>['admin'],
        'messaging.send'=>['admin','meo','records'],
        'messaging.log'=>['admin','meo'],
        'biometrics.manage'=>['admin'],
        'system.manage'=>['admin'],
        'import.manage'=>['admin','meo'],
        'teachers.view'=>['admin','meo','records','payroll','viewer'],
    ];
    static $u;
    static function user(){
        if(self::$u===null){ self::$u = isset($_SESSION['uid']) ? (DB::row('SELECT * FROM users WHERE id=? AND status="active"',[$_SESSION['uid']]) ?: false) : false; }
        return self::$u ?: null;
    }
    static function can($p){ $u=self::user(); return $u && in_array($u['role'],self::PERMS[$p] ?? []); }
    static function attempt($email,$pw){
        $u=DB::row('SELECT * FROM users WHERE email=?',[strtolower(trim($email))]);
        if(!$u){ Audit::log('login_failed','user',null,null,['email'=>$email]); return 'Email or password is incorrect.'; }
        if($u['status']!=='active') return 'This account is disabled. Contact the system administrator.';
        if($u['locked_until'] && strtotime($u['locked_until'])>time()) return 'Too many failed attempts. Try again in a few minutes.';
        if(!password_verify($pw,$u['password_hash'])){
            $f=$u['failed_attempts']+1; $lock=null;
            if($f>=cfg('max_failed_logins')){ $lock=date('Y-m-d H:i:s',time()+cfg('lock_minutes')*60); $f=0; }
            DB::q('UPDATE users SET failed_attempts=?,locked_until=? WHERE id=?',[$f,$lock,$u['id']]);
            Audit::log('login_failed','user',$u['id'],null,['email'=>$email],$u);
            return 'Email or password is incorrect.';
        }
        session_regenerate_id(true);
        $_SESSION['uid']=$u['id']; $_SESSION['last']=time();
        DB::q('UPDATE users SET failed_attempts=0,locked_until=NULL,last_login=NOW() WHERE id=?',[$u['id']]);
        Audit::log('login','user',$u['id'],null,null,$u);
        return null;
    }
    static function logout(){ if(self::user()) Audit::log('logout','user',self::user()['id']); $_SESSION=[]; session_destroy(); }
}

class Controller {
    function view($name,$data=[],$layout='layout/app'){
        if($layout==='layout/app' && !Auth::user()) $layout='layout/auth';
        extract($data); $viewFile=__DIR__."/../views/$name.php";
        ob_start(); require $viewFile; $content=ob_get_clean();
        require __DIR__."/../views/$layout.php"; unset($_SESSION['old']);
    }
    function redirect($r,$q=[]){ header('Location: '.url($r,$q)); exit; }
    function needLogin(){ if(!Auth::user()) $this->redirect('auth/login'); }
    function need($perm){ $this->needLogin(); if(!Auth::can($perm)){ http_response_code(403); $this->view('403'); exit; } }
    function post(){ if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); exit; } Csrf::verify(); }
}
