<?php
declare(strict_types=1);

require dirname(__DIR__) . '/store/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || (string) ($_COOKIE['ke_consent'] ?? '') !== '1') {
	echo json_encode(['ok' => 0]);
	exit;
}

$host = (string) (parse_url((string) ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST) ?? '');
if ($host !== 'kuyaelytours.com' && $host !== 'www.kuyaelytours.com') {
	echo json_encode(['ok' => 0]);
	exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$events = is_array($payload['events'] ?? null) ? $payload['events'] : [];
$url = trim((string) ($payload['url'] ?? ''));
$ok = store_capi_send($events, $url);
echo json_encode(['ok' => $ok ? 1 : 0]);
