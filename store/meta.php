<?php
declare(strict_types=1);

function store_meta_config(): array
{
	$file = STORE_ROOT . '/meta.local.php';
	if (!is_readable($file)) {
		return [];
	}
	$cfg = include $file;
	return is_array($cfg) ? $cfg : [];
}

function store_capi_ready(): bool
{
	$cfg = store_meta_config();
	return trim((string) ($cfg['pixel_id'] ?? '')) !== '' && trim((string) ($cfg['access_token'] ?? '')) !== '';
}

function store_capi_hash(string $value): string
{
	$value = strtolower(trim($value));
	if ($value === '') {
		return '';
	}
	return hash('sha256', $value);
}

function store_capi_phone(string $phone): string
{
	$digits = preg_replace('/\D+/', '', $phone) ?? '';
	if ($digits === '') {
		return '';
	}
	if (str_starts_with($digits, '0')) {
		$digits = '63' . substr($digits, 1);
	} elseif (strlen($digits) === 10) {
		$digits = '63' . $digits;
	}
	return store_capi_hash($digits);
}

function store_capi_client_ip(): string
{
	$forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
	if ($forwarded !== '') {
		$first = trim(explode(',', $forwarded)[0]);
		if (filter_var($first, FILTER_VALIDATE_IP)) {
			return $first;
		}
	}
	$remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
	return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '';
}

function store_capi_source_url(): string
{
	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
	$host = (string) ($_SERVER['HTTP_HOST'] ?? 'kuyaelytours.com');
	$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
	return ($https ? 'https' : 'http') . '://' . $host . $uri;
}

function store_capi_send(array $events, string $sourceUrl = ''): bool
{
	if (!store_capi_ready() || !$events) {
		return false;
	}
	if ((string) ($_COOKIE['ke_consent'] ?? '') !== '1') {
		return false;
	}
	$cfg = store_meta_config();
	$pixelId = preg_replace('/\D+/', '', (string) ($cfg['pixel_id'] ?? '')) ?? '';
	$token = trim((string) ($cfg['access_token'] ?? ''));
	if ($pixelId === '' || $token === '') {
		return false;
	}
	$user = function_exists('store_user') ? store_user() : null;
	$email = store_capi_hash((string) ($user['email'] ?? ''));
	$phone = store_capi_phone((string) ($user['phone'] ?? ''));
	$external = store_capi_hash((string) ($user['id'] ?? ''));
	$fbp = trim((string) ($_COOKIE['_fbp'] ?? ''));
	$fbc = trim((string) ($_COOKIE['_fbc'] ?? ''));
	if ($fbc === '' && isset($_GET['fbclid'])) {
		$click = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['fbclid']) ?? '';
		if ($click !== '') {
			$fbc = 'fb.1.' . (string) (int) round(microtime(true) * 1000) . '.' . $click;
		}
	}
	$userData = array_filter([
		'client_ip_address' => store_capi_client_ip(),
		'client_user_agent' => substr(trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 512),
		'fbp' => $fbp,
		'fbc' => $fbc,
		'em' => $email !== '' ? [$email] : null,
		'ph' => $phone !== '' ? [$phone] : null,
		'external_id' => $external !== '' ? [$external] : null,
	], static function ($value) {
		return $value !== null && $value !== '';
	});
	if ($sourceUrl === '' || !preg_match('#^https://([a-z0-9-]+\.)?kuyaelytours\.com(/|$)#i', $sourceUrl)) {
		$sourceUrl = store_capi_source_url();
	}
	$allowed = ['ViewContent', 'Search', 'AddToCart', 'InitiateCheckout', 'AddPaymentInfo', 'Purchase', 'Lead', 'CompleteRegistration', 'Contact', 'Schedule', 'Subscribe', 'CustomizeProduct'];
	$data = [];
	foreach (array_slice($events, 0, 10) as $event) {
		if (!is_array($event)) {
			continue;
		}
		$name = (string) ($event['event'] ?? '');
		if (!in_array($name, $allowed, true)) {
			continue;
		}
		$eventId = preg_replace('/[^a-f0-9]/', '', (string) ($event['event_id'] ?? '')) ?? '';
		if ($eventId === '') {
			$eventId = bin2hex(random_bytes(16));
		}
		$params = is_array($event['params'] ?? null) ? $event['params'] : [];
		$custom = [];
		foreach (['currency', 'content_name', 'content_type', 'content_category', 'search_string', 'status'] as $key) {
			if (isset($params[$key]) && is_scalar($params[$key]) && (string) $params[$key] !== '') {
				$custom[$key] = (string) $params[$key];
			}
		}
		if (isset($params['value']) && is_numeric($params['value'])) {
			$custom['value'] = (float) $params['value'];
		}
		if (isset($params['num_items']) && is_numeric($params['num_items'])) {
			$custom['num_items'] = (int) $params['num_items'];
		}
		if (isset($params['content_ids']) && is_array($params['content_ids'])) {
			$ids = [];
			foreach ($params['content_ids'] as $id) {
				if (is_scalar($id) && (string) $id !== '') {
					$ids[] = (string) $id;
				}
			}
			if ($ids) {
				$custom['content_ids'] = $ids;
			}
		}
		$row = [
			'event_name' => $name,
			'event_time' => time(),
			'event_id' => $eventId,
			'action_source' => 'website',
			'event_source_url' => $sourceUrl,
			'user_data' => $userData,
		];
		if ($custom) {
			$row['custom_data'] = $custom;
		}
		$data[] = $row;
	}
	if (!$data) {
		return false;
	}
	$body = json_encode(['data' => $data]);
	if ($body === false) {
		return false;
	}
	$ch = curl_init('https://graph.facebook.com/v21.0/' . rawurlencode($pixelId) . '/events');
	curl_setopt_array($ch, [
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
		CURLOPT_POSTFIELDS => $body,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 4,
		CURLOPT_URL => 'https://graph.facebook.com/v21.0/' . rawurlencode($pixelId) . '/events?access_token=' . rawurlencode($token),
	]);
	$raw = curl_exec($ch);
	$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$decoded = json_decode((string) $raw, true);
	return $code >= 200 && $code < 300 && (int) ($decoded['events_received'] ?? 0) > 0;
}
