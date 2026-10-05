<?php
class AuditController extends Controller {
    function index(){
        $this->need('audit.view');
        $f=['q'=>trim($_GET['q']??''),'action'=>$_GET['action']??'','from'=>$_GET['from']??'','to'=>$_GET['to']??''];
        $sql=' FROM audit_logs WHERE 1'; $p=[];
        if($f['q']!==''){ $sql.=' AND (user_name LIKE ? OR entity LIKE ? OR ip LIKE ?)'; array_push($p,"%{$f['q']}%","%{$f['q']}%","%{$f['q']}%"); }
        if($f['action']!==''){ $sql.=' AND action=?'; $p[]=$f['action']; }
        if($f['from']!==''){ $sql.=' AND created_at>=?'; $p[]=$f['from'].' 00:00:00'; }
        if($f['to']!==''){ $sql.=' AND created_at<=?'; $p[]=$f['to'].' 23:59:59'; }
        $page=max(1,(int)($_GET['page']??1)); $per=25;
        $total=(int)DB::val('SELECT COUNT(*)'.$sql,$p);
        $rows=DB::all('SELECT *'.$sql.' ORDER BY id DESC LIMIT '.$per.' OFFSET '.(($page-1)*$per),$p);
        $actions=array_column(DB::all('SELECT DISTINCT action FROM audit_logs ORDER BY action'),'action');
        $pages=max(1,(int)ceil($total/$per));
        $this->view('audit/index',compact('rows','f','page','pages','total','actions'));
    }
}
