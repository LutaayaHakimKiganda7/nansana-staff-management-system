<?php
class DashboardController extends Controller {
    function index(){
        $this->needLogin();
        if(Auth::user()['must_change_password']) $this->redirect('auth/password');
        $y=(int)date('Y'); $act='termination_status="active"';
        $s=[ 'schools'=>DB::val('SELECT COUNT(*) FROM schools WHERE status="active"'),
             'primary'=>DB::val('SELECT COUNT(*) FROM schools WHERE status="active" AND type="primary"'),
             'secondary'=>DB::val('SELECT COUNT(*) FROM schools WHERE status="active" AND type="secondary"'),
             'teachers'=>DB::val("SELECT COUNT(*) FROM teachers WHERE $act"),
             'verified'=>DB::val("SELECT COUNT(*) FROM teachers WHERE $act AND verified=1"),
             'on_payroll'=>DB::val("SELECT COUNT(*) FROM teachers WHERE $act AND payroll_status='on_payroll'"),
             'off_payroll'=>DB::val("SELECT COUNT(*) FROM teachers WHERE $act AND payroll_status='off_payroll'"),
             'retire_year'=>DB::val("SELECT COUNT(*) FROM teachers WHERE $act AND retirement_year=?",[$y]),
             'retire_next'=>DB::val("SELECT COUNT(*) FROM teachers WHERE $act AND retirement_year=?",[$y+1]),
             'pending'=>DB::val('SELECT COUNT(*) FROM movements WHERE status="pending"'),
             'awaiting'=>DB::val('SELECT COUNT(*) FROM teachers WHERE termination_status="active" AND biometrics_status="captured" AND verified=0'),
             'users'=>DB::val('SELECT COUNT(*) FROM users WHERE status="active"'),
             'failed'=>DB::val('SELECT COUNT(*) FROM audit_logs WHERE action="login_failed" AND created_at>NOW()-INTERVAL 24 HOUR')];
        $s['unverified']=$s['teachers']-$s['verified'];
        $retiring=DB::all("SELECT t.*,s.name school_name".TFROM." WHERE t.$act AND t.retirement_year BETWEEN ? AND ? ORDER BY t.retirement_year,t.date_of_birth LIMIT 6",[$y,$y+1]);
        $recent=Auth::can('audit.view')?DB::all('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 6'):[];
        $this->view('dashboard/index',compact('s','retiring','recent','y'));
    }
}
