<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

$id = trim((string) ($_GET['booking'] ?? $_POST['booking'] ?? ''));
$booking = $id !== '' ? store_find_booking($id) : null;
$user = store_user();
$owner = $booking && $user && (string) ($booking['user_id'] ?? '') !== '' && (string) $booking['user_id'] === (string) $user['id'];
$granted = $booking && store_booking_granted($id);
if (!$booking || (!$owner && !$granted)) {
	store_redirect('/shop/cart.php');
}

$payNote = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok() && (string) ($_POST['action'] ?? '') === 'pay') {
	$booking['status'] = 'awaiting_payment';
	$booking['pay_method'] = 'pay_now';
	store_update_booking($booking);
	$qr = store_paymongo_qr($booking, (int) ($booking['total'] ?? 0));
	if ($qr['image_url'] !== '') {
		$_SESSION['pay_qr'][$id] = $qr;
		if ($qr['intent_id'] !== '' && !str_contains((string) ($booking['notes'] ?? ''), $qr['intent_id'])) {
			$booking['notes'] = trim((string) ($booking['notes'] ?? '') . "\nPayMongo intent " . $qr['intent_id']);
			store_update_booking($booking);
		}
		store_redirect('/shop/checkout.php?booking=' . rawurlencode($id));
	}
	$detail = store_paymongo_last_error();
	$payNote = $detail !== '' ? $detail : 'PayMongo did not open a QR code. Your booking is still saved.';
}

$lines = '';
foreach ($booking['items'] ?? [] as $item) {
	if (!is_array($item)) {
		continue;
	}
	$product = store_product((string) ($item['product_id'] ?? ''));
	$name = (string) ($product['name'] ?? ($item['name'] ?? 'Tour'));
	$lines .= '<p class="ke-sum-line"><span>' . store_h($name)
		. (!empty($item['date']) ? ' · ' . store_h((string) $item['date']) : '')
		. '</span><span>' . store_money(store_line_total($item, $product)) . '</span></p>';
}

$ok = (string) ($_GET['ok'] ?? '');
$banner = '';
if ($payNote !== '') {
	$banner = '<p class="ke-err ke-banner">' . store_h($payNote) . '</p>';
} elseif ($ok === '1') {
	$banner = '<p class="ke-ok ke-banner">Booking is confirmed. Check your email every now and then, as our staff will connect with you shortly.</p>';
} elseif ($ok === '0') {
	$banner = '<p class="ke-err ke-banner">Payment was cancelled. This booking is still saved and you can pay later.</p>';
}

$pay = '';
if ((string) ($booking['status'] ?? '') !== 'paid' && (string) ($booking['status'] ?? '') !== 'cancelled') {
	$pay = '<form method="post" class="ke-check-form">'
		. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
		. '<input type="hidden" name="booking" value="' . store_h($id) . '">'
		. '<button class="ke-btn" name="action" value="pay">Pay with QR Ph</button>'
		. '</form>'
		. '<p class="lede">This opens PayMongo for the total above. Scan the QR Ph code to pay. GCash and Maya turn on after PayMongo finishes your account upgrade.</p>'
		. '<p><a class="ke-btn ghost" href="https://wa.me/639209851802?text=' . rawurlencode('Booking ' . $id) . '">WhatsApp this booking</a></p>';
}

$account = $owner ? '<p><a class="ke-btn ghost" href="/account/bookings.php">My bookings</a></p>' : '';

$body = $banner
	. '<h1>Booking confirmed</h1>'
	. '<p class="lede">' . store_h((string) $booking['guest_name']) . ' · ' . store_h((string) $booking['email'])
	. ' · ' . store_h(store_booking_status_label((string) ($booking['status'] ?? ''))) . '</p>'
	. '<p>Reference <strong>' . store_h($id) . '</strong></p>'
	. $lines
	. '<p class="ke-total">Total ' . store_money((int) ($booking['total'] ?? 0)) . '</p>'
	. (!empty($booking['notes']) ? '<p>' . nl2br(store_h((string) $booking['notes'])) . '</p>' : '')
	. $pay
	. $account;

store_page('Booking confirmed', $body, 'Booking');
