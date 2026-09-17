<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
$user = store_user();
echo json_encode([
	'count' => store_cart_count(),
	'name' => $user['name'] ?? '',
	'logged_in' => (bool) $user,
	'csrf' => store_csrf_token(),
]);
