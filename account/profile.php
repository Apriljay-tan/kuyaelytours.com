<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
store_require_login('/account/profile.php');
$user = store_user();
if (!$user) {
	store_redirect('/account/login.php');
}

$ok = '';
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$user['name'] = trim((string) ($_POST['name'] ?? $user['name']));
	$user['phone'] = trim((string) ($_POST['phone'] ?? $user['phone']));
	if ($user['name'] === '') {
		$error = 'Name is required.';
	} elseif (store_update_user($user)) {
		$ok = 'Profile saved.';
	} else {
		$error = 'Could not save your profile.';
	}
}

$body = '<h1>Profile</h1>';
if ($ok !== '') {
	$body .= '<p class="ke-ok">' . store_h($ok) . '</p>';
}
if ($error !== '') {
	$body .= '<p class="ke-err">' . store_h($error) . '</p>';
}
$body .= '<form method="post">'
	. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
	. '<label>Name<input type="text" name="name" value="' . store_h((string) $user['name']) . '" required></label>'
	. '<label>Email<input type="email" value="' . store_h((string) $user['email']) . '" disabled></label>'
	. '<label>Phone<input type="tel" name="phone" value="' . store_h((string) ($user['phone'] ?? '')) . '"></label>'
	. '<button class="ke-btn" type="submit">Save</button></form>'
	. '<p><a href="/account/bookings.php">My bookings</a> · <a href="/account/logout.php">Sign out</a></p>';

store_page('Profile', $body, 'Account');
