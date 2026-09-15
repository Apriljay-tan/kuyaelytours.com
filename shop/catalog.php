<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

$items = store_catalog();
$cards = '';
foreach ($items as $item) {
	$cards .= '<article class="ke-card">'
		. '<img src="' . store_h((string) $item['image']) . '" alt="' . store_h((string) $item['name']) . '">'
		. '<div class="ke-card-body">'
		. '<p class="ke-kicker">' . store_h((string) $item['type']) . '</p>'
		. '<h2>' . store_h((string) $item['name']) . '</h2>'
		. '<p>' . store_h((string) $item['description']) . '</p>'
		. '<p class="ke-price">From ' . store_money((int) $item['price_from']) . ' <span>' . store_h((string) ($item['unit'] ?? '')) . '</span></p>'
		. '<div class="ke-actions">'
		. '<a class="ke-btn" href="' . store_h((string) $item['page']) . '">Details</a>'
		. '<form method="post" action="/shop/add-to-cart.php">'
		. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
		. '<input type="hidden" name="product_id" value="' . store_h((string) $item['id']) . '">'
		. '<input type="hidden" name="date" value="' . store_h(store_today()) . '">'
		. '<input type="hidden" name="guests" value="2">'
		. '<input type="hidden" name="next" value="/shop/cart.php">'
		. '<button class="ke-btn ghost" type="submit">Add to cart</button>'
		. '</form></div></div></article>';
}

store_page('Tours and fleet', '<h1>Book a private tour or van</h1><p class="lede">Add a trip to your cart, then Book now or Pay now at checkout. Staff still confirm van and date.</p><div class="ke-grid">' . $cards . '</div>', 'Store');
