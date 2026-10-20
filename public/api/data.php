<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (($_GET['track'] ?? '') === '1') {
            $id = (int) ($_GET['order'] ?? 0);
            $phone = clean_phone((string) ($_GET['phone'] ?? ''));
            if (!$id && !$phone) json_response(['error' => 'Buyurtma raqami yoki telefon raqamidan birini kiriting'], 400);
            $fields = 'id,customer_name,phone,items,subtotal,discount,delivery_fee,total,status,payment,payment_status,created_at';
            $orders = $id
                ? array_filter([query_one("SELECT {$fields} FROM orders WHERE id = ?", [$id])])
                : query_all("SELECT {$fields} FROM orders WHERE phone_normalized = ? ORDER BY id DESC", [$phone]);
            if (!$orders) json_response(['error' => 'Buyurtma topilmadi. Raqam yoki telefonni tekshiring'], 404);
            $orders = array_values(array_map('public_order', $orders));
            json_response(['order' => $orders[0], 'orders' => $orders]);
        }

        $admin = ($_GET['admin'] ?? '') === '1';
        if ($admin) require_admin(true);
        $products = query_all('SELECT * FROM products ' . ($admin ? '' : 'WHERE available = 1 ') . 'ORDER BY id DESC');
        if (!$admin) {
            json_response([
                'products' => $products,
                'zones' => query_all('SELECT * FROM zones WHERE active = 1 ORDER BY fee'),
                'checkoutEnabled' => checkout_api_key() !== null,
                'csrf' => csrf_token(),
            ]);
        }
        $settings = [];
        foreach (query_all('SELECT `key`, value FROM settings WHERE `key` != ?', [CHECKOUT_KEY_SETTING]) as $row) $settings[$row['key']] = $row['value'];
        $stats = query_one("SELECT COUNT(*) orders,COALESCE(SUM(total),0) revenue,COALESCE(AVG(total),0) avg_order,SUM(status='new') new_orders FROM orders") ?: [];
        json_response([
            'products'=>$products,
            'orders'=>query_all('SELECT * FROM orders ORDER BY id DESC'),
            'customers'=>query_all('SELECT * FROM customers ORDER BY total_spent DESC'),
            'promos'=>query_all('SELECT * FROM promos ORDER BY id DESC'),
            'zones'=>query_all('SELECT * FROM zones ORDER BY id'),
            'settings'=>$settings,
            'checkout_configured'=>checkout_api_key() !== null,
            'stats'=>$stats,
            'csrf'=>csrf_token(),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'Method not allowed'], 405);
    $body = json_input();
    require_csrf($body);
    $action = (string) ($body['action'] ?? '');

    if ($action === 'create-order') {
        $name = trim((string) ($body['name'] ?? ''));
        $email = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone = trim((string) ($body['phone'] ?? ''));
        $address = trim((string) ($body['address'] ?? ''));
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        if ($name === '' || !$email || $phone === '' || $address === '' || !$items) json_response(['error' => 'Barcha maydonlarni to‘ldiring'], 422);
        if (mb_strlen($name) > 160 || mb_strlen($address) > 1200) json_response(['error' => 'Kiritilgan ma’lumot juda uzun'], 422);

        $payment = ($body['payment'] ?? '') === 'checkout' ? 'checkout' : 'cash_on_delivery';
        $apiKey = $payment === 'checkout' ? checkout_api_key() : null;
        if ($payment === 'checkout' && !$apiKey) json_response(['error' => 'Onlayn to‘lov admin panelda sozlanmagan'], 503);

        $ids = array_values(array_filter(array_map(fn($item) => (int) ($item['id'] ?? 0), $items)));
        if (!$ids) json_response(['error' => 'Savat bo‘sh'], 422);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = query_all("SELECT id,name,price FROM products WHERE available = 1 AND id IN ({$placeholders})", $ids);
        $byId = [];
        foreach ($rows as $row) $byId[(int) $row['id']] = $row;
        $trustedItems = [];
        foreach ($ids as $id) if (isset($byId[$id])) $trustedItems[] = $byId[$id];
        if (!$trustedItems) json_response(['error' => 'Mahsulotlar topilmadi'], 422);
        $subtotal = array_sum(array_map(fn($item) => (int) $item['price'], $trustedItems));
        $discount = 0;
        $promoCode = strtoupper(trim((string) ($body['promo'] ?? '')));
        if ($promoCode !== '') {
            $promo = query_one('SELECT percent FROM promos WHERE code = ? AND active = 1', [$promoCode]);
            if ($promo) $discount = (int) round($subtotal * (int) $promo['percent'] / 100);
        }
        $zone = query_one('SELECT fee FROM zones WHERE id = ? AND active = 1', [(int) ($body['zoneId'] ?? 0)]);
        if (!$zone) json_response(['error' => 'Yetkazib berish hududini tanlang'], 422);
        $fee = (int) $zone['fee'];
        $total = $subtotal - $discount + $fee;
        if ($total < 1000 || $total > 10000000) json_response(['error' => 'Summa ruxsat etilgan chegaradan tashqarida'], 422);

        db()->beginTransaction();
        try {
            execute_sql('INSERT INTO orders(customer_name,customer_email,phone,phone_normalized,address,items,subtotal,discount,delivery_fee,total,status,payment,payment_status) VALUES(?,?,?,?,?,?,?,?,?,?,\'new\',?,?)', [
                $name, $email, $phone, clean_phone($phone), $address, json_encode($trustedItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $subtotal, $discount, $fee, $total, $payment, $payment === 'checkout' ? 'initiating' : 'unpaid'
            ]);
            $orderId = (int) db()->lastInsertId();
            execute_sql('INSERT INTO customers(name,email,phone,order_count,total_spent) VALUES(?,?,?,1,?) ON DUPLICATE KEY UPDATE name=VALUES(name),phone=VALUES(phone),order_count=order_count+1,total_spent=total_spent+VALUES(total_spent)', [$name,$email,$phone,$total]);
            db()->commit();
        } catch (Throwable $exception) {
            db()->rollBack();
            throw $exception;
        }

        $paymentUrl = null;
        if ($payment === 'checkout' && $apiKey) {
            $webhookSecret = (string) config('CHECKOUT_WEBHOOK_SECRET', '');
            if (strlen($webhookSecret) < 16) {
                execute_sql('DELETE FROM orders WHERE id = ?', [$orderId]);
                json_response(['error' => 'Webhook xavfsizlik kaliti sozlanmagan'], 503);
            }
            try {
                $created = checkout_request($apiKey, 'create_payment', [
                    'amount'=>$total,
                    'description'=>"YASHIL buyurtma #{$orderId}",
                    'webhook_url'=>base_url('api/checkout/webhook?token=' . rawurlencode($webhookSecret)),
                    'return_url'=>base_url('track?payment=returned'),
                ]);
                $provider = $created['payment'] ?? [];
                if (empty($provider['_id']) || empty($provider['_uuid']) || empty($provider['_url']) || (int) ($provider['_amount'] ?? 0) !== $total) throw new RuntimeException('CHECKOUT.UZ noto‘g‘ri invoice qaytardi');
                execute_sql("UPDATE orders SET payment_status='pending',checkout_payment_id=?,checkout_payment_uuid=? WHERE id=?", [(int)$provider['_id'],(string)$provider['_uuid'],$orderId]);
                $paymentUrl = (string) $provider['_url'];
            } catch (Throwable $exception) {
                execute_sql('DELETE FROM orders WHERE id = ?', [$orderId]);
                json_response(['error' => substr($exception->getMessage(), 0, 240)], 502);
            }
        }
        json_response(['ok'=>true,'id'=>$orderId,'total'=>$total,'discount'=>$discount,'paymentUrl'=>$paymentUrl]);
    }

    require_admin(true);
    switch ($action) {
        case 'save-product':
            $params = [trim((string)$body['name']), (string)$body['category'], trim((string)$body['description']), (int)$body['price'], trim((string)$body['image']), trim((string)($body['badge'] ?? '')) ?: null, !empty($body['available']) ? 1 : 0];
            if (!empty($body['id'])) execute_sql('UPDATE products SET name=?,category=?,description=?,price=?,image=?,badge=?,available=? WHERE id=?', [...$params,(int)$body['id']]);
            else execute_sql('INSERT INTO products(name,category,description,price,image,badge,available) VALUES(?,?,?,?,?,?,?)',$params);
            break;
        case 'delete-product': execute_sql('DELETE FROM products WHERE id=?',[(int)$body['id']]); break;
        case 'order-status':
            if (!in_array($body['status'] ?? '', ['new','preparing','delivery','done','cancelled'], true)) json_response(['error'=>'Noto‘g‘ri holat'],422);
            execute_sql('UPDATE orders SET status=? WHERE id=?',[$body['status'],(int)$body['id']]); break;
        case 'save-promo':
            $params=[strtoupper(trim((string)$body['code'])),max(1,min(100,(int)$body['percent'])),!empty($body['active'])?1:0];
            if (!empty($body['id'])) execute_sql('UPDATE promos SET code=?,percent=?,active=? WHERE id=?',[...$params,(int)$body['id']]);
            else execute_sql('INSERT INTO promos(code,percent,active) VALUES(?,?,?)',$params); break;
        case 'delete-promo': execute_sql('DELETE FROM promos WHERE id=?',[(int)$body['id']]); break;
        case 'save-zone':
            $params=[trim((string)$body['name']),(int)$body['fee'],trim((string)$body['eta']),!empty($body['active'])?1:0];
            if (!empty($body['id'])) execute_sql('UPDATE zones SET name=?,fee=?,eta=?,active=? WHERE id=?',[...$params,(int)$body['id']]);
            else execute_sql('INSERT INTO zones(name,fee,eta,active) VALUES(?,?,?,?)',$params); break;
        case 'delete-zone': execute_sql('DELETE FROM zones WHERE id=?',[(int)$body['id']]); break;
        case 'save-settings':
            foreach (($body['settings'] ?? []) as $key=>$value) if (in_array($key,['store_name','phone','min_order'],true)) save_setting($key,(string)$value);
            break;
        case 'save-checkout-api':
            $key=trim((string)($body['apiKey']??''));
            if(strlen($key)<12) json_response(['error'=>'API kalitini to‘liq kiriting'],422);
            $methods=checkout_request($key,'get_payment_methods');
            save_setting(CHECKOUT_KEY_SETTING,encrypt_secret($key));
            json_response(['ok'=>true,'methods'=>$methods['data']??[]]);
        case 'remove-checkout-api': execute_sql('DELETE FROM settings WHERE `key`=?',[CHECKOUT_KEY_SETTING]); break;
        default: json_response(['error'=>'Noma’lum amal'],400);
    }
    json_response(['ok'=>true]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    json_response(['error'=>config('APP_ENV')==='development'?$exception->getMessage():'Baza bilan ishlashda xatolik'],500);
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    json_response(['error'=>config('APP_ENV')==='development'?$exception->getMessage():'Server xatosi'],500);
}
