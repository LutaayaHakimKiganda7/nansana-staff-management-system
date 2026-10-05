<?php
class RetirementController extends Controller {
    function index(){
        $this->need('teachers.view'); $y0=(int)date('Y'); $year=(int)($_GET['year']??$y0); if($year<$y0||$year>$y0+10) $year=$y0;
        $rows=DB::all('SELECT t.*,s.name school_name,DATE_ADD(t.date_of_birth,INTERVAL t.retirement_age YEAR) retire_date,
            (SELECT COUNT(*) FROM movements m WHERE m.teacher_id=t.id AND m.status="pending") pending'.TFROM.' WHERE t.termination_status="active" AND t.retirement_year<=? ORDER BY t.retirement_year,t.date_of_birth',[$year]);
        $this->view('retirement/index',compact('rows','year','y0'));
    }
}
