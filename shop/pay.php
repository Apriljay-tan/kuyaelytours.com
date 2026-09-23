<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

store_require_login('/account/bookings.php');
$id = trim((string) ($_GET['booking'] ?? ''));
$booking = $id !== '' ? store_find_booking($id) : null;
$user = store_user();
if (!$booking || !$user || ($booking['user_id'] ?? '') !== $user['id']) {
	store_redirect('/account/bookings.php');
}

$cfg = store_pay_config();
$gcash = (string) ($cfg['gcash'] ?? '+63 920 985 1802');
$body = '<h1>Pay this booking</h1>'
	. '<p class="lede">Booking ' . store_h((string) $booking['id']) . ' · ' . store_h(store_booking_status_label((string) $booking['status'])) . '</p>'
	. '<p class="ke-price">' . store_money((int) $booking['total']) . '</p>';
if (store_pay_ready()) {
	store_redirect('/shop/checkout.php?booking=' . rawurlencode((string) $booking['id']));
} else {
	$body .= '<p class="lede">Online checkout keys are not connected yet. Send GCash to <strong>' . store_h($gcash) . '</strong> with your booking ID, or WhatsApp the receipt. Your booking stays as awaiting payment until staff confirm.</p>';
	$body .= '<p><a class="ke-btn" href="https://wa.me/639209851802?text=' . rawurlencode('Payment for booking ' . $booking['id']) . '">Send receipt on WhatsApp</a></p>';
}
$body .= '<p><a class="ke-btn ghost" href="/account/bookings.php">My bookings</a></p>';
store_page('Pay now', $body, 'Payment');
