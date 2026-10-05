<?php
// Daily from Hostinger hPanel > Advanced > Cron Jobs:  php /home/USER/path/to/mgtm/cron/backup.php
if(PHP_SAPI!=='cli') exit('CLI only');
require __DIR__.'/../app/core/Core.php'; require __DIR__.'/../app/core/Backup.php'; date_default_timezone_set(cfg('timezone'));
echo date('c').' backup written: '.Backup::run()."\n";
