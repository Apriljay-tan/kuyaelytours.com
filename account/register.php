<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (store_user()) {
	store_redirect('/account/bookings.php');
}

$error = '';
$next = store_safe_next((string) ($_GET['next'] ?? $_POST['next'] ?? '/shop/checkout.php'), '/shop/checkout.php');
$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$error = store_register($name, $email, $phone, (string) ($_POST['password'] ?? ''));
	if ($error === '') {
		store_redirect($next);
	}
} else {
	$error = store_oauth_error_message((string) ($_GET['err'] ?? ''));
}

$form = '<form method="post" class="ke-auth-form">'
	. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
	. '<input type="hidden" name="next" value="' . store_h($next) . '">'
	. store_field('Full name', 'name', 'text', $name, 'user', true, 'autocomplete="name"')
	. store_field('Email', 'email', 'email', $email, 'mail', true, 'autocomplete="email"')
	. store_field('Phone / WhatsApp', 'phone', 'tel', $phone, 'phone', true, 'autocomplete="tel"')
	. store_field('Password', 'password', 'password', '', 'lock', true, 'minlength="10" autocomplete="new-password"')
	. '<p class="ke-hint">Use at least 10 characters.</p>'
	. '<button class="ke-btn ke-auth-submit" type="submit">Create account</button>'
	. '</form>'
	. store_oauth_buttons($next);

$alt = '<p class="ke-auth-alt">Already have an account? <a href="/account/login.php?next=' . rawurlencode($next) . '">Login</a></p>';

store_auth_page(
	'Create account',
	'Join the trip',
	'Create your Kuya Ely account',
	$form,
	$alt,
	$error,
	'Your dates. Our vans.',
	'Save bookings, get confirmation from info@kuyaelytours.com, and pick up from Mactan or Cebu.'
);
