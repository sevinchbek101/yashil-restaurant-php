<?php
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Method not allowed'],405);
$expected=(string)config('CHECKOUT_WEBHOOK_SECRET','');
$supplied=(string)($_GET['token']??'');
if($expected===''||!hash_equals($expected,$supplied)) json_response(['error'=>'Ruxsat yo‘q'],403);
$body=json_input();
$providerId=(int)($body['data']['order_id']??0);
if(($body['event']??'')!=='payment_confirmed'||!$providerId) json_response(['ok'=>true,'ignored'=>true]);
$order=query_one('SELECT id,total,payment_status FROM orders WHERE checkout_payment_id=?',[$providerId]);
if(!$order||$order['payment_status']==='paid') json_response(['ok'=>true,'ignored'=>true]);
$key=checkout_api_key();
if(!$key) json_response(['error'=>'CHECKOUT sozlanmagan'],503);
$verified=checkout_request($key,'status_payment',['id'=>$providerId]);
if(($verified['data']['status']??'')!=='paid'||(int)($verified['data']['amount']??0)!==(int)$order['total']) json_response(['error'=>'To‘lov tasdiqlanmadi'],409);
execute_sql("UPDATE orders SET payment_status='paid' WHERE id=? AND payment_status!='paid'",[(int)$order['id']]);
json_response(['ok'=>true]);
