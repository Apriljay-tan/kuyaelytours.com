<?php
declare(strict_types=1);
define('STORE_SKIP_SESSION', true);
require dirname(__DIR__) . '/store/bootstrap.php';

$cfg = store_pay_config();
$secret = trim((string) ($cfg['webhook_secret'] ?? ''));
$raw = (string) file_get_contents('php://input');
$header = (string) ($_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '');

if ($secret === '' || $raw === '' || $header === '' || !store_paymongo_signature_ok($raw, $header, $secret)) {
	http_response_code(400);
	echo 'ignored';
	exit;
}

$data = json_decode($raw, true);
$type = (string) ($data['data']['attributes']['type'] ?? '');
$bookingId = (string) ($data['data']['attributes']['data']['attributes']['metadata']['booking_id'] ?? '');
if ($bookingId === '' || !str_contains($type, 'paid')) {
	echo 'ok';
	exit;
}

$booking = store_find_booking($bookingId);
if ($booking && ($booking['status'] ?? '') !== 'cancelled') {
	$booking['status'] = 'paid';
	$booking['pay_method'] = 'paymongo';
	store_update_booking($booking);
}
echo 'ok';

function store_paymongo_signature_ok(string $raw, string $header, string $secret): bool
{
	$parts = [];
	foreach (explode(',', $header) as $piece) {
		$kv = explode('=', trim($piece), 2);
		if (count($kv) === 2) {
			$parts[$kv[0]] = $kv[1];
		}
	}
	$timestamp = (string) ($parts['t'] ?? '');
	$sent = (string) ($parts['te'] ?? $parts['li'] ?? '');
	if ($timestamp === '' || $sent === '' || !ctype_digit($timestamp)) {
		return false;
	}
	if (abs(time() - (int) $timestamp) > 300) {
		return false;
	}
	$expected = hash_hmac('sha256', $timestamp . '.' . $raw, $secret);
	return hash_equals($expected, $sent);
}
