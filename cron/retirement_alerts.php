<?php
// Run daily from Hostinger hPanel > Advanced > Cron Jobs:  php /home/USER/path/to/mgtm/cron/retirement_alerts.php
if(PHP_SAPI!=='cli') exit('CLI only');
require __DIR__.'/../app/core/Core.php'; require __DIR__.'/../app/core/Helpers.php'; require __DIR__.'/../app/core/Notify.php';
date_default_timezone_set(cfg('timezone'));
$days=max(1,(int)setting('retirement_notice_days','90')); $sent=0; $n=0;
$rows=DB::all('SELECT t.*,s.name school_name,DATE_ADD(t.date_of_birth,INTERVAL t.retirement_age YEAR) rdate FROM teachers t LEFT JOIN schools s ON s.id=t.school_id
  WHERE t.termination_status="active" HAVING rdate BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL ? DAY)
  AND NOT EXISTS (SELECT 1 FROM message_log l WHERE l.teacher_id=t.id AND l.template_code="retirement_reminder" AND l.status="sent")',[$days]);
foreach($rows as $t){ $n++; $sent+=Notify::event('retirement_reminder',$t,['date'=>fdate($t['rdate'])]); }
echo date('c')." teachers checked: $n, messages sent: $sent\n";
