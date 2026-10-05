<?php
class ImportController extends Controller {
    const HEAD=['registration_no','surname','first_name','date_of_birth','date_employed','designation','school','school_since','contact','email','nin','ipps_no','nssf_no','tin','file_no','department','salary_scale','retirement_age','payroll_status','subjects','classes','kin_name','kin_relationship','kin_contact'];
    function index(){ $this->need('import.manage'); $imp=$_SESSION['import']??null; $this->view('import/index',compact('imp')); }
    function template(){ $this->need('import.manage'); header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="teacher-import-template.csv"'); $o=fopen('php://output','w'); fwrite($o,"\xEF\xBB\xBF");
        fputcsv($o,self::HEAD); fputcsv($o,['REG-0001','Okello','Grace','1980-05-14','2005-02-01','Teacher','EMIS-001','2020-01-15','0772000000','','CF80000000000A','','','','F-12','Primary','U5','60','on','English','P5','Akello Mary','Sister','0701000000']); exit; }
    private function date($v){ $v=trim($v); foreach(['Y-m-d','d/m/Y','d-m-Y','j/n/Y','j-n-Y'] as $f){ $d=DateTime::createFromFormat($f,$v); if($d&&$d->format($f)===$v) return $d->format('Y-m-d'); } return null; }
    function upload(){
        $this->need('import.manage'); $this->post(); $fl=$_FILES['file']??null;
        if(!$fl||$fl['error']!==UPLOAD_ERR_OK||$fl['size']>2*1024*1024||!preg_match('/\.csv$/i',$fl['name'])){ flash('danger','Choose a CSV file under 2 MB. In Excel use Save As, CSV.'); $this->redirect('import/index'); }
        $h=fopen($fl['tmp_name'],'r'); $first=fgets($h); rewind($h); $delim=substr_count($first,';')>substr_count($first,',')?';':',';
        $head=array_map(fn($x)=>strtolower(str_replace([' ',"\xEF\xBB\xBF"],['_',''],trim($x))),fgetcsv($h,0,$delim)?:[]);
        foreach(['registration_no','surname','first_name','date_of_birth','date_employed','designation','school'] as $r) if(!in_array($r,$head)){ flash('danger',"The column \"$r\" is missing. Download the template and use its headings."); $this->redirect('import/index'); }
        $schools=[]; foreach(DB::all('SELECT id,name,emis_code FROM schools WHERE status="active"') as $s){ $schools[strtolower($s['name'])]=$s['id']; if($s['emis_code']) $schools[strtolower($s['emis_code'])]=$s['id']; }
        $seen=['registration_no'=>[],'nin'=>[],'ipps_no'=>[]]; $ok=[]; $errs=[]; $n=1;
        while(($r=fgetcsv($h,0,$delim))!==false){ $n++; if(count(array_filter($r,fn($x)=>trim($x)!==''))===0) continue; if($n>2001){ $errs[]=[$n,'Stopped: the limit is 2000 rows per file.']; break; }
            $x=array_combine($head,array_pad(array_slice($r,0,count($head)),count($head),'')); $x=array_map('trim',$x); $e=[];
            foreach(['registration_no','surname','first_name','designation'] as $k) if($x[$k]==='') $e[]="$k is empty";
            $dob=$this->date($x['date_of_birth']??''); $emp=$this->date($x['date_employed']??''); $since=($x['school_since']??'')!==''?$this->date($x['school_since']):$emp;
            if(!$dob) $e[]='date_of_birth is not a valid date (use dd/mm/yyyy or yyyy-mm-dd)'; elseif($dob>=date('Y-m-d')) $e[]='date_of_birth must be in the past';
            if(!$emp) $e[]='date_employed is not a valid date'; elseif($dob&&$emp<=$dob) $e[]='date_employed must be after date_of_birth';
            if(!$since) $e[]='school_since is not a valid date';
            $sid=$schools[strtolower($x['school'])]??null; if(!$sid) $e[]='school "'.$x['school'].'" was not found (use the EMIS code or exact name of an active school)';
            $age=(int)(($x['retirement_age']??'')?:60); if($age<50||$age>70) $e[]='retirement_age must be 50 to 70';
            $nin=strtoupper(preg_replace('/\s+/','',$x['nin']??'')); if($nin&&!preg_match('/^[A-Z0-9]{14}$/',$nin)) $e[]='nin must be 14 letters and digits';
            $em=strtolower($x['email']??''); if($em&&!filter_var($em,FILTER_VALIDATE_EMAIL)) $e[]='email is not valid';
            foreach(['registration_no'=>$x['registration_no'],'nin'=>$nin,'ipps_no'=>$x['ipps_no']??''] as $k=>$v){ if($v==='') continue;
                if(isset($seen[$k][$v])) $e[]="$k $v is repeated in this file (row {$seen[$k][$v]})"; elseif(DB::val("SELECT COUNT(*) FROM teachers WHERE $k=?",[$v])) $e[]="$k $v already exists in the system"; else $seen[$k][$v]=$n; }
            if($e){ $errs[]=[$n,implode('; ',$e)]; continue; }
            $ok[]=['registration_no'=>$x['registration_no'],'file_no'=>nz($x['file_no']),'surname'=>$x['surname'],'first_name'=>$x['first_name'],'date_of_birth'=>$dob,'contact'=>nz($x['contact']),'email'=>nz($em),'date_employed'=>$emp,'department'=>nz($x['department']),'designation'=>$x['designation'],
              'retirement_age'=>$age,'retirement_year'=>(int)substr($dob,0,4)+$age,'salary_scale'=>nz($x['salary_scale']),'nssf_no'=>nz($x['nssf_no']),'ipps_no'=>nz($x['ipps_no']),'nin'=>nz($nin),'tin'=>nz($x['tin']),'subjects'=>nz($x['subjects']),'classes'=>nz($x['classes']),
              'school_id'=>$sid,'school_since'=>$since,'payroll_status'=>in_array(strtolower($x['payroll_status']??''),['off','off_payroll','no'])?'off_payroll':'on_payroll','_kin'=>['name'=>nz($x['kin_name']),'rel'=>nz($x['kin_relationship']),'contact'=>nz($x['kin_contact'])]]; }
        $_SESSION['import']=['file'=>$fl['name'],'rows'=>$ok,'errors'=>$errs]; $this->redirect('import/index');
    }
    function commit(){
        $this->need('import.manage'); $this->post(); $imp=$_SESSION['import']??null; if(!$imp||!$imp['rows']){ flash('danger','Nothing to import.'); $this->redirect('import/index'); }
        $pdo=DB::pdo(); $pdo->beginTransaction();
        try{ foreach($imp['rows'] as $r){ $kin=$r['_kin']; unset($r['_kin']); $r['created_by']=Auth::user()['id'];
                $id=DB::insert('INSERT INTO teachers ('.implode(',',array_keys($r)).') VALUES ('.implode(',',array_fill(0,count($r),'?')).')',array_values($r));
                DB::q('INSERT INTO teacher_postings (teacher_id,school_id,designation,from_date) VALUES (?,?,?,?)',[$id,$r['school_id'],$r['designation'],$r['school_since']]);
                if($kin['name']) DB::q('INSERT INTO teacher_kin (teacher_id,name,relationship,contact) VALUES (?,?,?,?)',[$id,$kin['name'],$kin['rel'],$kin['contact']]);
                Audit::log('teacher_created','teacher',$id,null,['source'=>'csv_import','file'=>$imp['file'],'registration_no'=>$r['registration_no']]); }
            $pdo->commit(); }catch(Throwable $e){ $pdo->rollBack(); flash('danger','Import stopped and nothing was saved: '.e($e->getMessage())); $this->redirect('import/index'); }
        $c=count($imp['rows']); unset($_SESSION['import']); Audit::log('teachers_imported','teacher',null,null,['file'=>$imp['file'],'count'=>$c]); flash('ok',"$c teachers imported."); $this->redirect('teachers/index');
    }
    function discard(){ $this->need('import.manage'); $this->post(); unset($_SESSION['import']); $this->redirect('import/index'); }
}
