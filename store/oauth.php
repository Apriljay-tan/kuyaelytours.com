<?php
declare(strict_types=1);

// TEMPORARY FB DEBUG: remove these helpers and their call sites after diagnosis.
function store_fb_debug(string $message): void
{
	if (defined('STORE_FB_DEBUG') && STORE_FB_DEBUG) {
		error_log('[FB DEBUG]' . (strpos($message, '[FAIL]') === 0 ? '' : ' ') . $message);
	}
}

function store_fb_debug_stage(string $stage = ''): string
{
	static $current = 'CALLBACK';
	if ($stage !== '') {
		$current = $stage;
	}
	return $current;
}

function store_fb_debug_secrets(array $values = []): array
{
	static $secrets = [];
	if (defined('STORE_FB_DEBUG') && STORE_FB_DEBUG) {
		foreach ($values as $value) {
			if (is_string($value) && $value !== '') {
				$secrets[] = $value;
				$secrets[] = rawurlencode($value);
				$secrets[] = urlencode($value);
			}
		}
	}
	return $secrets;
}

function store_fb_debug_exception(Throwable $e, string $stage = ''): void
{
	if (!defined('STORE_FB_DEBUG') || !STORE_FB_DEBUG) {
		return;
	}
	$secrets = store_fb_debug_secrets([session_id()]);
	$message = str_replace($secrets, '[REDACTED]', $e->getMessage());
	// Never emit request URLs, query strings, or credential-labelled values.
	$message = preg_replace('~https?://[^\s<>]+~i', '[URL REDACTED]', $message) ?? '[REDACTED]';
	$message = preg_replace('~\b(client_secret|app_secret|access_token|code|state|PHPSESSID)\b["\x27]?\s*[:=]\s*[^\s,;]+~i', '$1=[REDACTED]', $message) ?? '[REDACTED]';
	store_fb_debug('[FAIL] ' . ($stage !== '' ? $stage : store_fb_debug_stage()) . ' EXCEPTION=' . json_encode([
		'message' => $message,
		'file' => str_replace($secrets, '[REDACTED]', $e->getFile()),
		'line' => $e->getLine(),
	], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
}

function store_oauth_config(): array
{
	static $cfg = null;
	if (is_array($cfg)) {
		return $cfg;
	}
	$file = STORE_ROOT . '/oauth.local.php';
	if (!is_readable($file)) {
		$cfg = [];
		return $cfg;
	}
	$loaded = include $file;
	$cfg = is_array($loaded) ? $loaded : [];
	return $cfg;
}

function store_oauth_enabled(string $provider): bool
{
	$cfg = store_oauth_config()[$provider] ?? [];
	if (!is_array($cfg) || ($cfg['client_id'] ?? '') === '') {
		return false;
	}
	if ($provider === 'apple') {
		return ($cfg['team_id'] ?? '') !== '' && ($cfg['key_id'] ?? '') !== '' && ($cfg['private_key'] ?? '') !== '';
	}
	return ($cfg['client_secret'] ?? '') !== '';
}

function store_oauth_redirect_uri(): string
{
	$cfg = store_oauth_config();
	if (!empty($cfg['redirect']) && is_string($cfg['redirect'])) {
		return $cfg['redirect'];
	}
	$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
	$host = (string) ($_SERVER['HTTP_HOST'] ?? 'kuyaelytours.com');
	return ($https ? 'https' : 'http') . '://' . $host . '/account/oauth-callback.php';
}

function store_oauth_error_message(string $code): string
{
	switch ($code) {
		case 'social_setup':
			return 'Social sign-in is connected after Google or Facebook keys are added. Use email for now.';
		case 'social_denied':
			return 'Sign-in was cancelled.';
		case 'social_failed':
			return 'Could not finish social sign-in. Please try email, or try again.';
		case 'social':
			return 'That sign-in option is not available.';
		default:
			return '';
	}
}

function store_oauth_safe_next(string $next): string
{
	if ($next === '' || $next[0] !== '/') {
		return '/account/bookings.php';
	}
	return $next;
}

function store_oauth_authorize_url(string $provider, string $state): string
{
	$cfg = store_oauth_config()[$provider] ?? [];
	$clientId = rawurlencode((string) ($cfg['client_id'] ?? ''));
	$redirect = rawurlencode(store_oauth_redirect_uri());
	$stateQ = rawurlencode($state);
	if ($provider === 'google') {
		return 'https://accounts.google.com/o/oauth2/v2/auth?client_id=' . $clientId
			. '&redirect_uri=' . $redirect
			. '&response_type=code&scope=openid%20email%20profile&state=' . $stateQ
			. '&prompt=select_account';
	}
	if ($provider === 'facebook') {
		return 'https://www.facebook.com/v19.0/dialog/oauth?client_id=' . $clientId
			. '&redirect_uri=' . $redirect
			. '&state=' . $stateQ
			. '&scope=email,public_profile';
	}
	return 'https://appleid.apple.com/auth/authorize?client_id=' . $clientId
		. '&redirect_uri=' . $redirect
		. '&response_type=code&response_mode=form_post&scope=name%20email&state=' . $stateQ;
}

function store_oauth_profile(string $provider, string $code): ?array
{
	if ($provider === 'google') {
		return store_oauth_google($code);
	}
	if ($provider === 'facebook') {
		return store_oauth_facebook($code);
	}
	if ($provider === 'apple') {
		return store_oauth_apple($code);
	}
	return null;
}

function store_oauth_google(string $code): ?array
{
	$cfg = store_oauth_config()['google'] ?? [];
	$token = store_http_form('https://oauth2.googleapis.com/token', [
		'code' => $code,
		'client_id' => (string) ($cfg['client_id'] ?? ''),
		'client_secret' => (string) ($cfg['client_secret'] ?? ''),
		'redirect_uri' => store_oauth_redirect_uri(),
		'grant_type' => 'authorization_code',
	]);
	$access = (string) ($token['access_token'] ?? '');
	if ($access === '') {
		return null;
	}
	$info = store_http_bearer('https://www.googleapis.com/oauth2/v3/userinfo', $access);
	$email = strtolower(trim((string) ($info['email'] ?? '')));
	$id = (string) ($info['sub'] ?? '');
	if ($email === '' || $id === '') {
		return null;
	}
	return [
		'id' => $id,
		'email' => $email,
		'name' => trim((string) ($info['name'] ?? '')) ?: explode('@', $email)[0],
	];
}

function store_oauth_facebook(string $code): ?array
{
	store_fb_debug_stage('TOKEN_EXCHANGE');
	$cfg = store_oauth_config()['facebook'] ?? [];
	store_fb_debug_secrets([$code, (string) ($cfg['client_secret'] ?? '')]);
	$url = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
		'client_id' => (string) ($cfg['client_id'] ?? ''),
		'client_secret' => (string) ($cfg['client_secret'] ?? ''),
		'redirect_uri' => store_oauth_redirect_uri(),
		'code' => $code,
	]);
	$token = store_http_get($url, 'TOKEN');
	$access = (string) ($token['access_token'] ?? '');
	store_fb_debug_secrets([$access]);
	store_fb_debug('TOKEN_RECEIVED=' . ($access !== '' ? 'YES' : 'NO'));
	if ($access === '') {
		store_fb_debug('[FAIL] TOKEN_EXCHANGE');
		return null;
	}
	store_fb_debug_stage('GRAPH_API');
	$info = store_http_get('https://graph.facebook.com/me?fields=id,name,email&access_token=' . rawurlencode($access), 'GRAPH');
	$email = strtolower(trim((string) ($info['email'] ?? '')));
	$id = (string) ($info['id'] ?? '');
	store_fb_debug('FACEBOOK_ID=' . ($id !== '' ? 'YES' : 'NO'));
	store_fb_debug('EMAIL_PRESENT=' . ($email !== '' ? 'YES' : 'NO'));
	if ($id === '') {
		store_fb_debug('[FAIL] FACEBOOK_ID_MISSING');
		return null;
	}
	if ($email === '') {
		store_fb_debug('EMAIL_COMPLETION_MAY_BE_REQUIRED=YES');
	}
	return [
		'id' => $id,
		'email' => $email,
		'name' => trim((string) ($info['name'] ?? '')) ?: explode('@', $email)[0],
	];
}

function store_oauth_apple(string $code): ?array
{
	$cfg = store_oauth_config()['apple'] ?? [];
	$secret = store_apple_client_secret($cfg);
	if ($secret === '') {
		return null;
	}
	$token = store_http_form('https://appleid.apple.com/auth/token', [
		'client_id' => (string) ($cfg['client_id'] ?? ''),
		'client_secret' => $secret,
		'code' => $code,
		'grant_type' => 'authorization_code',
		'redirect_uri' => store_oauth_redirect_uri(),
	]);
	$idToken = (string) ($token['id_token'] ?? '');
	$payload = store_jwt_payload($idToken);
	$email = strtolower(trim((string) ($payload['email'] ?? '')));
	$id = (string) ($payload['sub'] ?? '');
	$name = '';
	$userJson = (string) ($_POST['user'] ?? '');
	if ($userJson !== '') {
		$user = json_decode($userJson, true);
		if (is_array($user)) {
			$first = trim((string) (($user['name']['firstName'] ?? '')));
			$last = trim((string) (($user['name']['lastName'] ?? '')));
			$name = trim($first . ' ' . $last);
			if ($email === '') {
				$email = strtolower(trim((string) ($user['email'] ?? '')));
			}
		}
	}
	if ($id === '') {
		return null;
	}
	if ($email === '') {
		$email = 'apple-' . $id . '@users.kuyaelytours.com';
	}
	return [
		'id' => $id,
		'email' => $email,
		'name' => $name !== '' ? $name : explode('@', $email)[0],
	];
}

function store_apple_client_secret(array $cfg): string
{
	$clientId = (string) ($cfg['client_id'] ?? '');
	$teamId = (string) ($cfg['team_id'] ?? '');
	$keyId = (string) ($cfg['key_id'] ?? '');
	$privateKey = (string) ($cfg['private_key'] ?? '');
	if ($clientId === '' || $teamId === '' || $keyId === '' || $privateKey === '') {
		return '';
	}
	$now = time();
	$header = store_b64url(json_encode(['alg' => 'ES256', 'kid' => $keyId], JSON_UNESCAPED_SLASHES));
	$claims = store_b64url(json_encode([
		'iss' => $teamId,
		'iat' => $now,
		'exp' => $now + 86400 * 120,
		'aud' => 'https://appleid.apple.com',
		'sub' => $clientId,
	], JSON_UNESCAPED_SLASHES));
	$data = $header . '.' . $claims;
	$key = openssl_pkey_get_private($privateKey);
	if ($key === false) {
		return '';
	}
	$signature = '';
	if (!openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256)) {
		return '';
	}
	$raw = store_ecdsa_der_to_jose($signature);
	if ($raw === '') {
		return '';
	}
	return $data . '.' . store_b64url($raw);
}

function store_ecdsa_der_to_jose(string $der): string
{
	$offset = 0;
	if (strlen($der) < 8 || ord($der[$offset++]) !== 0x30) {
		return '';
	}
	$seqLen = ord($der[$offset++]);
	if ($seqLen & 0x80) {
		$n = $seqLen & 0x7f;
		$offset += $n;
	}
	if (!isset($der[$offset]) || ord($der[$offset++]) !== 0x02) {
		return '';
	}
	$rLen = ord($der[$offset++]);
	$r = substr($der, $offset, $rLen);
	$offset += $rLen;
	if (!isset($der[$offset]) || ord($der[$offset++]) !== 0x02) {
		return '';
	}
	$sLen = ord($der[$offset++]);
	$s = substr($der, $offset, $sLen);
	$r = ltrim($r, "\x00");
	$s = ltrim($s, "\x00");
	$r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
	$s = str_pad($s, 32, "\x00", STR_PAD_LEFT);
	return $r . $s;
}

function store_jwt_payload(string $jwt): array
{
	$parts = explode('.', $jwt);
	if (count($parts) < 2) {
		return [];
	}
	$json = store_b64url_decode($parts[1]);
	$data = json_decode($json, true);
	return is_array($data) ? $data : [];
}

function store_b64url(string $value): string
{
	return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function store_b64url_decode(string $value): string
{
	$pad = strlen($value) % 4;
	if ($pad) {
		$value .= str_repeat('=', 4 - $pad);
	}
	$out = base64_decode(strtr($value, '-_', '+/'), true);
	return is_string($out) ? $out : '';
}

function store_http_form(string $url, array $fields): array
{
	return store_http_request($url, [
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query($fields),
		CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
	]);
}

function store_http_get(string $url, string $debugStage = ''): array
{
	return store_http_request($url, [], $debugStage);
}

function store_http_bearer(string $url, string $token): array
{
	return store_http_request($url, [
		CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
	]);
}

function store_http_request(string $url, array $opts, string $debugStage = ''): array
{
	// TEMPORARY: only Facebook callers supply a diagnostic stage.
	$debug = defined('STORE_FB_DEBUG') && STORE_FB_DEBUG && in_array($debugStage, ['TOKEN', 'GRAPH'], true);
	$failure = $debugStage === 'TOKEN' ? 'TOKEN_EXCHANGE' : 'GRAPH_API';
	if (!function_exists('curl_init')) {
		if ($debug) {
			store_fb_debug($debugStage . '_HTTP=0');
			store_fb_debug('[FAIL] ' . $failure . ' CURL_UNAVAILABLE');
		}
		return [];
	}
	$ch = curl_init($url);
	$defaults = [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_CONNECTTIMEOUT => 10,
	];
	curl_setopt_array($ch, $defaults + $opts);
	$raw = curl_exec($ch);
	if ($debug) {
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errno = curl_errno($ch);
		store_fb_debug($debugStage . '_HTTP=' . $status);
		store_fb_debug($debugStage . '_CURL_ERRNO=' . $errno);
		if ($errno !== 0 || $status < 200 || $status >= 300) {
			store_fb_debug('[FAIL] ' . $failure . ' HTTP_OR_TRANSPORT');
		}
	}
	curl_close($ch);
	if (!is_string($raw) || $raw === '') {
		if ($debug) {
			store_fb_debug('[FAIL] ' . $failure . ' EMPTY_RESPONSE');
		}
		return [];
	}
	$data = json_decode($raw, true);
	if ($debug) {
		store_fb_debug($debugStage . '_RESPONSE_SUCCESS=' . ($status >= 200 && $status < 300 && $errno === 0 && is_array($data) && !isset($data['error']) ? 'YES' : 'NO'));
		if (!is_array($data)) {
			store_fb_debug('[FAIL] ' . $failure . ' INVALID_JSON');
		} elseif (isset($data['error'])) {
			// Numeric API error codes only; never log response bodies or tokens.
			$apiError = is_array($data['error']) ? $data['error'] : [];
			store_fb_debug('[FAIL] ' . $failure . ' API_ERROR_CODE=' . (int) ($apiError['code'] ?? 0)
				. ' API_ERROR_SUBCODE=' . (int) ($apiError['error_subcode'] ?? 0));
		}
	}
	return is_array($data) ? $data : [];
}
