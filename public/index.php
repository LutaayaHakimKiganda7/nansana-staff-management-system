<?php
require __DIR__.'/../app/core/Core.php';
require __DIR__.'/../app/core/Helpers.php';
require __DIR__.'/../app/core/Notify.php';
require __DIR__.'/../app/core/Backup.php';
date_default_timezone_set(cfg('timezone'));
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
session_start();
if(isset($_SESSION['uid'])){
    if(time()-($_SESSION['last']??0) > cfg('session_idle_minutes')*60){ $_SESSION=[]; session_destroy(); session_start(); flash('warn','You were signed out after a period of inactivity.'); }
    else $_SESSION['last']=time();
}
$route = $_GET['r'] ?? 'dashboard/index';
if(!preg_match('~^([a-z]+)/([a-z]+)$~',$route,$m)){ $m=[1=>'dashboard',2=>'index']; }
$file=__DIR__.'/../app/controllers/'.ucfirst($m[1]).'Controller.php';
$class=ucfirst($m[1]).'Controller';
try{
    if(!is_file($file)){ http_response_code(404); (new Controller)->view('404'); exit; }
    require $file; $c=new $class;
    if(!method_exists($c,$m[2])){ http_response_code(404); (new Controller)->view('404'); exit; }
    $c->{$m[2]}();
}catch(PDOException $ex){
    http_response_code(500); echo '<h3>Database error</h3><p>Check app/config/config.php and import database/schema.sql.</p>';
}
