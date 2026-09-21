<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function ke_chat_payload(?array $chat): array
{
	$user = store_user();
	$messages = [];
	$name = '';
	$email = '';
	$phone = '';
	if ($chat) {
		$messages = store_chat_public_messages(store_chat_messages((string) $chat['id']));
		$name = (string) ($chat['guest_name'] ?? '');
		$email = (string) ($chat['email'] ?? '');
		$phone = (string) ($chat['phone'] ?? '');
	}
	if ($user) {
		$name = $name !== '' ? $name : (string) ($user['name'] ?? '');
		$email = $email !== '' ? $email : (string) ($user['email'] ?? '');
		$phone = $phone !== '' ? $phone : (string) ($user['phone'] ?? '');
	}
	return [
		'ok' => true,
		'csrf' => store_csrf_token(),
		'logged_in' => (bool) $user,
		'name' => $name,
		'email' => $email,
		'phone' => $phone,
		'messages' => $messages,
		'channels' => function_exists('store_chat_channels_public') ? store_chat_channels_public() : new stdClass(),
	];
}

function ke_chat_current(): ?array
{
	$user = store_user();
	if ($user) {
		$chat = store_find_chat_user((string) $user['id']);
		if ($chat) {
			return $chat;
		}
	}
	return store_find_chat_token(store_chat_token(false));
}

function ke_chat_rate_ok(): bool
{
	$hits = $_SESSION['ke_chat_hits'] ?? [];
	if (!is_array($hits)) {
		$hits = [];
	}
	$now = time();
	$hits = array_values(array_filter($hits, static function ($t) use ($now) {
		return is_int($t) && $t > $now - 300;
	}));
	if (count($hits) >= 12) {
		$_SESSION['ke_chat_hits'] = $hits;
		return false;
	}
	$hits[] = $now;
	$_SESSION['ke_chat_hits'] = $hits;
	return true;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'GET') {
	echo json_encode(ke_chat_payload(ke_chat_current()));
	exit;
}

if ($method !== 'POST') {
	http_response_code(405);
	echo json_encode(['ok' => false, 'error' => 'Use POST to send a message.']);
	exit;
}

if (!store_csrf_ok()) {
	http_response_code(400);
	echo json_encode(['ok' => false, 'error' => 'Refresh the page and try again.']);
	exit;
}

if (trim((string) ($_POST['website'] ?? '')) !== '') {
	echo json_encode(ke_chat_payload(ke_chat_current()));
	exit;
}

if (!ke_chat_rate_ok()) {
	http_response_code(429);
	echo json_encode(['ok' => false, 'error' => 'Please wait a moment before sending another message.']);
	exit;
}

$text = trim(strip_tags((string) ($_POST['text'] ?? '')));
$text = preg_replace('/\s+/u', ' ', $text) ?? $text;
if (function_exists('mb_substr')) {
	$text = mb_substr($text, 0, 1000);
} else {
	$text = substr($text, 0, 1000);
}
if (strlen($text) < 2) {
	http_response_code(400);
	echo json_encode(['ok' => false, 'error' => 'Type a short message first.']);
	exit;
}

$name = trim(strip_tags((string) ($_POST['name'] ?? '')));
if (function_exists('mb_substr')) {
	$name = mb_substr($name, 0, 80);
} else {
	$name = substr($name, 0, 80);
}
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$phone = trim(strip_tags((string) ($_POST['phone'] ?? '')));
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$email = '';
}
if (strlen($name) < 2) {
	http_response_code(400);
	echo json_encode(['ok' => false, 'error' => 'Enter your name so we know who to reply to.']);
	exit;
}

$token = store_chat_token(true);
$chat = store_chat_open($token, [
	'name' => $name,
	'email' => $email,
	'phone' => $phone,
]);
$existing = store_chat_messages((string) $chat['id']);
$hasDesk = false;
$guestCount = 0;
foreach ($existing as $row) {
	$who = strtolower(trim((string) ($row['sender'] ?? $row['from'] ?? '')));
	if ($who === 'desk') {
		$hasDesk = true;
	}
	if ($who === 'guest') {
		$guestCount++;
	}
}
store_chat_add_message($chat, 'guest', $text);
if ($guestCount < 1 && !$hasDesk) {
	$first = explode(' ', $name)[0];
	store_chat_add_message($chat, 'desk', 'Thanks, ' . $first . '. We have your message. A teammate will reply in this chat.');
}

echo json_encode(ke_chat_payload(store_find_chat_id((string) $chat['id']) ?: $chat));
