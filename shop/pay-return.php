<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

store_require_login('/account/bookings.php');
$id = trim((string) ($_GET['booking'] ?? ''));
$ok = (string) ($_GET['ok'] ?? '');
$booking = $id !== '' ? store_find_booking($id) : null;
$user = store_user();
if ($booking && $user && ($booking['user_id'] ?? '') === $user['id'] && $ok === '1') {
	$booking['status'] = 'paid';
	$booking['pay_method'] = 'paymongo';
	store_update_booking($booking);
}
store_redirect('/account/bookings.php?paid=' . ($ok === '1' ? '1' : '0'));
