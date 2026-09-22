<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require_once dirname(__DIR__) . '/store/facebook.php';

// TEMPORARY: retain the same safe Facebook diagnostics during profile completion.
define('STORE_FB_DEBUG', true);
header('Cache-Control: no-store');
$pending = store_facebook_pending();
$error = '';
$form = '';
$alt = '';
if (!$pending) {
	$error = 'Facebook sign-in expired. Please start again.';
	$alt = '<p class="ke-auth-alt"><a href="/account/oauth-start.php?provider=facebook">Start Facebook sign-in again</a></p>';
} else {
	$next = store_safe_next((string) $pending['next'], '/account/bookings.php');
	$current = store_user();
	$email = trim((string) ($_POST['email'] ?? ($pending['email'] !== '' ? $pending['email'] : ($current['email'] ?? ''))));
	if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
		if (!store_csrf_ok()) {
			$error = 'Your form expired. Please try again.';
		} elseif (($_POST['action'] ?? '') === 'cancel') {
			unset($_SESSION['facebook_completion']);
			store_redirect('/account/login.php');
		} else {
			$error = store_complete_facebook($email, (string) ($_POST['password'] ?? ''));
			if ($error === '') {
				store_fb_debug('COMPLETE_PROFILE_SUCCESS=YES');
				store_redirect($next);
			}
		}
	}
	$form = '<form method="post" action="/account/complete-profile.php" class="ke-auth-form">'
		. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
		. '<p class="ke-hint">Enter your email to finish signing in with Facebook. If you already have an account, confirm ownership before linking it.</p>'
		. store_field('Email', 'email', 'email', $email, 'mail', true, 'maxlength="190" autocomplete="email"')
		. store_field('Existing account password (if needed)', 'password', 'password', '', 'lock', false, 'autocomplete="current-password"')
		. '<p class="ke-hint">For an existing account, enter its password or sign in using the link below, then return here to confirm. No password is needed for a new account.</p>'
		. '<button class="ke-btn ke-auth-submit" type="submit">Continue with Facebook</button>'
		. '<button class="ke-btn" type="submit" name="action" value="cancel" formnovalidate>Cancel</button>'
		. '</form>';
	$alt = '<p class="ke-auth-alt"><a href="/account/login.php?next=%2Faccount%2Fcomplete-profile.php">Sign in to an existing account</a></p>';
}
store_auth_page('Complete your profile', 'One more step', 'Finish Facebook sign-in', $form, $alt, $error);
