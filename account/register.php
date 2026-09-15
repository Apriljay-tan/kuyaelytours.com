<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (store_user()) {
	store_redirect('/account/bookings.php');
}

$error = '';
$next = (string) ($_GET['next'] ?? $_POST['next'] ?? '/shop/checkout.php');
if ($next === '' || $next[0] !== '/') {
	$next = '/shop/checkout.php';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$error = store_register(
		(string) ($_POST['name'] ?? ''),
		(string) ($_POST['email'] ?? ''),
		(string) ($_POST['phone'] ?? ''),
		(string) ($_POST['password'] ?? '')
	);
	if ($error === '') {
		store_redirect($next);
	}
}

$body = '<h1>Create account</h1><p class="lede">Save your trips, book vans, and get confirmation emails from info@kuyaelytours.com.</p>';
if ($error !== '') {
	$body .= '<p class="ke-err">' . store_h($error) . '</p>';
}
$body .= '<form method="post">'
	. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
	. '<input type="hidden" name="next" value="' . store_h($next) . '">'
	. '<label>Full name<input type="text" name="name" required></label>'
	. '<label>Email<input type="email" name="email" required></label>'
	. '<label>Phone / WhatsApp<input type="tel" name="phone" required></label>'
	. '<label>Password<input type="password" name="password" minlength="10" required></label>'
	. '<button class="ke-btn" type="submit">Create account</button></form>'
	. '<p>Already have an account? <a href="/account/login.php">Sign in</a></p>';

store_page('Create account', $body, 'Account');
