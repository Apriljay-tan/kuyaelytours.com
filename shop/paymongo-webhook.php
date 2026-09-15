<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

$cfg = store_pay_config();
$secret = trim((string) ($cfg['webhook_secret'] ?? $cfg['secret_key'] ?? ''));
$raw = (string) file_get_contents('php://input');
$data = json_decode($raw, true);
$type = (string) ($data['data']['attributes']['type'] ?? '');
$bookingId = (string) ($data['data']['attributes']['data']['attributes']['metadata']['booking_id'] ?? '');
if ($secret === '' || $bookingId === '') {
	http_response_code(400);
	echo 'ignored';
	exit;
}
$booking = store_find_booking($bookingId);
if ($booking && str_contains($type, 'paid')) {
	$booking['status'] = 'paid';
	$booking['pay_method'] = 'paymongo';
	store_update_booking($booking);
}
echo 'ok';
