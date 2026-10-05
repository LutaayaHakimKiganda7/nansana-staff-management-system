<?php
// One-time installer. DELETE this file after use.
require __DIR__.'/../app/core/Core.php';
$msg=''; $done=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $pdo=DB::pdo();
        foreach(['schema.sql','phase2_4.sql','phase5.sql','phase6.sql','phase7.sql'] as $f) foreach(array_filter(array_map('trim',explode(';',file_get_contents(__DIR__.'/../database/'.$f)))) as $sql){ $pdo->exec($sql); }
        if(DB::val('SELECT COUNT(*) FROM users')>0) throw new Exception('Already installed. Delete install.php.');
        $email=strtolower(trim($_POST['email'])); $pw=$_POST['password'];
        if(!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pw)<10) throw new Exception('Use a valid email and a password of at least 10 characters.');
        DB::q('INSERT INTO users (name,email,role,password_hash) VALUES (?,?,?,?)',[trim($_POST['name']),$email,'admin',password_hash($pw,PASSWORD_DEFAULT)]);
        $done=true;
    }catch(Throwable $ex){ $msg=$ex->getMessage(); }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install MGTM</title>
<link rel="stylesheet" href="assets/css/app.css"></head><body class="auth"><main class="auth-card" style="margin:auto">
<h1>Install MGTM System</h1>
<?php if($done): ?><p class="ok">Installed. Delete <b>public/install.php</b> now, then <a href="index.php">sign in</a>.</p>
<?php else: ?><p class="muted">Creates the tables and your System Admin account.</p>
<?php if($msg): ?><div class="alert danger"><?=e($msg)?></div><?php endif; ?>
<form method="post" class="form"><label>Full name<input name="name" required></label><label>Email<input type="email" name="email" required></label>
<label>Password (10+ characters)<input type="password" name="password" required minlength="10"></label><button class="btn primary">Install</button></form><?php endif; ?>
</main></body></html>
