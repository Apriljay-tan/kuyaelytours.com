<?php
declare(strict_types=1);

function store_db(): ?PDO
{
	static $pdo = null;
	static $tried = false;
	if ($tried) {
		return $pdo;
	}
	$tried = true;
	$file = STORE_ROOT . '/db.local.php';
	if (!is_readable($file)) {
		return null;
	}
	$cfg = include $file;
	if (!is_array($cfg) || empty($cfg['dsn'])) {
		return null;
	}
	try {
		$dsn = (string) $cfg['dsn'];
		if (stripos($dsn, 'connect_timeout') === false) {
			$dsn .= ';connect_timeout=3';
		}
		$pdo = new PDO(
			$dsn,
			(string) ($cfg['user'] ?? ''),
			(string) ($cfg['pass'] ?? ''),
			[
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_TIMEOUT => 3,
			]
		);
		store_db_migrate($pdo);
		store_db_seed_from_json($pdo);
		return $pdo;
	} catch (Throwable $e) {
		// TEMPORARY FB DEBUG: expose exceptions previously swallowed here.
		if (function_exists('store_fb_debug_exception')) {
			store_fb_debug_exception($e, 'DATABASE INITIALIZATION');
		}
		$pdo = null;
		return null;
	}
}

function store_db_migrate(PDO $pdo): void
{
	$pdo->exec('CREATE TABLE IF NOT EXISTS ke_users (
		id VARCHAR(32) PRIMARY KEY,
		name VARCHAR(140) NOT NULL,
		email VARCHAR(190) NULL UNIQUE,
		phone VARCHAR(40) NOT NULL DEFAULT "",
		password_hash VARCHAR(255) NOT NULL,
		oauth_provider VARCHAR(40) NOT NULL DEFAULT "",
		oauth_id VARCHAR(190) NOT NULL DEFAULT "",
		created VARCHAR(32) NOT NULL
	)');
	$pdo->exec('CREATE TABLE IF NOT EXISTS ke_bookings (
		id VARCHAR(32) PRIMARY KEY,
		user_id VARCHAR(32) NOT NULL,
		status VARCHAR(40) NOT NULL,
		pay_method VARCHAR(40) NOT NULL DEFAULT "",
		total INT NOT NULL DEFAULT 0,
		guest_name VARCHAR(140) NOT NULL,
		email VARCHAR(190) NOT NULL,
		phone VARCHAR(40) NOT NULL DEFAULT "",
		notes TEXT,
		items_json MEDIUMTEXT NOT NULL,
		created VARCHAR(32) NOT NULL
	)');
	$pdo->exec('CREATE TABLE IF NOT EXISTS ke_blocked (
		id VARCHAR(32) PRIMARY KEY,
		vehicle VARCHAR(80) NOT NULL,
		travel_date VARCHAR(16) NOT NULL,
		booking_id VARCHAR(32) NOT NULL
	)');
	store_db_add_column($pdo, 'ke_users', 'oauth_provider', 'VARCHAR(40) NOT NULL DEFAULT ""');
	store_db_add_column($pdo, 'ke_users', 'oauth_id', 'VARCHAR(190) NOT NULL DEFAULT ""');
	try {
		$pdo->exec('CREATE TABLE IF NOT EXISTS ke_chats (
			id VARCHAR(32) PRIMARY KEY,
			token VARCHAR(64) NOT NULL UNIQUE,
			user_id VARCHAR(32) NOT NULL DEFAULT "",
			guest_name VARCHAR(140) NOT NULL DEFAULT "",
			email VARCHAR(190) NOT NULL DEFAULT "",
			phone VARCHAR(40) NOT NULL DEFAULT "",
			last_message TEXT,
			last_at VARCHAR(32) NOT NULL DEFAULT "",
			unread_staff INT NOT NULL DEFAULT 0,
			created VARCHAR(32) NOT NULL
		)');
		$pdo->exec('CREATE TABLE IF NOT EXISTS ke_chat_messages (
			id VARCHAR(32) PRIMARY KEY,
			chat_id VARCHAR(32) NOT NULL,
			sender VARCHAR(16) NOT NULL,
			body TEXT NOT NULL,
			created VARCHAR(32) NOT NULL
		)');
	} catch (Throwable $e) {
		// TEMPORARY FB DEBUG: expose exceptions previously swallowed here.
		if (function_exists('store_fb_debug_exception')) {
			store_fb_debug_exception($e, 'DATABASE INITIALIZATION');
		}
		// Keep bookings/users online if chat tables cannot be created.
	}
}

function store_db_seed_from_json(PDO $pdo): void
{
	try {
		$n = (int) $pdo->query('SELECT COUNT(*) FROM ke_users')->fetchColumn();
	} catch (Throwable $e) {
		// TEMPORARY FB DEBUG: expose exceptions previously swallowed here.
		if (function_exists('store_fb_debug_exception')) {
			store_fb_debug_exception($e, 'DATABASE INITIALIZATION');
		}
		return;
	}
	if ($n > 0) {
		return;
	}
	$users = store_read_json('users');
	if (!$users) {
		return;
	}
	try {
		$pdo->beginTransaction();
		$userStmt = $pdo->prepare('INSERT IGNORE INTO ke_users (id, name, email, phone, password_hash, oauth_provider, oauth_id, created) VALUES (?,?,?,?,?,?,?,?)');
		foreach ($users as $user) {
			if (!is_array($user) || ($user['id'] ?? '') === '') {
				continue;
			}
			$userStmt->execute([
				(string) $user['id'],
				(string) ($user['name'] ?? ''),
				(string) ($user['email'] ?? ''),
				(string) ($user['phone'] ?? ''),
				(string) ($user['password_hash'] ?? ''),
				(string) ($user['oauth_provider'] ?? ''),
				(string) ($user['oauth_id'] ?? ''),
				(string) ($user['created'] ?? ''),
			]);
		}
		$bookStmt = $pdo->prepare('INSERT IGNORE INTO ke_bookings (id, user_id, status, pay_method, total, guest_name, email, phone, notes, items_json, created) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
		foreach (store_read_json('bookings') as $booking) {
			if (!is_array($booking) || ($booking['id'] ?? '') === '') {
				continue;
			}
			$items = $booking['items'] ?? [];
			$bookStmt->execute([
				(string) $booking['id'],
				(string) ($booking['user_id'] ?? ''),
				(string) ($booking['status'] ?? ''),
				(string) ($booking['pay_method'] ?? ''),
				(int) ($booking['total'] ?? 0),
				(string) ($booking['guest_name'] ?? ''),
				(string) ($booking['email'] ?? ''),
				(string) ($booking['phone'] ?? ''),
				(string) ($booking['notes'] ?? ''),
				json_encode(is_array($items) ? $items : [], JSON_UNESCAPED_UNICODE),
				(string) ($booking['created'] ?? ''),
			]);
		}
		$blockStmt = $pdo->prepare('INSERT IGNORE INTO ke_blocked (id, vehicle, travel_date, booking_id) VALUES (?,?,?,?)');
		foreach (store_read_json('blocked') as $row) {
			if (!is_array($row)) {
				continue;
			}
			$blockStmt->execute([
				(string) ($row['id'] ?? bin2hex(random_bytes(8))),
				(string) ($row['vehicle'] ?? ''),
				(string) ($row['travel_date'] ?? ''),
				(string) ($row['booking_id'] ?? ''),
			]);
		}
		$chatStmt = $pdo->prepare('INSERT IGNORE INTO ke_chats (id, token, user_id, guest_name, email, phone, last_message, last_at, unread_staff, created) VALUES (?,?,?,?,?,?,?,?,?,?)');
		$msgStmt = $pdo->prepare('INSERT IGNORE INTO ke_chat_messages (id, chat_id, sender, body, created) VALUES (?,?,?,?,?)');
		foreach (store_read_json('chats') as $chat) {
			if (!is_array($chat) || ($chat['id'] ?? '') === '') {
				continue;
			}
			$chatStmt->execute([
				(string) $chat['id'],
				(string) ($chat['token'] ?? ''),
				(string) ($chat['user_id'] ?? ''),
				(string) ($chat['guest_name'] ?? ''),
				(string) ($chat['email'] ?? ''),
				(string) ($chat['phone'] ?? ''),
				(string) ($chat['last_message'] ?? ''),
				(string) ($chat['last_at'] ?? ''),
				(int) ($chat['unread_staff'] ?? 0),
				(string) ($chat['created'] ?? ''),
			]);
			$messages = is_array($chat['messages'] ?? null) ? $chat['messages'] : [];
			foreach ($messages as $msg) {
				if (!is_array($msg) || ($msg['id'] ?? '') === '') {
					continue;
				}
				$msgStmt->execute([
					(string) $msg['id'],
					(string) ($msg['chat_id'] ?? $chat['id']),
					(string) ($msg['sender'] ?? 'guest'),
					(string) ($msg['body'] ?? $msg['text'] ?? ''),
					(string) ($msg['created'] ?? ''),
				]);
			}
		}
		$pdo->commit();
	} catch (Throwable $e) {
		// TEMPORARY FB DEBUG: expose exceptions previously swallowed here.
		if (function_exists('store_fb_debug_exception')) {
			store_fb_debug_exception($e, 'DATABASE INITIALIZATION');
		}
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
	}
}

function store_db_add_column(PDO $pdo, string $table, string $column, string $ddl): void
{
	$cols = $pdo->query('SHOW COLUMNS FROM ' . $table)->fetchAll(PDO::FETCH_COLUMN);
	if (in_array($column, $cols, true)) {
		return;
	}
	$pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $ddl);
}

function store_read_json(string $name, array $default = []): array
{
	$file = STORE_DATA . '/' . $name . '.json';
	if (!is_file($file)) {
		return $default;
	}
	$data = json_decode((string) file_get_contents($file), true);
	return is_array($data) ? $data : $default;
}

function store_write_json(string $name, array $data): bool
{
	if (!is_dir(STORE_DATA) && !mkdir(STORE_DATA, 0750, true) && !is_dir(STORE_DATA)) {
		return false;
	}
	$file = STORE_DATA . '/' . $name . '.json';
	$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	return (bool) file_put_contents($file, $json, LOCK_EX);
}

function store_profile_meta(string $userId): array
{
	$all = store_read_json('profile_meta');
	$row = $all[$userId] ?? [];
	return is_array($row) ? $row : [];
}

function store_save_profile_meta(string $userId, array $meta): bool
{
	$all = store_read_json('profile_meta');
	$all[$userId] = $meta;
	return store_write_json('profile_meta', $all);
}

function store_users(): array
{
	$db = store_db();
	if ($db) {
		return $db->query('SELECT * FROM ke_users ORDER BY created DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}
	return store_read_json('users');
}

function store_save_user(array $user): bool
{
	$db = store_db();
	if ($db) {
		$stmt = $db->prepare('INSERT INTO ke_users (id, name, email, phone, password_hash, oauth_provider, oauth_id, created) VALUES (?,?,?,?,?,?,?,?)');
		return $stmt->execute([
			$user['id'],
			$user['name'],
			$user['email'],
			$user['phone'] ?? '',
			$user['password_hash'] ?? '',
			$user['oauth_provider'] ?? '',
			$user['oauth_id'] ?? '',
			$user['created'],
		]);
	}
	$users = store_users();
	$users[] = $user;
	return store_write_json('users', $users);
}

function store_update_user(array $user): bool
{
	$db = store_db();
	if ($db) {
		$stmt = $db->prepare('UPDATE ke_users SET name=?, phone=?, oauth_provider=?, oauth_id=? WHERE id=?');
		return $stmt->execute([
			$user['name'],
			$user['phone'] ?? '',
			$user['oauth_provider'] ?? '',
			$user['oauth_id'] ?? '',
			$user['id'],
		]);
	}
	$users = store_users();
	foreach ($users as $i => $row) {
		if (($row['id'] ?? '') === $user['id']) {
			$users[$i] = $user;
			return store_write_json('users', $users);
		}
	}
	return false;
}

function store_set_user_password(string $userId, string $password): bool
{
	if ($userId === '' || strlen($password) < 10) {
		return false;
	}
	$hash = password_hash($password, PASSWORD_DEFAULT);
	$db = store_db();
	if ($db) {
		$stmt = $db->prepare('UPDATE ke_users SET password_hash=? WHERE id=?');
		return $stmt->execute([$hash, $userId]);
	}
	$users = store_users();
	foreach ($users as $i => $row) {
		if (($row['id'] ?? '') === $userId) {
			$users[$i]['password_hash'] = $hash;
			return store_write_json('users', $users);
		}
	}
	return false;
}

function store_saved_cart(string $userId): array
{
	$meta = store_profile_meta($userId);
	$cart = $meta['cart'] ?? [];
	return is_array($cart) ? $cart : [];
}

function store_persist_user_cart(): void
{
	if (!function_exists('store_user')) {
		return;
	}
	$user = store_user();
	if (!$user) {
		return;
	}
	$meta = store_profile_meta((string) $user['id']);
	$meta['cart'] = function_exists('store_cart') ? store_cart() : [];
	$meta['cart_updated'] = function_exists('store_now') ? store_now() : date('c');
	store_save_profile_meta((string) $user['id'], $meta);
}

function store_chat_token(bool $create = false): string
{
	$token = (string) ($_COOKIE['ke_chat'] ?? '');
	if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
		$token = '';
	}
	if ($token === '' && $create) {
		$token = bin2hex(random_bytes(16));
		$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
		setcookie('ke_chat', $token, [
			'expires' => time() + 86400 * 400,
			'path' => '/',
			'secure' => $secure,
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		$_COOKIE['ke_chat'] = $token;
	}
	return $token;
}

function store_find_chat_token(string $token): ?array
{
	if ($token === '') {
		return null;
	}
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('SELECT * FROM ke_chats WHERE token=?');
			$stmt->execute([$token]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row) {
				return $row;
			}
		} catch (Throwable $e) {
		}
	}
	foreach (store_read_json('chats') as $row) {
		if (($row['token'] ?? '') === $token) {
			return $row;
		}
	}
	return null;
}

function store_find_chat_user(string $userId): ?array
{
	if ($userId === '') {
		return null;
	}
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('SELECT * FROM ke_chats WHERE user_id=? ORDER BY created DESC');
			$stmt->execute([$userId]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row) {
				return $row;
			}
		} catch (Throwable $e) {
		}
	}
	foreach (store_read_json('chats') as $row) {
		if ((string) ($row['user_id'] ?? '') === $userId) {
			return $row;
		}
	}
	return null;
}

function store_find_chat_id(string $id): ?array
{
	if ($id === '') {
		return null;
	}
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('SELECT * FROM ke_chats WHERE id=?');
			$stmt->execute([$id]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row) {
				return $row;
			}
		} catch (Throwable $e) {
		}
	}
	foreach (store_read_json('chats') as $row) {
		if (($row['id'] ?? '') === $id) {
			return $row;
		}
	}
	return null;
}

function store_chats(): array
{
	$db = store_db();
	if ($db) {
		try {
			$rows = $db->query('SELECT * FROM ke_chats ORDER BY last_at DESC, created DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
			if ($rows) {
				return $rows;
			}
		} catch (Throwable $e) {
		}
	}
	$rows = store_read_json('chats');
	usort($rows, static function ($a, $b) {
		return strcmp((string) ($b['last_at'] ?? ''), (string) ($a['last_at'] ?? ''));
	});
	return array_values($rows);
}

function store_chat_unread_count(): int
{
	$n = 0;
	foreach (store_chats() as $chat) {
		if ((int) ($chat['unread_staff'] ?? 0) > 0) {
			$n++;
		}
	}
	return $n;
}

function store_chat_messages(string $chatId): array
{
	if ($chatId === '') {
		return [];
	}
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('SELECT * FROM ke_chat_messages WHERE chat_id=? ORDER BY created ASC, id ASC');
			$stmt->execute([$chatId]);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
			if ($rows) {
				return $rows;
			}
		} catch (Throwable $e) {
		}
	}
	$chat = null;
	foreach (store_read_json('chats') as $row) {
		if (($row['id'] ?? '') === $chatId) {
			$chat = $row;
			break;
		}
	}
	$msgs = is_array($chat['messages'] ?? null) ? $chat['messages'] : [];
	return array_values($msgs);
}

function store_chat_public_messages(array $rows): array
{
	$out = [];
	foreach ($rows as $row) {
		$sender = strtolower(trim((string) ($row['sender'] ?? $row['from'] ?? '')));
		$out[] = [
			'id' => (string) ($row['id'] ?? ''),
			'from' => ($sender === 'desk' || $sender === 'staff' || $sender === 'admin') ? 'desk' : 'guest',
			'text' => (string) ($row['body'] ?? $row['text'] ?? ''),
			'at' => (string) ($row['created'] ?? ''),
		];
	}
	return $out;
}

function store_chat_write_json_row(array $chat): bool
{
	$all = store_read_json('chats');
	$found = false;
	foreach ($all as $i => $row) {
		if (($row['id'] ?? '') === ($chat['id'] ?? '')) {
			$all[$i] = $chat;
			$found = true;
			break;
		}
	}
	if (!$found) {
		$all[] = $chat;
	}
	return store_write_json('chats', array_values($all));
}

function store_chat_update(array $chat): bool
{
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('UPDATE ke_chats SET user_id=?, guest_name=?, email=?, phone=?, last_message=?, last_at=?, unread_staff=? WHERE id=?');
			$stmt->execute([
				$chat['user_id'] ?? '',
				$chat['guest_name'] ?? '',
				$chat['email'] ?? '',
				$chat['phone'] ?? '',
				$chat['last_message'] ?? '',
				$chat['last_at'] ?? store_now(),
				(int) ($chat['unread_staff'] ?? 0),
				$chat['id'],
			]);
		} catch (Throwable $e) {
		}
	}
	return store_chat_write_json_row($chat);
}

function store_chat_open(string $token, array $guest): array
{
	$user = function_exists('store_user') ? store_user() : null;
	$chat = $user ? store_find_chat_user((string) $user['id']) : null;
	if (!$chat) {
		$chat = store_find_chat_token($token);
	}
	$name = trim((string) ($guest['name'] ?? ''));
	$email = strtolower(trim((string) ($guest['email'] ?? '')));
	$phone = trim((string) ($guest['phone'] ?? ''));
	if ($chat) {
		if ($name !== '') {
			$chat['guest_name'] = $name;
		}
		if ($email !== '') {
			$chat['email'] = $email;
		}
		if ($phone !== '') {
			$chat['phone'] = $phone;
		}
		if ($user) {
			$chat['user_id'] = (string) $user['id'];
			if ($name === '') {
				$chat['guest_name'] = (string) ($user['name'] ?? $chat['guest_name']);
			}
			if ($email === '') {
				$chat['email'] = (string) ($user['email'] ?? $chat['email']);
			}
			if ($phone === '') {
				$chat['phone'] = (string) ($user['phone'] ?? $chat['phone']);
			}
		}
		store_chat_update($chat);
		return $chat;
	}
	$chat = [
		'id' => store_id(),
		'token' => $token,
		'user_id' => $user ? (string) $user['id'] : '',
		'guest_name' => $name !== '' ? $name : (string) ($user['name'] ?? ''),
		'email' => $email !== '' ? $email : (string) ($user['email'] ?? ''),
		'phone' => $phone !== '' ? $phone : (string) ($user['phone'] ?? ''),
		'last_message' => '',
		'last_at' => store_now(),
		'unread_staff' => 0,
		'created' => store_now(),
		'messages' => [],
	];
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('INSERT INTO ke_chats (id, token, user_id, guest_name, email, phone, last_message, last_at, unread_staff, created) VALUES (?,?,?,?,?,?,?,?,?,?)');
			$stmt->execute([
				$chat['id'], $chat['token'], $chat['user_id'], $chat['guest_name'], $chat['email'], $chat['phone'],
				$chat['last_message'], $chat['last_at'], 0, $chat['created'],
			]);
		} catch (Throwable $e) {
		}
	}
	store_chat_write_json_row($chat);
	return $chat;
}

function store_chat_add_message(array &$chat, string $sender, string $body): ?array
{
	$body = trim($body);
	if ($body === '' || ($chat['id'] ?? '') === '') {
		return null;
	}
	$msg = [
		'id' => store_id(),
		'chat_id' => (string) $chat['id'],
		'sender' => $sender === 'desk' ? 'desk' : 'guest',
		'body' => $body,
		'created' => store_now(),
	];
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('INSERT INTO ke_chat_messages (id, chat_id, sender, body, created) VALUES (?,?,?,?,?)');
			$stmt->execute([$msg['id'], $msg['chat_id'], $msg['sender'], $msg['body'], $msg['created']]);
		} catch (Throwable $e) {
		}
	}
	$latest = store_find_chat_id((string) $chat['id']);
	if (is_array($latest)) {
		$chat = $latest;
	}
	$chat['messages'] = is_array($chat['messages'] ?? null) ? $chat['messages'] : store_chat_messages((string) $chat['id']);
	$chat['messages'][] = $msg;
	$chat['last_message'] = $body;
	$chat['last_at'] = $msg['created'];
	if ($msg['sender'] === 'guest') {
		$chat['unread_staff'] = (int) ($chat['unread_staff'] ?? 0) + 1;
	} else {
		$chat['unread_staff'] = 0;
	}
	store_chat_update($chat);
	store_chat_write_json_row($chat);
	return $msg;
}

function store_chat_pretty_phone(string $phone): string
{
	$raw = trim($phone);
	$digits = preg_replace('/\D+/', '', $raw) ?? '';
	if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
		$digits = '63' . substr($digits, 1);
	}
	if (strlen($digits) === 12 && str_starts_with($digits, '63')) {
		return '+63 ' . substr($digits, 2, 3) . ' ' . substr($digits, 5, 3) . ' ' . substr($digits, 8);
	}
	return $raw;
}

function store_chat_channel_digits(string $phone): string
{
	$digits = preg_replace('/\D+/', '', $phone) ?? '';
	if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
		return '63' . substr($digits, 1);
	}
	return $digits;
}

function store_chat_channels(): array
{
	$defaults = [
		'whatsapp' => [
			'phone' => '+63 920 985 1802',
			'handle' => '',
			'qr' => '',
			'show' => true,
		],
		'wechat' => [
			'phone' => '',
			'handle' => '',
			'qr' => '',
			'show' => true,
		],
		'viber' => [
			'phone' => '',
			'handle' => '',
			'qr' => '',
			'show' => true,
		],
	];
	$saved = store_read_json('chat_channels');
	foreach ($defaults as $key => $row) {
		$in = is_array($saved[$key] ?? null) ? $saved[$key] : [];
		$defaults[$key]['phone'] = trim((string) ($in['phone'] ?? $row['phone']));
		$defaults[$key]['handle'] = trim((string) ($in['handle'] ?? $row['handle']));
		$defaults[$key]['qr'] = trim((string) ($in['qr'] ?? $row['qr']));
		$defaults[$key]['show'] = array_key_exists('show', $in) ? !empty($in['show']) : $row['show'];
	}
	return $defaults;
}

function store_chat_channels_public(): array
{
	$out = [];
	foreach (store_chat_channels() as $key => $row) {
		$phone = store_chat_pretty_phone((string) $row['phone']);
		$digits = store_chat_channel_digits((string) $row['phone']);
		$handle = trim((string) $row['handle']);
		$qr = trim((string) $row['qr']);
		$href = '';
		if ($key === 'whatsapp' && $digits !== '') {
			$href = 'https://wa.me/' . $digits;
		} elseif ($key === 'viber' && $digits !== '') {
			$href = 'viber://chat?number=%2B' . $digits;
		}
		$out[$key] = [
			'phone' => $phone,
			'handle' => $handle,
			'qr' => $qr,
			'href' => $href,
			'show' => !empty($row['show']),
			'ready' => $phone !== '' || $handle !== '' || $qr !== '',
		];
	}
	return $out;
}

function store_chat_channels_save(array $channels): bool
{
	$clean = [];
	foreach (store_chat_channels() as $key => $row) {
		$in = is_array($channels[$key] ?? null) ? $channels[$key] : [];
		$phone = trim(strip_tags((string) ($in['phone'] ?? $row['phone'])));
		if (function_exists('mb_substr')) {
			$phone = mb_substr($phone, 0, 40);
			$handle = mb_substr(trim(strip_tags((string) ($in['handle'] ?? $row['handle']))), 0, 80);
		} else {
			$phone = substr($phone, 0, 40);
			$handle = substr(trim(strip_tags((string) ($in['handle'] ?? $row['handle']))), 0, 80);
		}
		$qr = trim((string) ($in['qr'] ?? $row['qr']));
		if ($qr !== '' && !preg_match('#^/assets/uploads/[A-Za-z0-9._/\-]+\.(jpe?g|png|webp|gif)$#i', $qr)) {
			$qr = $row['qr'];
		}
		$clean[$key] = [
			'phone' => $phone,
			'handle' => $handle,
			'qr' => $qr,
			'show' => !empty($in['show']),
		];
	}
	return store_write_json('chat_channels', $clean);
}

function store_find_user_email(string $email): ?array
{
	$email = strtolower(trim($email));
	if ($email === '') {
		return null;
	}
	$db = store_db();
	if ($db) {
		try {
			$st = $db->prepare('SELECT * FROM ke_users WHERE email = ? LIMIT 1');
			$st->execute([$email]);
			$row = $st->fetch(PDO::FETCH_ASSOC);
			return $row ?: null;
		} catch (Throwable $e) {
			return null;
		}
	}
	foreach (store_read_json('users') as $user) {
		if (is_array($user) && strtolower((string) ($user['email'] ?? '')) === $email) {
			return $user;
		}
	}
	return null;
}

function store_find_user_oauth(string $provider, string $oauthId): ?array
{
	$provider = trim($provider);
	$oauthId = trim($oauthId);
	if ($provider === '' || $oauthId === '') {
		return null;
	}
	$db = store_db();
	if ($db) {
		try {
			$st = $db->prepare('SELECT * FROM ke_users WHERE oauth_provider = ? AND oauth_id = ? LIMIT 1');
			$st->execute([$provider, $oauthId]);
			$row = $st->fetch(PDO::FETCH_ASSOC);
			return $row ?: null;
		} catch (Throwable $e) {
			return null;
		}
	}
	foreach (store_read_json('users') as $user) {
		if (is_array($user) && ($user['oauth_provider'] ?? '') === $provider && (string) ($user['oauth_id'] ?? '') === $oauthId) {
			return $user;
		}
	}
	return null;
}

function store_find_user_id(string $id): ?array
{
	$id = trim($id);
	if ($id === '') {
		return null;
	}
	$db = store_db();
	if ($db) {
		try {
			$st = $db->prepare('SELECT * FROM ke_users WHERE id = ? LIMIT 1');
			$st->execute([$id]);
			$row = $st->fetch(PDO::FETCH_ASSOC);
			return $row ?: null;
		} catch (Throwable $e) {
			return null;
		}
	}
	foreach (store_read_json('users') as $user) {
		if (is_array($user) && ($user['id'] ?? '') === $id) {
			return $user;
		}
	}
	return null;
}

function store_decode_booking_row(array $row): array
{
	if (!isset($row['items']) || !is_array($row['items'])) {
		$row['items'] = json_decode((string) ($row['items_json'] ?? '[]'), true) ?: [];
	}
	return $row;
}

function store_bookings(): array
{
	$db = store_db();
	if ($db) {
		$rows = $db->query('SELECT * FROM ke_bookings ORDER BY created DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
		return array_map('store_decode_booking_row', $rows);
	}
	return store_read_json('bookings');
}

function store_save_booking(array $booking): bool
{
	$db = store_db();
	if ($db) {
		$stmt = $db->prepare('INSERT INTO ke_bookings (id, user_id, status, pay_method, total, guest_name, email, phone, notes, items_json, created) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
		return $stmt->execute([
			$booking['id'],
			$booking['user_id'],
			$booking['status'],
			$booking['pay_method'] ?? '',
			(int) ($booking['total'] ?? 0),
			$booking['guest_name'],
			$booking['email'],
			$booking['phone'] ?? '',
			$booking['notes'] ?? '',
			json_encode($booking['items'] ?? [], JSON_UNESCAPED_UNICODE),
			$booking['created'],
		]);
	}
	$rows = store_bookings();
	array_unshift($rows, $booking);
	return store_write_json('bookings', $rows);
}

function store_update_booking(array $booking): bool
{
	$db = store_db();
	if ($db) {
		$stmt = $db->prepare('UPDATE ke_bookings SET status=?, pay_method=?, notes=? WHERE id=?');
		return $stmt->execute([
			$booking['status'],
			$booking['pay_method'] ?? '',
			$booking['notes'] ?? '',
			$booking['id'],
		]);
	}
	$rows = store_bookings();
	foreach ($rows as $i => $row) {
		if (($row['id'] ?? '') === $booking['id']) {
			$rows[$i] = $booking;
			return store_write_json('bookings', $rows);
		}
	}
	return false;
}

function store_find_booking(string $id): ?array
{
	foreach (store_bookings() as $row) {
		if (($row['id'] ?? '') === $id) {
			return store_decode_booking_row($row);
		}
	}
	return null;
}

function store_blocked(): array
{
	$db = store_db();
	if ($db) {
		return $db->query('SELECT * FROM ke_blocked')->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}
	return store_read_json('blocked');
}

function store_block_vehicle(string $vehicle, string $date, string $bookingId): bool
{
	if ($vehicle === '' || $date === '') {
		return true;
	}
	$db = store_db();
	if ($db) {
		$stmt = $db->prepare('INSERT INTO ke_blocked (id, vehicle, travel_date, booking_id) VALUES (?,?,?,?)');
		return $stmt->execute([store_id(), $vehicle, $date, $bookingId]);
	}
	$rows = store_blocked();
	$rows[] = ['id' => store_id(), 'vehicle' => $vehicle, 'travel_date' => $date, 'booking_id' => $bookingId];
	return store_write_json('blocked', $rows);
}

function store_unblock_booking(string $bookingId): void
{
	$db = store_db();
	if ($db) {
		$stmt = $db->prepare('DELETE FROM ke_blocked WHERE booking_id=?');
		$stmt->execute([$bookingId]);
		return;
	}
	$rows = array_values(array_filter(store_blocked(), static function ($row) use ($bookingId) {
		return ($row['booking_id'] ?? '') !== $bookingId;
	}));
	store_write_json('blocked', $rows);
}

function store_is_blocked(string $vehicle, string $date): bool
{
	if ($vehicle === '' || $date === '') {
		return false;
	}
	foreach (store_blocked() as $row) {
		if (($row['vehicle'] ?? '') === $vehicle && ($row['travel_date'] ?? '') === $date) {
			return true;
		}
	}
	return false;
}
