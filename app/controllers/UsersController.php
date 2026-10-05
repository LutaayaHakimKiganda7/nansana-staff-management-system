<?php
class UsersController extends Controller {
    function index(){
        $this->need('users.manage');
        $q=trim($_GET['q']??''); $role=$_GET['role']??'';
        $sql='SELECT * FROM users WHERE 1'; $p=[];
        if($q!==''){ $sql.=' AND (name LIKE ? OR email LIKE ?)'; $p[]="%$q%"; $p[]="%$q%"; }
        if(isset(Auth::ROLES[$role])){ $sql.=' AND role=?'; $p[]=$role; }
        $users=DB::all($sql.' ORDER BY name',$p);
        $this->view('users/index',compact('users','q','role'));
    }
    function form(){
        $this->need('users.manage');
        $user=isset($_GET['id']) ? DB::row('SELECT * FROM users WHERE id=?',[(int)$_GET['id']]) : null;
        $this->view('users/form',compact('user'));
    }
    function save(){
        $this->need('users.manage'); $this->post();
        $id=(int)($_POST['id']??0);
        $d=['name'=>trim($_POST['name']??''),'email'=>strtolower(trim($_POST['email']??'')),'phone'=>trim($_POST['phone']??''),
            'role'=>$_POST['role']??'viewer','status'=>($_POST['status']??'active')==='disabled'?'disabled':'active'];
        $err=null;
        if($d['name']==='') $err='Enter the full name.';
        elseif(!filter_var($d['email'],FILTER_VALIDATE_EMAIL)) $err='Enter a valid email address.';
        elseif(!isset(Auth::ROLES[$d['role']])) $err='Choose a role.';
        elseif(DB::val('SELECT COUNT(*) FROM users WHERE email=? AND id<>?',[$d['email'],$id])) $err='That email is already used by another account.';
        elseif($id===Auth::user()['id'] && ($d['status']==='disabled' || $d['role']!=='admin')) $err='You cannot disable or demote your own account.';
        if($err){ $_SESSION['old']=$d; flash('danger',$err); $this->redirect('users/form',$id?['id'=>$id]:[]); }
        if($id){
            $old=DB::row('SELECT * FROM users WHERE id=?',[$id]);
            DB::q('UPDATE users SET name=?,email=?,phone=?,role=?,status=? WHERE id=?',[...array_values($d),$id]);
            [$o,$n]=Audit::diff($old,$d); if($n) Audit::log('user_updated','user',$id,$o,$n);
            flash('ok','Changes saved.');
        } else {
            $tmp=substr(bin2hex(random_bytes(6)),0,10).'A!';
            $id=DB::insert('INSERT INTO users (name,email,phone,role,status,password_hash,must_change_password) VALUES (?,?,?,?,?,?,1)',[...array_values($d),password_hash($tmp,PASSWORD_DEFAULT)]);
            Audit::log('user_created','user',$id,null,$d);
            flash('ok',"User added. Temporary password: <b>$tmp</b> (shown once; they must change it at first sign-in).");
        }
        $this->redirect('users/index');
    }
    function reset(){
        $this->need('users.manage'); $this->post(); $id=(int)$_POST['id'];
        $tmp=substr(bin2hex(random_bytes(6)),0,10).'A!';
        DB::q('UPDATE users SET password_hash=?,must_change_password=1,failed_attempts=0,locked_until=NULL WHERE id=?',[password_hash($tmp,PASSWORD_DEFAULT),$id]);
        Audit::log('password_reset','user',$id);
        flash('ok',"Password reset. Temporary password: <b>$tmp</b> (shown once).");
        $this->redirect('users/index');
    }
}
