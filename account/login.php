<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (store_user()) {
	store_redirect('/account/bookings.php');
}

$error = '';
$next = (string) ($_GET['next'] ?? $_POST['next'] ?? '/account/bookings.php');
if ($next === '' || $next[0] !== '/') {
	$next = '/account/bookings.php';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$error = store_login((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
	if ($error === '') {
		store_redirect($next);
	}
}

$body = '<h1>Sign in</h1><p class="lede">Use your Kuya Ely Tours account to checkout, Book now, or Pay now.</p>';
if ($error !== '') {
	$body .= '<p class="ke-err">' . store_h($error) . '</p>';
}
$body .= '<form method="post">'
	. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
	. '<input type="hidden" name="next" value="' . store_h($next) . '">'
	. '<label>Email<input type="email" name="email" required></label>'
	. '<label>Password<input type="password" name="password" required></label>'
	. '<button class="ke-btn" type="submit">Sign in</button></form>'
	. '<p>New guest? <a href="/account/register.php?next=' . rawurlencode($next) . '">Create account</a></p>';

store_page('Sign in', $body, 'Account');
