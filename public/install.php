<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

$lock = dirname(__DIR__) . '/storage/installed.lock';
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf($_POST);
    if (is_file($lock)) {
        $error = 'O‘rnatish allaqachon yakunlangan. storage/installed.lock faylini faqat qayta o‘rnatishda o‘chiring.';
    } else {
        try {
            $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
            db()->exec($schema ?: '');
            $products = [
                ['Quyosh bowl','bowl','Tovuq, guruch, sabzi, avokado va limonli sous.',59000,'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=900&q=85','ENG SEVIMLI'],
                ['Yashil quvvat','bowl','Brokkoli, no‘xat, bodring, ko‘kat va tahini.',52000,'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=85',null],
                ['Achchiq teriyaki','bowl','Mol go‘shti, karam, guruch va achchiq teriyaki.',64000,'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=900&q=85','FAQAT ONLINE'],
                ['Bog‘ salati','salad','Pomidor, bodring, rediska, ko‘kat va feta.',42000,'https://images.unsplash.com/photo-1543362906-acfc16c67564?auto=format&fit=crop&w=900&q=85',null],
                ['Sezar yangi','salad','Tovuq, salat bargi, parmesan va yengil sous.',55000,'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?auto=format&fit=crop&w=900&q=85',null],
                ['Yashil fresh','drink','Olma, bodring, ismaloq va limon.',25000,'https://images.unsplash.com/photo-1610970881699-44a5587cabec?auto=format&fit=crop&w=900&q=85',null],
            ];
            if ((int) query_one('SELECT COUNT(*) AS n FROM products')['n'] === 0) {
                $stmt = db()->prepare('INSERT INTO products(name,category,description,price,image,badge,available) VALUES(?,?,?,?,?,?,1)');
                foreach ($products as $product) $stmt->execute($product);
            }
            execute_sql("INSERT IGNORE INTO promos(code,percent,active) VALUES('YASHIL10',10,1)");
            execute_sql("INSERT IGNORE INTO zones(id,name,fee,eta,active) VALUES(1,'Markaz',12000,'25–35 daqiqa',1),(2,'Shahar cheti',20000,'40–55 daqiqa',1)");
            foreach (['store_name'=>'YASHIL','phone'=>'+998 90 123 45 67','payments'=>'Naqd,Payme,Click','min_order'=>'40000'] as $key=>$value) save_setting($key,$value);
            file_put_contents($lock, date(DATE_ATOM));
            $message = 'O‘rnatish yakunlandi. Endi sayt va /admin ishlaydi.';
        } catch (Throwable $exception) {
            $error = config('APP_ENV') === 'development' ? $exception->getMessage() : 'Bazani o‘rnatishda xatolik.';
        }
    }
}
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>YASHIL o‘rnatish</title><link rel="stylesheet" href="/assets/css/styles.css"></head><body><main class="adminLoginPage"><section class="adminLoginCard"><span class="eyebrow">PHP + MYSQL</span><h1>YASHIL o‘rnatish</h1><p>.env faylini sozlang, keyin quyidagi tugmani bosing.</p><?php if($message):?><div class="adminToast"><?=htmlspecialchars($message)?></div><a class="primaryBtn" href="/">Saytni ochish</a><?php elseif($error):?><div class="adminLoginError"><?=htmlspecialchars($error)?></div><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=htmlspecialchars(csrf_token())?>"><button class="primaryBtn">Bazani yaratish va namuna qo‘shish</button></form></section></main></body></html>
