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

function store_paymongo_last_error(): string
{
	return (string) ($GLOBALS['ke_paymongo_error'] ?? '');
}

function store_paymongo_checkout(array $booking, int $chargePesos = 0): string
{
	$cfg = store_pay_config();
	$secret = trim((string) ($cfg['secret_key'] ?? ''));
	if ($secret === '') {
		$GLOBALS['ke_paymongo_error'] = 'PayMongo secret key is missing.';
		return '';
	}
	$methods = $cfg['methods'] ?? ['qrph'];
	if (!is_array($methods) || !$methods) {
		$methods = ['qrph'];
	}
	$label = 'Kuya Ely Tours booking';
	$items = $booking['items'] ?? [];
	if (is_array($items) && isset($items[0]) && is_array($items[0])) {
		$label = trim((string) ($items[0]['label'] ?? ''));
		if ($label === '' && function_exists('store_product')) {
			$product = store_product((string) ($items[0]['product_id'] ?? ''));
			$label = trim((string) ($product['name'] ?? ''));
		}
		if ($label === '') {
			$label = 'Kuya Ely Tours booking';
		}
		$extra = count($items) - 1;
		if ($extra > 0) {
			$label .= ' +' . $extra;
		}
	}
	$amountPesos = $chargePesos > 0 ? $chargePesos : max(0, (int) ($booking['total'] ?? 0));
	$amount = $amountPesos * 100;
	if ($amountPesos < (int) ($booking['total'] ?? 0)) {
		$label .= ' (half payment)';
	}
	if ($amount < 10000) {
		$GLOBALS['ke_paymongo_error'] = 'PayMongo needs a total of at least ₱100.';
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
				'description' => $label . ' ' . $booking['id'],
				'line_items' => [[
					'currency' => 'PHP',
					'amount' => $amount,
					'name' => $label,
					'quantity' => 1,
				]],
				'payment_method_types' => array_values($methods),
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
	$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$data = json_decode((string) $raw, true);
	$url = (string) ($data['data']['attributes']['checkout_url'] ?? '');
	if ($url === '') {
		$detail = (string) ($data['errors'][0]['detail'] ?? '');
		$GLOBALS['ke_paymongo_error'] = $detail !== '' ? $detail : ('PayMongo did not open a checkout (' . $code . ').');
	}
	return $url;
}

function store_paymongo_request(string $method, string $path, ?array $body = null): ?array
{
	$cfg = store_pay_config();
	$secret = trim((string) ($cfg['secret_key'] ?? ''));
	if ($secret === '') {
		$GLOBALS['ke_paymongo_error'] = 'PayMongo secret key is missing.';
		return null;
	}
	$ch = curl_init('https://api.paymongo.com' . $path);
	$opts = [
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/json',
			'Authorization: Basic ' . base64_encode($secret . ':'),
		],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 25,
	];
	if ($body !== null) {
		$opts[CURLOPT_POSTFIELDS] = json_encode($body);
	}
	curl_setopt_array($ch, $opts);
	$raw = curl_exec($ch);
	$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$data = json_decode((string) $raw, true);
	if (!is_array($data) || $code >= 400) {
		$detail = (string) ($data['errors'][0]['detail'] ?? '');
		$GLOBALS['ke_paymongo_error'] = $detail !== '' ? $detail : ('PayMongo request failed (' . $code . ').');
		return null;
	}
	return $data;
}

function store_paymongo_qr(array $booking, int $amountPesos): array
{
	$empty = ['image_url' => '', 'intent_id' => '', 'amount' => $amountPesos];
	$centavos = $amountPesos * 100;
	if ($centavos < 10000) {
		$GLOBALS['ke_paymongo_error'] = 'PayMongo needs a total of at least ₱100.';
		return $empty;
	}
	$label = 'Kuya Ely Tours';
	$items = $booking['items'] ?? [];
	if (is_array($items) && isset($items[0]) && is_array($items[0])) {
		$label = trim((string) ($items[0]['label'] ?? ''));
		if ($label === '' && function_exists('store_product')) {
			$product = store_product((string) ($items[0]['product_id'] ?? ''));
			$label = trim((string) ($product['name'] ?? 'Kuya Ely Tours'));
		}
	}
	$intent = store_paymongo_request('POST', '/v1/payment_intents', [
		'data' => ['attributes' => [
			'amount' => $centavos,
			'payment_method_allowed' => ['qrph'],
			'currency' => 'PHP',
			'capture_type' => 'automatic',
			'description' => 'Booking ' . $booking['id'] . ' ' . $label,
			'statement_descriptor' => 'KUYA ELY TOURS',
			'metadata' => ['booking_id' => (string) $booking['id']],
		]],
	]);
	$intentId = (string) ($intent['data']['id'] ?? '');
	if ($intentId === '') {
		return $empty;
	}
	$method = store_paymongo_request('POST', '/v1/payment_methods', [
		'data' => ['attributes' => [
			'type' => 'qrph',
			'billing' => [
				'name' => (string) ($booking['guest_name'] ?? ''),
				'email' => (string) ($booking['email'] ?? ''),
				'phone' => (string) ($booking['phone'] ?? ''),
			],
		]],
	]);
	$methodId = (string) ($method['data']['id'] ?? '');
	if ($methodId === '') {
		return $empty;
	}
	$attached = store_paymongo_request('POST', '/v1/payment_intents/' . rawurlencode($intentId) . '/attach', [
		'data' => ['attributes' => [
			'payment_method' => $methodId,
			'return_url' => 'https://kuyaelytours.com/shop/checkout.php?booking=' . rawurlencode((string) $booking['id']),
		]],
	]);
	$code = $attached['data']['attributes']['next_action']['code'] ?? [];
	$image = '';
	if (is_array($code)) {
		$image = (string) ($code['image_url'] ?? ($code['qr_image'] ?? ''));
	}
	if ($image === '') {
		if (store_paymongo_last_error() === '') {
			$GLOBALS['ke_paymongo_error'] = 'PayMongo did not return a QR code.';
		}
		return $empty;
	}
	return ['image_url' => $image, 'intent_id' => $intentId, 'amount' => $amountPesos];
}

function store_paymongo_intent_status(string $intentId): string
{
	$intentId = trim($intentId);
	if ($intentId === '') {
		return '';
	}
	$data = store_paymongo_request('GET', '/v1/payment_intents/' . rawurlencode($intentId));
	return (string) ($data['data']['attributes']['status'] ?? '');
}
