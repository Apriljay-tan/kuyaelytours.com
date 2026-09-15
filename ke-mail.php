<?php
declare(strict_types=1);

const KE_MAIL_TO = 'info@kuyaelytours.com';
const KE_MAIL_FROM = 'info@kuyaelytours.com';
const KE_MAIL_FROM_NAME = 'Kuya Ely Tours Website';
const KE_MAIL_BRAND = 'Kuya Ely Tours';
const KE_MAIL_LOGO_CID = 'ke-logo@kuyaelytours.com';
const KE_MAIL_LOGO_FILE = __DIR__ . '/assets/downloaded/kuyaely_logo_web.png';

function ke_header_safe(string $value): string
{
	return str_replace(["\r", "\n", "\0"], '', $value);
}

function ke_h(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function ke_mail_config(): array
{
	$file = __DIR__ . '/admin/mail.local.php';
	if (!is_readable($file)) {
		return [];
	}
	$cfg = include $file;
	return is_array($cfg) ? $cfg : [];
}

function ke_mail_ready(): bool
{
	$cfg = ke_mail_config();
	return trim((string) ($cfg['password'] ?? '')) !== '';
}

function ke_smtp_read($fp): string
{
	$data = '';
	while (!feof($fp)) {
		$line = fgets($fp, 1024);
		if ($line === false) {
			break;
		}
		$data .= $line;
		if (preg_match('/^\d{3} /', $line)) {
			break;
		}
		if (strlen($data) > 8192) {
			break;
		}
	}
	return $data;
}

function ke_smtp_cmd($fp, string $command, array $ok): string
{
	if ($command !== '') {
		fwrite($fp, $command . "\r\n");
	}
	$reply = ke_smtp_read($fp);
	$code = (int) substr($reply, 0, 3);
	if (!in_array($code, $ok, true)) {
		throw new RuntimeException('SMTP ' . $code);
	}
	return $reply;
}

function ke_qp(string $value): string
{
	$encoded = quoted_printable_encode($value);
	$encoded = str_replace(["\r\n", "\n"], "\r\n", $encoded);
	return str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $encoded);
}

function ke_logo_base64(): string
{
	if (!is_readable(KE_MAIL_LOGO_FILE)) {
		return '';
	}
	$raw = file_get_contents(KE_MAIL_LOGO_FILE);
	if ($raw === false || $raw === '') {
		return '';
	}
	return chunk_split(base64_encode($raw), 76, "\r\n");
}

function ke_mime_payload(string $to, string $subject, string $text, string $html, string $replyTo, string $fromName, bool $auto): string
{
	$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
	$messageId = '<ke-' . bin2hex(random_bytes(8)) . '@kuyaelytours.com>';
	$headers = [
		'From: ' . ke_header_safe($fromName) . ' <' . KE_MAIL_FROM . '>',
		'To: ' . $to,
		'Reply-To: ' . ke_header_safe($replyTo),
		'Subject: ' . $encodedSubject,
		'Date: ' . gmdate('D, d M Y H:i:s O'),
		'Message-ID: ' . $messageId,
		'MIME-Version: 1.0',
		'X-Mailer: KuyaElyTours',
		'X-Auto-Response-Suppress: All',
	];
	if ($auto) {
		$headers[] = 'Auto-Submitted: auto-replied';
	}

	if ($html === '') {
		$headers[] = 'Content-Type: text/plain; charset=UTF-8';
		$headers[] = 'Content-Transfer-Encoding: quoted-printable';
		return implode("\r\n", $headers) . "\r\n\r\n" . ke_qp($text) . "\r\n.";
	}

	$alt = 'ke_alt_' . bin2hex(random_bytes(6));
	$rel = 'ke_rel_' . bin2hex(random_bytes(6));
	$logo = ke_logo_base64();
	$headers[] = $logo !== ''
		? 'Content-Type: multipart/related; type="multipart/alternative"; boundary="' . $rel . '"'
		: 'Content-Type: multipart/alternative; boundary="' . $alt . '"';

	$altParts = [
		'--' . $alt,
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: quoted-printable',
		'',
		ke_qp($text),
		'--' . $alt,
		'Content-Type: text/html; charset=UTF-8',
		'Content-Transfer-Encoding: quoted-printable',
		'',
		ke_qp($html),
		'--' . $alt . '--',
	];

	if ($logo === '') {
		$parts = array_merge(['This is a multi-part message in MIME format.', ''], $altParts, ['.']);
		return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts);
	}

	$parts = [
		'This is a multi-part message in MIME format.',
		'',
		'--' . $rel,
		'Content-Type: multipart/alternative; boundary="' . $alt . '"',
		'',
		implode("\r\n", $altParts),
		'--' . $rel,
		'Content-Type: image/png',
		'Content-Transfer-Encoding: base64',
		'Content-ID: <' . KE_MAIL_LOGO_CID . '>',
		'Content-Disposition: inline; filename="kuyaely-logo.png"',
		'',
		rtrim($logo),
		'--' . $rel . '--',
		'.',
	];
	return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts);
}

function ke_smtp_try(string $remote, bool $starttls, array $cfg, string $to, string $subject, string $text, string $html, string $replyTo, string $fromName, bool $auto): bool
{
	$user = (string) ($cfg['username'] ?? KE_MAIL_FROM);
	$pass = (string) ($cfg['password'] ?? '');
	if ($user === '' || $pass === '') {
		return false;
	}

	$context = stream_context_create([
		'ssl' => [
			'verify_peer' => true,
			'verify_peer_name' => true,
			'allow_self_signed' => false,
			'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
		],
	]);

	$fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
	if (!is_resource($fp)) {
		return false;
	}
	stream_set_timeout($fp, 25);

	try {
		ke_smtp_cmd($fp, '', [220]);
		ke_smtp_cmd($fp, 'EHLO kuyaelytours.com', [250]);

		if ($starttls) {
			ke_smtp_cmd($fp, 'STARTTLS', [220]);
			if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
				throw new RuntimeException('TLS');
			}
			ke_smtp_cmd($fp, 'EHLO kuyaelytours.com', [250]);
		}

		ke_smtp_cmd($fp, 'AUTH LOGIN', [334]);
		ke_smtp_cmd($fp, base64_encode($user), [334]);
		ke_smtp_cmd($fp, base64_encode($pass), [235]);
		ke_smtp_cmd($fp, 'MAIL FROM:<' . KE_MAIL_FROM . '>', [250]);
		ke_smtp_cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
		ke_smtp_cmd($fp, 'DATA', [354]);
		ke_smtp_cmd($fp, ke_mime_payload($to, $subject, $text, $html, $replyTo, $fromName, $auto), [250]);
		ke_smtp_cmd($fp, 'QUIT', [221, 250]);
		fclose($fp);
		return true;
	} catch (Throwable $e) {
		fclose($fp);
		return false;
	}
}

function ke_smtp_send(array $cfg, string $to, string $subject, string $text, string $html, string $replyTo, string $fromName, bool $auto): bool
{
	$host = (string) ($cfg['host'] ?? 'smtp.hostinger.com');
	$port = (int) ($cfg['port'] ?? 465);
	$secure = strtolower((string) ($cfg['secure'] ?? 'ssl'));

	$attempts = [];
	if ($secure === 'tls' || $port === 587) {
		$attempts[] = ['tcp://' . $host . ':587', true];
		$attempts[] = ['ssl://' . $host . ':465', false];
	} else {
		$attempts[] = ['ssl://' . $host . ':465', false];
		$attempts[] = ['tcp://' . $host . ':587', true];
	}

	foreach ($attempts as $attempt) {
		if (ke_smtp_try($attempt[0], $attempt[1], $cfg, $to, $subject, $text, $html, $replyTo, $fromName, $auto)) {
			return true;
		}
	}
	return false;
}

function ke_send_mail(string $to, string $subject, string $body, string $replyTo, string $fromName = KE_MAIL_FROM_NAME, string $html = '', bool $auto = false): string
{
	$cfg = ke_mail_config();
	if (ke_smtp_send($cfg, $to, $subject, $body, $html, $replyTo, $fromName, $auto)) {
		return 'smtp';
	}
	return '';
}

function ke_row(string $label, string $value): string
{
	if ($value === '') {
		$value = 'Not provided';
	}
	return '<tr>'
		. '<td style="padding:8px 0;color:#8aa0a4;width:140px;vertical-align:top;">' . ke_h($label) . '</td>'
		. '<td style="padding:8px 0;color:#122327;">' . nl2br(ke_h($value)) . '</td>'
		. '</tr>';
}

function ke_confirm_html(array $d): string
{
	$name = trim((string) ($d['name'] ?? ''));
	$hello = $name !== '' ? 'Hi ' . $name . ',' : 'Hi,';
	$rows = ke_row('Service', (string) ($d['service'] ?? ''))
		. ke_row('Travel date', (string) ($d['date'] ?? ''))
		. ke_row('Guests', (string) ($d['guests'] ?? ''))
		. ke_row('Phone / WhatsApp', (string) ($d['phone'] ?? ''))
		. ke_row('Email', (string) ($d['email'] ?? ''))
		. ke_row('Notes', (string) ($d['message'] ?? ''));

	return '<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#f3f6f6;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f6;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background:#ffffff;">
<tr>
<td style="background:#122327;padding:28px 32px;text-align:center;">
<img src="cid:' . KE_MAIL_LOGO_CID . '" alt="Kuya Ely Tours" width="72" height="72" style="display:inline-block;border:0;border-radius:50%;background:#ffffff;">
<p style="margin:14px 0 0;color:#f5c518;letter-spacing:3px;font-size:18px;">KUYA ELY TOURS</p>
</td>
</tr>
<tr>
<td style="padding:32px;">
<p style="margin:0 0 12px;color:#122327;font-size:16px;">' . ke_h($hello) . '</p>
<h1 style="margin:0 0 12px;color:#122327;font-size:26px;">We received your request</h1>
<p style="margin:0 0 20px;color:#3d4f53;font-size:16px;line-height:1.6;">Thank you for messaging Kuya Ely Tours and Transport Services. This is a confirmation only. We will reply with rates, dates, and van availability during business hours.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f4ea;border:1px solid #f5c518;">
<tr><td style="padding:12px 16px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0">' . $rows . '</table>
</td></tr>
</table>
<p style="margin:22px 0 8px;color:#3d4f53;font-size:16px;">Need a faster answer?</p>
<p style="margin:0 0 6px;"><a href="https://wa.me/639209851802" style="color:#122327;font-weight:bold;">WhatsApp +63 920 985 1802</a></p>
<p style="margin:0;"><a href="tel:+639209851802" style="color:#122327;">Call +63 920 985 1802</a></p>
</td>
</tr>
<tr>
<td style="background:#122327;padding:18px 32px;color:#9db0b3;font-size:13px;line-height:1.6;">
Kuya Ely Tours and Transport Services<br>
Sitio Capilis, Suba-Basbas, Lapu-Lapu City, Cebu 6015<br>
<a href="mailto:info@kuyaelytours.com" style="color:#f5c518;">info@kuyaelytours.com</a>
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>';
}

function ke_confirm_text(array $d): string
{
	$name = trim((string) ($d['name'] ?? ''));
	$hello = $name !== '' ? 'Hi ' . $name . ',' : 'Hi,';
	$lines = [
		$hello,
		'',
		'We received your request with Kuya Ely Tours and Transport Services.',
		'This is a confirmation only. We will reply with rates, dates, and van availability during business hours.',
		'',
		'Service: ' . (($d['service'] ?? '') !== '' ? $d['service'] : 'Not provided'),
		'Travel date: ' . (($d['date'] ?? '') !== '' ? $d['date'] : 'Not provided'),
		'Guests: ' . (($d['guests'] ?? '') !== '' ? $d['guests'] : 'Not provided'),
		'Phone / WhatsApp: ' . (($d['phone'] ?? '') !== '' ? $d['phone'] : 'Not provided'),
		'Email: ' . (($d['email'] ?? '') !== '' ? $d['email'] : 'Not provided'),
		'Notes: ' . (($d['message'] ?? '') !== '' ? $d['message'] : 'Not provided'),
		'',
		'WhatsApp: +63 920 985 1802',
		'Call: +63 920 985 1802',
		'Email: info@kuyaelytours.com',
		'Sitio Capilis, Suba-Basbas, Lapu-Lapu City, Cebu 6015',
	];
	return implode("\r\n", $lines);
}
