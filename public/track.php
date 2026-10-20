<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/view.php';
render_head('Buyurtmani kuzatish — YASHIL');
?><main class="trackPage" id="main-content" data-page="track"><?php render_header('track'); ?>
<section class="trackHero shell"><span class="eyebrow">BUYURTMA HOLATI</span><h1>Taomingiz<br>qayerda?</h1><p>Buyurtma raqami yoki telefon raqamidan bittasini kiriting.</p></section><section class="trackBody shell"><form class="trackForm" id="track-form"><div class="trackMode" role="group"><button type="button" class="active" data-mode="id" aria-pressed="true">Buyurtma ID</button><button type="button" data-mode="phone" aria-pressed="false">Telefon</button></div><label id="track-label">Buyurtma raqami<small>Bitta buyurtmani topadi</small><div class="orderInput"><span>#</span><input id="track-input" inputmode="numeric" placeholder="Masalan: 24"></div></label><button class="pill submitBtn">HOLATNI KO‘RISH</button></form><div class="trackError" id="track-error" hidden></div><div class="trackingResults" id="tracking-results" aria-live="polite"></div></section>
<?php render_cart(); render_footer(); ?>
