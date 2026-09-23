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
require STORE_ROOT . '/pay.php';
require STORE_ROOT . '/meta.php';
require STORE_ROOT . '/layout.php';

function store_h(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function store_redirect(string $path): void
{
	header('Location: ' . $path, true, 303);
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
