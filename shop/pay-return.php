<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

store_require_login('/account/bookings.php');
$id = trim((string) ($_GET['booking'] ?? ''));
$booking = $id !== '' ? store_find_booking($id) : null;
$user = store_user();
$paid = $booking
	&& $user
	&& ($booking['user_id'] ?? '') === ($user['id'] ?? '')
	&& (($booking['status'] ?? '') === 'paid');
store_redirect('/account/bookings.php' . ($paid ? '?paid=1' : ''));
