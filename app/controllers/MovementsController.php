<?php
class MovementsController extends Controller {
    const GROUPS=['transfer'=>['transfer'],'retirement'=>['retirement']];
    function index(){
        $this->need('teachers.view');
        $g=$_GET['group']??''; $st=$_GET['status']??'pending'; $p=[]; $w=' WHERE 1';
        if(isset(self::GROUPS[$g])){ $w.=' AND m.type IN ("'.implode('","',self::GROUPS[$g]).'")'; } else $g='';
        if(in_array($st,['pending','approved','rejected','cancelled'])){ $w.=' AND m.status=?'; $p[]=$st; } else $st='';
        $rows=DB::all('SELECT m.*,t.surname,t.first_name,t.registration_no,fs.name from_name,ts.name to_name,ru.name req_name,du.name dec_name FROM movements m JOIN teachers t ON t.id=m.teacher_id
            LEFT JOIN schools fs ON fs.id=m.from_school_id LEFT JOIN schools ts ON ts.id=m.to_school_id LEFT JOIN users ru ON ru.id=m.requested_by LEFT JOIN users du ON du.id=m.decided_by'.$w.' ORDER BY m.id DESC LIMIT 200',$p);
        $this->view('movements/index',compact('rows','g','st'));
    }
    function create(){
        $this->needLogin(); $type=$_GET['type']??''; if(!isset(MOVE_TYPES[$type])) $this->redirect('movements/index');
        if(!can_request($type)){ http_response_code(403); $this->view('403'); return; }
        $t=null; $results=[]; $q=trim($_GET['q']??'');
        if(!empty($_GET['teacher_id'])) $t=DB::row('SELECT t.*,s.name school_name FROM teachers t LEFT JOIN schools s ON s.id=t.school_id WHERE t.id=?',[(int)$_GET['teacher_id']]);
        elseif($q!==''){ $like='%'.$q.'%'; $results=DB::all('SELECT t.*,s.name school_name FROM teachers t LEFT JOIN schools s ON s.id=t.school_id WHERE t.termination_status="active" AND (t.surname LIKE ? OR t.first_name LIKE ? OR t.registration_no LIKE ? OR t.nin LIKE ?) ORDER BY t.surname LIMIT 10',[$like,$like,$like,$like]); }
        $schools=DB::all('SELECT id,name,type FROM schools WHERE status="active" ORDER BY name');
        $problem=$t?$this->check($t,$type):null;
        $this->view('movements/create',compact('type','t','results','q','schools','problem'));
    }
    private function check($t,$type){
        if($t['termination_status']!=='active') return 'This teacher is no longer active ('.$t['termination_status'].').';
        if(DB::val('SELECT COUNT(*) FROM movements WHERE teacher_id=? AND status="pending"',[$t['id']])) return 'This teacher already has a request waiting for approval. Decide or cancel it first.';
        if($type==='transfer' && !$t['school_id']) return 'This teacher has no current school.';
        return null;
    }
    function save(){
        $this->needLogin(); $this->post(); $type=$_POST['type']??''; $tid=(int)($_POST['teacher_id']??0);
        if(!isset(MOVE_TYPES[$type]) || !can_request($type)){ http_response_code(403); exit('Not allowed.'); }
        $t=DB::row('SELECT * FROM teachers WHERE id=?',[$tid]); $back=['type'=>$type,'teacher_id'=>$tid];
        $eff=$_POST['effective_date']??''; $reason=nz($_POST['reason']??''); $to=(int)($_POST['to_school_id']??0); $des=nz($_POST['new_designation']??''); $out=null;
        $err=$t?$this->check($t,$type):'Choose a teacher.';
        if(!$err && !valid_date($eff)) $err='Enter a valid effective date.';
        if(!$err && $type==='transfer'){
            if(!$to || $to==$t['school_id'] || !DB::val('SELECT COUNT(*) FROM schools WHERE id=? AND status="active"',[$to])) $err='Choose a different, active destination school.';
            elseif($t['school_since'] && $eff<$t['school_since']) $err='The effective date is before the teacher joined the current school.';
        }
        if($err){ flash('danger',$err); $this->redirect('movements/create',$back); }
        $id=DB::insert('INSERT INTO movements (type,teacher_id,from_school_id,to_school_id,new_designation,outcome,effective_date,reason,requested_by) VALUES (?,?,?,?,?,?,?,?,?)',
            [$type,$tid,$type==='transfer'?$t['school_id']:null,$type==='transfer'?$to:null,$type==='transfer'?$des:null,$out,$eff,$reason,Auth::user()['id']]);
        Audit::log('movement_requested','teacher',$tid,null,['request'=>$id,'type'=>$type,'effective'=>$eff,'reason'=>$reason,'to_school'=>$to?:null]);
        flash('ok','Request submitted for approval.'); $this->redirect('movements/index',['status'=>'pending']);
    }
    function decide(){
        $this->need('movements.approve'); $this->post(); $id=(int)$_POST['id']; $note=nz($_POST['note']??''); $approve=($_POST['decision']??'')==='approve';
        $m=DB::row('SELECT * FROM movements WHERE id=?',[$id]); $me=Auth::user();
        if(!$m||$m['status']!=='pending'){ flash('danger','That request is no longer pending.'); $this->redirect('movements/index'); }
        if($approve && !isset(MOVE_WHO[$m['type']])){ flash('danger','This request type is no longer available for approval.'); $this->redirect('movements/index'); }
        if($m['requested_by']==$me['id'] && !cfg('allow_self_approval')){ flash('danger','You cannot decide a request you submitted. Ask another approver.'); $this->redirect('movements/index'); }
        if(!$approve && !$note){ flash('danger','Give a reason when rejecting a request.'); $this->redirect('movements/index'); }
        $pdo=DB::pdo(); $pdo->beginTransaction();
        try{
            $changes=[null,null];
            if($approve) $changes=$this->apply($m);
            DB::q('UPDATE movements SET status=?,decided_by=?,decided_at=NOW(),decision_note=? WHERE id=?',[$approve?'approved':'rejected',$me['id'],$note,$id]);
            $pdo->commit();
        }catch(Exception $ex){ $pdo->rollBack(); flash('danger',$ex->getMessage()); $this->redirect('movements/index'); }
        Audit::log($approve?'movement_approved':'movement_rejected','teacher',$m['teacher_id'],$changes[0],($changes[1]??[])+['request'=>$id,'type'=>$m['type'],'note'=>$note]);
        if($approve){ try{ Notify::movement($m); }catch(Throwable $ex){} }
        flash('ok',$approve?'Approved and applied to the teacher record.':'Request rejected.'); $this->redirect('movements/index');
    }
    private function apply($m){
        $t=DB::row('SELECT * FROM teachers WHERE id=? FOR UPDATE',[$m['teacher_id']]);
        if($t['termination_status']!=='active') throw new Exception('This teacher is no longer active.');
        $eff=$m['effective_date'];
        switch($m['type']){
            case 'transfer':
                if($t['school_id']!=$m['from_school_id']) throw new Exception('The teacher\'s school changed since this request. Cancel it and submit a new one.');
                DB::q('UPDATE teacher_postings SET to_date=? WHERE teacher_id=? AND to_date IS NULL',[$eff,$t['id']]);
                $des=$m['new_designation']?:$t['designation'];
                DB::q('INSERT INTO teacher_postings (teacher_id,school_id,designation,from_date) VALUES (?,?,?,?)',[$t['id'],$m['to_school_id'],$des,$eff]);
                DB::q('UPDATE teachers SET school_id=?,school_since=?,designation=? WHERE id=?',[$m['to_school_id'],$eff,$des,$t['id']]);
                return [['school_id'=>$t['school_id'],'designation'=>$t['designation']],['school_id'=>$m['to_school_id'],'designation'=>$des,'effective'=>$eff]];
            case 'retirement':
                $new='retired';
                DB::q('UPDATE teacher_postings SET to_date=? WHERE teacher_id=? AND to_date IS NULL',[$eff,$t['id']]);
                DB::q('UPDATE teachers SET termination_status=?,payroll_status="off_payroll" WHERE id=?',[$new,$t['id']]);
                return [['termination_status'=>'active','payroll_status'=>$t['payroll_status']],['termination_status'=>$new,'payroll_status'=>'off_payroll','effective'=>$eff]];
            default:
                throw new Exception('This request type is no longer supported.');
        }
    }
    function cancel(){
        $this->needLogin(); $this->post(); $id=(int)$_POST['id']; $m=DB::row('SELECT * FROM movements WHERE id=?',[$id]); $me=Auth::user();
        if($m && $m['status']==='pending' && ($m['requested_by']==$me['id'] || $me['role']==='admin')){
            DB::q('UPDATE movements SET status="cancelled",decided_by=?,decided_at=NOW() WHERE id=?',[$me['id'],$id]);
            Audit::log('movement_cancelled','teacher',$m['teacher_id'],null,['request'=>$id,'type'=>$m['type']]); flash('ok','Request cancelled.');
        } else flash('danger','You cannot cancel that request.');
        $this->redirect('movements/index');
    }
}
