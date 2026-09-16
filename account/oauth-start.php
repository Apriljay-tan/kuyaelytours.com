<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

$provider = strtolower(trim((string) ($_GET['provider'] ?? '')));
$next = store_oauth_safe_next((string) ($_GET['next'] ?? '/account/bookings.php'));
$login = '/account/login.php?next=' . rawurlencode($next);

if (!in_array($provider, ['google', 'facebook'], true)) {
	store_redirect($login . '&err=social');
}
if (!store_oauth_enabled($provider)) {
	store_redirect($login . '&err=social_setup');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_provider'] = $provider;
$_SESSION['oauth_next'] = $next;
store_redirect(store_oauth_authorize_url($provider, $state));
