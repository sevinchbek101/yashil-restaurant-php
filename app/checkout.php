<?php
declare(strict_types=1);

const CHECKOUT_API = 'https://checkout.uz/api/v1';
const CHECKOUT_KEY_SETTING = 'checkout_api_key_encrypted';

function encryption_key(): string
{
    $secret = (string) config('APP_SECRET', '');
    if (strlen($secret) < 32) throw new RuntimeException('APP_SECRET kamida 32 belgidan iborat bo‘lishi kerak');
    return hash('sha256', $secret, true);
}

function encrypt_secret(string $value): string
{
    $iv = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt($value, 'aes-256-gcm', encryption_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($encrypted === false) throw new RuntimeException('Kalitni shifrlab bo‘lmadi');
    return 'v1.' . rtrim(strtr(base64_encode($iv), '+/', '-_'), '=') . '.' . rtrim(strtr(base64_encode($tag), '+/', '-_'), '=') . '.' . rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
}

function decode_b64url(string $value): string
{
    return base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true) ?: '';
}

function decrypt_secret(string $value): ?string
{
    $parts = explode('.', $value);
    if (count($parts) !== 4 || $parts[0] !== 'v1') return null;
    $plain = openssl_decrypt(decode_b64url($parts[3]), 'aes-256-gcm', encryption_key(), OPENSSL_RAW_DATA, decode_b64url($parts[1]), decode_b64url($parts[2]));
    return $plain === false ? null : $plain;
}

function checkout_api_key(): ?string
{
    $encrypted = setting(CHECKOUT_KEY_SETTING);
    return $encrypted ? decrypt_secret($encrypted) : null;
}

function checkout_request(string $apiKey, string $endpoint, ?array $body = null): array
{
    $ch = curl_init(CHECKOUT_API . '/' . rawurlencode($endpoint));
    $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json']];
    if ($body !== null) $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($raw === false) throw new RuntimeException('CHECKOUT.UZ ulanish xatosi: ' . $error);
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new RuntimeException('CHECKOUT.UZ javobi o‘qilmadi');
    if ($status < 200 || $status >= 300) throw new RuntimeException(substr((string) ($data['error'] ?? "CHECKOUT.UZ xatosi ({$status})"), 0, 240));
    return $data;
}
