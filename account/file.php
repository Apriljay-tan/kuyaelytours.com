<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

store_require_login('/account/bookings.php');
$user = store_user();
$id = trim((string) ($_GET['id'] ?? ''));
$file = ($user && preg_match('/^[a-f0-9]{8,32}$/', $id)) ? store_find_booking_file($id) : null;
$path = $file ? store_booking_file_path($file) : '';
$owner = $file && (string) ($file['user_id'] ?? '') === (string) ($user['id'] ?? '');
if (!$file || $path === '' || (!$owner && !store_is_staff($user))) {
	http_response_code(404);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'File not found.';
	exit;
}
$mime = (string) ($file['mime'] ?? 'application/octet-stream');
if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
	$mime = 'application/octet-stream';
}
$name = store_desk_safe_filename((string) ($file['original_name'] ?? 'document'));
$ascii = preg_replace('/[^A-Za-z0-9._ -]+/', '', $name) ?: 'document';
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $ascii) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
header('Cache-Control: private, no-store');
readfile($path);
exit;
