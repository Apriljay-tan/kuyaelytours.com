<?php
declare(strict_types=1);

function store_user(): ?array
{
	$id = (string) ($_SESSION['store_user'] ?? '');
	if ($id === '') {
		return null;
	}
	return store_find_user_id($id);
}

function store_require_login(string $next = '/shop/checkout.php'): void
{
	if (store_user()) {
		return;
	}
	store_redirect('/account/login.php?next=' . rawurlencode($next));
}

function store_after_login(): void
{
	if (function_exists('store_cart_count') && store_cart_count() < 1 && function_exists('store_saved_cart')) {
		$user = store_user();
		if ($user) {
			$saved = store_saved_cart((string) $user['id']);
			if ($saved && function_exists('store_cart_save')) {
				store_cart_save($saved);
				return;
			}
		}
	}
	if (function_exists('store_persist_user_cart')) {
		store_persist_user_cart();
	}
}

function store_register(string $name, string $email, string $phone, string $password): string
{
	$name = trim($name);
	$email = strtolower(trim($email));
	$phone = trim($phone);
	if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return 'Enter a valid name and email.';
	}
	if (strlen($password) < 10) {
		return 'Use at least 10 characters for the password.';
	}
	if (store_find_user_email($email)) {
		return 'That email already has an account. Please sign in.';
	}
	$user = [
		'id' => store_id(),
		'name' => $name,
		'email' => $email,
		'phone' => $phone,
		'password_hash' => password_hash($password, PASSWORD_DEFAULT),
		'oauth_provider' => '',
		'oauth_id' => '',
		'created' => store_now(),
	];
	if (!store_save_user($user)) {
		return 'Could not save the account. Please try again.';
	}
	$_SESSION['store_user'] = $user['id'];
	session_regenerate_id(true);
	store_after_login();
	return '';
}

function store_login(string $email, string $password): string
{
	$user = store_find_user_email($email);
	$hash = (string) ($user['password_hash'] ?? '');
	if (!$user || $hash === '' || !password_verify($password, $hash)) {
		return 'Those details are not correct.';
	}
	$_SESSION['store_user'] = $user['id'];
	session_regenerate_id(true);
	store_after_login();
	return '';
}

function store_login_oauth(string $provider, array $profile): string
{
	if ($provider === 'facebook') {
		require_once __DIR__ . '/facebook.php';
		return store_login_facebook($profile);
	}
	// TEMPORARY FB DEBUG: observe failures without changing login decisions.
	store_fb_debug_stage('ACCOUNT_LINKING');
	$id = (string) ($profile['id'] ?? '');
	$email = strtolower(trim((string) ($profile['email'] ?? '')));
	$name = trim((string) ($profile['name'] ?? ''));
	if ($id === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		store_fb_debug('[FAIL] ACCOUNT_LINKING INVALID_PROFILE');
		store_fb_debug('EMAIL_VALID=' . (filter_var($email, FILTER_VALIDATE_EMAIL) ? 'YES' : 'NO'));
		return 'Could not read that social account.';
	}
	store_fb_debug_stage('DATABASE');
	$oauthUser = store_find_user_oauth($provider, $id);
	$user = $oauthUser ?? store_find_user_email($email);
	store_fb_debug('OAUTH_USER_FOUND=' . ($oauthUser ? 'YES' : 'NO'));
	if (defined('STORE_FB_DEBUG') && STORE_FB_DEBUG) {
		// The original lookup skips email when an OAuth link already exists.
		// This extra read is diagnostic only and must not change account choice.
		try {
			$emailUser = $oauthUser !== null ? store_find_user_email($email) : $user;
			store_fb_debug('EXISTING_USER=' . ($emailUser ? 'YES' : 'NO'));
		} catch (Throwable $e) {
			store_fb_debug('EXISTING_USER=UNKNOWN');
			store_fb_debug_exception($e, 'DATABASE EMAIL_LOOKUP_DIAGNOSTIC');
		}
		store_fb_debug('STORAGE_BACKEND=' . (store_db() ? 'PDO' : 'JSON'));
	}
	if ($user) {
		$user['oauth_provider'] = $provider;
		$user['oauth_id'] = $id;
		if ($name !== '' && trim((string) ($user['name'] ?? '')) === '') {
			$user['name'] = $name;
		}
		store_fb_debug_stage('ACCOUNT_LINKING');
		store_fb_debug('DATABASE_OPERATION=UPDATE_LINK');
		$updated = store_update_user($user);
		store_fb_debug('DATABASE_SUCCESS=' . ($updated ? 'YES' : 'NO'));
		if (!$updated) {
			store_fb_debug('[FAIL] DATABASE UPDATE');
			store_fb_debug('[FAIL] ACCOUNT_LINKING');
		}
		// Existing behavior deliberately preserved: update failure does not abort.
		store_fb_debug_stage('LOGIN_SESSION');
		$_SESSION['store_user'] = $user['id'];
		$regenerated = session_regenerate_id(true);
		store_fb_debug('SESSION_REGENERATED=' . ($regenerated ? 'YES' : 'NO'));
		$sessionCreated = session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['store_user']) && $_SESSION['store_user'] === $user['id'];
		store_fb_debug('LOGIN_SESSION_CREATED=' . ($sessionCreated ? 'YES' : 'NO'));
		if (!$regenerated || !$sessionCreated) {
			store_fb_debug('[FAIL] LOGIN_SESSION');
		}
		store_fb_debug_stage('AFTER_LOGIN');
		store_after_login();
		store_fb_debug('AFTER_LOGIN_SUCCESS=YES');
		return '';
	}
	store_fb_debug_stage('ACCOUNT_CREATION');
	$user = [
		'id' => store_id(),
		'name' => $name !== '' ? $name : explode('@', $email)[0],
		'email' => $email,
		'phone' => '',
		'password_hash' => '',
		'oauth_provider' => $provider,
		'oauth_id' => $id,
		'created' => store_now(),
	];
	store_fb_debug_stage('DATABASE');
	store_fb_debug('DATABASE_OPERATION=INSERT');
	if (!store_save_user($user)) {
		store_fb_debug('DATABASE_SUCCESS=NO');
		store_fb_debug('[FAIL] DATABASE INSERT');
		return 'Could not save the account. Please try again.';
	}
	store_fb_debug('DATABASE_SUCCESS=YES');
	store_pixel_push('CompleteRegistration', [
		'content_name' => 'Account',
		'status' => $provider,
	]);
	store_fb_debug_stage('LOGIN_SESSION');
	$_SESSION['store_user'] = $user['id'];
	$regenerated = session_regenerate_id(true);
	store_fb_debug('SESSION_REGENERATED=' . ($regenerated ? 'YES' : 'NO'));
	$sessionCreated = session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['store_user']) && $_SESSION['store_user'] === $user['id'];
	store_fb_debug('LOGIN_SESSION_CREATED=' . ($sessionCreated ? 'YES' : 'NO'));
	if (!$regenerated || !$sessionCreated) {
		store_fb_debug('[FAIL] LOGIN_SESSION');
	}
	store_fb_debug_stage('AFTER_LOGIN');
	store_after_login();
	store_fb_debug('AFTER_LOGIN_SUCCESS=YES');
	return '';
}

function store_logout(): void
{
	unset($_SESSION['store_user']);
	$_SESSION['cart'] = [];
	unset($_SESSION['promo']);
}

function store_password_reset_request(string $email): void
{
	$email = strtolower(trim($email));
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return;
	}
	$tries = (int) ($_SESSION['ke_reset_tries'] ?? 0);
	if ($tries > 5) {
		return;
	}
	$_SESSION['ke_reset_tries'] = $tries + 1;
	$user = store_find_user_email($email);
	if (!$user || (string) ($user['password_hash'] ?? '') === '') {
		return;
	}
	$token = bin2hex(random_bytes(32));
	$now = time();
	$kept = [];
	foreach (store_read_json('password_resets') as $row) {
		if (is_array($row) && (int) ($row['exp'] ?? 0) > $now && (string) ($row['email'] ?? '') !== $email) {
			$kept[] = $row;
		}
	}
	$kept[] = [
		'email' => $email,
		'hash' => hash('sha256', $token),
		'exp' => $now + 3600,
	];
	store_write_json('password_resets', $kept);
	$link = 'https://kuyaelytours.com/account/reset.php?token=' . rawurlencode($token);
	$mailer = STORE_SITE . '/ke-mail.php';
	if (!is_file($mailer)) {
		return;
	}
	require_once $mailer;
	if (function_exists('ke_send_mail')) {
		ke_send_mail(
			$email,
			'Reset your Kuya Ely Tours password',
			"Open this link within one hour to choose a new password:\n\n" . $link . "\n\nIf you did not ask for this, you can ignore this email.",
			KE_MAIL_FROM
		);
	}
}

function store_password_reset_apply(string $token, string $password): string
{
	$token = trim($token);
	if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
		return 'This reset link is not valid. Ask for a new one.';
	}
	if (strlen($password) < 10) {
		return 'Use at least 10 characters for the password.';
	}
	$hash = hash('sha256', $token);
	$now = time();
	$email = '';
	$kept = [];
	foreach (store_read_json('password_resets') as $row) {
		if (!is_array($row) || (int) ($row['exp'] ?? 0) <= $now) {
			continue;
		}
		if ($email === '' && hash_equals((string) ($row['hash'] ?? ''), $hash)) {
			$email = strtolower((string) ($row['email'] ?? ''));
			continue;
		}
		$kept[] = $row;
	}
	store_write_json('password_resets', $kept);
	if ($email === '') {
		return 'This reset link has expired. Ask for a new one.';
	}
	$user = store_find_user_email($email);
	if (!$user || !store_set_user_password((string) $user['id'], $password)) {
		return 'Could not update that password. Please try again.';
	}
	$_SESSION['store_user'] = $user['id'];
	session_regenerate_id(true);
	store_after_login();
	return '';
}
