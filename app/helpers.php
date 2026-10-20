<?php
declare(strict_types=1);

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) json_response(['error' => 'Noto‘g‘ri JSON'], 400);
    return $data;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}

function require_csrf(array $body): void
{
    $token = (string) ($body['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if ($token === '' || !hash_equals(csrf_token(), $token)) json_response(['error' => 'Sessiya eskirgan. Sahifani yangilang.'], 419);
}

function clean_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?: '';
}

function public_order(array $order): array
{
    $order['items'] = is_string($order['items']) ? (json_decode($order['items'], true) ?: []) : $order['items'];
    unset($order['customer_email'], $order['address'], $order['checkout_payment_id'], $order['checkout_payment_uuid']);
    return $order;
}

function money(int|float|string $amount): string
{
    return number_format((float) $amount, 0, '.', ' ') . ' so‘m';
}

function setting(string $key, ?string $default = null): ?string
{
    $row = query_one('SELECT value FROM settings WHERE `key` = ?', [$key]);
    return $row['value'] ?? $default;
}

function save_setting(string $key, string $value): void
{
    execute_sql('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$key, $value]);
}
