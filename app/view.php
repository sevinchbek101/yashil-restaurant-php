<?php
declare(strict_types=1);

function icon(string $name, int $size = 22): string
{
    $paths = [
        'cart'=>'<path d="M3 4h2l2 11h10l2-7H7M9 20h.01M17 20h.01"/>',
        'menu'=>'<path d="M4 7h16M4 12h16M4 17h16"/>',
        'search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'pluscart'=>'<path d="M3 4h2l2 11h10l2-7H7M12 7v6M9 10h6M9 20h.01M17 20h.01"/>',
        'close'=>'<path d="m6 6 12 12M18 6 6 18"/>',
        'trash'=>'<path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/>',
        'bowl'=>'<path d="M4 11h16c0 5-3 8-8 8s-8-3-8-8ZM7 7c1-2 3-2 4 0M13 6c1-2 3-2 4 0"/>',
        'leaf'=>'<path d="M19 4C10 4 5 8 5 14c0 3 2 5 5 5 6 0 9-6 9-15Z"/><path d="M5 20c2-5 6-8 11-11"/>',
        'drink'=>'<path d="M7 3h10l-1 17H8L7 3ZM9 7h6M14 3l3-2"/>',
        'grid'=>'<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
    ];
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['grid']).'</svg>';
}

function render_head(string $title): void
{
    $csrf = htmlspecialchars(csrf_token(), ENT_QUOTES);
    echo '<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="csrf-token" content="'.$csrf.'"><meta name="theme-color" content="#f4f3e7"><title>'.htmlspecialchars($title).'</title><meta name="description" content="YASHIL sog‘lom taomlar restorani"><link rel="icon" href="/favicon.svg"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;500;600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/css/styles.css"></head><body><a class="skipLink" href="#main-content">Asosiy mazmunga o‘tish</a><div class="premiumAmbient" aria-hidden="true"><span class="ambientLayer ambientLayerOne"><i class="ambientShape ambientOrb"></i></span><span class="ambientLayer ambientLayerTwo"><i class="ambientShape ambientRing"></i></span></div>';
}

function render_header(string $active): void
{
    $links = [['home','/','Bosh sahifa'],['menu','/menu','Menyu'],['about','/#about','Biz haqimizda'],['track','/track','Kuzatish']];
    echo '<header class="siteHeader"><div class="siteHeaderInner shell"><a class="siteBrand" href="/"><strong>yashil</strong><small>healthy kitchen</small></a><nav class="siteNav" aria-label="Asosiy navigatsiya">';
    foreach($links as [$id,$href,$label]) echo '<a href="'.$href.'" class="'.($active===$id?'active':'').'" '.($active===$id?'aria-current="page"':'').'>'.$label.'</a>';
    echo '</nav><div class="headerActions"><div class="desktopLanguage"><div class="langSwitch" role="group" aria-label="Tilni tanlang"><button data-lang="uz" class="active">UZ</button><button data-lang="ru">RU</button><button data-lang="en">EN</button></div></div><button class="headerCart" id="cart-open" type="button">'.icon('cart',22).'<span class="headerCartLabel">Savat</span><b id="cart-count">0</b></button><button class="mobileMenuToggle" id="mobile-menu-toggle" type="button" aria-label="Menyuni ochish" aria-expanded="false">'.icon('menu',25).'</button></div></div><div id="mobile-navigation" class="mobileNavPanel" hidden><nav class="shell" aria-label="Mobil navigatsiya">';
    foreach($links as [$id,$href,$label]) echo '<a href="'.$href.'" class="'.($active===$id?'active':'').'">'.$label.'</a>';
    echo '<div class="mobileLanguage"><div class="langSwitch"><button data-lang="uz" class="active">UZ</button><button data-lang="ru">RU</button><button data-lang="en">EN</button></div></div></nav></div></header>';
}

function render_cart(): void
{
    echo '<div class="back" id="cart-backdrop" aria-hidden="true"><aside class="cart" id="cart-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-title" tabindex="-1"><div class="cartHead"><h3 id="cart-title">Sizning savatingiz</h3><button class="iconBtn" id="cart-close" type="button" aria-label="Savatni yopish">'.icon('close',25).'</button></div><div class="cartList" id="cart-list"></div><div class="cartFoot"><div><b>Jami</b><strong id="cart-total">0 so‘m</strong></div><a class="pill" id="cart-checkout" href="/menu?checkout=1">BUYURTMANI RASMIYLASHTIRISH</a></div></aside></div>';
}

function render_footer(): void
{
    echo '<footer><div class="shell"><a class="logo" href="/">yashil</a><p>© 2026 YASHIL · Toshkent · <a href="tel:+998901234567">+998 90 123 45 67</a></p></div></footer><script src="/assets/js/app.js" defer></script></body></html>';
}
