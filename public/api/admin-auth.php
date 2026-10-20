<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_input();
    require_csrf($body);
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
    if ($lockedUntil > time()) json_response(['error'=>'Ko‘p urinish. Bir necha daqiqadan keyin qayta urinib ko‘ring.'],429);
    if (!admin_credentials_valid((string)($body['login']??''),(string)($body['password']??''))) {
        $_SESSION['login_attempts']=$attempts+1;
        if ($attempts+1>=5) {$_SESSION['login_locked_until']=time()+300;$_SESSION['login_attempts']=0;}
        json_response(['error'=>'Login yoki parol noto‘g‘ri'],401);
    }
    $_SESSION['login_attempts']=0;
    login_admin((string)$body['login']);
    json_response(['ok'=>true]);
}
json_response(['error'=>'Method not allowed'],405);
