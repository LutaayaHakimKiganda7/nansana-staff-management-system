<?php
class LedgerController extends Controller {
    const SORTS=['name'=>'t.surname','reg'=>'t.registration_no','school'=>'s.name','designation'=>'t.designation','tenure'=>'t.school_since','retire'=>'t.retirement_year','payroll'=>'t.payroll_status'];
    private function listing($limit=true){
        $f=teacher_f(); $p=[]; $w=teacher_where($f,$p);
        $sort=isset(self::SORTS[$f['sort']])?$f['sort']:'name'; $dir=$f['dir']==='desc'?'desc':'asc';
        $col=self::SORTS[$sort]; if($sort==='tenure') $dir=$dir==='asc'?'desc':'asc';
        $order=" ORDER BY $col $dir, t.surname, t.first_name";
        return [$f,$p,$w,$sort,$f['dir']==='desc'?'desc':'asc',$order];
    }
    function index(){
        $this->need('teachers.view'); [$f,$p,$w,$sort,$dir,$order]=$this->listing();
        $page=max(1,(int)($_GET['page']??1)); $per=25; $total=(int)DB::val('SELECT COUNT(*)'.TFROM.$w,$p);
        $rows=DB::all('SELECT t.*,s.name school_name,s.type school_type'.TFROM.$w.$order.' LIMIT '.$per.' OFFSET '.(($page-1)*$per),$p);
        $schools=DB::all('SELECT id,name FROM schools ORDER BY name'); $pages=max(1,(int)ceil($total/$per));
        $this->view('ledger/index',compact('rows','f','schools','total','page','pages','sort','dir'));
    }
    function export(){
        $this->need('teachers.view'); [$f,$p,$w,$sort,$dir,$order]=$this->listing();
        $rows=DB::all('SELECT t.*,s.name school_name,s.type school_type'.TFROM.$w.$order,$p);
        Audit::log('ledger_exported','teacher',null,null,['rows'=>count($rows),'filters'=>array_filter($f)]);
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="teacher-ledger-'.date('Y-m-d').'.csv"');
        $o=fopen('php://output','w'); fwrite($o,"\xEF\xBB\xBF");
        fputcsv($o,['Registration No','Surname','First name','Designation','Current school','School type','Years at school','Retirement year','Payroll','Status','Verified','Contact']);
        $safe=fn($v)=>(is_string($v)&&preg_match('/^[=+\-@]/',$v))?"'".$v:$v;
        foreach($rows as $t) fputcsv($o,array_map($safe,[$t['registration_no'],$t['surname'],$t['first_name'],$t['designation'],$t['school_name'],$t['school_type'],tenure($t['school_since']),$t['retirement_year'],$t['payroll_status']==='on_payroll'?'On payroll':'Off payroll',$t['termination_status'],$t['verified']?'Yes':'No',$t['contact']]));
        exit;
    }
    function profile(){
        $this->need('teachers.view');
        $t=DB::row('SELECT t.*,s.name school_name,s.type school_type,s.location school_location,u.name verified_by_name'.TFROM.' LEFT JOIN users u ON u.id=t.verified_by WHERE t.id=?',[(int)($_GET['id']??0)]);
        if(!$t){ http_response_code(404); $this->view('404'); return; }
        $kin=DB::all('SELECT * FROM teacher_kin WHERE teacher_id=? ORDER BY id',[$t['id']]);
        $posts=DB::all('SELECT p.*,s.name school_name FROM teacher_postings p JOIN schools s ON s.id=p.school_id WHERE p.teacher_id=? ORDER BY p.from_date DESC,p.id DESC',[$t['id']]);
        $moves=DB::all('SELECT m.*,fs.name from_name,ts.name to_name FROM movements m LEFT JOIN schools fs ON fs.id=m.from_school_id LEFT JOIN schools ts ON ts.id=m.to_school_id WHERE m.teacher_id=? ORDER BY m.id DESC',[$t['id']]);
        $this->view('ledger/profile',compact('t','kin','posts','moves'));
    }
}
