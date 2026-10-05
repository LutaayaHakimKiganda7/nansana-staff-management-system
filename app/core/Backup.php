<?php
class Backup {
    static function dir(){ $d=__DIR__.'/../../storage/backups'; if(!is_dir($d)) mkdir($d,0750,true); return realpath($d); }
    static function files(){ $o=[]; foreach(glob(self::dir().'/mgtm-*.sql.gz') as $f) $o[]=['name'=>basename($f),'size'=>filesize($f),'time'=>filemtime($f)]; usort($o,fn($a,$b)=>$b['time']<=>$a['time']); return $o; }
    static function run($keep=14){
        $pdo=DB::pdo(); $name='mgtm-'.date('Ymd-His').'.sql.gz'; $path=self::dir().'/'.$name; $gz=gzopen($path,'wb9');
        gzwrite($gz,"-- MGTM backup ".date('c')."\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n");
        foreach($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t){
            gzwrite($gz,"DROP TABLE IF EXISTS `$t`;\n".$pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1].";\n");
            for($o=0;;$o+=100){ $rows=$pdo->query("SELECT * FROM `$t` LIMIT 100 OFFSET $o")->fetchAll(PDO::FETCH_NUM); if(!$rows) break;
                $v=array_map(fn($r)=>'('.implode(',',array_map(fn($x)=>$x===null?'NULL':$pdo->quote((string)$x),$r)).')',$rows); gzwrite($gz,"INSERT INTO `$t` VALUES ".implode(',',$v).";\n"); }
        }
        gzwrite($gz,"SET FOREIGN_KEY_CHECKS=1;\n"); gzclose($gz);
        foreach(array_slice(self::files(),$keep) as $f) @unlink(self::dir().'/'.$f['name']);
        return $name;
    }
}
