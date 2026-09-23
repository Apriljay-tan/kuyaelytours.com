<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (store_user()) {
	store_redirect('/account/bookings.php');
}

$sent = false;
$error = '';
$email = trim((string) ($_POST['email'] ?? ''));
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error = 'Enter the email on your account.';
	} else {
		store_password_reset_request($email);
		$sent = true;
	}
}

$form = $sent
	? '<p>If that email has a password on this site, we sent a reset link. It expires in one hour.</p>'
	: '<form method="post" class="ke-auth-form">'
		. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
		. store_field('Email', 'email', 'email', $email, 'mail', true, 'autocomplete="email"')
		. '<button class="ke-btn ke-auth-submit" type="submit">Send reset link</button>'
		. '</form>';

store_auth_page(
	'Forgot password',
	'Account',
	'Reset your password',
	$form,
	'<p class="ke-auth-alt"><a href="/account/login.php">Back to sign in</a></p>',
	$error,
	'Island days, done right',
	'We email a private link. It works only for accounts that use a password.'
);
