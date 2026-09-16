<?php
declare(strict_types=1);

if (!defined('STORE_ROOT')) {
	define('STORE_ROOT', __DIR__);
}
define('STORE_DATA', STORE_ROOT . '/data');
define('STORE_SITE', dirname(__DIR__));

if (session_status() !== PHP_SESSION_ACTIVE && !defined('STORE_SKIP_SESSION')) {
	session_name('ke_shop');
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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
