<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (store_user()) {
	store_redirect('/account/bookings.php');
}

$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$password = (string) ($_POST['password'] ?? '');
	$confirm = (string) ($_POST['confirm'] ?? '');
	if ($password !== $confirm) {
		$error = 'Those passwords do not match.';
	} else {
		$error = store_password_reset_apply($token, $password);
		if ($error === '') {
			store_redirect('/account/bookings.php');
		}
	}
}

$form = '<form method="post" class="ke-auth-form">'
	. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
	. '<input type="hidden" name="token" value="' . store_h($token) . '">'
	. store_field('New password', 'password', 'password', '', 'lock', true, 'minlength="10" autocomplete="new-password"')
	. store_field('Confirm password', 'confirm', 'password', '', 'lock', true, 'minlength="10" autocomplete="new-password"')
	. '<button class="ke-btn ke-auth-submit" type="submit">Save password</button>'
	. '</form>';

store_auth_page(
	'Choose a new password',
	'Account',
	'Choose a new password',
	$form,
	'<p class="ke-auth-alt"><a href="/account/forgot.php">Send a new link</a></p>',
	$error,
	'Island days, done right',
	'Use at least 10 characters. This link works once.'
);
