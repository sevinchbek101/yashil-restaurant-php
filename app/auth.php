<?php
declare(strict_types=1);

function admin_logged_in(): bool
{
    return ($_SESSION['admin'] ?? false) === true && isset($_SESSION['admin_login']);
}

function require_admin(bool $json = false): void
{
    if (admin_logged_in()) return;
    if ($json) json_response(['error' => 'Kirish talab qilinadi'], 401);
    header('Location: /admin/login');
    exit;
}

function admin_credentials_valid(string $login, string $password): bool
{
    $expectedLogin = (string) config('ADMIN_LOGIN', 'admin');
    $hash = (string) config('ADMIN_PASSWORD_HASH', '');
    $plain = (string) config('ADMIN_PASSWORD', '');
    $passwordOk = $hash !== ''
        ? password_verify($password, $hash)
        : ($plain !== '' && hash_equals($plain, $password));
    return hash_equals($expectedLogin, $login) && $passwordOk;
}

function login_admin(string $login): void
{
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['admin_login'] = $login;
    $_SESSION['admin_at'] = time();
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
