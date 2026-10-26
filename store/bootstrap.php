<?php
declare(strict_types=1);

if (!defined('STORE_ROOT')) {
	define('STORE_ROOT', __DIR__);
}
define('STORE_DATA', STORE_ROOT . '/data');
define('STORE_SITE', dirname(__DIR__));

if (!headers_sent()) {
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: SAMEORIGIN');
	header('Referrer-Policy: strict-origin-when-cross-origin');
	header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
	|| (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
	|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if (session_status() !== PHP_SESSION_ACTIVE && !defined('STORE_SKIP_SESSION')) {
	session_name('ke_shop');
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'secure' => $https,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	session_start();
}

require STORE_ROOT . '/storage.php';
require STORE_ROOT . '/catalog.php';
require STORE_ROOT . '/cart.php';
require STORE_ROOT . '/auth.php';
require STORE_ROOT . '/oauth.php';
require STORE_ROOT . '/bookings.php';
require STORE_ROOT . '/desk.php';
require STORE_ROOT . '/pay.php';
require STORE_ROOT . '/paypal.php';
require STORE_ROOT . '/banks.php';
require STORE_ROOT . '/meta.php';
require STORE_ROOT . '/layout.php';
require STORE_ROOT . '/partners.php';

function store_h(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function store_redirect(string $path): void
{
	header('Location: ' . $path, true, 303);
	exit;
}

function store_replace(string $path): void
{
	if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '://')) {
		store_redirect('/shop/cart.php');
	}
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Content-Type: text/html; charset=UTF-8');
	$js = json_encode($path, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Continuing</title></head><body>';
	echo '<p><a href="' . store_h($path) . '">Continue</a></p>';
	echo '<script>location.replace(' . $js . ');</script></body></html>';
	exit;
}

function store_pay_away(string $url, string $bookingId): void
{
	$host = strtolower((string) parse_url($url, PHP_URL_HOST));
	$allowed = ['checkout.paymongo.com', 'www.paypal.com', 'www.sandbox.paypal.com', 'paypal.com', 'sandbox.paypal.com'];
	if (!str_starts_with($url, 'https://') || !in_array($host, $allowed, true)) {
		$_SESSION['pay_error'] = 'Payment page did not open.';
		store_redirect('/shop/checkout.php?booking=' . rawurlencode($bookingId));
	}
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Content-Type: text/html; charset=UTF-8');
	$js = json_encode($url, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="referrer" content="strict-origin"><title>Opening payment</title></head><body>';
	echo '<p>Opening the payment page. <a href="' . store_h($url) . '">Continue</a></p>';
	echo '<script>location.replace(' . $js . ');</script></body></html>';
	exit;
}

function store_safe_next(string $next, string $fallback): string
{
	$next = trim(str_replace('\\', '/', $next));
	if ($next === '' || strlen($next) > 200) {
		return $fallback;
	}
	if ($next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '://') || str_contains($next, '@')) {
		return $fallback;
	}
	if (!preg_match('#^/[A-Za-z0-9/_.\-]*(\?[A-Za-z0-9/_\-=&%.]*)?$#', $next)) {
		return $fallback;
	}
	return $next;
}

function store_login_allowed(): bool
{
	return time() >= (int) ($_SESSION['ke_login_lock'] ?? 0);
}

function store_login_fail(): void
{
	$n = (int) ($_SESSION['ke_login_fails'] ?? 0) + 1;
	$_SESSION['ke_login_fails'] = $n;
	if ($n >= 8) {
		$_SESSION['ke_login_lock'] = time() + 900;
	}
}

function store_login_clear(): void
{
	unset($_SESSION['ke_login_fails'], $_SESSION['ke_login_lock']);
}

function store_request_ip(): string
{
	$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
	return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function store_login_guard_edit(callable $change): void
{
	$file = STORE_DATA . '/login-guard.json';
	if (!is_dir(STORE_DATA) && !mkdir(STORE_DATA, 0750, true) && !is_dir(STORE_DATA)) {
		return;
	}
	$handle = fopen($file, 'c+');
	if ($handle === false) {
		return;
	}
	flock($handle, LOCK_EX);
	$raw = stream_get_contents($handle);
	$rows = json_decode((string) $raw, true);
	if (!is_array($rows)) {
		$rows = [];
	}
	$now = time();
	$kept = [];
	foreach ($rows as $key => $row) {
		if (!is_array($row)) {
			continue;
		}
		$seen = (int) ($row['seen'] ?? 0);
		$until = (int) ($row['until'] ?? 0);
		if ($until > $now || $seen > $now - 3600) {
			$kept[$key] = $row;
		}
	}
	$rows = $change($kept);
	if (!is_array($rows)) {
		$rows = $kept;
	}
	ftruncate($handle, 0);
	rewind($handle);
	fwrite($handle, json_encode($rows));
	fflush($handle);
	flock($handle, LOCK_UN);
	fclose($handle);
}

function store_login_guard_blocked(string $email): bool
{
	$blocked = false;
	$email = strtolower(trim($email));
	$keys = ['e:' . $email, 'i:' . store_request_ip()];
	store_login_guard_edit(static function (array $rows) use ($keys, &$blocked): array {
		$now = time();
		foreach ($keys as $key) {
			if ((int) (($rows[$key]['until'] ?? 0)) > $now) {
				$blocked = true;
			}
		}
		return $rows;
	});
	return $blocked;
}

function store_login_guard_fail(string $email): void
{
	$email = strtolower(trim($email));
	$limits = [
		'e:' . $email => 8,
		'i:' . store_request_ip() => 30,
	];
	store_login_guard_edit(static function (array $rows) use ($limits): array {
		$now = time();
		foreach ($limits as $key => $limit) {
			$row = is_array($rows[$key] ?? null) ? $rows[$key] : ['n' => 0, 'until' => 0, 'seen' => $now];
			if ((int) ($row['until'] ?? 0) > $now) {
				$rows[$key] = $row;
				continue;
			}
			if ((int) ($row['seen'] ?? 0) < $now - 900) {
				$row['n'] = 0;
			}
			$row['n'] = (int) ($row['n'] ?? 0) + 1;
			$row['seen'] = $now;
			if ($row['n'] >= $limit) {
				$row['until'] = $now + 900;
				$row['n'] = 0;
			}
			$rows[$key] = $row;
		}
		return $rows;
	});
}

function store_login_guard_ok(string $email): void
{
	$email = strtolower(trim($email));
	$drop = ['e:' . $email, 'i:' . store_request_ip()];
	store_login_guard_edit(static function (array $rows) use ($drop): array {
		foreach ($drop as $key) {
			unset($rows[$key]);
		}
		return $rows;
	});
}

function store_csrf_token(): string
{
	if (empty($_SESSION['store_csrf']) || !is_string($_SESSION['store_csrf'])) {
		$_SESSION['store_csrf'] = bin2hex(random_bytes(16));
	}
	return $_SESSION['store_csrf'];
}

function store_csrf_ok(): bool
{
	$token = (string) ($_POST['csrf'] ?? '');
	$known = (string) ($_SESSION['store_csrf'] ?? '');
	return $known !== '' && hash_equals($known, $token);
}

function store_today(): string
{
	return (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
}

function store_now(): string
{
	return (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d H:i:s');
}

function store_id(): string
{
	return bin2hex(random_bytes(8));
}

function store_money(int $amount): string
{
	return '₱' . number_format($amount);
}
