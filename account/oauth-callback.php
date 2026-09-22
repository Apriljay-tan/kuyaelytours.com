<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

$code = (string) ($_POST['code'] ?? $_GET['code'] ?? '');
$state = (string) ($_POST['state'] ?? $_GET['state'] ?? '');
$error = (string) ($_POST['error'] ?? $_GET['error'] ?? '');
$provider = (string) ($_SESSION['oauth_provider'] ?? '');
$next = store_oauth_safe_next((string) ($_SESSION['oauth_next'] ?? '/account/bookings.php'));
$known = (string) ($_SESSION['oauth_state'] ?? '');
if ($provider === 'facebook') {
	$next = store_safe_next($next, '/account/bookings.php');
}
$login = '/account/login.php?next=' . rawurlencode($next);

// TEMPORARY FB DEBUG: also diagnose callbacks whose provider is missing/invalid.
define('STORE_FB_DEBUG', $provider !== 'google');
store_fb_debug_secrets([$code, $state, $known, session_id()]);
if (STORE_FB_DEBUG) {
	error_log('[FB DEBUG] STATE_MATCH=' . ($state !== '' && $known !== '' && hash_equals($known, $state) ? 'YES' : 'NO'));
}
if (STORE_FB_DEBUG) {
	error_log('[FB DEBUG] CODE_PRESENT=' . ($code !== '' ? 'YES' : 'NO'));
}
if (STORE_FB_DEBUG) {
	error_log('[FB DEBUG] PROVIDER_VALID=' . (in_array($provider, ['google', 'facebook'], true) ? 'YES' : 'NO'));
}

unset($_SESSION['oauth_state'], $_SESSION['oauth_provider'], $_SESSION['oauth_next']);

if ($error !== '') {
	if (STORE_FB_DEBUG) {
		error_log('[FB DEBUG][FAIL] PROVIDER_DENIED');
	}
	store_redirect($login . '&err=social_denied');
}
if ($code === '' || $state === '' || $known === '' || !hash_equals($known, $state) || !in_array($provider, ['google', 'facebook'], true)) {
	if ($state === '' || $known === '' || !hash_equals($known, $state)) {
		if (STORE_FB_DEBUG) {
			error_log('[FB DEBUG][FAIL] STATE_MISMATCH');
		}
	}
	if ($code === '') {
		if (STORE_FB_DEBUG) {
			error_log('[FB DEBUG][FAIL] CODE_MISSING');
		}
	}
	if (!in_array($provider, ['google', 'facebook'], true)) {
		if (STORE_FB_DEBUG) {
			error_log('[FB DEBUG][FAIL] PROVIDER_INVALID');
		}
	}
	if (STORE_FB_DEBUG) {
		error_log('[FB DEBUG][FAIL] CALLBACK_VALIDATION');
	}
	store_redirect($login . '&err=social_failed');
}

try {
	store_fb_debug_stage('PROFILE');
	$profile = store_oauth_profile($provider, $code);
} catch (Throwable $e) {
	store_fb_debug_exception($e);
	throw $e; // Preserve existing exception behavior.
}
if (!$profile) {
	if (STORE_FB_DEBUG) {
		error_log('[FB DEBUG][FAIL] PROFILE_UNAVAILABLE');
	}
	store_redirect($login . '&err=social_failed');
}

try {
	$fail = store_login_oauth($provider, $profile);
} catch (Throwable $e) {
	store_fb_debug_exception($e);
	throw $e; // Preserve existing exception behavior.
}
if ($provider === 'facebook' && $fail === 'complete_profile') {
	store_facebook_begin_completion($profile, $next);
	store_fb_debug('COMPLETE_PROFILE_REQUIRED=YES');
	store_redirect('/account/complete-profile.php');
}
if ($fail !== '') {
	if (STORE_FB_DEBUG) {
		error_log('[FB DEBUG][FAIL] LOCAL_ACCOUNT_LOGIN');
	}
	store_redirect($login . '&err=social_failed');
}
if (STORE_FB_DEBUG) {
	error_log('[FB DEBUG] CALLBACK_SUCCESS=YES');
}
store_redirect($next);
