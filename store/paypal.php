<?php
declare(strict_types=1);

function store_paypal_config(): array
{
	$file = STORE_ROOT . '/paypal.local.php';
	if (!is_readable($file)) {
		return [];
	}
	$cfg = include $file;
	return is_array($cfg) ? $cfg : [];
}

function store_paypal_ready(): bool
{
	$cfg = store_paypal_config();
	return trim((string) ($cfg['client_id'] ?? '')) !== '' && trim((string) ($cfg['secret'] ?? '')) !== '';
}

function store_paypal_live(): bool
{
	if (!store_paypal_ready()) {
		return false;
	}
	return strtolower(trim((string) (store_paypal_config()['mode'] ?? ''))) === 'live';
}

function store_paypal_base(): string
{
	$mode = strtolower(trim((string) (store_paypal_config()['mode'] ?? 'sandbox')));
	return $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}

function store_paypal_last_error(): string
{
	return (string) ($GLOBALS['ke_paypal_error'] ?? '');
}

function store_paypal_token(): string
{
	static $token = '';
	if ($token !== '') {
		return $token;
	}
	$cfg = store_paypal_config();
	$id = trim((string) ($cfg['client_id'] ?? ''));
	$secret = trim((string) ($cfg['secret'] ?? ''));
	if ($id === '' || $secret === '') {
		$GLOBALS['ke_paypal_error'] = 'PayPal is not set up yet.';
		return '';
	}
	$ch = curl_init(store_paypal_base() . '/v1/oauth2/token');
	curl_setopt_array($ch, [
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => [
			'Accept: application/json',
			'Accept-Language: en_US',
			'Authorization: Basic ' . base64_encode($id . ':' . $secret),
		],
		CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 25,
	]);
	$raw = curl_exec($ch);
	curl_close($ch);
	$data = json_decode((string) $raw, true);
	$token = trim((string) ($data['access_token'] ?? ''));
	if ($token === '') {
		$GLOBALS['ke_paypal_error'] = 'PayPal did not accept the login.';
	}
	return $token;
}

function store_paypal_request(string $method, string $path, ?array $body = null): ?array
{
	$token = store_paypal_token();
	if ($token === '') {
		return null;
	}
	$ch = curl_init(store_paypal_base() . $path);
	$headers = [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $token,
	];
	$opts = [
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 25,
	];
	if ($body !== null) {
		$opts[CURLOPT_POSTFIELDS] = $body === [] ? '{}' : (string) json_encode($body);
	}
	curl_setopt_array($ch, $opts);
	$raw = curl_exec($ch);
	$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$data = json_decode((string) $raw, true);
	if ($code < 200 || $code >= 300 || !is_array($data)) {
		$detail = (string) ($data['message'] ?? ($data['details'][0]['description'] ?? ''));
		$GLOBALS['ke_paypal_error'] = $detail !== '' ? $detail : ('PayPal did not answer (' . $code . ').');
		return null;
	}
	return $data;
}

function store_paypal_checkout(array $booking, int $pesos): string
{
	if ($pesos < 100) {
		$GLOBALS['ke_paypal_error'] = 'PayPal needs a total of at least ₱100.';
		return '';
	}
	$id = (string) ($booking['id'] ?? '');
	$base = 'https://kuyaelytours.com';
	$order = store_paypal_request('POST', '/v2/checkout/orders', [
		'intent' => 'CAPTURE',
		'purchase_units' => [[
			'custom_id' => $id,
			'description' => 'Kuya Ely Tours booking ' . $id,
			'amount' => [
				'currency_code' => 'PHP',
				'value' => number_format($pesos, 2, '.', ''),
			],
		]],
		'application_context' => [
			'brand_name' => 'Kuya Ely Tours',
			'shipping_preference' => 'NO_SHIPPING',
			'user_action' => 'PAY_NOW',
			'return_url' => $base . '/shop/paypal-return.php?booking=' . rawurlencode($id),
			'cancel_url' => $base . '/shop/pay-return.php?booking=' . rawurlencode($id) . '&ok=0',
		],
	]);
	if (!$order) {
		return '';
	}
	$orderId = (string) ($order['id'] ?? '');
	if ($orderId !== '' && $id !== '') {
		$fresh = store_find_booking($id) ?: $booking;
		if (!str_contains((string) ($fresh['notes'] ?? ''), 'PayPal order ' . $orderId)) {
			$fresh['notes'] = rtrim((string) ($fresh['notes'] ?? '')) . "\nPayPal order " . $orderId;
			store_update_booking($fresh);
		}
	}
	foreach ((array) ($order['links'] ?? []) as $link) {
		if (is_array($link) && ($link['rel'] ?? '') === 'approve') {
			return (string) ($link['href'] ?? '');
		}
	}
	$GLOBALS['ke_paypal_error'] = 'PayPal did not open a checkout.';
	return '';
}

function store_paypal_capture(string $orderId): array
{
	$fail = ['ok' => false, 'amount' => 0, 'currency' => '', 'error' => 'PayPal did not confirm the payment.'];
	$orderId = trim($orderId);
	if ($orderId === '') {
		return $fail;
	}
	$data = store_paypal_request('POST', '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture', []);
	if (!$data) {
		$fail['error'] = store_paypal_last_error() !== '' ? store_paypal_last_error() : $fail['error'];
		return $fail;
	}
	$status = strtoupper((string) ($data['status'] ?? ''));
	$capture = $data['purchase_units'][0]['payments']['captures'][0] ?? null;
	if ($status !== 'COMPLETED' || !is_array($capture)) {
		return $fail;
	}
	$value = (string) ($capture['amount']['value'] ?? '0');
	$currency = strtoupper((string) ($capture['amount']['currency_code'] ?? ''));
	$pesos = (int) round((float) $value);
	if (strtoupper((string) ($capture['status'] ?? '')) !== 'COMPLETED' || $pesos < 1) {
		return $fail;
	}
	return ['ok' => true, 'amount' => $pesos * 100, 'currency' => $currency, 'error' => ''];
}
