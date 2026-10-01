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

function ke_imap_quote(string $value): string
{
	$value = str_replace(["\r", "\n", "\0"], '', $value);
	return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
}

function ke_imap_read_response($fp, string $tag): array
{
	$lines = [];
	$ok = false;
	while (!feof($fp)) {
		$line = fgets($fp, 8192);
		if ($line === false) {
			break;
		}
		while (preg_match('/\{(\d+)\}\r\n$/', $line, $match)) {
			$need = (int) $match[1];
			$literal = '';
			if ($need > 4000000) {
				$left = $need;
				while ($left > 0) {
					$chunk = fread($fp, min(8192, $left));
					if ($chunk === false || $chunk === '') {
						break;
					}
					$left -= strlen($chunk);
				}
				$literal = "\r\n[This message is too large to show here. Open it in Hostinger Mail.]\r\n";
			} else {
				while (strlen($literal) < $need) {
					$chunk = fread($fp, $need - strlen($literal));
					if ($chunk === false || $chunk === '') {
						break;
					}
					$literal .= $chunk;
				}
			}
			$line = substr($line, 0, -strlen($match[0])) . $literal;
			$rest = fgets($fp, 8192);
			if ($rest === false) {
				break;
			}
			$line .= $rest;
		}
		$clean = preg_replace("/\r\n$/", '', $line) ?? $line;
		$lines[] = $clean;
		if (preg_match('/^' . preg_quote($tag, '/') . ' (OK|NO|BAD)/i', $clean, $status)) {
			$ok = strtoupper($status[1]) === 'OK';
			break;
		}
		if (count($lines) > 4000) {
			break;
		}
	}
	return ['ok' => $ok, 'lines' => $lines];
}

function ke_imap_command($fp, string $tag, string $command): array
{
	fwrite($fp, $tag . ' ' . $command . "\r\n");
	return ke_imap_read_response($fp, $tag);
}

function ke_imap_connect()
{
	$cfg = ke_mail_config();
	$user = trim((string) ($cfg['username'] ?? KE_MAIL_FROM));
	$pass = (string) ($cfg['password'] ?? '');
	if ($user === '' || $pass === '') {
		return null;
	}
	$context = stream_context_create([
		'ssl' => [
			'verify_peer' => true,
			'verify_peer_name' => true,
			'allow_self_signed' => false,
		],
	]);
	$fp = @stream_socket_client('ssl://imap.hostinger.com:993', $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $context);
	if (!is_resource($fp)) {
		return null;
	}
	stream_set_timeout($fp, 25);
	$greet = fgets($fp, 2048);
	if ($greet === false) {
		fclose($fp);
		return null;
	}
	$login = ke_imap_command($fp, 'A1', 'LOGIN ' . ke_imap_quote($user) . ' ' . ke_imap_quote($pass));
	if (!$login['ok']) {
		fclose($fp);
		return null;
	}
	return $fp;
}

function ke_imap_close($fp): void
{
	if (!is_resource($fp)) {
		return;
	}
	@fwrite($fp, "A9 LOGOUT\r\n");
	@fclose($fp);
}

function ke_imap_decode_words(string $value): string
{
	$decoded = preg_replace_callback('/=\?([^?]+)\?([BQbq])\?([^?]*)\?=/', static function (array $match): string {
		$raw = strtoupper($match[2]) === 'B'
			? base64_decode(str_replace(' ', '', $match[3]), true)
			: quoted_printable_decode(str_replace('_', ' ', $match[3]));
		if (!is_string($raw) || $raw === '') {
			return $match[0];
		}
		if (function_exists('mb_convert_encoding') && !preg_match('/utf-8/i', $match[1])) {
			$converted = @mb_convert_encoding($raw, 'UTF-8', $match[1]);
			if (is_string($converted) && $converted !== '') {
				return $converted;
			}
		}
		return $raw;
	}, $value);
	return trim((string) $decoded);
}

function ke_imap_header_value(string $raw, string $name): string
{
	$unfolded = preg_replace("/\r\n[ \t]/", ' ', $raw) ?? $raw;
	if (!preg_match('/^' . preg_quote($name, '/') . ':\s*(.*)$/mi', $unfolded, $match)) {
		return '';
	}
	return ke_imap_decode_words(trim($match[1]));
}

function ke_mail_address(string $from): string
{
	$email = $from;
	if (preg_match('/<([^>]+)>/', $from, $match)) {
		$email = $match[1];
	}
	$email = strtolower(trim(ke_header_safe($email)));
	return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function ke_mail_display_name(string $from): string
{
	$name = $from;
	if (preg_match('/^\s*"?([^"<]+)"?\s*</', $from, $match)) {
		$name = trim($match[1]);
	}
	$name = ke_imap_decode_words(trim($name, " \t\""));
	$email = ke_mail_address($from);
	if ($name === '' || strcasecmp($name, $email) === 0) {
		return $email !== '' ? $email : 'Unknown sender';
	}
	return $name;
}

function ke_mail_decode_bytes(string $body, string $encoding): string
{
	$encoding = strtolower(trim($encoding));
	if ($encoding === 'base64') {
		$decoded = base64_decode(preg_replace('/\s+/', '', $body) ?? '', true);
		return is_string($decoded) ? $decoded : '';
	}
	if ($encoding === 'quoted-printable') {
		return quoted_printable_decode($body);
	}
	return $body;
}

function ke_mail_to_utf8(string $body, string $type): string
{
	if (!function_exists('mb_convert_encoding') || !preg_match('/charset="?([^";]+)"?/i', $type, $match)) {
		return $body;
	}
	if (preg_match('/utf-8/i', $match[1])) {
		return $body;
	}
	$converted = @mb_convert_encoding($body, 'UTF-8', $match[1]);
	return is_string($converted) && $converted !== '' ? $converted : $body;
}

function ke_mail_walk(string $headers, string $body, array &$images): array
{
	$result = ['html' => '', 'plain' => ''];
	$type = ke_imap_header_value($headers, 'Content-Type');
	$encoding = ke_imap_header_value($headers, 'Content-Transfer-Encoding');
	if (stripos($type, 'multipart/') === 0 && preg_match('/boundary="?([^";]+)"?/i', $type, $match)) {
		foreach (explode('--' . $match[1], $body) as $chunk) {
			$chunk = ltrim($chunk, "\r\n");
			if ($chunk === '' || str_starts_with($chunk, '--')) {
				continue;
			}
			$split = preg_split("/\r\n\r\n/", $chunk, 2);
			$part = ke_mail_walk((string) ($split[0] ?? ''), (string) ($split[1] ?? ''), $images);
			if ($result['html'] === '' && $part['html'] !== '') {
				$result['html'] = $part['html'];
			}
			if ($result['plain'] === '' && $part['plain'] !== '') {
				$result['plain'] = $part['plain'];
			}
		}
		return $result;
	}
	$decoded = ke_mail_decode_bytes($body, $encoding);
	if (stripos($type, 'image/') === 0) {
		$cid = trim(ke_imap_header_value($headers, 'Content-ID'), "<> \t");
		$mime = strtolower(trim(strtok($type, ';') ?: 'image/png'));
		if ($cid !== '' && $decoded !== '' && str_starts_with($mime, 'image/')) {
			$images[$cid] = 'data:' . $mime . ';base64,' . base64_encode($decoded);
		}
		return $result;
	}
	$decoded = ke_mail_to_utf8($decoded, $type);
	if (stripos($type, 'text/html') === 0 || ($type === '' && preg_match('/^\s*</', $decoded))) {
		$result['html'] = $decoded;
	} elseif (stripos($type, 'text/plain') === 0 || $type === '') {
		$result['plain'] = trim($decoded);
	}
	return $result;
}

function ke_mail_safe_html(string $html, array $images): string
{
	$html = preg_replace_callback('/cid:([^"\'\s>]+)/i', static function (array $match) use ($images): string {
		$key = rawurldecode(trim($match[1], '<>'));
		return $images[$key] ?? $match[0];
	}, $html) ?? $html;
	$html = preg_replace('/\s(src|href)\s*=\s*(["\'])\/\//i', ' $1=$2https://', $html) ?? $html;
	$html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
	$html = preg_replace('/<(iframe|object|embed|form|base|meta|link)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
	$html = preg_replace('/<(iframe|object|embed|form|base|meta|link)\b[^>]*\/?>/is', '', $html) ?? $html;
	$html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
	$html = preg_replace('/javascript\s*:/i', '', $html) ?? $html;
	$guard = '<meta http-equiv="Content-Security-Policy" content="script-src \'none\'; object-src \'none\'; base-uri \'none\'; img-src https: http: data: blob:; style-src \'unsafe-inline\' https: http:; font-src https: http: data:;">';
	if (!preg_match('/<html[\s>]/i', $html)) {
		$html = '<!DOCTYPE html><html><head><meta charset="utf-8">' . $guard . '</head><body>' . $html . '</body></html>';
	} elseif (preg_match('/<head[^>]*>/i', $html)) {
		$html = preg_replace('/<head[^>]*>/i', '$0' . $guard, $html, 1) ?? $html;
	} else {
		$html = preg_replace('/<html[^>]*>/i', '$0<head>' . $guard . '</head>', $html, 1) ?? $html;
	}
	return $html;
}

function ke_mail_visible(string $raw): array
{
	$split = preg_split("/\r\n\r\n/", $raw, 2);
	$images = [];
	$parts = ke_mail_walk((string) ($split[0] ?? ''), (string) ($split[1] ?? ''), $images);
	$html = $parts['html'] !== '' ? ke_mail_safe_html($parts['html'], $images) : '';
	$plain = $parts['plain'];
	if (function_exists('mb_substr')) {
		$plain = mb_substr($plain, 0, 20000);
	} else {
		$plain = substr($plain, 0, 20000);
	}
	return ['html' => $html, 'plain' => $plain];
}

function ke_imap_parse_fetch(array $lines): array
{
	$items = [];
	foreach ($lines as $line) {
		if (!preg_match('/^\* \d+ FETCH /', $line)) {
			continue;
		}
		if (!preg_match('/\bUID (\d+)/', $line, $uidMatch)) {
			continue;
		}
		$header = '';
		if (preg_match('/BODY\[HEADER[^\]]*\] (.*)$/s', $line, $headerMatch)) {
			$header = preg_replace('/\)\s*$/', '', $headerMatch[1]) ?? $headerMatch[1];
		}
		$full = '';
		if (preg_match('/BODY\[\] (.*)$/s', $line, $bodyMatch)) {
			$full = preg_replace('/\)\s*$/', '', $bodyMatch[1]) ?? $bodyMatch[1];
			if ($header === '') {
				$header = (string) (preg_split("/\r\n\r\n/", $full, 2)[0] ?? '');
			}
		}
		$from = ke_imap_header_value($header, 'From');
		$replyTo = ke_imap_header_value($header, 'Reply-To');
		$to = ke_imap_header_value($header, 'To');
		$email = ke_mail_address($replyTo);
		if ($email === '') {
			$email = ke_mail_address($from);
		}
		$dateRaw = ke_imap_header_value($header, 'Date');
		$stamp = strtotime($dateRaw);
		$visible = $full !== '' ? ke_mail_visible($full) : ['html' => '', 'plain' => ''];
		$items[] = [
			'uid' => $uidMatch[1],
			'from' => ke_mail_display_name($from),
			'email' => $email,
			'to' => ke_mail_display_name($to),
			'to_email' => ke_mail_address($to),
			'subject' => ke_imap_header_value($header, 'Subject') !== '' ? ke_imap_header_value($header, 'Subject') : '(no subject)',
			'date' => $stamp ? date('M j, Y g:i A', $stamp) : $dateRaw,
			'seen' => (bool) preg_match('/FLAGS \((?:[^)]* )?\\\\Seen(?: [^)]*)?\)/i', $line),
			'html' => $visible['html'],
			'body' => $visible['plain'],
		];
	}
	return $items;
}

function ke_imap_folder_key(string $folder): string
{
	return in_array($folder, ['inbox', 'spam', 'sent'], true) ? $folder : 'inbox';
}

function ke_imap_folder_names($fp): array
{
	$found = ['inbox' => 'INBOX', 'spam' => '', 'sent' => ''];
	$listed = ke_imap_command($fp, 'A2', 'LIST "" "*"');
	foreach ($listed['lines'] as $line) {
		if (!preg_match('/^\* LIST \(([^)]*)\) "[^"]*" (.+)$/', $line, $match)) {
			continue;
		}
		$flags = strtolower($match[1]);
		$name = trim($match[2]);
		if (str_starts_with($name, '"') && str_ends_with($name, '"')) {
			$name = stripcslashes(substr($name, 1, -1));
		}
		$leaf = strtolower((string) preg_replace('/^.*[.]/', '', $name));
		if (strcasecmp($name, 'INBOX') === 0) {
			$found['inbox'] = $name;
		}
		if ($found['spam'] === '' && (str_contains($flags, '\\junk') || in_array($leaf, ['spam', 'junk'], true))) {
			$found['spam'] = $name;
		}
		if ($found['sent'] === '' && (str_contains($flags, '\\sent') || $leaf === 'sent')) {
			$found['sent'] = $name;
		}
	}
	$try = static function ($fp, array $names): string {
		$tag = 30;
		foreach ($names as $name) {
			$tag++;
			$check = ke_imap_command($fp, 'A' . $tag, 'EXAMINE ' . ke_imap_quote($name));
			if ($check['ok']) {
				return $name;
			}
		}
		return '';
	};
	if ($found['spam'] === '') {
		$found['spam'] = $try($fp, ['INBOX.Spam', 'Spam', 'INBOX.Junk', 'Junk']);
	}
	if ($found['sent'] === '') {
		$found['sent'] = $try($fp, ['INBOX.Sent', 'Sent', 'INBOX.Sent Items', 'Sent Items']);
	}
	return $found;
}

function ke_imap_list(string $folder = 'inbox', int $limit = 40): array
{
	$folder = ke_imap_folder_key($folder);
	$empty = ['ok' => false, 'error' => '', 'items' => [], 'folder' => $folder];
	$fp = ke_imap_connect();
	if (!is_resource($fp)) {
		$empty['error'] = 'Could not open the mailbox. Use the gear to check the mailbox password.';
		return $empty;
	}
	$names = ke_imap_folder_names($fp);
	$mailbox = (string) ($names[$folder] ?? '');
	if ($mailbox === '') {
		ke_imap_close($fp);
		$empty['ok'] = true;
		$empty['error'] = 'That folder is not on this mailbox.';
		return $empty;
	}
	$box = ke_imap_command($fp, 'A20', 'SELECT ' . ke_imap_quote($mailbox));
	if (!$box['ok']) {
		ke_imap_close($fp);
		$empty['error'] = 'Could not open that folder.';
		return $empty;
	}
	$exists = 0;
	foreach ($box['lines'] as $line) {
		if (preg_match('/^\* (\d+) EXISTS/', $line, $match)) {
			$exists = (int) $match[1];
		}
	}
	if ($exists < 1) {
		ke_imap_close($fp);
		return ['ok' => true, 'error' => '', 'items' => [], 'folder' => $folder];
	}
	$start = max(1, $exists - max(1, $limit) + 1);
	$fetched = ke_imap_command($fp, 'A21', 'FETCH ' . $start . ':' . $exists . ' (UID FLAGS BODY.PEEK[HEADER.FIELDS (FROM TO SUBJECT DATE)])');
	ke_imap_close($fp);
	if (!$fetched['ok']) {
		$empty['error'] = 'Could not load messages.';
		return $empty;
	}
	return ['ok' => true, 'error' => '', 'items' => array_reverse(ke_imap_parse_fetch($fetched['lines'])), 'folder' => $folder];
}

function ke_imap_message(string $uid, string $folder = 'inbox'): array
{
	$folder = ke_imap_folder_key($folder);
	if (!preg_match('/^\d{1,20}$/', $uid)) {
		return ['ok' => false, 'error' => 'That message could not be opened.', 'message' => null];
	}
	$fp = ke_imap_connect();
	if (!is_resource($fp)) {
		return ['ok' => false, 'error' => 'Could not open the mailbox. Use the gear to check the mailbox password.', 'message' => null];
	}
	$names = ke_imap_folder_names($fp);
	$mailbox = (string) ($names[$folder] ?? '');
	if ($mailbox === '') {
		ke_imap_close($fp);
		return ['ok' => false, 'error' => 'That folder is not on this mailbox.', 'message' => null];
	}
	$selected = ke_imap_command($fp, 'A20', 'SELECT ' . ke_imap_quote($mailbox));
	if (!$selected['ok']) {
		ke_imap_close($fp);
		return ['ok' => false, 'error' => 'Could not open that folder.', 'message' => null];
	}
	$fetched = ke_imap_command($fp, 'A21', 'UID FETCH ' . $uid . ' (UID FLAGS BODY.PEEK[])');
	$items = ke_imap_parse_fetch($fetched['lines']);
	if ($fetched['ok'] && $items) {
		ke_imap_command($fp, 'A22', 'UID STORE ' . $uid . ' +FLAGS.SILENT (\Seen)');
	}
	ke_imap_close($fp);
	if (!$fetched['ok'] || !$items) {
		return ['ok' => false, 'error' => 'That message could not be opened.', 'message' => null];
	}
	return ['ok' => true, 'error' => '', 'message' => $items[0]];
}
