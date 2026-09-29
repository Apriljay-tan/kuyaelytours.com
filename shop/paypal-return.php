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
if (!$booking || (!$owner && !store_booking_granted($id))) {
	store_redirect('/shop/cart.php');
}

$orderId = trim((string) ($_GET['token'] ?? ''));
if ($orderId === '' && preg_match('/PayPal order ([A-Z0-9]+)/', (string) ($booking['notes'] ?? ''), $match)) {
	$orderId = $match[1];
}
$captured = store_paypal_capture($orderId);
if (!empty($captured['ok']) && store_booking_confirm_payment($booking, (int) $captured['amount'], (string) $captured['currency'], 'paypal')) {
	store_redirect('/shop/checkout.php?booking=' . rawurlencode($id) . '&ok=1');
}

$_SESSION['pay_error'] = (string) ($captured['error'] ?? 'PayPal did not confirm this payment.');
store_redirect('/shop/checkout.php?booking=' . rawurlencode($id) . '&ok=pending');
