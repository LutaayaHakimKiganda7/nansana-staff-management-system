<?php
class OfflineController extends Controller {
    function manifest(){
        $this->need('teachers.view'); $m=Auth::can('teachers.manage');
        $u=[url('dashboard/index'),url('teachers/index'),url('ledger/index'),url('schools/index'),url('retirement/index')]; if($m){ $u[]=url('teachers/form'); $u[]=url('schools/form'); }
        foreach(DB::all('SELECT id FROM teachers WHERE termination_status="active" ORDER BY id LIMIT 1500') as $r){ $u[]=url('ledger/profile',['id'=>$r['id']]); if($m) $u[]=url('teachers/form',['id'=>$r['id']]); }
        if($m) foreach(DB::all('SELECT id FROM schools') as $r) $u[]=url('schools/form',['id'=>$r['id']]);
        Audit::log('offline_data_prepared','system',null,null,['pages'=>count($u)]);
        header('Content-Type: application/json'); header('Cache-Control: no-store'); echo json_encode(['urls'=>$u]); exit;
    }
}
