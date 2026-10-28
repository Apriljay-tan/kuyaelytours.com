<?php
declare(strict_types=1);

require __DIR__ . '/ke-mail.php';
require __DIR__ . '/ke-antispam.php';

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

function ke_success_url(bool $isBooking, string $service): string
{
	$next = 'inquiry-sent.html?ok=1&kind=' . ($isBooking ? 'lead' : 'subscribe');
	if ($isBooking && $service !== '') {
		$next .= '&tour=' . rawurlencode($service);
	}
	return $next;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	ke_redirect('contact.html');
}

if (!empty($_FILES)) {
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
$isBooking = $name !== '' || $phone !== '' || $service !== '' || $message !== '';

// ---- Anti-spam. Every rejection below looks like a normal success to the sender,
// ---- but nothing is emailed and nothing is saved to admin Requests.
$keIpSource = '';
$keIp = ke_as_client_ip($keIpSource);
$keSpam = function (string $reason) use ($isBooking, $service, $keIpSource): void {
	ke_as_log($reason, $keIpSource);
	ke_redirect(ke_success_url($isBooking, $service));
};

// 1) Honeypot fields (hidden from people, bots fill them).
if (ke_post('company') !== '' || ke_post('company_site') !== '') {
	$keSpam('honeypot');
}

// 2) Signed form token (issued by ke-form-token.php when the page loads, added by kuyaely-nav.js).
//    Bots that post directly have no valid token. Elapsed time is measured on the server.
$keAge = ke_as_token_age(ke_post('ke_tk'));
if ($keAge === null) {
	// Shown as a visible error (not fake success) so a real guest whose page was open too long,
	// or whose browser blocked the token request, knows to try again or use WhatsApp.
	ke_as_log('no-token', $keIpSource);
	ke_back('0');
}
if ($keAge < KE_AS_MIN_SECONDS) {
	$keSpam('too-fast');
}
$keElapsed = ke_post('ke_el');
if ($keElapsed !== '' && ctype_digit($keElapsed) && (int) $keElapsed < KE_AS_MIN_SECONDS) {
	$keSpam('too-fast-js');
}

// 3) Content checks: links, Cyrillic, random no-space message, long name.
$keReason = ke_as_content_reason($name, $message, $emailRaw, $phone);
if ($keReason !== '') {
	$keSpam($keReason);
}

// 4) Max 5 requests per IP per hour (file in admin/data, not web-accessible).
if (!ke_as_rate_ok($keIp, 5, 3600)) {
	$keSpam('rate-limit');
}

session_start();
$now = time();
$last = (int) ($_SESSION['ke_last_mail'] ?? 0);
if ($last > 0 && ($now - $last) < 20) {
	ke_back('0');
}

$email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
if ($email === false) {
	ke_back('0');
}

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
ke_redirect(ke_success_url($isBooking, $service));
