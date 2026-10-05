<?php
class TeachersController extends Controller {
    const FIELDS=['registration_no','file_no','surname','first_name','date_of_birth','date_opened','contact','email','date_employed','department','designation','retirement_age','salary_scale','nssf_no','ipps_no','nin','tin','health_status','subjects','classes'];
    function index(){
        $this->need('teachers.view'); $f=teacher_f(); $p=[];
        $w=teacher_where($f,$p); $page=max(1,(int)($_GET['page']??1)); $per=20;
        $total=(int)DB::val('SELECT COUNT(*)'.TFROM.$w,$p);
        $rows=DB::all('SELECT t.*,s.name school_name'.TFROM.$w.' ORDER BY t.surname,t.first_name LIMIT '.$per.' OFFSET '.(($page-1)*$per),$p);
        $schools=DB::all('SELECT id,name FROM schools ORDER BY name'); $pages=max(1,(int)ceil($total/$per));
        $this->view('teachers/index',compact('rows','f','schools','total','page','pages'));
    }
    function form(){
        $this->need('teachers.manage');
        $t=isset($_GET['id'])?DB::row('SELECT * FROM teachers WHERE id=?',[(int)$_GET['id']]):null;
        $kin=$t?DB::all('SELECT * FROM teacher_kin WHERE teacher_id=? ORDER BY id',[$t['id']]):[];
        $schools=DB::all('SELECT id,name,type FROM schools WHERE status="active" ORDER BY name');
        $this->view('teachers/form',compact('t','kin','schools'));
    }
    function save(){
        $this->need('teachers.manage'); $this->post(); $id=(int)($_POST['id']??0);
        $d=[]; foreach(self::FIELDS as $k) $d[$k]=nz($_POST[$k]??'');
        if($d['email']) $d['email']=strtolower($d['email']);
        foreach(['nin'] as $k) if($d[$k]) $d[$k]=strtoupper(preg_replace('/\s+/','',$d[$k]));
        $d['retirement_age']=(int)($d['retirement_age']?:60);
        $kin=[]; foreach(($_POST['kin_name']??[]) as $i=>$n){ $n=trim($n); if($n==='') continue;
            $kn=strtoupper(preg_replace('/\s+/','',$_POST['kin_nin'][$i]??'')); $kin[]=['name'=>$n,'relationship'=>nz($_POST['kin_rel'][$i]??''),'contact'=>nz($_POST['kin_contact'][$i]??''),'nin'=>$kn?:null]; }
        $old=$id?DB::row('SELECT * FROM teachers WHERE id=?',[$id]):null; $err=null;
        foreach(['registration_no'=>'registration number','surname'=>'surname','first_name'=>'first name','date_of_birth'=>'date of birth','date_employed'=>'date employed','designation'=>'designation'] as $k=>$l) if(!$d[$k]){ $err="Enter the $l."; break; }
        if(!$err) foreach(['date_of_birth','date_employed','date_opened'] as $k) if($d[$k] && !valid_date($d[$k])){ $err='One of the dates is not valid.'; break; }
        if(!$err && $d['date_of_birth']>=date('Y-m-d')) $err='Date of birth must be in the past.';
        if(!$err && $d['date_employed']<=$d['date_of_birth']) $err='Date employed must be after the date of birth.';
        if(!$err && ($d['retirement_age']<50||$d['retirement_age']>70)) $err='Retirement age must be between 50 and 70.';
        if(!$err && $d['nin'] && !preg_match('/^[A-Z0-9]{14}$/',$d['nin'])) $err='A NIN has 14 letters and digits.';
        if(!$err && $d['email'] && !filter_var($d['email'],FILTER_VALIDATE_EMAIL)) $err='Enter a valid email address.';
        if(!$err && $old && !empty($_POST['base_updated']) && $_POST['base_updated']!==$old['updated_at']) $err='This record was changed by someone else after you opened it. Open it again and re-apply your changes.';
        if(!$err && !$kin) $err='Add at least one next of kin.';
        foreach(['registration_no'=>'registration number','ipps_no'=>'IPPS number','nin'=>'NIN'] as $k=>$l)
            if(!$err && $d[$k] && DB::val("SELECT COUNT(*) FROM teachers WHERE $k=? AND id<>?",[$d[$k],$id])) $err="That $l already belongs to another teacher.";
        if(!$id){
            if(!DB::val('SELECT COUNT(*) FROM schools WHERE id=? AND status="active"',[(int)($_POST['school_id']??0)]) && !$err) $err='Choose the school the teacher is posted to.';
            if(!$err && !valid_date($_POST['school_since']??'')) $err='Enter the date posted to the school.';
        }
        $photo=null;
        if(!$err && !empty($_FILES['photo']['name'])){ [$photo,$perr]=$this->savePhoto($_FILES['photo']); if($perr) $err=$perr; }
        if($err){ $_SESSION['old']=$_POST; flash('danger',$err); $this->redirect('teachers/form',$id?['id'=>$id]:[]); }
        $d['retirement_year']=(int)substr($d['date_of_birth'],0,4)+$d['retirement_age'];
        $keys=array_keys($d);
        if($id){
            $set=implode(',',array_map(fn($k)=>"$k=?",$keys)); $args=array_values($d);
            if($photo){ $set.=',photo=?'; $args[]=$photo; }
            DB::q("UPDATE teachers SET $set WHERE id=?",[...$args,$id]);
            [$o,$n]=Audit::diff($old,$d+($photo?['photo'=>$photo]:[]));
            $oldKin=DB::all('SELECT name,relationship,contact,nin FROM teacher_kin WHERE teacher_id=? ORDER BY id',[$id]);
            if($oldKin!=$kin){ $o['next_of_kin']=count($oldKin).' entries'; $n['next_of_kin']=$kin; }
            if($n) Audit::log('teacher_updated','teacher',$id,$o,$n);
        } else {
            $d+=['school_id'=>(int)$_POST['school_id'],'school_since'=>$_POST['school_since'],'payroll_status'=>($_POST['payroll_status']??'')==='off_payroll'?'off_payroll':'on_payroll','created_by'=>Auth::user()['id']];
            if($photo) $d['photo']=$photo;
            $cols=array_keys($d);
            $id=DB::insert('INSERT INTO teachers ('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')',array_values($d));
            DB::q('INSERT INTO teacher_postings (teacher_id,school_id,designation,from_date) VALUES (?,?,?,?)',[$id,$d['school_id'],$d['designation'],$d['school_since']]);
            $log=$d; $log['next_of_kin']=count($kin).' entries'; Audit::log('teacher_created','teacher',$id,null,$log);
        }
        DB::q('DELETE FROM teacher_kin WHERE teacher_id=?',[$id]);
        foreach($kin as $k) DB::q('INSERT INTO teacher_kin (teacher_id,name,relationship,contact,nin) VALUES (?,?,?,?,?)',[$id,$k['name'],$k['relationship'],$k['contact'],$k['nin']]);
        flash('ok','Teacher saved.'); $this->redirect('ledger/profile',['id'=>$id]);
    }
    private function savePhoto($f){
        if($f['error']!==UPLOAD_ERR_OK) return [null,'The photo could not be uploaded. Try a smaller file.'];
        if($f['size']>3*1024*1024) return [null,'The photo must be under 3 MB.'];
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
        if(!$ext) return [null,'The photo must be a JPG, PNG or WebP image.'];
        $name=bin2hex(random_bytes(12)).'.'.$ext; $dest=__DIR__.'/../../public/uploads/photos/'.$name;
        if(function_exists('imagecreatefromstring') && ($im=@imagecreatefromstring(file_get_contents($f['tmp_name'])))){
            $w=imagesx($im); $h=imagesy($im); $s=min(1,600/max($w,$h));
            if($s<1){ $im2=imagescale($im,(int)($w*$s),(int)($h*$s)); $im=$im2?:$im; }
            $ok=match($ext){'jpg'=>imagejpeg($im,$dest,85),'png'=>imagepng($im,$dest),'webp'=>imagewebp($im,$dest,85)};
            return $ok?[$name,null]:[null,'The photo could not be saved.'];
        }
        return move_uploaded_file($f['tmp_name'],$dest)?[$name,null]:[null,'The photo could not be saved.'];
    }
}
