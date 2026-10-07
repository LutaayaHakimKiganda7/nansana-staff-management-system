<?php
class BiometricsController extends Controller {
    const SLOTS=['r_thumb'=>'Right thumb','r_index'=>'Right index','r_middle'=>'Right middle','r_ring'=>'Right ring','r_little'=>'Right little','l_thumb'=>'Left thumb','l_index'=>'Left index','l_middle'=>'Left middle','l_ring'=>'Left ring','l_little'=>'Left little'];
    const MIN_FP=2; const MAX_BYTES=600000;
    private function json($a,$code=200){ http_response_code($code); header('Content-Type: application/json'); header('Cache-Control: no-store'); echo json_encode($a); exit; }
    private function teacher($id){ $t=DB::row('SELECT t.*,s.name school_name FROM teachers t LEFT JOIN schools s ON s.id=t.school_id WHERE t.id=?',[(int)$id]); if(!$t){ http_response_code(404); $this->view('404'); exit; } return $t; }
    private function samples($tid){ return array_column(DB::all('SELECT id,slot,quality,captured_at FROM biometric_samples WHERE teacher_id=?',[$tid]),null,'slot'); }
    private function quarter(){ $month=(int)date('n'); return ['year'=>(int)date('Y'),'quarter'=>(int)ceil($month/3)]; }
    private function quarterSamples($tid,$period){ return array_column(DB::all('SELECT id,slot,quality,captured_at FROM quarterly_biometric_samples WHERE teacher_id=? AND year=? AND quarter=?',[$tid,$period['year'],$period['quarter']]),null,'slot'); }
    private function quarterStats($tid,$period){
        return ['fp'=>(int)DB::val('SELECT COUNT(*) FROM quarterly_biometric_samples WHERE teacher_id=? AND year=? AND quarter=? AND slot<>"face"',[$tid,$period['year'],$period['quarter']]),
            'face'=>(int)DB::val('SELECT COUNT(*) FROM quarterly_biometric_samples WHERE teacher_id=? AND year=? AND quarter=? AND slot="face"',[$tid,$period['year'],$period['quarter']])];
    }
    private function refresh($tid){
        $fp=(int)DB::val('SELECT COUNT(*) FROM biometric_samples WHERE teacher_id=? AND slot<>"face"',[$tid]); $face=(int)DB::val('SELECT COUNT(*) FROM biometric_samples WHERE teacher_id=? AND slot="face"',[$tid]);
        $st=($face&&$fp>=self::MIN_FP)?'captured':'pending'; $t=DB::row('SELECT verified FROM teachers WHERE id=?',[$tid]); $reset=false;
        DB::q('UPDATE teachers SET biometrics_status=? WHERE id=?',[$st,$tid]);
        if($t['verified']){ DB::q('UPDATE teachers SET verified=0,verified_by=NULL,verified_at=NULL WHERE id=?',[$tid]); Audit::log('verification_reset','teacher',$tid,null,['reason'=>'biometrics changed']); $reset=true; }
        return ['fp'=>$fp,'face'=>$face,'status'=>$st,'reset'=>$reset,'min'=>self::MIN_FP];
    }
    function index(){
        $this->need('biometrics.manage'); $stage=$_GET['stage']??'awaiting'; $q=trim($_GET['q']??''); $p=[]; $w=' WHERE t.termination_status="active"';
        if($q!==''){ $w.=' AND (t.surname LIKE ? OR t.first_name LIKE ? OR t.registration_no LIKE ?)'; array_push($p,"%$q%","%$q%","%$q%"); }
        $cond=['pending'=>' AND t.biometrics_status="pending"','awaiting'=>' AND t.biometrics_status="captured" AND t.verified=0','verified'=>' AND t.verified=1'];
        $counts=[]; foreach($cond as $k=>$c) $counts[$k]=(int)DB::val('SELECT COUNT(*) FROM teachers t'.$w.$c,$p); if(!isset($cond[$stage])) $stage='awaiting';
        $page=max(1,(int)($_GET['page']??1)); $per=25;
        $rows=DB::all('SELECT t.*,s.name school_name,(SELECT COUNT(*) FROM biometric_samples b WHERE b.teacher_id=t.id AND b.slot<>"face") fp,(SELECT COUNT(*) FROM biometric_samples b WHERE b.teacher_id=t.id AND b.slot="face") face
            FROM teachers t LEFT JOIN schools s ON s.id=t.school_id'.$w.$cond[$stage].' ORDER BY t.surname,t.first_name LIMIT '.$per.' OFFSET '.(($page-1)*$per),$p);
        $pages=max(1,(int)ceil($counts[$stage]/$per)); $f=['stage'=>$stage,'q'=>$q]; $this->view('biometrics/index',compact('rows','counts','stage','q','page','pages','f'));
    }
    function quarterly(){
        $this->need('biometrics.manage'); $period=$this->quarter(); $stage=$_GET['stage']??'due'; $q=trim($_GET['q']??''); $p=[$period['year'],$period['quarter']]; $w=' WHERE t.termination_status="active"';
        if($q!==''){ $w.=' AND (t.surname LIKE ? OR t.first_name LIKE ? OR t.registration_no LIKE ?)'; array_push($p,"%$q%","%$q%","%$q%"); }
        $counts=[];
        foreach(['due'=>' AND v.id IS NULL','progress'=>' AND v.id IS NOT NULL AND v.verified_at IS NULL','verified'=>' AND v.verified_at IS NOT NULL'] as $k=>$condition)
            $counts[$k]=(int)DB::val('SELECT COUNT(*) FROM teachers t LEFT JOIN quarterly_verifications v ON v.teacher_id=t.id AND v.year=? AND v.quarter=?'.$w.$condition,$p);
        if(!isset($counts[$stage])) $stage='due';
        $condition=['due'=>' AND v.id IS NULL','progress'=>' AND v.id IS NOT NULL AND v.verified_at IS NULL','verified'=>' AND v.verified_at IS NOT NULL'][$stage];
        $page=max(1,(int)($_GET['page']??1)); $per=25;
        $rows=DB::all('SELECT t.*,s.name school_name,v.verified_at,v.consented_at,
            (SELECT COUNT(*) FROM quarterly_biometric_samples b WHERE b.teacher_id=t.id AND b.year=? AND b.quarter=? AND b.slot<>"face") fp,
            (SELECT COUNT(*) FROM quarterly_biometric_samples b WHERE b.teacher_id=t.id AND b.year=? AND b.quarter=? AND b.slot="face") face
            FROM teachers t LEFT JOIN schools s ON s.id=t.school_id
            LEFT JOIN quarterly_verifications v ON v.teacher_id=t.id AND v.year=? AND v.quarter=?'.$w.$condition.'
            ORDER BY t.surname,t.first_name LIMIT '.$per.' OFFSET '.(($page-1)*$per),
            [$period['year'],$period['quarter'],$period['year'],$period['quarter'],$period['year'],$period['quarter'],...array_slice($p,2)]);
        $pages=max(1,(int)ceil($counts[$stage]/$per)); $f=['stage'=>$stage,'q'=>$q];
        $this->view('biometrics/quarterly',compact('rows','counts','stage','q','page','pages','f','period'));
    }
    function capture(){
        $this->need('biometrics.manage'); $t=$this->teacher($_GET['id']??0); $samples=$this->samples($t['id']);
        Audit::log('biometrics_opened','teacher',$t['id']); $this->view('biometrics/capture',compact('t','samples'));
    }
    function quarterlycapture(){
        $this->need('biometrics.manage'); $t=$this->teacher($_GET['id']??0); $period=$this->quarter();
        if($t['termination_status']!=='active'){ flash('danger','Only active teachers require quarterly verification.'); $this->redirect('biometrics/quarterly'); }
        $cycle=DB::row('SELECT * FROM quarterly_verifications WHERE teacher_id=? AND year=? AND quarter=?',[$t['id'],$period['year'],$period['quarter']]);
        $samples=$this->quarterSamples($t['id'],$period); $quarterly=true;
        Audit::log('quarterly_biometrics_opened','teacher',$t['id'],null,$period);
        $this->view('biometrics/capture',compact('t','samples','period','cycle','quarterly'));
    }
    function quarterlyverify(){
        $this->need('biometrics.manage'); $t=$this->teacher($_GET['id']??0); $period=$this->quarter();
        $cycle=DB::row('SELECT v.*,u.name verified_by_name FROM quarterly_verifications v LEFT JOIN users u ON u.id=v.verified_by WHERE v.teacher_id=? AND v.year=? AND v.quarter=?',[$t['id'],$period['year'],$period['quarter']]);
        $samples=$this->quarterSamples($t['id'],$period); $stats=$this->quarterStats($t['id'],$period);
        Audit::log('quarterly_verification_opened','teacher',$t['id'],null,$period);
        $this->view('biometrics/quarterly_verify',compact('t','period','cycle','samples','stats'));
    }
    function save(){
        $this->need('biometrics.manage'); $this->post(); $tid=(int)($_POST['teacher_id']??0); $slot=$_POST['slot']??'';
        $t=DB::row('SELECT * FROM teachers WHERE id=? AND termination_status="active"',[$tid]);
        if(!$t) $this->json(['error'=>'Teacher not found or no longer active.'],404);
        if($slot!=='face'&&!isset(self::SLOTS[$slot])) $this->json(['error'=>'Unknown finger.'],422);
        $bytes=null;
        if(!empty($_FILES['file']['tmp_name'])&&$_FILES['file']['error']===UPLOAD_ERR_OK) $bytes=file_get_contents($_FILES['file']['tmp_name']);
        elseif(preg_match('~^data:image/[a-z]+;base64,(.+)$~',$_POST['image']??'',$m)) $bytes=base64_decode($m[1],true);
        if(!$bytes) $this->json(['error'=>'No image was received.'],422);
        if(strlen($bytes)>self::MAX_BYTES) $this->json(['error'=>'The image is too large (limit 600 KB).'],422);
        $mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes); if(!in_array($mime,['image/jpeg','image/png','image/bmp'])) $this->json(['error'=>'Use a JPG, PNG or BMP image.'],422);
        if(!$t['biometric_consent_at']){
            if(($_POST['consent']??'')!=='1') $this->json(['error'=>'Record the teacher\'s consent first.'],422);
            DB::q('UPDATE teachers SET biometric_consent_at=NOW() WHERE id=?',[$tid]); Audit::log('biometric_consent_recorded','teacher',$tid);
        }
        $hash=hash('sha256',$bytes);
        if(DB::val('SELECT COUNT(*) FROM biometric_samples WHERE hash=? AND teacher_id<>?',[$hash,$tid])) $this->json(['error'=>'This exact image was already captured for another teacher.'],422);
        $tpl=substr(trim($_POST['template']??''),0,20000); $q=isset($_POST['quality'])&&$_POST['quality']!==''?max(0,min(100,(int)$_POST['quality'])):null;
        $payload=Crypt::enc(json_encode(['img'=>base64_encode($bytes),'tpl'=>$tpl?:null,'mime'=>$mime]));
        $replaced=(bool)DB::val('SELECT COUNT(*) FROM biometric_samples WHERE teacher_id=? AND slot=?',[$tid,$slot]);
        DB::q('INSERT INTO biometric_samples (teacher_id,slot,quality,hash,payload,captured_by) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE quality=VALUES(quality),hash=VALUES(hash),payload=VALUES(payload),captured_by=VALUES(captured_by),captured_at=NOW()',[$tid,$slot,$q,$hash,$payload,Auth::user()['id']]);
        $id=(int)DB::val('SELECT id FROM biometric_samples WHERE teacher_id=? AND slot=?',[$tid,$slot]);
        Audit::log('biometric_captured','teacher',$tid,null,['slot'=>$slot,'quality'=>$q,'replaced'=>$replaced]);
        $this->json(['ok'=>true,'id'=>$id]+$this->refresh($tid));
    }
    function quarterlysave(){
        $this->need('biometrics.manage'); $this->post(); $tid=(int)($_POST['teacher_id']??0); $slot=$_POST['slot']??''; $period=$this->quarter();
        $t=DB::row('SELECT id,termination_status FROM teachers WHERE id=? AND termination_status="active"',[$tid]);
        if(!$t) $this->json(['error'=>'Teacher not found or no longer active.'],404);
        if($slot!=='face'&&!isset(self::SLOTS[$slot])) $this->json(['error'=>'Unknown finger.'],422);
        if(DB::val('SELECT COUNT(*) FROM quarterly_verifications WHERE teacher_id=? AND year=? AND quarter=? AND verified_at IS NOT NULL',[$tid,$period['year'],$period['quarter']]))
            $this->json(['error'=>'This teacher is already verified for the current quarter.'],409);
        $bytes=null;
        if(!empty($_FILES['file']['tmp_name'])&&$_FILES['file']['error']===UPLOAD_ERR_OK) $bytes=file_get_contents($_FILES['file']['tmp_name']);
        elseif(preg_match('~^data:image/[a-z]+;base64,(.+)$~',$_POST['image']??'',$m)) $bytes=base64_decode($m[1],true);
        if(!$bytes) $this->json(['error'=>'No image was received.'],422);
        if(strlen($bytes)>self::MAX_BYTES) $this->json(['error'=>'The image is too large (limit 600 KB).'],422);
        $mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes); if(!in_array($mime,['image/jpeg','image/png','image/bmp'])) $this->json(['error'=>'Use a JPG, PNG or BMP image.'],422);
        $cycle=DB::row('SELECT consented_at FROM quarterly_verifications WHERE teacher_id=? AND year=? AND quarter=?',[$tid,$period['year'],$period['quarter']]);
        if(empty($cycle['consented_at']) && ($_POST['consent']??'')!=='1') $this->json(['error'=>'Record consent for this quarter before capturing biometrics.'],422);
        $hash=hash('sha256',$bytes);
        if(DB::val('SELECT (SELECT COUNT(*) FROM biometric_samples WHERE hash=?)+(SELECT COUNT(*) FROM quarterly_biometric_samples WHERE hash=?)',[$hash,$hash]))
            $this->json(['error'=>'This exact image was already captured. Use a newly captured image for this quarter.'],422);
        $tpl=substr(trim($_POST['template']??''),0,20000); $quality=isset($_POST['quality'])&&$_POST['quality']!==''?max(0,min(100,(int)$_POST['quality'])):null;
        $payload=Crypt::enc(json_encode(['img'=>base64_encode($bytes),'tpl'=>$tpl?:null,'mime'=>$mime]));
        $pdo=DB::pdo(); $pdo->beginTransaction();
        try{
            DB::q('INSERT IGNORE INTO quarterly_verifications (teacher_id,year,quarter,consented_at) VALUES (?,?,?,NOW())',[$tid,$period['year'],$period['quarter']]);
            $cycle=DB::row('SELECT verified_at FROM quarterly_verifications WHERE teacher_id=? AND year=? AND quarter=? FOR UPDATE',[$tid,$period['year'],$period['quarter']]);
            if($cycle['verified_at']){ $pdo->rollBack(); $this->json(['error'=>'This teacher is already verified for the current quarter.'],409); }
            DB::q('INSERT INTO quarterly_biometric_samples (teacher_id,year,quarter,slot,quality,hash,payload,captured_by) VALUES (?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE quality=VALUES(quality),hash=VALUES(hash),payload=VALUES(payload),captured_by=VALUES(captured_by),captured_at=NOW()',
                [$tid,$period['year'],$period['quarter'],$slot,$quality,$hash,$payload,Auth::user()['id']]);
            $pdo->commit();
        }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
        $stats=$this->quarterStats($tid,$period);
        Audit::log('quarterly_biometric_captured','teacher',$tid,null,['year'=>$period['year'],'quarter'=>$period['quarter'],'slot'=>$slot,'quality'=>$quality]);
        $this->json(['ok'=>true,'fp'=>$stats['fp'],'face'=>$stats['face'],'status'=>$stats['face']&&$stats['fp']>=self::MIN_FP?'captured':'pending','reset'=>false,'min'=>self::MIN_FP,'id'=>(int)DB::val('SELECT id FROM quarterly_biometric_samples WHERE teacher_id=? AND year=? AND quarter=? AND slot=?',[$tid,$period['year'],$period['quarter'],$slot])]);
    }
    function quarterlyremove(){
        $this->need('biometrics.manage'); $this->post(); $tid=(int)($_POST['teacher_id']??0); $slot=$_POST['slot']??''; $period=$this->quarter();
        if($slot!=='face'&&!isset(self::SLOTS[$slot])) $this->json(['error'=>'Unknown biometric slot.'],422);
        $pdo=DB::pdo(); $pdo->beginTransaction();
        try{
            $cycle=DB::row('SELECT verified_at FROM quarterly_verifications WHERE teacher_id=? AND year=? AND quarter=? FOR UPDATE',[$tid,$period['year'],$period['quarter']]);
            if($cycle&&$cycle['verified_at']){ $pdo->rollBack(); $this->json(['error'=>'Biometrics cannot be changed after quarterly verification is complete.'],409); }
            DB::q('DELETE FROM quarterly_biometric_samples WHERE teacher_id=? AND year=? AND quarter=? AND slot=?',[$tid,$period['year'],$period['quarter'],$slot]);
            $pdo->commit();
        }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
        $stats=$this->quarterStats($tid,$period);
        Audit::log('quarterly_biometric_removed','teacher',$tid,null,['year'=>$period['year'],'quarter'=>$period['quarter'],'slot'=>$slot]);
        $this->json(['ok'=>true,'fp'=>$stats['fp'],'face'=>$stats['face'],'status'=>$stats['face']&&$stats['fp']>=self::MIN_FP?'captured':'pending','reset'=>false,'min'=>self::MIN_FP]);
    }
    function quarterlyimage(){
        $this->need('biometrics.manage'); $r=DB::val('SELECT payload FROM quarterly_biometric_samples WHERE id=?',[(int)($_GET['id']??0)]);
        $d=$r?json_decode(Crypt::dec($r),true):null; if(!$d){ http_response_code(404); exit; }
        header('Content-Type: '.$d['mime']); header('Cache-Control: private, no-store'); header('X-Content-Type-Options: nosniff'); echo base64_decode($d['img']); exit;
    }
    function quarterlyconfirm(){
        $this->need('biometrics.manage'); $this->post(); $tid=(int)($_POST['id']??0); $t=$this->teacher($tid); $period=$this->quarter();
        if($t['termination_status']!=='active'){ flash('danger','Only active teachers can be verified for this quarter.'); $this->redirect('biometrics/quarterly'); }
        if(($_POST['confirm']??'')!=='1'){ flash('danger','Tick the box to confirm the quarterly biometric check.'); $this->redirect('biometrics/quarterlyverify',['id'=>$tid]); }
        $pdo=DB::pdo(); $pdo->beginTransaction();
        $cycle=DB::row('SELECT * FROM quarterly_verifications WHERE teacher_id=? AND year=? AND quarter=? FOR UPDATE',[$tid,$period['year'],$period['quarter']]);
        $stats=$this->quarterStats($tid,$period);
        if(!$cycle||!$cycle['consented_at']||$stats['face']!==1||$stats['fp']<self::MIN_FP){
            $pdo->rollBack(); flash('danger','Capture a face and at least '.self::MIN_FP.' fingerprints for this quarter before verifying.'); $this->redirect('biometrics/quarterlycapture',['id'=>$tid]);
        }
        if($cycle['verified_at']){ $pdo->rollBack(); flash('danger','This teacher has already been verified for the current quarter.'); $this->redirect('biometrics/quarterlyverify',['id'=>$tid]); }
        DB::q('UPDATE quarterly_verifications SET verified_by=?,verified_at=NOW(),fingerprint_count=?,note=? WHERE teacher_id=? AND year=? AND quarter=? AND verified_at IS NULL',
            [Auth::user()['id'],$stats['fp'],nz($_POST['note']??''),$tid,$period['year'],$period['quarter']]);
        $pdo->commit();
        Audit::log('teacher_quarterly_verified','teacher',$tid,null,['year'=>$period['year'],'quarter'=>$period['quarter'],'fingerprints'=>$stats['fp'],'note'=>nz($_POST['note']??'')]);
        flash('ok',e(fullname($t)).' is verified for Q'.$period['quarter'].' '.$period['year'].'.'); $this->redirect('biometrics/quarterly');
    }
    function remove(){
        $this->need('biometrics.manage'); $this->post(); $tid=(int)$_POST['teacher_id']; $slot=$_POST['slot']??'';
        DB::q('DELETE FROM biometric_samples WHERE teacher_id=? AND slot=?',[$tid,$slot]); Audit::log('biometric_removed','teacher',$tid,null,['slot'=>$slot]);
        $this->json(['ok'=>true]+$this->refresh($tid));
    }
    function image(){
        $this->need('biometrics.manage'); $r=DB::val('SELECT payload FROM biometric_samples WHERE id=?',[(int)($_GET['id']??0)]);
        $d=$r?json_decode(Crypt::dec($r),true):null; if(!$d){ http_response_code(404); exit; }
        header('Content-Type: '.$d['mime']); header('Cache-Control: private, no-store'); header('X-Content-Type-Options: nosniff'); echo base64_decode($d['img']); exit;
    }
    function verify(){
        $this->need('biometrics.manage'); $t=$this->teacher($_GET['id']??0); $samples=$this->samples($t['id']); $by=$t['verified_by']?DB::val('SELECT name FROM users WHERE id=?',[$t['verified_by']]):null;
        Audit::log('biometrics_opened','teacher',$t['id']); $this->view('biometrics/verify',compact('t','samples','by'));
    }
    function confirm(){
        $this->need('biometrics.manage'); $this->post(); $t=$this->teacher($_POST['id']??0); $note=nz($_POST['note']??'');
        if($t['termination_status']!=='active'||$t['biometrics_status']!=='captured'||$t['verified']){ flash('danger','This teacher cannot be verified right now.'); $this->redirect('biometrics/index'); }
        if(($_POST['confirm']??'')!=='1'){ flash('danger','Tick the box to confirm you have checked the teacher\'s identity.'); $this->redirect('biometrics/verify',['id'=>$t['id']]); }
        DB::q('UPDATE teachers SET verified=1,verified_by=?,verified_at=NOW() WHERE id=?',[Auth::user()['id'],$t['id']]);
        Audit::log('teacher_verified','teacher',$t['id'],['verified'=>0],['verified'=>1,'note'=>$note]); flash('ok',e(fullname($t)).' is now verified.'); $this->redirect('biometrics/index');
    }
    function revoke(){
        $this->need('biometrics.manage'); $this->post(); $t=$this->teacher($_POST['id']??0); $why=nz($_POST['reason']??'');
        if(!$why){ flash('danger','Give a reason for revoking verification.'); $this->redirect('biometrics/verify',['id'=>$t['id']]); }
        DB::q('UPDATE teachers SET verified=0,verified_by=NULL,verified_at=NULL WHERE id=?',[$t['id']]); Audit::log('verification_revoked','teacher',$t['id'],['verified'=>1],['verified'=>0,'reason'=>$why]);
        flash('ok','Verification revoked.'); $this->redirect('biometrics/verify',['id'=>$t['id']]);
    }
}
