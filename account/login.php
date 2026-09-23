<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (store_user()) {
	store_redirect('/account/bookings.php');
}

$error = '';
$next = store_safe_next((string) ($_GET['next'] ?? $_POST['next'] ?? '/account/bookings.php'), '/account/bookings.php');
$email = trim((string) ($_POST['email'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	if (!store_login_allowed()) {
		$error = 'Please wait a few minutes and try again.';
	} else {
		$error = store_login($email, (string) ($_POST['password'] ?? ''));
		if ($error === '') {
			store_login_clear();
			store_redirect($next);
		}
		store_login_fail();
	}
} else {
	$error = store_oauth_error_message((string) ($_GET['err'] ?? ''));
}

$form = '<form method="post" class="ke-auth-form">'
	. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
	. '<input type="hidden" name="next" value="' . store_h($next) . '">'
	. store_field('Email', 'email', 'email', $email, 'mail', true, 'autocomplete="email"')
	. store_field('Password', 'password', 'password', '', 'lock', true, 'autocomplete="current-password"')
	. '<button class="ke-btn ke-auth-submit" type="submit">Login</button>'
	. '<p class="ke-auth-alt"><a href="/account/forgot.php">Forgot password?</a></p>'
	. '</form>'
	. store_oauth_buttons($next);

$alt = '<p class="ke-auth-alt">Don\'t have an account? <a href="/account/register.php?next=' . rawurlencode($next) . '">Register now</a></p>';

store_auth_page(
	'Sign in',
	'Welcome',
	'Login with email',
	$form,
	$alt,
	$error,
	'Island days, done right',
	'Private tours and van rental from Mactan and Cebu — planned around your dates, hotel, and pace.'
);
