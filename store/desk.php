<?php
declare(strict_types=1);

if (function_exists('store_is_staff')) {
	return;
}

function store_desk_emails(): array
{
	$emails = ['support@kuyaelytours.com', 'info@kuyaelytours.com'];
	$file = STORE_ROOT . '/desk.local.php';
	if (is_readable($file)) {
		$cfg = include $file;
		if (is_array($cfg)) {
			$list = $cfg['emails'] ?? $cfg;
			if (is_array($list)) {
				foreach ($list as $email) {
					$emails[] = (string) $email;
				}
			}
		}
	}
	$out = [];
	foreach ($emails as $email) {
		$email = strtolower(trim($email));
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$out[$email] = $email;
		}
	}
	return array_values($out);
}

function store_is_staff(?array $user): bool
{
	if (!$user) {
		return false;
	}
	$email = strtolower(trim((string) ($user['email'] ?? '')));
	return $email !== '' && in_array($email, store_desk_emails(), true);
}

function store_require_staff(): array
{
	if (!store_user()) {
		$next = (string) ($_SERVER['REQUEST_URI'] ?? '/desk/');
		store_redirect('/account/login.php?next=' . rawurlencode(store_safe_next($next, '/desk/')));
	}
	if (store_password_change_required()) {
		store_redirect('/account/password.php');
	}
	$user = store_user();
	if (!$user || !store_is_staff($user)) {
		http_response_code(403);
		$html = '<div class="ke-desk-wrap"><h1>Staff desk</h1>'
			. '<p>This page is for Kuya Ely staff. Sign in with the office email, support@kuyaelytours.com.</p>'
			. '<p>To add another staff login, copy store/desk.local.example.php to store/desk.local.php and list that email address.</p>'
			. '<p><a class="ke-desk-btn" href="/account/logout.php">Log out</a></p></div>';
		store_page('Staff desk', $html, '', true, 'ke-desk');
		exit;
	}
	return $user;
}

function store_desk_page(string $title, string $body): void
{
	$staff = store_require_staff();
	$html = '<div class="ke-desk-wrap"><header class="ke-desk-bar"><div><p class="ke-desk-kicker">Staff desk</p><h1>'
		. store_h($title) . '</h1></div><nav>'
		. '<a href="/desk/">Trips</a>'
		. '<a href="/desk/add.php">Add client</a>'
		. '<a href="/account/bookings.php">My account</a>'
		. '<a href="/account/logout.php">Log out</a>'
		. '</nav></header><p class="ke-desk-signed">Signed in as ' . store_h((string) $staff['email']) . '</p>'
		. store_desk_flash_html()
		. $body
		. '</div>';
	store_page($title, $html, '', true, 'ke-desk');
}

function store_desk_flash(string $message, bool $ok = true): void
{
	$_SESSION['desk_flash'] = ['ok' => $ok, 'message' => $message];
}

function store_desk_flash_html(): string
{
	$flash = $_SESSION['desk_flash'] ?? null;
	unset($_SESSION['desk_flash']);
	if (!is_array($flash)) {
		return '';
	}
	$message = trim((string) ($flash['message'] ?? ''));
	if ($message === '') {
		return '';
	}
	$class = !empty($flash['ok']) ? 'ke-desk-ok' : 'ke-desk-err';
	return '<p class="' . $class . '">' . store_h($message) . '</p>';
}

function store_desk_temp_password(): string
{
	$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
	$max = strlen($alphabet) - 1;
	$out = 'Kuya-';
	for ($i = 0; $i < 8; $i++) {
		$out .= $alphabet[random_int(0, $max)];
	}
	return $out;
}

function store_desk_set_temp_password(string $userId): string
{
	$password = store_desk_temp_password();
	if (!store_set_user_password($userId, $password)) {
		return '';
	}
	store_user_must_change($userId, true);
	return $password;
}

function store_desk_remember_password(string $bookingId, string $password): void
{
	$_SESSION['desk_pass'] = [
		'booking_id' => $bookingId,
		'password' => $password,
	];
}

function store_desk_saved_password(string $bookingId): string
{
	$row = $_SESSION['desk_pass'] ?? null;
	if (!is_array($row) || (string) ($row['booking_id'] ?? '') !== $bookingId) {
		return '';
	}
	return (string) ($row['password'] ?? '');
}

function store_desk_login_identity(?array $user, string $fallbackEmail = ''): array
{
	$email = strtolower(trim((string) ($user['email'] ?? $fallbackEmail)));
	$username = store_clean_username((string) ($user['username'] ?? ''));
	if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return ['Email', $email];
	}
	if ($username !== '') {
		return ['Username', $username];
	}
	return ['Email', $email];
}

function store_desk_login_message(string $login, string $password, string $label = 'Email'): string
{
	$lines = ['Sir, kindly sign in at https://kuyaelytours.com/account/login.php'];
	$lines[] = $label . ': ' . $login;
	if ($password !== '') {
		$lines[] = 'Temporary password: ' . $password;
		$lines[] = 'Then open My Bookings. Your quotation, travel confirmation, receipt, and driver are on that trip. Please change the password after you sign in.';
	} else {
		$lines[] = 'Use the password you already have for this ' . strtolower($label) . '.';
		$lines[] = 'Then open My Bookings. Your quotation, travel confirmation, receipt, and driver are on that trip.';
	}
	return implode("\n", $lines);
}

function store_desk_open_client(string $name, string $email, string $phone, string $username = ''): array
{
	$name = trim($name);
	$email = strtolower(trim($email));
	$phone = trim($phone);
	$username = store_clean_username($username);
	$fail = ['error' => 'Enter a name, and either an email or a username.', 'user' => null, 'password' => '', 'created' => false];
	if ($name === '' || strlen($name) > 140) {
		return $fail;
	}
	if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) {
		$fail['error'] = 'That email is not valid. Leave it blank and enter a username instead.';
		return $fail;
	}
	if ($email === '' && !store_valid_username($username)) {
		$fail['error'] = 'No email on file. Enter a username they can sign in with. Use letters and numbers, at least 3 characters.';
		return $fail;
	}
	if ($username !== '' && !store_valid_username($username)) {
		$fail['error'] = 'Usernames start with a letter and use letters, numbers, dots, or dashes.';
		return $fail;
	}
	if (strlen($phone) > 40) {
		$fail['error'] = 'That phone number is too long.';
		return $fail;
	}
	if ($email !== '') {
		$existing = store_find_user_email($email);
	} else {
		$existing = store_find_user_username($username);
	}
	if ($username !== '') {
		$taken = store_find_user_username($username);
		if ($taken && (!$existing || (string) ($taken['id'] ?? '') !== (string) ($existing['id'] ?? ''))) {
			$fail['error'] = 'That username is already used. Pick another one.';
			return $fail;
		}
	}
	if ($existing) {
		if ($phone !== '' && trim((string) ($existing['phone'] ?? '')) === '') {
			$existing['phone'] = $phone;
			store_update_user($existing);
		}
		return ['error' => '', 'user' => $existing, 'password' => '', 'created' => false];
	}
	$password = store_desk_temp_password();
	$user = [
		'id' => store_id(),
		'name' => $name,
		'email' => $email,
		'username' => $username,
		'phone' => $phone,
		'password_hash' => password_hash($password, PASSWORD_DEFAULT),
		'oauth_provider' => '',
		'oauth_id' => '',
		'created' => store_now(),
		'must_change_password' => 1,
	];
	if (!store_save_user($user)) {
		$fail['error'] = 'Could not save that account. Please try again.';
		return $fail;
	}
	store_user_must_change((string) $user['id'], true);
	return ['error' => '', 'user' => $user, 'password' => $password, 'created' => true];
}

function store_desk_cover(string $label): string
{
	$label = strtolower($label);
	$map = [
		'bohol' => '/assets/downloaded/dest-bohol.jpg',
		'siquijor' => '/assets/downloaded/dest-siquijor.jpg',
		'dumaguete' => '/assets/downloaded/dest-dumaguete.jpg',
		'cebu' => '/assets/downloaded/dest-cebu.jpg',
		'mactan' => '/assets/downloaded/dest-cebu.jpg',
	];
	foreach ($map as $word => $image) {
		if (str_contains($label, $word)) {
			return $image;
		}
	}
	return '/assets/downloaded/dest-cebu.jpg';
}

function store_desk_pesos(string $raw): int
{
	$raw = trim(str_replace(['₱', ',', ' '], '', $raw));
	if ($raw === '') {
		return 0;
	}
	if (!preg_match('/^\d+(\.\d{1,2})?$/', $raw)) {
		return -1;
	}
	return max(0, (int) round((float) $raw));
}

function store_desk_create_trip(array $user, string $tour, string $date, int $guests, int $total, string $status, string $note = ''): ?array
{
	$tour = trim($tour);
	if ($tour === '' || strlen($tour) > 140) {
		return null;
	}
	if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return null;
	}
	if (!in_array($status, ['confirmed', 'awaiting_payment', 'paid'], true)) {
		$status = 'confirmed';
	}
	$guests = max(1, min(99, $guests));
	$total = max(0, $total);
	$note = trim($note);
	if (strlen($note) > 2000) {
		$note = substr($note, 0, 2000);
	}
	$notes = 'Added from the staff desk';
	if ($note !== '') {
		$notes .= "\n" . $note;
	}
	$booking = [
		'id' => store_id(),
		'user_id' => (string) $user['id'],
		'status' => $status,
		'pay_method' => $status === 'awaiting_payment' ? '' : 'desk',
		'total' => $total,
		'guest_name' => trim((string) ($user['name'] ?? '')),
		'email' => (string) ($user['email'] ?? ''),
		'phone' => (string) ($user['phone'] ?? ''),
		'notes' => $notes,
		'items' => [[
			'product_id' => '',
			'label' => $tour,
			'date' => $date,
			'guests' => $guests,
			'line_total' => $total,
			'cover' => store_desk_cover($tour),
		]],
		'driver_name' => '',
		'driver_phone' => '',
		'driver_vehicle' => '',
		'pickup_time' => '',
		'created' => store_now(),
	];
	if ($booking['guest_name'] === '') {
		return null;
	}
	return store_save_booking($booking) ? $booking : null;
}

function store_desk_first_item(array $booking): array
{
	$items = $booking['items'] ?? [];
	if (!is_array($items) || !isset($items[0]) || !is_array($items[0])) {
		return [];
	}
	return $items[0];
}

function store_desk_trip_label(array $booking): string
{
	$first = store_desk_first_item($booking);
	$label = trim((string) ($first['label'] ?? ''));
	if ($label !== '') {
		return $label;
	}
	$product = store_product((string) ($first['product_id'] ?? ''));
	return (string) ($product['name'] ?? 'Private trip');
}

function store_client_files_dir(): string
{
	$dir = STORE_DATA . '/client-files';
	if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
		return $dir;
	}
	$ht = $dir . '/.htaccess';
	if (!is_file($ht)) {
		file_put_contents($ht, "Require all denied\n");
	}
	return $dir;
}

function store_desk_safe_filename(string $name): string
{
	$name = basename(str_replace('\\', '/', $name));
	$name = preg_replace('/[^A-Za-z0-9._ -]+/', '', $name) ?? '';
	$name = trim((string) $name, '. ');
	if ($name === '') {
		$name = 'document';
	}
	return substr($name, 0, 120);
}

function store_booking_files(string $bookingId): array
{
	$bookingId = trim($bookingId);
	if ($bookingId === '') {
		return [];
	}
	$db = store_db();
	if ($db) {
		try {
			$st = $db->prepare('SELECT * FROM ke_booking_files WHERE booking_id = ? ORDER BY created ASC');
			$st->execute([$bookingId]);
			return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
		} catch (Throwable $e) {
			return [];
		}
	}
	$rows = [];
	foreach (store_read_json('booking_files') as $row) {
		if (is_array($row) && (string) ($row['booking_id'] ?? '') === $bookingId) {
			$rows[] = $row;
		}
	}
	return $rows;
}

function store_find_booking_file(string $id): ?array
{
	$id = trim($id);
	if (!preg_match('/^[a-f0-9]{8,32}$/', $id)) {
		return null;
	}
	$db = store_db();
	if ($db) {
		try {
			$st = $db->prepare('SELECT * FROM ke_booking_files WHERE id = ? LIMIT 1');
			$st->execute([$id]);
			$row = $st->fetch(PDO::FETCH_ASSOC);
			return $row ?: null;
		} catch (Throwable $e) {
			return null;
		}
	}
	foreach (store_read_json('booking_files') as $row) {
		if (is_array($row) && (string) ($row['id'] ?? '') === $id) {
			return $row;
		}
	}
	return null;
}

function store_save_booking_file(array $row): bool
{
	$db = store_db();
	if ($db) {
		try {
			$st = $db->prepare('INSERT INTO ke_booking_files (id, booking_id, user_id, kind, original_name, stored_name, mime, uploaded_by, created) VALUES (?,?,?,?,?,?,?,?,?)');
			return $st->execute([
				$row['id'],
				$row['booking_id'],
				$row['user_id'],
				$row['kind'],
				$row['original_name'],
				$row['stored_name'],
				$row['mime'],
				$row['uploaded_by'] ?? '',
				$row['created'],
			]);
		} catch (Throwable $e) {
			return false;
		}
	}
	$rows = store_read_json('booking_files');
	$rows[] = $row;
	return store_write_json('booking_files', $rows);
}

function store_booking_file_path(array $row): string
{
	$name = (string) ($row['stored_name'] ?? '');
	if (!preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png|webp)$/', $name)) {
		return '';
	}
	$path = store_client_files_dir() . '/' . $name;
	return is_file($path) ? $path : '';
}

function store_delete_booking_file(array $row): bool
{
	$path = store_booking_file_path($row);
	if ($path !== '') {
		unlink($path);
	}
	$id = (string) ($row['id'] ?? '');
	if ($id === '') {
		return false;
	}
	$db = store_db();
	if ($db) {
		try {
			$st = $db->prepare('DELETE FROM ke_booking_files WHERE id = ?');
			return $st->execute([$id]);
		} catch (Throwable $e) {
			return false;
		}
	}
	$kept = [];
	foreach (store_read_json('booking_files') as $item) {
		if (is_array($item) && (string) ($item['id'] ?? '') !== $id) {
			$kept[] = $item;
		}
	}
	return store_write_json('booking_files', $kept);
}

function store_booking_file_save(array $booking, string $kind, array $file, string $staffId): string
{
	if (!in_array($kind, ['quotation', 'confirmation', 'receipt'], true)) {
		return 'That file slot is not valid.';
	}
	$code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
	if ($code === UPLOAD_ERR_NO_FILE) {
		return '';
	}
	if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
		return 'That file is too large. Use a PDF or photo under 8 MB.';
	}
	if ($code !== UPLOAD_ERR_OK) {
		return 'Could not upload that file.';
	}
	$size = (int) ($file['size'] ?? 0);
	if ($size < 1 || $size > 8 * 1024 * 1024) {
		return 'Each file must be 8 MB or smaller.';
	}
	$ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
	$allowed = [
		'pdf' => ['application/pdf', 'application/octet-stream'],
		'jpg' => ['image/jpeg'],
		'jpeg' => ['image/jpeg'],
		'png' => ['image/png'],
		'webp' => ['image/webp'],
	];
	if (!isset($allowed[$ext])) {
		return 'Use a PDF or a photo (JPG, PNG, or WEBP).';
	}
	$tmp = (string) ($file['tmp_name'] ?? '');
	if ($tmp === '' || !is_uploaded_file($tmp)) {
		return 'Could not upload that file.';
	}
	if (class_exists('finfo')) {
		$mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
		if (!in_array($mime, $allowed[$ext], true)) {
			return 'That file type is not allowed.';
		}
	}
	$storedExt = $ext === 'jpeg' ? 'jpg' : $ext;
	$stored = bin2hex(random_bytes(16)) . '.' . $storedExt;
	$dest = store_client_files_dir() . '/' . $stored;
	if (!move_uploaded_file($tmp, $dest)) {
		return 'Could not save that file.';
	}
	$mimeOut = $storedExt === 'pdf' ? 'application/pdf' : ($storedExt === 'png' ? 'image/png' : ($storedExt === 'webp' ? 'image/webp' : 'image/jpeg'));
	$row = [
		'id' => store_id(),
		'booking_id' => (string) $booking['id'],
		'user_id' => (string) $booking['user_id'],
		'kind' => $kind,
		'original_name' => store_desk_safe_filename((string) ($file['name'] ?? 'document')),
		'stored_name' => $stored,
		'mime' => $mimeOut,
		'uploaded_by' => $staffId,
		'created' => store_now(),
	];
	if (!store_save_booking_file($row)) {
		unlink($dest);
		return 'Could not save that file.';
	}
	foreach (store_booking_files((string) $booking['id']) as $old) {
		if ((string) ($old['kind'] ?? '') === $kind && (string) ($old['id'] ?? '') !== $row['id']) {
			store_delete_booking_file($old);
		}
	}
	return '';
}

function store_file_kind_label(string $kind): string
{
	$map = [
		'quotation' => 'Quotation',
		'confirmation' => 'Travel confirmation',
		'receipt' => 'Receipt',
	];
	return $map[$kind] ?? 'File';
}

function store_booking_client_extras(array $booking): string
{
	$name = trim((string) ($booking['driver_name'] ?? ''));
	$phone = trim((string) ($booking['driver_phone'] ?? ''));
	$vehicle = trim((string) ($booking['driver_vehicle'] ?? ''));
	$pickup = trim((string) ($booking['pickup_time'] ?? ''));
	$bits = [];
	if ($name !== '') {
		$bits[] = store_h($name);
	}
	if ($phone !== '') {
		$tel = preg_replace('/[^0-9+]/', '', $phone) ?? '';
		$bits[] = '<a href="tel:' . store_h($tel) . '">' . store_h($phone) . '</a>';
	}
	if ($vehicle !== '') {
		$bits[] = store_h($vehicle);
	}
	if ($pickup !== '') {
		$bits[] = 'Pickup ' . store_h($pickup);
	}
	$files = [];
	foreach (store_booking_files((string) ($booking['id'] ?? '')) as $file) {
		$files[(string) ($file['kind'] ?? '')] = $file;
	}
	if (!$bits && !$files) {
		return '';
	}
	$html = '<div class="ke-bk-docs">';
	if ($bits) {
		$html .= '<p class="ke-bk-driver"><strong>Your driver</strong> ' . implode(' · ', $bits) . '</p>';
	}
	if ($files) {
		$html .= '<p class="ke-bk-files">';
		foreach (['quotation', 'confirmation', 'receipt'] as $kind) {
			if (empty($files[$kind]['id'])) {
				continue;
			}
			$html .= '<a href="/account/file.php?id=' . store_h((string) $files[$kind]['id']) . '">' . store_h(store_file_kind_label($kind)) . '</a>';
		}
		$html .= '</p>';
	}
	$html .= '</div>';
	return $html;
}
