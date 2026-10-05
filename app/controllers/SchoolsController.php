<?php
class SchoolsController extends Controller {
    function index(){
        $this->need('teachers.view');
        $q=trim($_GET['q']??''); $type=$_GET['type']??''; $st=$_GET['status']??'';
        $sql='SELECT s.*,(SELECT COUNT(*) FROM teachers t WHERE t.school_id=s.id AND t.termination_status="active") n FROM schools s WHERE 1'; $p=[];
        if($q!==''){ $sql.=' AND (name LIKE ? OR location LIKE ? OR emis_code LIKE ? OR uneb_centre_no LIKE ?)'; array_push($p,"%$q%","%$q%","%$q%","%$q%"); }
        if(in_array($type,['primary','secondary'])){ $sql.=' AND type=?'; $p[]=$type; }
        if(in_array($st,['active','inactive'])){ $sql.=' AND status=?'; $p[]=$st; }
        $schools=DB::all($sql.' ORDER BY name',$p);
        $this->view('schools/index',compact('schools','q','type','st'));
    }
    function form(){
        $this->need('schools.manage');
        $school=isset($_GET['id'])?DB::row('SELECT * FROM schools WHERE id=?',[(int)$_GET['id']]):null;
        $this->view('schools/form',compact('school'));
    }
    function save(){
        $this->need('schools.manage'); $this->post(); $id=(int)($_POST['id']??0);
        $d=['name'=>trim($_POST['name']??''),'type'=>($_POST['type']??'')==='secondary'?'secondary':'primary','location'=>nz($_POST['location']??''),
            'po_box'=>nz($_POST['po_box']??''),'uneb_centre_no'=>nz($_POST['uneb_centre_no']??''),'emis_code'=>nz($_POST['emis_code']??''),
            'status'=>($_POST['status']??'active')==='inactive'?'inactive':'active'];
        $err=null;
        if($d['name']==='') $err='Enter the school name.';
        elseif($d['emis_code'] && DB::val('SELECT COUNT(*) FROM schools WHERE emis_code=? AND id<>?',[$d['emis_code'],$id])) $err='That EMIS code is already registered to another school.';
        elseif($id && $d['status']==='inactive' && DB::val('SELECT COUNT(*) FROM teachers WHERE school_id=? AND termination_status="active"',[$id])) $err='This school still has teachers. Move them before deactivating it.';
        if($err){ $_SESSION['old']=$d; flash('danger',$err); $this->redirect('schools/form',$id?['id'=>$id]:[]); }
        if($id){
            $old=DB::row('SELECT * FROM schools WHERE id=?',[$id]);
            DB::q('UPDATE schools SET name=?,type=?,location=?,po_box=?,uneb_centre_no=?,emis_code=?,status=? WHERE id=?',[...array_values($d),$id]);
            [$o,$n]=Audit::diff($old,$d); if($n) Audit::log('school_updated','school',$id,$o,$n);
        } else {
            $id=DB::insert('INSERT INTO schools (name,type,location,po_box,uneb_centre_no,emis_code,status) VALUES (?,?,?,?,?,?,?)',array_values($d));
            Audit::log('school_created','school',$id,null,$d);
        }
        flash('ok','School saved.'); $this->redirect('schools/index');
    }
}
