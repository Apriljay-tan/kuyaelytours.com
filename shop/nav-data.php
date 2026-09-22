<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$count = store_cart_count();
$csrf = store_csrf_token();
session_write_close();
$user = store_user();
echo json_encode([
	'count' => $count,
	'name' => $user['name'] ?? '',
	'email' => $user['email'] ?? '',
	'phone' => $user['phone'] ?? '',
	'logged_in' => (bool) $user,
	'csrf' => $csrf,
]);
