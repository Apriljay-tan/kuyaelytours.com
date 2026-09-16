<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

$code = (string) ($_POST['code'] ?? $_GET['code'] ?? '');
$state = (string) ($_POST['state'] ?? $_GET['state'] ?? '');
$error = (string) ($_POST['error'] ?? $_GET['error'] ?? '');
$provider = (string) ($_SESSION['oauth_provider'] ?? '');
$next = store_oauth_safe_next((string) ($_SESSION['oauth_next'] ?? '/account/bookings.php'));
$known = (string) ($_SESSION['oauth_state'] ?? '');
$login = '/account/login.php?next=' . rawurlencode($next);

unset($_SESSION['oauth_state'], $_SESSION['oauth_provider'], $_SESSION['oauth_next']);

if ($error !== '') {
	store_redirect($login . '&err=social_denied');
}
if ($code === '' || $state === '' || $known === '' || !hash_equals($known, $state) || !in_array($provider, ['google', 'facebook'], true)) {
	store_redirect($login . '&err=social_failed');
}

$profile = store_oauth_profile($provider, $code);
if (!$profile) {
	store_redirect($login . '&err=social_failed');
}

$fail = store_login_oauth($provider, $profile);
if ($fail !== '') {
	store_redirect($login . '&err=social_failed');
}
store_redirect($next);
