<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

$id = trim((string) ($_GET['booking'] ?? ''));
$booking = $id !== '' ? store_find_booking($id) : null;
$user = store_user();
$owner = $booking
	&& $user
	&& (string) ($booking['user_id'] ?? '') !== ''
	&& (string) $booking['user_id'] === (string) $user['id'];
if (!$booking || (!$owner && !store_booking_granted($id))) {
	store_redirect('/shop/cart.php');
}

$bundle = store_banks_bundle();
$due = store_booking_due_pesos($booking);
$total = (int) ($booking['total'] ?? 0);
$guestName = trim((string) ($booking['guest_name'] ?? ''));
$first = $guestName !== '' ? preg_split('/\s+/', $guestName)[0] : '';
$guest = store_h($first !== '' ? $first : 'there');
$ref = store_h((string) $booking['id']);
$email = store_h((string) ($booking['email'] ?? ''));

$accountNames = [];
foreach ($bundle['banks'] as $bank) {
	$who = trim((string) ($bank['account_name'] ?? ''));
	if ($who !== '') {
		$accountNames[$who] = true;
	}
}
$sharedName = count($accountNames) === 1 ? (string) array_key_first($accountNames) : '';

$banks = '';
foreach ($bundle['banks'] as $bank) {
	$logo = (string) ($bank['logo'] ?? '');
	$logoHtml = $logo !== ''
		? '<img class="ke-thanks-logo" src="' . store_h($logo) . '" alt="' . store_h((string) $bank['name']) . '">'
		: '<strong class="ke-thanks-bankname">' . store_h((string) $bank['name']) . '</strong>';
	$who = trim((string) ($bank['account_name'] ?? ''));
	$whoHtml = ($sharedName === '' && $who !== '')
		? '<span>Account name</span><strong>' . store_h($who) . '</strong>'
		: '';
	$banks .= '<div class="ke-thanks-acct">'
		. $logoHtml
		. '<div><span>Acct#</span><strong>' . store_h((string) $bank['account_number']) . '</strong>'
		. $whoHtml
		. '</div></div>';
}
if ($banks === '') {
	$banks = '<p>Bank details are not posted yet. Email info@kuyaelytours.com and include your booking reference.</p>';
}

$lines = '';
$packageNames = [];
foreach ((array) ($booking['items'] ?? []) as $item) {
	if (!is_array($item)) {
		continue;
	}
	$label = trim((string) ($item['label'] ?? ''));
	if ($label !== '') {
		$packageNames[$label] = true;
	}
	$guests = max(1, (int) ($item['guests'] ?? $item['qty'] ?? 1));
	$lines .= '<div class="ke-thanks-item"><div><strong>' . store_h((string) ($item['label'] ?? 'Tour')) . '</strong>'
		. '<span>Tour date: ' . store_h((string) ($item['date'] ?? '')) . '</span>'
		. '<span>Guests: ' . $guests . '</span></div>'
		. '<b>' . store_h(store_money(store_line_total($item))) . '</b></div>';
}

$method = (string) ($booking['pay_method'] ?? '');
$methodLabel = $method === 'paypal' ? 'PayPal' : ($method === 'bank_transfer' ? 'Bank transfer or GCash' : 'QR Ph');

$body = '<div class="ke-thanks-wrap">'
	. '<div class="ke-thanks-check" aria-hidden="true"><svg viewBox="0 0 64 64"><circle cx="32" cy="32" r="30" fill="#22a45a"/><path d="M18 33.5 27.5 43 46 22" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>'
	. '<h1>Thank you for your booking, ' . $guest . '</h1>'
	. '<p class="ke-thanks-lead">We saved booking <strong>' . $ref . '</strong>'
	. ($email !== '' ? ' and an email is on its way to <strong>' . $email . '</strong>.' : '.')
	. ' Check your spam folder if it is not in your inbox yet.</p>'
	. '<div class="ke-thanks-next"><strong>What\'s next?</strong> ' . nl2br(store_h((string) $bundle['note'])) . '</div>'
	. '<div class="ke-thanks-grid"><section class="ke-thanks-card"><h2>Booking details</h2>'
	. $lines
	. '<p class="ke-thanks-line"><span>Subtotal</span><strong>' . store_h(store_money($total)) . '</strong></p>'
	. '<p class="ke-thanks-line"><span>Payment method</span><strong>' . store_h($methodLabel) . '</strong></p>'
	. ($due < $total ? '<p class="ke-thanks-line"><span>Due now</span><strong>' . store_h(store_money($due)) . '</strong></p>' : '')
	. '<p class="ke-thanks-total"><span>Total</span><strong>' . store_h(store_money($due < $total ? $due : $total)) . '</strong></p>'
	. '</section><section class="ke-thanks-card"><h2>Our bank details</h2>'
	. ($sharedName !== '' ? '<p class="ke-thanks-who">Account name: <strong>' . store_h($sharedName) . '</strong></p>' : '')
	. $banks
	. '<p class="ke-thanks-ref">Put booking <strong>' . $ref . '</strong> in the transfer note if the bank allows it.</p>'
	. '</section></div>'
	. '<a class="ke-thanks-back" href="/tours-and-packages.php">Back to tours</a>'
	. '</div>';

$tourPackage = $packageNames !== [] ? implode(', ', array_keys($packageNames)) : 'Tour';
$lead = json_encode([
	'event' => 'generate_lead',
	'form_name' => 'checkout_booking',
	'tour_package' => $tourPackage,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
$leadKey = json_encode('generate_lead:' . (string) $booking['id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
if ($lead !== false && $leadKey !== false) {
	$body .= '<script>window.dataLayer=window.dataLayer||[];(function(){var key=' . $leadKey . ';try{if(sessionStorage.getItem(key))return;sessionStorage.setItem(key,"1");}catch(e){}window.dataLayer.push(' . $lead . ');})();</script>';
}

store_page('Thank you', $body, '', true, 'ke-site ke-dash ke-secure ke-thanks');
