<?php
declare(strict_types=1);

function store_page(string $title, string $body, string $kicker = 'Kuya Ely Tours'): void
{
	$user = store_user();
	$count = store_cart_count();
	$account = $user ? store_h((string) $user['name']) : 'Account';
	echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<title>' . store_h($title) . ' | Kuya Ely Tours</title>';
	echo '<link rel="icon" type="image/png" sizes="56x56" href="/assets/images/fav-icon.png">';
	echo '<link rel="stylesheet" href="/assets/css/kuyaely-shop.css">';
	echo '</head><body class="ke-shop">';
	echo '<header class="ke-shop-top"><a class="ke-shop-brand" href="/index.html">';
	echo '<img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours"><span>KUYA ELY</span></a>';
	echo '<nav>';
	echo '<a href="/shop/catalog.php">Tours & fleet</a>';
	echo '<a href="/account/login.php">' . $account . '</a>';
	echo '<a class="ke-cart-link" href="/shop/cart.php">Cart (' . $count . ')</a>';
	echo '</nav></header>';
	echo '<main class="ke-shop-main"><p class="ke-kicker">' . store_h($kicker) . '</p>';
	echo $body;
	echo '</main><footer class="ke-shop-foot">Kuya Ely Tours and Transport Services · Sitio Capilis, Suba-Basbas, Lapu-Lapu City · <a href="https://wa.me/639209851802">WhatsApp +63 920 985 1802</a></footer>';
	echo '</body></html>';
}
