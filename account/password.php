<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
store_require_login('/account/password.php');
$user = store_user();
if (!$user) {
	store_redirect('/account/login.php');
}

$error = '';
$must = store_password_change_required();
$hash = (string) ($user['password_hash'] ?? '');
$needCurrent = !$must && $hash !== '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$current = (string) ($_POST['current'] ?? '');
	$next = (string) ($_POST['password'] ?? '');
	$again = (string) ($_POST['confirm'] ?? '');
	if ($needCurrent && !password_verify($current, $hash)) {
		$error = 'The current password is not correct.';
	} elseif (strlen($next) < 10) {
		$error = 'Use at least 10 characters.';
	} elseif ($next !== $again) {
		$error = 'Those passwords do not match.';
	} elseif ($hash !== '' && password_verify($next, $hash)) {
		$error = 'Choose a different password than the one you just used.';
	} elseif (!store_set_user_password((string) $user['id'], $next)) {
		$error = 'Could not save that password. Please try again.';
	} else {
		store_redirect('/account/bookings.php?ready=1');
	}
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	$error = 'Please try that again.';
}

$csrf = store_h(store_csrf_token());
$lede = $must
	? 'This is a temporary password from Kuya Ely Tours. Choose one you will remember before opening your trips.'
	: 'Choose a new password for ' . (string) $user['email'] . '.';
$form = '<form method="post" class="ke-pass-form">';
$form .= '<input type="hidden" name="csrf" value="' . $csrf . '">';
if ($needCurrent) {
	$form .= '<label>Current password<input type="password" name="current" autocomplete="current-password" required></label>';
}
$form .= '<label>New password<input type="password" name="password" minlength="10" autocomplete="new-password" required></label>';
$form .= '<label>Confirm password<input type="password" name="confirm" minlength="10" autocomplete="new-password" required></label>';
$form .= '<p class="ke-pass-hint">Use at least 10 characters.</p>';
$form .= '<button class="ke-desk-btn" type="submit">Save password</button>';
if (!$must) {
	$form .= '<a class="ke-pass-back" href="/account/bookings.php">Back to my bookings</a>';
}
$form .= '</form>';
$html = '<section class="ke-pass-card"><p class="ke-desk-kicker">My account</p><h1>Choose a password</h1><p>'
	. store_h($lede) . '</p><p class="ke-pass-email">' . store_h((string) $user['email']) . '</p>';
if ($error !== '') {
	$html .= '<p class="ke-desk-err">' . store_h($error) . '</p>';
}
$html .= $form . '</section>';
store_page('Choose a password', $html, '', true, 'ke-site ke-dash ke-pass');
