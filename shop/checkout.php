<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

store_require_login('/shop/checkout.php');
$user = store_user();
if (!$user) {
	store_redirect('/account/login.php');
}

$error = '';
if (store_cart_count() === 0) {
	store_redirect('/shop/cart.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$notes = trim((string) ($_POST['notes'] ?? ''));
	$action = (string) ($_POST['action'] ?? 'book');
	$items = store_cart();
	foreach ($items as $item) {
		if (store_is_blocked((string) ($item['vehicle'] ?? ''), (string) ($item['date'] ?? ''))) {
			store_redirect('/shop/cart.php?err=blocked');
		}
	}
	$status = $action === 'pay' ? 'awaiting_payment' : 'pending_request';
	$method = $action === 'pay' ? 'pay_now' : 'book_now';
	$booking = store_create_booking($user, $items, $status, $method, $notes);
	if (!$booking) {
		$error = 'Could not save this booking. Try another date or van.';
	} else {
		store_cart_clear();
		store_booking_mail($booking, false);
		store_booking_mail($booking, true);
		if ($action === 'pay') {
			$url = store_paymongo_checkout($booking);
			if ($url !== '') {
				store_redirect($url);
			}
			store_redirect('/shop/pay.php?booking=' . rawurlencode((string) $booking['id']));
		}
		store_redirect('/account/bookings.php?placed=1');
	}
}

$lines = '';
foreach (store_cart() as $item) {
	$product = store_product((string) ($item['product_id'] ?? ''));
	$lines .= '<li>' . store_h((string) ($product['name'] ?? 'Item')) . ' · ' . store_h((string) ($item['date'] ?? '')) . ' · ' . store_money(store_line_total($item, $product)) . '</li>';
}

$form = '<h1>Checkout</h1><p class="lede">Signed in as ' . store_h((string) $user['name']) . '. Book now sends a request. Pay now holds the booking as awaiting payment.</p>';
if ($error !== '') {
	$form .= '<p class="ke-err">' . store_h($error) . '</p>';
}
$form .= '<ul class="ke-summary">' . $lines . '</ul><p class="ke-total">From ' . store_money(store_cart_total()) . '</p>';
$form .= '<form method="post"><input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">';
$form .= '<label>Notes for pickup / hotel<textarea name="notes" rows="4" placeholder="Hotel, flight time, or special request"></textarea></label>';
$form .= '<div class="ke-actions">';
$form .= '<button class="ke-btn" name="action" value="book">Book now</button>';
$form .= '<button class="ke-btn ghost" name="action" value="pay">Pay now</button>';
$form .= '</div></form>';
$form .= '<p class="trust">Your permits and fleet stay on the public site. Staff confirm every van date before it is locked.</p>';

store_page('Checkout', $form, 'Checkout');
