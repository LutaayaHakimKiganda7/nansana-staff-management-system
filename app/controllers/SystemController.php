<?php
class SystemController extends Controller {
    function backups(){ $this->need('system.manage'); $files=Backup::files(); $this->view('system/backups',compact('files')); }
    function run(){ $this->need('system.manage'); $this->post(); set_time_limit(300);
        try{ $n=Backup::run(); Audit::log('backup_created','system',null,null,['file'=>$n]); flash('ok','Backup created: '.e($n)); }catch(Throwable $e){ flash('danger','Backup failed: '.e($e->getMessage())); } $this->redirect('system/backups'); }
    function download(){ $this->need('system.manage'); $n=$_GET['file']??''; $f=Backup::dir().'/'.$n;
        if(!preg_match('/^mgtm-\d{8}-\d{6}\.sql\.gz$/',$n)||!is_file($f)){ http_response_code(404); exit; }
        Audit::log('backup_downloaded','system',null,null,['file'=>$n]); header('Content-Type: application/gzip'); header('Content-Disposition: attachment; filename="'.$n.'"'); header('Content-Length: '.filesize($f)); readfile($f); exit; }
}
