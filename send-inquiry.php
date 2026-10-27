<?php
declare(strict_types=1);

require __DIR__ . '/ke-mail.php';

function ke_redirect(string $url): void
{
	header('Location: ' . $url, true, 303);
	exit;
}

function ke_back(string $status): void
{
	$ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
	$path = 'contact.html';
	$fragment = '#booking';

	if ($ref !== '') {
		$parts = parse_url($ref);
		$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
		$refHost = strtolower((string) ($parts['host'] ?? ''));
		if ($refHost === '' || $refHost === $host) {
			$path = ltrim((string) ($parts['path'] ?? '/'), '/');
			if ($path === '' || $path === '/') {
				$path = 'index.html';
			}
			if (!empty($parts['fragment'])) {
				$fragment = '#' . $parts['fragment'];
			} elseif (str_contains($path, 'contact')) {
				$fragment = '#booking';
			} else {
				$fragment = '';
			}
		}
	}

	$join = str_contains($path, '?') ? '&' : '?';
	ke_redirect($path . $join . 'sent=' . rawurlencode($status) . $fragment);
}

function ke_post(string $key): string
{
	$value = $_POST[$key] ?? '';
	if (is_array($value)) {
		$value = reset($value);
	}
	$value = trim((string) $value);
	$value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/', '', $value) ?? '';
	return function_exists('mb_substr') ? mb_substr($value, 0, 4000) : substr($value, 0, 4000);
}

function ke_store_inquiry(array $item): bool
{
	$dir = __DIR__ . '/admin/data';
	$file = $dir . '/inquiries.json';
	if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
		return false;
	}
	$items = [];
	if (is_file($file)) {
		$decoded = json_decode((string) file_get_contents($file), true);
		if (is_array($decoded)) {
			$items = $decoded;
		}
	}
	array_unshift($items, $item);
	$items = array_slice($items, 0, 150);
	$json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	return (bool) file_put_contents($file, $json, LOCK_EX);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	ke_redirect('contact.html');
}

if (!empty($_FILES)) {
	ke_back('0');
}

if (ke_post('company') !== '' || ke_post('company_site') !== '') {
	ke_redirect('inquiry-sent.html');
}

// Anti-spam: too-fast submit (seconds between page load and submit, set by kuyaely-nav.js).
$keElapsed = ke_post('ke_el');
if ($keElapsed !== '' && ctype_digit($keElapsed) && (int) $keElapsed < 3) {
	ke_redirect('inquiry-sent.html');
}

// Anti-spam: max 5 requests per IP per hour (IP stored only as a hash).
function ke_rate_ok(int $max = 5, int $window = 3600): bool
{
	$ip = (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0');
	$key = substr(hash('sha256', 'ke-rl|' . $ip), 0, 24);
	$dir = __DIR__ . '/admin/data';
	$file = $dir . '/inquiry-rate.json';
	if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
		return true;
	}
	$fp = @fopen($file, 'c+');
	if ($fp === false) {
		return true;
	}
	$ok = true;
	if (flock($fp, LOCK_EX)) {
		$raw = stream_get_contents($fp);
		$data = json_decode((string) $raw, true);
		if (!is_array($data)) {
			$data = [];
		}
		$now = time();
		foreach ($data as $k => $times) {
			$data[$k] = array_values(array_filter((array) $times, static fn($t) => (int) $t > $now - $window));
			if (!$data[$k]) {
				unset($data[$k]);
			}
		}
		if (count($data[$key] ?? []) >= $max) {
			$ok = false;
		} else {
			$data[$key][] = $now;
		}
		ftruncate($fp, 0);
		rewind($fp);
		fwrite($fp, (string) json_encode($data));
		fflush($fp);
		flock($fp, LOCK_UN);
	}
	fclose($fp);
	return $ok;
}
if (!ke_rate_ok()) {
	ke_back('0');
}

session_start();
$now = time();
$last = (int) ($_SESSION['ke_last_mail'] ?? 0);
if ($last > 0 && ($now - $last) < 20) {
	ke_back('0');
}

$name = ke_post('Name');
$phone = ke_post('Phone');
$emailRaw = ke_post('Email') !== '' ? ke_post('Email') : ke_post('email');
$service = ke_post('Service');
$date = ke_post('Travel Date');
$guests = ke_post('Guests');
$message = ke_post('Message');
$page = ke_post('page');

$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
if ($email === false) {
	ke_back('0');
}

$isBooking = $name !== '' || $phone !== '' || $service !== '' || $message !== '';
if ($isBooking && ($name === '' || $phone === '')) {
	ke_back('0');
}

$details = [
	'created' => gmdate('Y-m-d H:i:s') . ' UTC',
	'name' => $name,
	'email' => $email,
	'phone' => $phone,
	'service' => $service,
	'date' => $date,
	'guests' => $guests,
	'message' => $message,
	'page' => $page,
];

$stored = ke_store_inquiry($details);

$subject = $isBooking
	? 'Website booking request' . ($service !== '' ? ' - ' . $service : '')
	: 'Website rates request';

$staffBody = implode("\r\n", [
	'New website request. Reply to this email to reach the guest.',
	'A copy is also saved in the private admin Requests page, so it is not lost if Hostinger puts this in Spam.',
	'',
	'Name: ' . ($name !== '' ? $name : '(not provided)'),
	'Email: ' . $email,
	'Phone / WhatsApp: ' . ($phone !== '' ? $phone : '(not provided)'),
	'Service: ' . ($service !== '' ? $service : '(not provided)'),
	'Travel date: ' . ($date !== '' ? $date : '(not provided)'),
	'Guests: ' . ($guests !== '' ? $guests : '(not provided)'),
	'Page: ' . ($page !== '' ? $page : '(not provided)'),
	'',
	'Message:',
	$message !== '' ? $message : '(no extra details)',
]);

$staffSent = ke_send_mail(KE_MAIL_TO, $subject, $staffBody, $email, KE_MAIL_FROM_NAME);

$guestSent = '';
if (strcasecmp($email, KE_MAIL_TO) !== 0) {
	$guestSent = ke_send_mail(
		$email,
		'We received your Kuya Ely Tours request',
		ke_confirm_text($details),
		KE_MAIL_FROM,
		KE_MAIL_BRAND,
		ke_confirm_html($details),
		true
	);
}

if (!$stored && $staffSent === '' && $guestSent === '') {
	ke_back('0');
}

$_SESSION['ke_last_mail'] = $now;
$next = 'inquiry-sent.html?ok=1&kind=' . ($isBooking ? 'lead' : 'subscribe');
if ($isBooking && $service !== '') {
	$next .= '&tour=' . rawurlencode($service);
}
ke_redirect($next);
