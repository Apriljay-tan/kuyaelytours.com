<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
store_require_login('/account/bookings.php');
$user = store_user();
if (!$user) {
	store_redirect('/account/login.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$id = (string) ($_POST['cancel'] ?? '');
	$booking = $id !== '' ? store_find_booking($id) : null;
	if ($booking && ($booking['user_id'] ?? '') === $user['id'] && in_array($booking['status'], ['pending_request', 'awaiting_payment'], true)) {
		store_cancel_booking($booking);
	}
	store_redirect('/account/bookings.php');
}

$list = '';
foreach (store_user_bookings((string) $user['id']) as $booking) {
	$list .= '<article class="ke-line"><div><h2>' . store_h(store_booking_status_label((string) $booking['status'])) . '</h2>'
		. '<p>' . store_h((string) $booking['created']) . ' · ' . store_money((int) $booking['total']) . ' · ID ' . store_h((string) $booking['id']) . '</p></div><div class="ke-line-side">';
	if (($booking['status'] ?? '') === 'awaiting_payment') {
		$list .= '<a class="ke-btn" href="/shop/pay.php?booking=' . store_h((string) $booking['id']) . '">Pay now</a>';
	}
	if (in_array($booking['status'] ?? '', ['pending_request', 'awaiting_payment'], true)) {
		$list .= '<form method="post"><input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
			. '<button class="ke-text" name="cancel" value="' . store_h((string) $booking['id']) . '">Cancel request</button></form>';
	}
	$list .= '</div></article>';
}

$notice = '';
if (($_GET['placed'] ?? '') === '1') {
	$notice = '<p class="ke-ok">Request sent. We emailed a confirmation and our team will reply with the final rate.</p>';
}
if (($_GET['paid'] ?? '') === '1') {
	$notice = '<p class="ke-ok">Payment recorded. Staff will still confirm the van and date.</p>';
}

store_page(
	'My bookings',
	$notice . '<h1>My bookings</h1><p class="lede"><a href="/account/profile.php">Profile</a> · <a href="/account/logout.php">Sign out</a></p>'
	. ($list !== '' ? $list : '<p class="lede">No bookings yet.</p><p><a class="ke-btn" href="/shop/catalog.php">Browse tours</a></p>'),
	'Account'
);
