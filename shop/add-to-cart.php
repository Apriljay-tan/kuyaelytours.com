<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !store_csrf_ok()) {
	store_redirect('/shop/catalog.php');
}

$productId = trim((string) ($_POST['product_id'] ?? ''));
$product = store_product($productId);
if (!$product) {
	store_redirect('/shop/catalog.php');
}

$vehicle = trim((string) ($_POST['vehicle'] ?? ($product['vehicle'] ?? '')));
$date = trim((string) ($_POST['date'] ?? ''));
if ($date === '') {
	$date = store_today();
}
if (store_is_blocked($vehicle, $date)) {
	store_redirect('/shop/cart.php?err=blocked');
}

store_cart_add([
	'product_id' => $productId,
	'date' => $date,
	'guests' => max(1, (int) ($_POST['guests'] ?? 1)),
	'vehicle' => $vehicle,
	'notes' => trim((string) ($_POST['notes'] ?? '')),
]);

$next = (string) ($_POST['next'] ?? '/shop/cart.php');
if ($next === '' || $next[0] !== '/') {
	$next = '/shop/cart.php';
}
store_redirect($next);
