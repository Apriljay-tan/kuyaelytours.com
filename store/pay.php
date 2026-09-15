<?php
declare(strict_types=1);

function store_pay_config(): array
{
	$file = STORE_ROOT . '/pay.local.php';
	if (!is_readable($file)) {
		return [];
	}
	$cfg = include $file;
	return is_array($cfg) ? $cfg : [];
}

function store_pay_ready(): bool
{
	$cfg = store_pay_config();
	return trim((string) ($cfg['secret_key'] ?? '')) !== '';
}

function store_paymongo_checkout(array $booking): string
{
	$cfg = store_pay_config();
	$secret = trim((string) ($cfg['secret_key'] ?? ''));
	if ($secret === '') {
		return '';
	}
	$base = 'https://kuyaelytours.com';
	$payload = [
		'data' => [
			'attributes' => [
				'billing' => [
					'name' => $booking['guest_name'],
					'email' => $booking['email'],
					'phone' => $booking['phone'] ?? '',
				],
				'send_email_receipt' => true,
				'show_description' => true,
				'show_line_items' => true,
				'description' => 'Kuya Ely Tours booking ' . $booking['id'],
				'line_items' => [[
					'currency' => 'PHP',
					'amount' => max(10000, (int) $booking['total'] * 100),
					'name' => 'Tour / van booking',
					'quantity' => 1,
				]],
				'payment_method_types' => ['gcash', 'card', 'paymaya'],
				'success_url' => $base . '/shop/pay-return.php?booking=' . rawurlencode((string) $booking['id']) . '&ok=1',
				'cancel_url' => $base . '/shop/pay-return.php?booking=' . rawurlencode((string) $booking['id']) . '&ok=0',
				'metadata' => ['booking_id' => $booking['id']],
			],
		],
	];
	$ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
	curl_setopt_array($ch, [
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/json',
			'Authorization: Basic ' . base64_encode($secret . ':'),
		],
		CURLOPT_POSTFIELDS => json_encode($payload),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 25,
	]);
	$raw = curl_exec($ch);
	curl_close($ch);
	$data = json_decode((string) $raw, true);
	return (string) ($data['data']['attributes']['checkout_url'] ?? '');
}
