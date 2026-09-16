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
		$pdo = new PDO(
			(string) $cfg['dsn'],
			(string) ($cfg['user'] ?? ''),
			(string) ($cfg['pass'] ?? ''),
			[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
		);
		store_db_migrate($pdo);
		return $pdo;
	} catch (Throwable $e) {
		$pdo = null;
		return null;
	}
}

function store_db_migrate(PDO $pdo): void
{
	$pdo->exec('CREATE TABLE IF NOT EXISTS ke_users (
		id VARCHAR(32) PRIMARY KEY,
		name VARCHAR(140) NOT NULL,
		email VARCHAR(190) NOT NULL UNIQUE,
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

function store_find_user_email(string $email): ?array
{
	$email = strtolower(trim($email));
	foreach (store_users() as $user) {
		if (strtolower((string) ($user['email'] ?? '')) === $email) {
			return $user;
		}
	}
	return null;
}

function store_find_user_oauth(string $provider, string $oauthId): ?array
{
	foreach (store_users() as $user) {
		if (($user['oauth_provider'] ?? '') === $provider && (string) ($user['oauth_id'] ?? '') === $oauthId) {
			return $user;
		}
	}
	return null;
}

function store_find_user_id(string $id): ?array
{
	foreach (store_users() as $user) {
		if (($user['id'] ?? '') === $id) {
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
