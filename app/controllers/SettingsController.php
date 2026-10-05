<?php
class SettingsController extends Controller {
    const KEYS=['system_name','municipality','district','contact_email','contact_phone'];
    function index(){
        $this->need('settings.manage');
        if($_SERVER['REQUEST_METHOD']==='POST'){
            Csrf::verify(); $old=$new=[];
            foreach(self::KEYS as $k){ $v=trim($_POST[$k]??''); if($v!==setting($k)){ $old[$k]=setting($k); $new[$k]=$v; }
                DB::q('INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)',[$k,$v]); }
            if($new) Audit::log('settings_updated','settings',null,$old,$new);
            flash('ok','Settings saved.'); $this->redirect('settings/index');
        }
        $this->view('settings/index');
    }
}
