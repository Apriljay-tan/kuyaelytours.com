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
	return '';
}

function store_login_oauth(string $provider, array $profile): string
{
	$id = (string) ($profile['id'] ?? '');
	$email = strtolower(trim((string) ($profile['email'] ?? '')));
	$name = trim((string) ($profile['name'] ?? ''));
	if ($id === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		return 'Could not read that social account.';
	}
	$user = store_find_user_oauth($provider, $id) ?? store_find_user_email($email);
	if ($user) {
		$user['oauth_provider'] = $provider;
		$user['oauth_id'] = $id;
		if ($name !== '' && trim((string) ($user['name'] ?? '')) === '') {
			$user['name'] = $name;
		}
		store_update_user($user);
		$_SESSION['store_user'] = $user['id'];
		session_regenerate_id(true);
		return '';
	}
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
	if (!store_save_user($user)) {
		return 'Could not save the account. Please try again.';
	}
	$_SESSION['store_user'] = $user['id'];
	session_regenerate_id(true);
	return '';
}

function store_logout(): void
{
	unset($_SESSION['store_user']);
}
