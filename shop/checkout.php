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
	$lines .= '<p class="ke-sum-line"><span>' . store_h((string) ($product['name'] ?? 'Item'))
		. (!empty($item['date']) ? ' · ' . store_h((string) $item['date']) : '')
		. '</span><span>' . store_money(store_line_total($item, $product)) . '</span></p>';
}

$notice = $error !== '' ? '<p class="ke-err ke-banner">' . store_h($error) . '</p>' : '';

$panel = $notice . '<div class="ke-cart-layout"><div class="ke-cart-list">'
	. '<h1>Checkout</h1>'
	. '<p class="lede">Signed in as ' . store_h((string) $user['name']) . '. Book now is a request. Pay now holds the booking as awaiting payment.</p>'
	. '<form method="post" class="ke-check-form">'
	. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
	. '<label>Notes for pickup / hotel<textarea name="notes" rows="4" placeholder="Hotel, flight time, or special request"></textarea></label>'
	. '<div class="ke-actions">'
	. '<button class="ke-btn" name="action" value="book">Book now</button>'
	. '<button class="ke-btn ghost" name="action" value="pay">Pay now</button>'
	. '</div></form>'
	. '<p class="trust">Staff confirm every van date before it is locked. Permits stay on the public site.</p>'
	. '</div><aside class="ke-cart-sum"><h2>Trip summary</h2>' . $lines
	. '<p class="ke-total">From ' . store_money(store_cart_total()) . '<span>Final rate confirmed by Kuya Ely</span></p>'
	. '</aside></div>';

store_account_frame('Checkout', 'cart', $panel, '');
