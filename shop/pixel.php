<?php
declare(strict_types=1);

require dirname(__DIR__) . '/store/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$events = [];
if (isset($_SESSION['ke_pixel']) && is_array($_SESSION['ke_pixel'])) {
	$events = array_values($_SESSION['ke_pixel']);
	unset($_SESSION['ke_pixel']);
}

echo json_encode(['events' => $events], JSON_UNESCAPED_UNICODE);
