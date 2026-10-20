# YASHIL Restaurant — PHP Edition

YASHIL restoran saytining PHP 8.0+ va MySQL uchun to‘liq ko‘chirilgan nashri.

## Imkoniyatlar

- O‘zbek, rus va ingliz tillari
- Responsive bosh sahifa va mahsulot menyusi
- Qidiruv va kategoriya filtrlari
- `localStorage` savati va checkout
- Olganda to‘lash yoki CHECKOUT.UZ onlayn to‘lovi
- Buyurtmani ID yoki telefon bo‘yicha kuzatish
- `/admin` orqali menyu, buyurtma, mijoz, promo va hududlarni boshqarish
- CHECKOUT.UZ API kalitini admin panelda AES-256-GCM bilan shifrlab saqlash
- CSRF, PDO prepared statements, HttpOnly/SameSite sessiya va login rate-limit

## Talablar

- PHP 8.0 yoki yangi
- MySQL 5.7+/MariaDB 10.4+
- PHP extensions: `pdo_mysql`, `curl`, `openssl`, `mbstring`, `json`
- Apache `mod_rewrite` (yoki Nginx rewrite)

## XAMPP’da o‘rnatish

1. Loyihani `C:\xampp\htdocs\yashil-restaurant-php` ichiga joylang.
2. `.env.example` faylidan `.env` nusxa yarating.
3. phpMyAdmin’da `yashil` nomli UTF-8 MySQL baza yarating.
4. `.env` ichida DB login/parolini va kamida 32 belgili `APP_SECRET` ni yozing.
5. `C:\xampp\apache\conf\extra\httpd-vhosts.conf` ichiga virtual host qo‘shing:

```apache
<VirtualHost *:80>
    ServerName yashil.local
    DocumentRoot "C:/xampp/htdocs/yashil-restaurant-php/public"
    <Directory "C:/xampp/htdocs/yashil-restaurant-php/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

6. Windows `hosts` fayliga `127.0.0.1 yashil.local` yozing va Apache’ni qayta yoqing.
7. `http://yashil.local/install.php` ni ochib bazani o‘rnating.
8. Sayt: `http://yashil.local/`, admin: `http://yashil.local/admin`.

Admin login va parolini `.env` orqali o‘zingiz belgilang. Parolni repozitoriyga yozmang. Eng yaxshi usul — parol xeshidan foydalanish:

```php
<?php echo password_hash('YANGI_KUCHLI_PAROL', PASSWORD_DEFAULT); ?>
```

Natijani `.env` dagi `ADMIN_PASSWORD_HASH` ga yozib, `ADMIN_PASSWORD` ni olib tashlang.

## Hosting

Saytning public root’i faqat `public/` bo‘lishi kerak. `.env`, `app/`, `database/` va `storage/` internetdan ochilmasin.

CHECKOUT.UZ uchun:

- `.env`: `APP_URL=https://domen.uz`
- `.env`: kuchli `CHECKOUT_WEBHOOK_SECRET`
- Admin → Sozlamalar → CHECKOUT.UZ API kaliti

## Papkalar

```text
app/                 PHP yadro, PDO, auth, checkout
database/schema.sql  MySQL sxemasi
public/              Web root
  admin/             Admin panel
  api/               JSON API va webhook
  assets/            CSS, JS, rasmlar va logolar
storage/             Lock va loglar
```

## Versiya

**PHP Version 1** — React/Cloudflare D1 Version 23 funksiyalarining PHP + MySQL ko‘chirmasi.
