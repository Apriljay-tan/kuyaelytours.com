<?php
declare(strict_types=1);

// Shared anti-spam helpers for public form handlers (send-inquiry.php).
// Data files live in admin/data (blocked from the web by admin/data/.htaccess).

const KE_AS_DIR = __DIR__ . '/admin/data';
const KE_AS_MIN_SECONDS = 3;
const KE_AS_MAX_TOKEN_AGE = 172800; // 48h

function ke_as_secret(): string
{
	$file = KE_AS_DIR . '/form-secret.local.php';
	if (is_file($file)) {
		$s = include $file;
		if (is_string($s) && strlen($s) >= 32) {
			return $s;
		}
	}
	if (!is_dir(KE_AS_DIR)) {
		@mkdir(KE_AS_DIR, 0750, true);
	}
	$s = bin2hex(random_bytes(32));
	@file_put_contents($file, "<?php\nreturn '" . $s . "';\n", LOCK_EX);
	@chmod($file, 0640);
	return $s;
}

function ke_as_make_token(): string
{
	$ts = (string) time();
	$nonce = bin2hex(random_bytes(6));
	$sig = substr(hash_hmac('sha256', $ts . '.' . $nonce, ke_as_secret()), 0, 32);
	return $ts . '.' . $nonce . '.' . $sig;
}

/** Returns seconds since the token was issued, or null if missing/invalid/expired. */
function ke_as_token_age(string $token): ?int
{
	if (!preg_match('/^(\d{9,11})\.([a-f0-9]{12})\.([a-f0-9]{32})$/', $token, $m)) {
		return null;
	}
	$expect = substr(hash_hmac('sha256', $m[1] . '.' . $m[2], ke_as_secret()), 0, 32);
	if (!hash_equals($expect, $m[3])) {
		return null;
	}
	$age = time() - (int) $m[1];
	if ($age < 0 || $age > KE_AS_MAX_TOKEN_AGE) {
		return null;
	}
	return $age;
}

function ke_as_ip_in_cidr(string $ip, string $cidr): bool
{
	[$net, $bits] = explode('/', $cidr) + [1 => ''];
	$ipBin = @inet_pton($ip);
	$netBin = @inet_pton($net);
	if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
		return false;
	}
	$bits = (int) $bits;
	$bytes = intdiv($bits, 8);
	if (substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
		return false;
	}
	$rem = $bits % 8;
	if ($rem === 0) {
		return true;
	}
	$mask = (0xFF << (8 - $rem)) & 0xFF;
	return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
}

/** Client IP. Trusts CF-Connecting-IP only when the request really came from Cloudflare (or a private proxy). */
function ke_as_client_ip(?string &$source = null): string
{
	$remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '0');
	$cf = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
	$source = 'remote';
	if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
		$trusted = !filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
		if (!$trusted) {
			$ranges = [
				'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
				'108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
				'162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
				'2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
				'2a06:98c0::/29', '2c0f:f248::/32',
			];
			foreach ($ranges as $r) {
				if (ke_as_ip_in_cidr($remote, $r)) {
					$trusted = true;
					break;
				}
			}
		}
		if ($trusted) {
			$source = 'cf';
			return $cf;
		}
	}
	return $remote;
}

/** Per-IP rate limit (IP stored only as a hash). Returns false when over the limit. */
function ke_as_rate_ok(string $ip, int $max = 5, int $window = 3600): bool
{
	$key = substr(hash('sha256', 'ke-rl|' . $ip), 0, 24);
	$file = KE_AS_DIR . '/inquiry-rate.json';
	if (!is_dir(KE_AS_DIR) && !@mkdir(KE_AS_DIR, 0750, true) && !is_dir(KE_AS_DIR)) {
		return true;
	}
	$fp = @fopen($file, 'c+');
	if ($fp === false) {
		return true;
	}
	$ok = true;
	if (flock($fp, LOCK_EX)) {
		$data = json_decode((string) stream_get_contents($fp), true);
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

function ke_as_has_url(string $s): bool
{
	return (bool) preg_match(
		'~(https?:|hxxps?:|www\.|\b[a-z0-9-]+\.(buzz|xyz|top|ru|su|click|link|online|site|icu|cfd|sbs|monster|rest|bond|shop|store|live|info|biz|cc|tk|ml|ga|cf|gq|pw|ws|lol|quest|today|my\.id)\b|\[url|<a\s)~iu',
		$s
	);
}

function ke_as_has_cyrillic(string $s): bool
{
	return (bool) preg_match('/\p{Cyrillic}/u', $s);
}

/** True for a long run of characters with no spaces that looks like keyboard noise (e.g. "kdsjfhKJHkjh234kj"). */
function ke_as_looks_random(string $s): bool
{
	$s = trim($s);
	if ($s === '' || preg_match('/\s/u', $s)) {
		return false;
	}
	$len = function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
	if ($len < 10) {
		return false;
	}
	// Phone numbers / dates / numbers alone are fine.
	if (preg_match('/^[\d+\-().\/]+$/', $s)) {
		return false;
	}
	return true;
}

/** Returns a short reason string if the submission is spam, or '' if it looks legit. */
function ke_as_content_reason(string $name, string $message, string $email, string $phone): string
{
	$nameLen = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
	if ($nameLen > 60) {
		return 'name-long';
	}
	if (ke_as_has_url($name) || ke_as_has_url($message)) {
		return 'url';
	}
	if (ke_as_has_cyrillic($name) || ke_as_has_cyrillic($message) || ke_as_has_cyrillic($phone)) {
		return 'cyrillic';
	}
	if (ke_as_looks_random($message)) {
		return 'random-message';
	}
	$domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));
	if ($domain !== '' && preg_match('/(^|\.)(gelml|geiiml)\.my\.id$/', $domain)) {
		return 'email-domain';
	}
	return '';
}

/** Minimal log of blocked attempts (no guest data; IP hashed). */
function ke_as_log(string $reason, string $ipSource = ''): void
{
	$line = gmdate('Y-m-d H:i:s') . " UTC\t" . $reason . "\t" . $ipSource . "\n";
	$file = KE_AS_DIR . '/spam-blocked.log';
	if (is_file($file) && filesize($file) > 512000) {
		@rename($file, $file . '.old');
	}
	@file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}
