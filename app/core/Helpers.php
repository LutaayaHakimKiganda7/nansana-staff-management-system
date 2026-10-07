<?php
function tenure($from){
    if(!$from) return '—'; $d=(new DateTime($from))->diff(new DateTime()); $y=$d->y; $m=$d->m;
    return ($y?$y.' yr'.($y>1?'s':''):'').($y&&$m?' ':'').(($m||!$y)?$m.' mo':'');
}
function fullname($t){ return trim($t['surname'].' '.$t['first_name']); }
function initials($t){ return strtoupper(substr($t['surname'],0,1).substr($t['first_name'],0,1)); }
function photo_url($t){ return !empty($t['photo']) ? base().'/uploads/photos/'.e($t['photo']) : null; }
function avatar($t,$cls=''){ $u=photo_url($t); return $u ? '<img class="thumb '.$cls.'" src="'.$u.'" alt="">' : '<span class="thumb '.$cls.'">'.e(initials($t)).'</span>'; }
function verified_tick($t){ return !empty($t['verified']) ? '<span class="verified-tick" role="img" aria-label="Verified teacher" title="Verified teacher"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="m3 8 3.2 3.2L13 4.5"/></svg></span>' : ''; }
function fdate($d){ return $d ? date('d M Y',strtotime($d)) : '—'; }
function nz($v){ $v=trim((string)$v); return $v===''?null:$v; }
function valid_date($v){ $d=DateTime::createFromFormat('Y-m-d',(string)$v); return $d && $d->format('Y-m-d')===$v; }
function sort_link($label,$col,$sort,$dir,$route,$f){
    $nd=($sort===$col&&$dir==='asc')?'desc':'asc'; $arrow=$sort===$col?($dir==='asc'?' ▲':' ▼'):'';
    return '<a href="'.url($route,array_filter($f+['sort'=>$col,'dir'=>$nd],fn($v)=>$v!=='')).'">'.e($label).$arrow.'</a>';
}
function pager($page,$pages,$route,$f){
    if($pages<2) return ''; $h='<div class="pager">';
    if($page>1) $h.='<a class="btn" href="'.url($route,array_filter($f+['page'=>$page-1],fn($v)=>$v!=='')).'">Previous</a>';
    $h.='<span>Page '.$page.' of '.$pages.'</span>';
    if($page<$pages) $h.='<a class="btn" href="'.url($route,array_filter($f+['page'=>$page+1],fn($v)=>$v!=='')).'">Next</a>';
    return $h.'</div>';
}
function teacher_f(){ $f=[]; foreach(['q','school','payroll','status','type','verified','sort','dir'] as $k) $f[$k]=trim($_GET[$k]??''); return $f; }
function teacher_where($f,&$p){
    $w=' WHERE 1';
    if($f['q']!==''){ $w.=' AND (t.surname LIKE ? OR t.first_name LIKE ? OR t.registration_no LIKE ? OR t.nin LIKE ? OR t.ipps_no LIKE ? OR t.file_no LIKE ?)'; for($i=0;$i<6;$i++) $p[]='%'.$f['q'].'%'; }
    if($f['school']!==''){ $w.=' AND t.school_id=?'; $p[]=(int)$f['school']; }
    if(in_array($f['payroll'],['on_payroll','off_payroll'])){ $w.=' AND t.payroll_status=?'; $p[]=$f['payroll']; }
    if(in_array($f['status'],['active','retired','terminated','deceased'])){ $w.=' AND t.termination_status=?'; $p[]=$f['status']; }
    if(in_array($f['type'],['primary','secondary'])){ $w.=' AND s.type=?'; $p[]=$f['type']; }
    if($f['verified']==='1'||$f['verified']==='0'){ $w.=' AND t.verified=?'; $p[]=(int)$f['verified']; }
    return $w;
}
const TFROM=' FROM teachers t LEFT JOIN schools s ON s.id=t.school_id';

const MOVE_TYPES=['transfer'=>'Transfer','payroll_add'=>'Add to payroll','payroll_remove'=>'Remove from payroll','retirement'=>'Retirement','termination'=>'Termination'];
const MOVE_WHO=['transfer'=>['admin','meo','records'],'retirement'=>['admin','meo','records']];
function can_request($type){ $u=Auth::user(); return $u && in_array($u['role'],MOVE_WHO[$type]??[]); }
function move_pill($s){ $c=['approved'=>'active','rejected'=>'disabled','cancelled'=>'','pending'=>'warnp'][$s]??''; return '<span class="pill '.$c.'">'.ucfirst($s).'</span>'; }
