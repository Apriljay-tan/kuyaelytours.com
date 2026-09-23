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

$status = (string) ($booking['status'] ?? '');
$flag = '0';
if (in_array($status, ['paid', 'confirmed'], true)) {
	$flag = '1';
} elseif ($status !== 'cancelled' && (string) ($_GET['ok'] ?? '') === '1') {
	$sessionId = '';
	if (preg_match('/PayMongo session (cs_[A-Za-z0-9]+)/', (string) ($booking['notes'] ?? ''), $match)) {
		$sessionId = $match[1];
	}
	$payment = $sessionId !== '' ? store_paymongo_session_payment($sessionId) : ['paid' => false, 'amount' => 0, 'currency' => ''];
	if (!empty($payment['paid']) && store_booking_confirm_payment($booking, (int) $payment['amount'], (string) $payment['currency'])) {
		$flag = '1';
	} else {
		$flag = 'pending';
	}
}

store_redirect('/shop/checkout.php?booking=' . rawurlencode($id) . '&ok=' . $flag);
