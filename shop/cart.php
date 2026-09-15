<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	if (!empty($_POST['remove'])) {
		store_cart_remove((string) $_POST['remove']);
	}
	store_redirect('/shop/cart.php');
}

$err = (string) ($_GET['err'] ?? '');
$rows = '';
foreach (store_cart() as $item) {
	$product = store_product((string) ($item['product_id'] ?? ''));
	$name = $product['name'] ?? 'Item';
	$rows .= '<article class="ke-line"><div>'
		. '<h2>' . store_h($name) . '</h2>'
		. '<p>' . store_h((string) ($item['date'] ?? '')) . ' · ' . store_h((string) ($item['guests'] ?? '1')) . ' guests'
		. (!empty($item['vehicle']) ? ' · ' . store_h((string) $item['vehicle']) : '')
		. '</p></div>'
		. '<div class="ke-line-side"><p class="ke-price">' . store_money(store_line_total($item, $product)) . '</p>'
		. '<form method="post"><input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
		. '<button class="ke-text" name="remove" value="' . store_h((string) $item['id']) . '">Remove</button></form></div></article>';
}

$notice = $err === 'blocked'
	? '<p class="ke-err">That vehicle is already confirmed on the selected date. Choose another date or van.</p>'
	: '';
$empty = store_cart_count() === 0
	? '<p class="lede">Your cart is empty. Add a Cebu tour or a van from the catalog.</p><p><a class="ke-btn" href="/shop/catalog.php">Browse tours</a></p>'
	: $rows . '<p class="ke-total">Total from ' . store_money(store_cart_total()) . ' <span>Final rate is confirmed by Kuya Ely</span></p>'
		. '<div class="ke-actions"><a class="ke-btn" href="/shop/checkout.php">Checkout</a><a class="ke-btn ghost" href="/shop/catalog.php">Keep browsing</a></div>';

store_page('Cart', $notice . '<h1>Your cart</h1>' . $empty, 'Cart (' . store_cart_count() . ')');
