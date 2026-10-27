<?php
declare(strict_types=1);

function store_partner_types(): array
{
	return [
		'hotel' => 'Hotel',
		'resort' => 'Resort',
		'agency' => 'Travel agency',
		'activity' => 'Activity or attraction',
		'transport' => 'Transport operator',
		'other' => 'Other',
	];
}

function store_partner_areas(): array
{
	return [
		'cebu' => 'Cebu',
		'bohol' => 'Bohol',
		'siquijor' => 'Siquijor',
		'dumaguete' => 'Dumaguete',
		'several' => 'More than one island',
	];
}

function store_is_partner(?array $user): bool
{
	if (!$user) {
		return false;
	}
	return strtolower(trim((string) ($user['role'] ?? ''))) === 'partner';
}

function store_partner_session_fresh(): bool
{
	$now = time();
	$ua = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
	if (empty($_SESSION['ke_partner_started'])) {
		$_SESSION['ke_partner_started'] = $now;
	}
	if (empty($_SESSION['ke_partner_seen'])) {
		$_SESSION['ke_partner_seen'] = $now;
	}
	if (empty($_SESSION['ke_partner_ua']) || !is_string($_SESSION['ke_partner_ua'])) {
		$_SESSION['ke_partner_ua'] = $ua;
	}
	$start = (int) $_SESSION['ke_partner_started'];
	$seen = (int) $_SESSION['ke_partner_seen'];
	$known = (string) $_SESSION['ke_partner_ua'];
	if (!hash_equals($known, $ua) || ($now - $start) > 43200 || ($now - $seen) > 14400) {
		return false;
	}
	$_SESSION['ke_partner_seen'] = $now;
	$rotated = (int) ($_SESSION['ke_partner_rotated'] ?? $start);
	if (($now - $rotated) > 1800) {
		session_regenerate_id(true);
		$_SESSION['ke_partner_rotated'] = $now;
	}
	return true;
}

function store_require_partner(): array
{
	$user = store_user();
	if (!$user || !store_is_partner($user)) {
		store_redirect('/partner/login.php');
	}
	if (!store_partner_session_fresh()) {
		store_logout();
		store_redirect('/partner/login.php?expired=1');
	}
	$script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
	if (store_password_change_required() && !in_array($script, ['password.php', 'logout.php'], true)) {
		store_redirect('/partner/password.php');
	}
	return $user;
}

function store_partner_applications(): array
{
	$rows = [];
	$seen = [];
	$db = store_db();
	if ($db) {
		try {
			$found = $db->query('SELECT * FROM ke_partner_applications ORDER BY created DESC')->fetchAll(PDO::FETCH_ASSOC);
			if (is_array($found)) {
				foreach ($found as $row) {
					if (!is_array($row)) {
						continue;
					}
					$id = (string) ($row['id'] ?? '');
					$rows[] = $row;
					if ($id !== '') {
						$seen[$id] = true;
					}
				}
			}
		} catch (Throwable $e) {
		}
	}
	foreach (store_read_json('partner_applications') as $row) {
		if (!is_array($row)) {
			continue;
		}
		$id = (string) ($row['id'] ?? '');
		if ($id !== '' && isset($seen[$id])) {
			continue;
		}
		$rows[] = $row;
		if ($id !== '') {
			$seen[$id] = true;
		}
	}
	usort($rows, static function ($a, $b) {
		return strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? ''));
	});
	return $rows;
}

function store_partner_json_save(array $row): bool
{
	$all = store_read_json('partner_applications');
	$id = (string) ($row['id'] ?? '');
	foreach ($all as $i => $item) {
		if (is_array($item) && (string) ($item['id'] ?? '') === $id && $id !== '') {
			$all[$i] = $row;
			return store_write_json('partner_applications', $all);
		}
	}
	$all[] = $row;
	return store_write_json('partner_applications', $all);
}

function store_save_partner_application(array $row): bool
{
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('INSERT INTO ke_partner_applications (id, business_name, contact_name, email, phone, partner_type, area, website, message, status, user_id, created) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
			if ($stmt->execute([
				(string) $row['id'],
				(string) $row['business_name'],
				(string) $row['contact_name'],
				(string) $row['email'],
				(string) $row['phone'],
				(string) $row['partner_type'],
				(string) $row['area'],
				(string) $row['website'],
				(string) $row['message'],
				(string) ($row['status'] ?? 'pending'),
				(string) ($row['user_id'] ?? ''),
				(string) $row['created'],
			])) {
				return true;
			}
		} catch (Throwable $e) {
		}
	}
	return store_partner_json_save($row);
}

function store_partner_pending_email(string $email): bool
{
	$email = strtolower(trim($email));
	foreach (store_partner_applications() as $row) {
		if (!is_array($row)) {
			continue;
		}
		if (strtolower((string) ($row['email'] ?? '')) === $email && (string) ($row['status'] ?? 'pending') === 'pending') {
			return true;
		}
	}
	return false;
}

function store_partner_clean(string $value, int $max): string
{
	$value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/', '', $value) ?? '');
	if (function_exists('mb_substr')) {
		return mb_substr($value, 0, $max);
	}
	return substr($value, 0, $max);
}

function store_partner_apply(array $input): string
{
	$tries = (int) ($_SESSION['ke_partner_apply_n'] ?? 0);
	if ($tries >= 5) {
		return 'Please wait and try again later.';
	}
	$business = store_partner_clean((string) ($input['business_name'] ?? ''), 160);
	$contact = store_partner_clean((string) ($input['contact_name'] ?? ''), 140);
	$email = strtolower(store_partner_clean((string) ($input['email'] ?? ''), 190));
	$phone = store_partner_clean((string) ($input['phone'] ?? ''), 40);
	$type = (string) ($input['partner_type'] ?? '');
	$area = (string) ($input['area'] ?? '');
	$website = store_partner_clean((string) ($input['website'] ?? ''), 190);
	$message = store_partner_clean((string) ($input['message'] ?? ''), 2000);
	$types = store_partner_types();
	$areas = store_partner_areas();

	if (strlen($business) < 2) {
		return 'Enter the business name.';
	}
	if ($contact === '') {
		return 'Enter the contact person.';
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return 'Enter a valid email.';
	}
	$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
	if (strlen($phoneDigits) < 7) {
		return 'Enter a phone or WhatsApp number.';
	}
	if (!isset($types[$type])) {
		return 'Choose what kind of partner you are.';
	}
	if (!isset($areas[$area])) {
		return 'Choose the area you work in.';
	}
	if ($website !== '') {
		if (!preg_match('#^https?://#i', $website)) {
			$website = 'https://' . $website;
		}
		if (!filter_var($website, FILTER_VALIDATE_URL)) {
			return 'Enter a valid website, or leave it blank.';
		}
	}
	if (strlen($message) < 10) {
		return 'Tell us a little about what you would like to offer together.';
	}
	if (store_partner_pending_email($email)) {
		return 'We already have an application from this email. The office will be in touch.';
	}
	$row = [
		'id' => store_id(),
		'business_name' => $business,
		'contact_name' => $contact,
		'email' => $email,
		'phone' => $phone,
		'partner_type' => $type,
		'area' => $area,
		'website' => $website,
		'message' => $message,
		'status' => 'pending',
		'user_id' => '',
		'created' => store_now(),
	];
	if (!store_save_partner_application($row)) {
		return 'Could not save the application. Please try again.';
	}
	$_SESSION['ke_partner_apply_n'] = $tries + 1;
	$mailed = store_partner_notify($row);
	$_SESSION['ke_partner_mail'] = !empty($mailed['applicant']) ? '1' : '0';
	return '';
}

function store_partner_mail_ready(): bool
{
	$mail = STORE_SITE . '/ke-mail.php';
	if (!is_readable($mail)) {
		return false;
	}
	require_once $mail;
	return function_exists('ke_mail_ready') && ke_mail_ready() && function_exists('ke_send_mail');
}

function store_partner_send(string $to, string $subject, string $textBody, string $html, string $replyTo): bool
{
	if (!store_partner_mail_ready() || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
		return false;
	}
	$subject = str_replace(["\r", "\n"], ' ', $subject);
	try {
		return ke_send_mail($to, $subject, $textBody, $replyTo, 'Kuya Ely Tours', $html) === 'smtp';
	} catch (Throwable $e) {
		return false;
	}
}

function store_partner_email_html(string $heading, string $intro, string $rows): string
{
	return '<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#f3f6f6;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f6;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background:#ffffff;">
<tr>
<td style="background:#122327;padding:28px 32px;text-align:center;">
<img src="https://kuyaelytours.com/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours" width="72" height="72" style="display:inline-block;border:0;border-radius:50%;background:#ffffff;">
<p style="margin:14px 0 0;color:#f5c518;letter-spacing:3px;font-size:18px;">KUYA ELY TOURS</p>
</td>
</tr>
<tr>
<td style="padding:32px;">
<h1 style="margin:0 0 12px;color:#122327;font-size:26px;">' . $heading . '</h1>
<div style="margin:0 0 20px;color:#3d4f53;font-size:16px;line-height:1.6;">' . $intro . '</div>
' . $rows . '
</td>
</tr>
<tr>
<td style="background:#122327;padding:18px 32px;color:#9db0b3;font-size:13px;line-height:1.6;">
Kuya Ely Tours and Transport Services<br>
Sitio Capilis, Suba-Basbas, Lapu-Lapu City, Cebu 6015<br>
<a href="mailto:support@kuyaelytours.com" style="color:#f5c518;">support@kuyaelytours.com</a>
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>';
}

function store_partner_email_row(string $label, string $value): string
{
	return '<tr><td style="padding:8px 0;color:#8aa0a4;width:160px;vertical-align:top;">' . store_h($label) . '</td>'
		. '<td style="padding:8px 0;color:#122327;">' . nl2br(store_h($value)) . '</td></tr>';
}

function store_partner_remember_inquiry(array $row): void
{
	$file = STORE_SITE . '/admin/data/inquiries.json';
	$dir = dirname($file);
	if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
		return;
	}
	$items = [];
	if (is_file($file)) {
		$decoded = json_decode((string) file_get_contents($file), true);
		if (is_array($decoded)) {
			$items = $decoded;
		}
	}
	$types = store_partner_types();
	$areas = store_partner_areas();
	$type = $types[(string) ($row['partner_type'] ?? '')] ?? (string) ($row['partner_type'] ?? '');
	$area = $areas[(string) ($row['area'] ?? '')] ?? (string) ($row['area'] ?? '');
	$detail = trim((string) ($row['business_name'] ?? ''));
	if ($type !== '') {
		$detail .= ($detail !== '' ? ' · ' : '') . $type;
	}
	if ($area !== '') {
		$detail .= ($detail !== '' ? ' · ' : '') . $area;
	}
	$message = trim((string) ($row['message'] ?? ''));
	if ($message !== '') {
		$detail .= ($detail !== '' ? "\n" : '') . $message;
	}
	array_unshift($items, [
		'created' => (string) ($row['created'] ?? store_now()),
		'name' => (string) ($row['contact_name'] ?? ''),
		'email' => (string) ($row['email'] ?? ''),
		'phone' => (string) ($row['phone'] ?? ''),
		'service' => 'Partner application',
		'date' => '',
		'guests' => '',
		'message' => $detail,
		'page' => '/partner/apply.php',
	]);
	$json = json_encode(array_slice($items, 0, 150), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	if ($json !== false) {
		file_put_contents($file, $json, LOCK_EX);
	}
}

function store_partner_notify(array $row): array
{
	$sent = ['office' => false, 'applicant' => false];
	store_partner_remember_inquiry($row);
	$types = store_partner_types();
	$areas = store_partner_areas();
	$type = $types[(string) ($row['partner_type'] ?? '')] ?? (string) ($row['partner_type'] ?? '');
	$area = $areas[(string) ($row['area'] ?? '')] ?? (string) ($row['area'] ?? '');
	$business = str_replace(["\r", "\n"], ' ', (string) ($row['business_name'] ?? ''));
	$contact = (string) ($row['contact_name'] ?? '');
	$email = (string) ($row['email'] ?? '');
	$phone = (string) ($row['phone'] ?? '');
	$website = (string) ($row['website'] ?? '');
	$message = (string) ($row['message'] ?? '');
	$officeText = implode("\n", [
		'New partner application from the website.',
		'',
		'Business: ' . $business,
		'Contact: ' . $contact,
		'Email: ' . $email,
		'Phone: ' . $phone,
		'Type: ' . $type,
		'Area: ' . $area,
		'Website: ' . ($website !== '' ? $website : '(none)'),
		'',
		'Message:',
		$message,
		'',
		'Open Partners in the admin dashboard to approve it and create their login.',
	]);
	$officeRows = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f4ea;border:1px solid #f5c518;"><tr><td style="padding:12px 16px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0">'
		. store_partner_email_row('Business', $business)
		. store_partner_email_row('Contact', $contact)
		. store_partner_email_row('Email', $email)
		. store_partner_email_row('Phone', $phone)
		. store_partner_email_row('Type', $type)
		. store_partner_email_row('Area', $area)
		. store_partner_email_row('Website', $website !== '' ? $website : '(none)')
		. store_partner_email_row('Message', $message)
		. '</table></td></tr></table>';
	$sent['office'] = store_partner_send(
		'support@kuyaelytours.com',
		'Partner application - ' . $business,
		$officeText,
		store_partner_email_html('New partnership application', '<p style="margin:0;">Someone applied to partner with Kuya Ely Tours. Reply to this email to reach them.</p>', $officeRows),
		$email
	);
	$hello = $contact !== '' ? 'Hi ' . store_h($contact) . ',' : 'Hi,';
	$applicantText = implode("\n", [
		'Hi ' . ($contact !== '' ? $contact : 'there') . ',',
		'',
		'We received your partnership application for ' . $business . '.',
		'This is a confirmation only. Our office will review it and email you if the partnership is approved.',
		'An application does not create a password.',
		'',
		'Business: ' . $business,
		'Contact: ' . $contact,
		'Email: ' . $email,
		'Phone: ' . $phone,
		'Type: ' . $type,
		'Area: ' . $area,
		'',
		'Questions? Reply to this email or WhatsApp +63 960 860 5034.',
	]);
	$applicantRows = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f4ea;border:1px solid #f5c518;"><tr><td style="padding:12px 16px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0">'
		. store_partner_email_row('Business', $business)
		. store_partner_email_row('Contact', $contact)
		. store_partner_email_row('Email', $email)
		. store_partner_email_row('Phone', $phone)
		. store_partner_email_row('Type', $type)
		. store_partner_email_row('Area', $area)
		. '</table></td></tr></table>'
		. '<p style="margin:22px 0 8px;color:#3d4f53;font-size:16px;">Need a faster answer?</p>'
		. '<p style="margin:0;"><a href="https://api.whatsapp.com/send?phone=639608605034" style="color:#122327;font-weight:bold;">WhatsApp +63 960 860 5034</a></p>';
	$sent['applicant'] = store_partner_send(
		$email,
		'We received your partnership application',
		$applicantText,
		store_partner_email_html('We received your application', '<p style="margin:0;">' . $hello . ' Thank you for applying to partner with Kuya Ely Tours. This is a confirmation only. We will email you if the partnership is approved. An application does not create a password.</p>', $applicantRows),
		'support@kuyaelytours.com'
	);
	return $sent;
}

function store_partner_send_credentials(string $email, string $name, string $password, string $business, bool $approved = true): bool
{
	$email = strtolower(trim($email));
	$name = trim($name);
	$business = trim($business);
	if ($password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return false;
	}
	$who = $business !== '' ? $business : 'your business';
	$hello = $name !== '' ? 'Hi ' . $name . ',' : 'Hi,';
	$lead = $approved
		? 'Your partnership application for ' . $who . ' is approved.'
		: 'Here is a new temporary password for your partner login.';
	$text = implode("\n", [
		$hello,
		'',
		$lead,
		'',
		'Sign in: https://kuyaelytours.com/partner/login.php',
		'Email: ' . $email,
		'Temporary password: ' . $password,
		'',
		'The first time you sign in, choose a password you will remember.',
		'You can change it at https://kuyaelytours.com/partner/password.php',
		'',
		'This login is for the partner page. It is separate from a guest booking account.',
	]);
	$rows = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f4ea;border:1px solid #f5c518;"><tr><td style="padding:12px 16px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0">'
		. store_partner_email_row('Sign in', 'https://kuyaelytours.com/partner/login.php')
		. store_partner_email_row('Email', $email)
		. store_partner_email_row('Temporary password', $password)
		. '</table></td></tr></table>'
		. '<p style="margin:22px 0 0;color:#3d4f53;font-size:16px;line-height:1.6;">After you sign in, choose your own password. That page opens automatically the first time.</p>';
	$introLead = $approved
		? ' Your partnership' . ($business !== '' ? ' for ' . store_h($business) : '') . ' is approved.'
		: ' Here is a new temporary password for your partner login.';
	$intro = '<p style="margin:0;">' . store_h($hello) . $introLead . ' Use this temporary password once, then replace it with one you will remember.</p>';
	$subject = $approved ? 'Your Kuya Ely partner login' : 'Your new Kuya Ely partner password';
	$heading = $approved ? 'Your partner login' : 'New temporary password';
	return store_partner_send($email, $subject, $text, store_partner_email_html($heading, $intro, $rows), 'support@kuyaelytours.com');
}

function store_partner_login(string $email, string $password): string
{
	$error = store_login($email, $password);
	if ($error !== '') {
		return $error;
	}
	$user = store_user();
	if (!store_is_partner($user)) {
		store_logout();
		return 'That sign-in is not a partner account. Partner logins are created by the Kuya Ely office after an application is approved.';
	}
	$profile = store_partner_for_user($user);
	$profileStatus = (string) ($profile['status'] ?? '');
	if ($profileStatus === 'inactive') {
		store_logout();
		return 'This partner login is inactive. Ask the Kuya Ely office to turn it back on.';
	}
	if ($profileStatus === 'declined') {
		store_logout();
		return 'This partner application was declined.';
	}
	return '';
}

function store_partner_pending_count(): int
{
	$n = 0;
	foreach (store_partner_applications() as $row) {
		if (is_array($row) && (string) ($row['status'] ?? '') === 'pending') {
			$n++;
		}
	}
	return $n;
}

function store_partner_find(string $id): ?array
{
	$id = trim($id);
	if ($id === '') {
		return null;
	}
	foreach (store_partner_applications() as $row) {
		if (is_array($row) && (string) ($row['id'] ?? '') === $id) {
			return $row;
		}
	}
	return null;
}

function store_partner_pending_for(string $email): ?array
{
	$email = strtolower(trim($email));
	if ($email === '') {
		return null;
	}
	foreach (store_partner_applications() as $row) {
		if (!is_array($row)) {
			continue;
		}
		if (strtolower((string) ($row['email'] ?? '')) === $email && (string) ($row['status'] ?? '') === 'pending') {
			return $row;
		}
	}
	return null;
}

function store_set_user_role(string $userId, string $role): bool
{
	if ($userId === '' || !in_array($role, ['guest', 'partner'], true)) {
		return false;
	}
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('UPDATE ke_users SET role=? WHERE id=?');
			$ok = $stmt->execute([$role, $userId]);
			return $ok && $stmt->rowCount() > 0;
		} catch (Throwable $e) {
			return false;
		}
	}
	$users = store_users();
	foreach ($users as $i => $row) {
		if ((string) ($row['id'] ?? '') === $userId) {
			$users[$i]['role'] = $role;
			return store_write_json('users', $users);
		}
	}
	return false;
}

function store_partner_save_row(array $row): bool
{
	$id = (string) ($row['id'] ?? '');
	if ($id === '') {
		return false;
	}
	$db = store_db();
	if ($db) {
		try {
			$stmt = $db->prepare('UPDATE ke_partner_applications SET business_name=?, contact_name=?, email=?, phone=?, partner_type=?, area=?, website=?, message=?, status=?, user_id=? WHERE id=?');
			$ok = $stmt->execute([
				(string) ($row['business_name'] ?? ''),
				(string) ($row['contact_name'] ?? ''),
				(string) ($row['email'] ?? ''),
				(string) ($row['phone'] ?? ''),
				(string) ($row['partner_type'] ?? ''),
				(string) ($row['area'] ?? ''),
				(string) ($row['website'] ?? ''),
				(string) ($row['message'] ?? ''),
				(string) ($row['status'] ?? 'pending'),
				(string) ($row['user_id'] ?? ''),
				$id,
			]);
			if ($ok && $stmt->rowCount() > 0) {
				return true;
			}
		} catch (Throwable $e) {
		}
	}
	return store_partner_json_save($row);
}

function store_partner_issue_login(array $app): array
{
	$fail = ['error' => 'Could not create that login.', 'password' => '', 'email' => '', 'name' => ''];
	$email = strtolower(trim((string) ($app['email'] ?? '')));
	$name = trim((string) ($app['contact_name'] ?? ''));
	if ($name === '') {
		$name = trim((string) ($app['business_name'] ?? ''));
	}
	$phone = trim((string) ($app['phone'] ?? ''));
	if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$fail['error'] = 'That application needs a contact name and a valid email.';
		return $fail;
	}
	$opened = store_desk_open_client($name, $email, $phone, '');
	$user = is_array($opened['user'] ?? null) ? $opened['user'] : null;
	if (!$user) {
		$fail['error'] = (string) ($opened['error'] ?? $fail['error']);
		return $fail;
	}
	$userId = (string) ($user['id'] ?? '');
	$password = (string) ($opened['password'] ?? '');
	if ($password === '') {
		$password = store_desk_set_temp_password($userId);
	}
	if ($password === '') {
		$fail['error'] = 'Could not set a password for that account.';
		return $fail;
	}
	if (!store_set_user_role($userId, 'partner')) {
		$fail['error'] = 'The account was saved, but it could not be marked as a partner.';
		return $fail;
	}
	$app['status'] = 'approved';
	$app['user_id'] = $userId;
	$business = trim((string) ($app['business_name'] ?? ''));
	$mailed = store_partner_send_credentials($email, $name, $password, $business);
	if (!store_partner_save_row($app)) {
		$fail['error'] = 'The login was created, but the application was not marked approved.';
		$fail['password'] = $password;
		$fail['email'] = $email;
		$fail['name'] = $name;
		$fail['mailed'] = $mailed;
		return $fail;
	}
	return ['error' => '', 'password' => $password, 'email' => $email, 'name' => $name, 'mailed' => $mailed];
}

function store_partner_decline(string $id): string
{
	$app = store_partner_find($id);
	if (!$app) {
		return 'That application was not found.';
	}
	if ((string) ($app['status'] ?? '') === 'approved') {
		return 'That partner already has a login.';
	}
	$app['status'] = 'declined';
	if (!store_partner_save_row($app)) {
		return 'Could not update that application.';
	}
	return '';
}

function store_partner_reset(string $id): array
{
	$fail = ['error' => 'Could not set a new password.', 'password' => '', 'email' => '', 'name' => ''];
	$app = store_partner_find($id);
	if (!$app || (string) ($app['status'] ?? '') !== 'approved') {
		$fail['error'] = 'Create the login before resetting the password.';
		return $fail;
	}
	$userId = (string) ($app['user_id'] ?? '');
	$email = strtolower(trim((string) ($app['email'] ?? '')));
	if ($userId === '') {
		$user = store_find_user_email($email);
		$userId = (string) ($user['id'] ?? '');
	}
	$password = $userId !== '' ? store_desk_set_temp_password($userId) : '';
	if ($password === '' || !store_set_user_role($userId, 'partner')) {
		return $fail;
	}
	$name = trim((string) ($app['contact_name'] ?? ''));
	if ($name === '') {
		$name = trim((string) ($app['business_name'] ?? ''));
	}
	$mailed = store_partner_send_credentials($email, $name, $password, trim((string) ($app['business_name'] ?? '')), false);
	return ['error' => '', 'password' => $password, 'email' => $email, 'name' => $name, 'mailed' => $mailed];
}

function store_partner_create_login(string $business, string $contact, string $email, string $phone, string $type = 'other', string $area = 'several'): array
{
	$fail = ['error' => 'Could not create that login.', 'password' => '', 'email' => '', 'name' => ''];
	$business = store_partner_clean($business, 160);
	$contact = store_partner_clean($contact, 140);
	$email = strtolower(store_partner_clean($email, 190));
	$phone = store_partner_clean($phone, 40);
	if (strlen($business) < 2 || $contact === '') {
		$fail['error'] = 'Enter the business name and the contact person.';
		return $fail;
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$fail['error'] = 'Enter a valid email. That is what they will use to sign in.';
		return $fail;
	}
	if (!isset(store_partner_types()[$type])) {
		$type = 'other';
	}
	if (!isset(store_partner_areas()[$area])) {
		$area = 'several';
	}
	$pending = store_partner_pending_for($email);
	if ($pending) {
		$pending['business_name'] = $business;
		$pending['contact_name'] = $contact;
		$pending['partner_type'] = $type;
		$pending['area'] = $area;
		if ($phone !== '') {
			$pending['phone'] = $phone;
		}
		return store_partner_issue_login($pending);
	}
	$row = [
		'id' => store_id(),
		'business_name' => $business,
		'contact_name' => $contact,
		'email' => $email,
		'phone' => $phone,
		'partner_type' => $type,
		'area' => $area,
		'website' => '',
		'message' => 'Created in the admin dashboard.',
		'status' => 'pending',
		'user_id' => '',
		'created' => store_now(),
	];
	if (!store_save_partner_application($row)) {
		$fail['error'] = 'Could not save that partner. Please try again.';
		return $fail;
	}
	return store_partner_issue_login($row);
}

function store_partner_for_user(array $user): ?array
{
	$id = (string) ($user['id'] ?? '');
	$email = strtolower(trim((string) ($user['email'] ?? '')));
	$fallback = null;
	foreach (store_partner_applications() as $row) {
		if (!is_array($row)) {
			continue;
		}
		$sameUser = $id !== '' && (string) ($row['user_id'] ?? '') === $id;
		$sameEmail = $email !== '' && strtolower((string) ($row['email'] ?? '')) === $email;
		if (!$sameUser && !$sameEmail) {
			continue;
		}
		if ((string) ($row['status'] ?? '') === 'approved') {
			return $row;
		}
		if ($fallback === null) {
			$fallback = $row;
		}
	}
	return $fallback;
}

function store_partner_desk_defaults(): array
{
	return [
		'address' => '',
		'notes' => '',
		'commission_type' => 'fixed',
		'commission_value' => '0',
		'payment_terms' => 'N/A',
		'can_book' => '1',
		'view_prices' => '1',
		'use_contracted' => '1',
		'manage_bookings' => '0',
		'login_at' => '',
		'prices' => [],
		'logo' => '',
		'visible' => '1',
	];
}

function store_partner_desk(string $id): array
{
	$all = store_read_json('partner_desk');
	$row = $all[$id] ?? [];
	if (!is_array($row)) {
		$row = [];
	}
	$row = array_merge(store_partner_desk_defaults(), $row);
	foreach (['can_book', 'view_prices', 'use_contracted', 'manage_bookings'] as $flag) {
		$row[$flag] = (string) ($row[$flag] ?? '0') === '1' ? '1' : '0';
	}
	if (!is_array($row['prices'])) {
		$row['prices'] = [];
	}
	return $row;
}

function store_partner_desk_put(string $id, array $row): bool
{
	$id = trim($id);
	if ($id === '') {
		return false;
	}
	$all = store_read_json('partner_desk');
	$all[$id] = $row;
	return store_write_json('partner_desk', $all);
}

function store_partner_package_rates(): array
{
	$data = store_read_json('partner_package_rates');
	return is_array($data) ? $data : [];
}

function store_partner_package_rate(string $slug): ?array
{
	$slug = strtolower(trim($slug));
	$row = store_partner_package_rates()[$slug] ?? null;
	return is_array($row) ? $row : null;
}

function store_partner_package_rate_put(string $slug, array $row): bool
{
	$slug = strtolower(trim($slug));
	if ($slug === '') {
		return false;
	}
	$all = store_partner_package_rates();
	$all[$slug] = $row;
	return store_write_json('partner_package_rates', $all);
}

function store_partner_child_off(array $tier, string $side, string $mode): int
{
	$key = $side . '_child_off';
	if (array_key_exists($key, $tier)) {
		return max(0, (int) $tier[$key]);
	}
	$adult = (int) ($tier[$side . '_adult'] ?? 0);
	$child = (int) ($tier[$side . '_child'] ?? $adult);
	if ($mode === 'amount') {
		return max(0, $adult - $child);
	}
	if ($mode === 'percent' && $adult > 0) {
		return max(0, min(100, (int) round((1 - ($child / $adult)) * 100)));
	}
	return max(0, $child);
}

function store_partner_rate_sheet_from_package(array $pkg): array
{
	$tiers = function_exists('ke_tour_price_tiers') ? ke_tour_price_tiers($pkg) : [];
	$mode = function_exists('ke_child_price_mode_value') ? ke_child_price_mode_value((string) ($pkg['child_price_mode'] ?? 'fixed')) : 'fixed';
	$clean = [];
	foreach ($tiers as $tier) {
		if (!is_array($tier)) {
			continue;
		}
		$adult = (int) ($tier['foreign_adult'] ?? 0);
		$local = (int) ($tier['local_adult'] ?? $adult);
		if ($adult < 1 && $local < 1) {
			continue;
		}
		$clean[] = [
			'min' => (int) ($tier['min'] ?? 1),
			'max' => (int) ($tier['max'] ?? 1),
			'foreign_adult' => $adult,
			'local_adult' => $local > 0 ? $local : $adult,
			'foreign_child' => (int) ($tier['foreign_child'] ?? $adult),
			'local_child' => (int) ($tier['local_child'] ?? $local),
			'foreign_child_off' => store_partner_child_off($tier, 'foreign', $mode),
			'local_child_off' => store_partner_child_off($tier, 'local', $mode),
		];
	}
	$cover = function_exists('ke_package_cover') ? (string) ke_package_cover($pkg) : '';
	return [
		'name' => (string) ($pkg['name'] ?? ''),
		'island' => (string) ($pkg['island'] ?? ''),
		'cover' => $cover,
		'duration' => (string) ($pkg['duration'] ?? ''),
		'split' => function_exists('ke_package_split_local') ? ke_package_split_local($pkg) : false,
		'child_price_mode' => $mode,
		'tiers' => $clean,
		'updated' => store_now(),
	];
}

function store_partner_catalog_rates(): array
{
	if (!function_exists('ke_tour')) {
		require_once STORE_ROOT . '/tours-data.php';
	}
	$out = [];
	foreach (store_partner_package_rates() as $slug => $row) {
		if (!is_array($row) || !is_array($row['tiers'] ?? null) || !$row['tiers']) {
			continue;
		}
		$slug = strtolower((string) $slug);
		$pkg = ke_tour($slug);
		if (!$pkg || empty($pkg['active'])) {
			continue;
		}
		$split = ke_package_split_local($pkg);
		$original = [];
		foreach (ke_tour_price_tiers($pkg) as $tier) {
			if (!is_array($tier)) {
				continue;
			}
			$original[(int) ($tier['min'] ?? 0) . '-' . (int) ($tier['max'] ?? 0)] = $tier;
		}
		$tiers = [];
		$best = null;
		foreach ($row['tiers'] as $tier) {
			if (!is_array($tier)) {
				continue;
			}
			$key = (int) ($tier['min'] ?? 0) . '-' . (int) ($tier['max'] ?? 0);
			$web = $original[$key] ?? null;
			$market = (int) (is_array($web) ? ($web['foreign_adult'] ?? 0) : ($tier['foreign_adult'] ?? 0));
			$rate = (int) ($tier['foreign_adult'] ?? 0);
			$localMarket = (int) (is_array($web) ? ($web['local_adult'] ?? $market) : ($tier['local_adult'] ?? $rate));
			$localRate = (int) ($tier['local_adult'] ?? $rate);
			if ($rate < 1 && $localRate < 1) {
				continue;
			}
			if ($market < 1) {
				$market = $rate;
			}
			if ($localMarket < 1) {
				$localMarket = $localRate > 0 ? $localRate : $market;
			}
			if ($market > 0) {
				$market += 500;
			}
			$save = max(0, $market - $rate);
			$item = [
				'min' => (int) ($tier['min'] ?? 1),
				'max' => (int) ($tier['max'] ?? 1),
				'market' => $market,
				'rate' => $rate,
				'save' => $save,
				'pct' => $market > 0 ? (int) round($save / $market * 100) : 0,
				'child' => (int) ($tier['foreign_child'] ?? 0),
				'local_market' => $localMarket,
				'local_rate' => $localRate,
				'local_child' => (int) ($tier['local_child'] ?? 0),
			];
			$tiers[] = $item;
			$candidates = [['market' => $market, 'rate' => $rate, 'save' => $save]];
			foreach ($candidates as $candidate) {
				if ($candidate['market'] < 1 || $candidate['rate'] < 1) {
					continue;
				}
				if ($best === null || $candidate['market'] < $best['market']) {
					$best = $candidate;
					$best['pct'] = $candidate['market'] > 0 ? (int) round($candidate['save'] / $candidate['market'] * 100) : 0;
				}
			}
		}
		usort($tiers, static function (array $a, array $b): int {
			return ((int) $a['min']) <=> ((int) $b['min']) ?: ((int) $a['max']) <=> ((int) $b['max']);
		});
		if (!$tiers || $best === null) {
			continue;
		}
		$cover = (string) ke_package_cover($pkg);
		if ($cover === '') {
			$cover = (string) ($row['cover'] ?? '');
		}
		$out[] = [
			'name' => (string) ($pkg['name'] ?? $row['name'] ?? $slug),
			'island' => ucfirst((string) ($pkg['island'] ?? $row['island'] ?? '')),
			'cover' => $cover,
			'split' => $split,
			'sort' => (int) ($pkg['sort'] ?? 0),
			'market' => (int) $best['market'],
			'rate' => (int) $best['rate'],
			'save' => (int) $best['save'],
			'pct' => (int) $best['pct'],
			'tiers' => $tiers,
			'slug' => $slug,
			'island_key' => strtolower((string) ($pkg['island'] ?? '')),
			'duration' => trim((string) ($pkg['duration'] ?? $row['duration'] ?? '')),
			'place' => trim((string) ($pkg['place'] ?? '')),
			'lead' => trim((string) ($pkg['lead'] ?? '')),
			'badge' => trim((string) ($pkg['badge'] ?? '')),
			'age_child' => trim((string) ($pkg['age_child'] ?? '')),
			'updated' => (string) ($row['updated'] ?? ''),
			'pills' => array_slice(array_values(array_filter(array_map('strval', (array) ($pkg['pills'] ?? [])))), 0, 3),
		];
	}
	usort($out, static function (array $a, array $b): int {
		return ((int) $a['sort']) <=> ((int) $b['sort']);
	});
	return $out;
}

function store_partner_logo_path(array $desk): string
{
	$path = trim((string) ($desk['logo'] ?? ''));
	if (!preg_match('#^/assets/uploads/partners/[a-f0-9]{16}\.(jpg|png|webp|gif)$#', $path)) {
		return '';
	}
	return $path;
}

function store_partner_logo_delete(string $path): void
{
	$path = store_partner_logo_path(['logo' => $path]);
	if ($path === '') {
		return;
	}
	$file = STORE_SITE . $path;
	if (is_file($file)) {
		unlink($file);
	}
}

function store_partner_save_logo(array $file): array
{
	$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
	if ($error === UPLOAD_ERR_NO_FILE) {
		return ['path' => '', 'error' => 'Choose a logo file first.'];
	}
	if ($error !== UPLOAD_ERR_OK) {
		return ['path' => '', 'error' => 'The logo could not be uploaded.'];
	}
	if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
		return ['path' => '', 'error' => 'Use a logo under 2 MB.'];
	}
	$tmp = (string) ($file['tmp_name'] ?? '');
	$info = @getimagesize($tmp);
	$types = [
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG => 'png',
		IMAGETYPE_WEBP => 'webp',
		IMAGETYPE_GIF => 'gif',
	];
	$kind = (int) ($info[2] ?? 0);
	if (!is_array($info) || !isset($types[$kind]) || !is_uploaded_file($tmp)) {
		return ['path' => '', 'error' => 'Use a JPG, PNG, WEBP, or GIF logo.'];
	}
	$dir = STORE_SITE . '/assets/uploads/partners';
	if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
		return ['path' => '', 'error' => 'The logo could not be saved.'];
	}
	$name = bin2hex(random_bytes(8)) . '.' . $types[$kind];
	if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
		return ['path' => '', 'error' => 'The logo could not be saved.'];
	}
	return ['path' => '/assets/uploads/partners/' . $name, 'error' => ''];
}
