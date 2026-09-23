<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

$id = trim((string) ($_GET['booking'] ?? ''));
$booking = $id !== '' ? store_find_booking($id) : null;
$user = store_user();
$owner = $booking
	&& $user
	&& (string) ($booking['user_id'] ?? '') !== ''
	&& (string) $booking['user_id'] === (string) $user['id'];
$ok = (string) ($_GET['ok'] ?? '');
if ($booking && ($owner || store_booking_granted($id))) {
	if ($ok === '1' && !in_array((string) ($booking['status'] ?? ''), ['paid', 'confirmed', 'cancelled'], true)) {
		$half = str_contains((string) ($booking['notes'] ?? ''), 'Balance due');
		$booking['status'] = $half ? 'confirmed' : 'paid';
		$booking['pay_method'] = $half ? 'half' : 'paymongo';
		store_update_booking($booking);
	}
	store_redirect('/shop/checkout.php?booking=' . rawurlencode($id) . ($ok === '1' ? '&ok=1' : '&ok=0'));
}
store_redirect('/shop/cart.php');
