<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$id = trim((string) ($_GET['booking'] ?? ''));
$booking = $id !== '' ? store_find_booking($id) : null;
$user = store_user();
$owner = $booking && $user && (string) ($booking['user_id'] ?? '') !== '' && (string) $booking['user_id'] === (string) $user['id'];
if (!$booking || (!$owner && !store_booking_granted($id))) {
	echo json_encode(['ok' => 0]);
	exit;
}

$status = (string) ($booking['status'] ?? '');
if ($status === 'paid' || $status === 'confirmed') {
	echo json_encode(['ok' => 1, 'status' => $status]);
	exit;
}

$saved = $_SESSION['pay_qr'][$id]['intent_id'] ?? '';
if (!is_string($saved) || $saved === '') {
	if (preg_match('/PayMongo intent (pi_[A-Za-z0-9]+)/', (string) ($booking['notes'] ?? ''), $match)) {
		$saved = $match[1];
	}
}
$remote = $saved !== '' ? store_paymongo_intent_status($saved) : '';
if ($remote === 'succeeded') {
	$deposit = str_contains((string) ($booking['notes'] ?? ''), 'Balance due');
	$booking['status'] = $deposit ? 'confirmed' : 'paid';
	$booking['pay_method'] = $deposit ? 'qrph_deposit' : 'paymongo';
	store_update_booking($booking);
	$status = (string) $booking['status'];
}
echo json_encode(['ok' => 1, 'status' => $status !== '' ? $status : $remote]);
