<?php
class AuthController extends Controller {
    function login(){
        if(Auth::user()) $this->redirect('dashboard/index');
        if($_SERVER['REQUEST_METHOD']==='POST'){
            Csrf::verify();
            $err=Auth::attempt($_POST['email']??'',$_POST['password']??'');
            if(!$err){ $this->redirect(Auth::user()['must_change_password'] ? 'auth/password' : 'dashboard/index'); }
            $_SESSION['old']=['email'=>$_POST['email']??'']; flash('danger',$err); $this->redirect('auth/login');
        }
        $this->view('auth/login',[],'layout/auth');
    }
    function logout(){ $this->post(); Auth::logout(); session_start(); flash('ok','You have been signed out.'); $this->redirect('auth/login'); }
    function password(){
        $this->needLogin(); $u=Auth::user();
        if($_SERVER['REQUEST_METHOD']==='POST'){
            Csrf::verify(); $cur=$_POST['current']??''; $new=$_POST['new']??'';
            if(!password_verify($cur,$u['password_hash'])) flash('danger','Your current password is incorrect.');
            elseif(strlen($new)<10 || $new!==($_POST['confirm']??'')) flash('danger','The new password must be at least 10 characters and match the confirmation.');
            else {
                DB::q('UPDATE users SET password_hash=?,must_change_password=0 WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$u['id']]);
                Audit::log('password_changed','user',$u['id']); flash('ok','Password changed.'); $this->redirect('dashboard/index');
            }
            $this->redirect('auth/password');
        }
        $this->view('auth/password',['forced'=>$u['must_change_password']]);
    }
}
