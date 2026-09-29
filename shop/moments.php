<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$file = dirname(__DIR__) . '/assets/data/moments.json';
if (!is_readable($file)) {
	echo '{"speed":40,"photos":[]}';
	exit;
}
$raw = file_get_contents($file);
echo is_string($raw) && $raw !== '' ? $raw : '{"speed":40,"photos":[]}';
