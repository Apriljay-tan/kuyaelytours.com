<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require dirname(__DIR__) . '/store/tours-data.php';

$user = store_user();
$error = '';
$booking = null;
$bookingId = trim((string) ($_GET['booking'] ?? ''));
if ($bookingId !== '') {
	$found = store_find_booking($bookingId);
	$owner = $found && $user && (string) ($found['user_id'] ?? '') !== '' && (string) $found['user_id'] === (string) $user['id'];
	if ($found && ($owner || store_booking_granted($bookingId))) {
		$booking = $found;
	}
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok() && !$booking && isset($_POST['promo'])) {
	store_promo_apply((string) ($_POST['code'] ?? ''));
	store_redirect('/shop/checkout.php?promo=1');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok() && $booking && (string) ($_POST['action'] ?? '') === 'pay') {
	$payableNow = (int) ($booking['total'] ?? 0);
	$due = (int) ($_SESSION['pay_due'][$bookingId] ?? 0);
	if ($due < 100 && preg_match('/Due now ₱([0-9,]+)/', (string) ($booking['notes'] ?? ''), $dueMatch)) {
		$due = (int) str_replace(',', '', $dueMatch[1]);
	}
	if ($due < 100) {
		$due = $payableNow;
	}
	$url = store_paymongo_checkout($booking, $due);
	if ($url === '') {
		$error = store_paymongo_last_error() !== '' ? store_paymongo_last_error() : 'PayMongo did not open.';
	} else {
		store_redirect($url);
	}
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok() && !$booking) {
	if (store_cart_count() === 0) {
		store_redirect('/shop/cart.php');
	}
	$first = trim((string) ($_POST['first_name'] ?? ''));
	$last = trim((string) ($_POST['last_name'] ?? ''));
	$email = trim((string) ($_POST['email'] ?? ''));
	$phone = trim((string) ($_POST['phone'] ?? ''));
	$hotel = trim((string) ($_POST['hotel'] ?? ''));
	$notes = trim((string) ($_POST['notes'] ?? ''));
	$plan = (string) ($_POST['plan'] ?? 'full') === 'half' ? 'half' : 'full';
	$terms = !empty($_POST['terms']);
	if ($user && $email === '') {
		$email = (string) $user['email'];
	}
	if ($user && $phone === '') {
		$phone = (string) ($user['phone'] ?? '');
	}
	if ($first === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
		$error = 'Enter your name, email, and mobile number.';
	} elseif (!$terms) {
		$error = 'Agree to the Terms and Conditions and Privacy Policy to pay.';
	} else {
		$guest = [
			'id' => (string) ($user['id'] ?? ''),
			'name' => trim($first . ' ' . $last),
			'email' => $email,
			'phone' => $phone,
		];
		$items = store_cart();
		foreach ($items as $item) {
			if (store_is_blocked((string) ($item['vehicle'] ?? ''), (string) ($item['date'] ?? ''))) {
				store_redirect('/shop/cart.php?err=blocked');
			}
		}
		$payable = store_cart_payable();
		$due = ($plan === 'half' && $payable >= 200) ? intdiv($payable, 2) : $payable;
		$noteLines = [];
		if ($hotel !== '') {
			$noteLines[] = 'Hotel: ' . $hotel;
		}
		if ($notes !== '') {
			$noteLines[] = $notes;
		}
		if ($due < $payable) {
			$noteLines[] = 'Payment plan: half payment. Due now ₱' . number_format($due) . '. Balance due ₱' . number_format($payable - $due) . '.';
		} else {
			$noteLines[] = 'Payment plan: full payment.';
		}
		$status = 'awaiting_payment';
		$method = 'pay_now';
		$discount = store_promo_discount(store_cart_total());
		$code = (string) (store_promo()['code'] ?? '');
		$created = store_create_booking($guest, $items, $status, $method, implode("\n", $noteLines), $discount, $code);
		if (!$created) {
			$error = 'Could not save this booking. Try another date or van.';
		} else {
			store_booking_grant((string) $created['id']);
			store_cart_clear();
			store_booking_mail($created, false);
			store_booking_mail($created, true);
			$_SESSION['pay_due'][(string) $created['id']] = $due;
			$url = store_paymongo_checkout($created, $due);
			if ($url !== '') {
				store_redirect($url);
			}
			$_SESSION['pay_error'] = store_paymongo_last_error() !== '' ? store_paymongo_last_error() : 'PayMongo did not open.';
			store_redirect('/shop/checkout.php?booking=' . rawurlencode((string) $created['id']));
		}
	}
}

if (!$booking && store_cart_count() === 0) {
	store_redirect('/shop/cart.php');
}

$sourceItems = $booking ? (array) ($booking['items'] ?? []) : store_cart();
$subtotal = 0;
foreach ($sourceItems as $item) {
	if (is_array($item)) {
		$subtotal += store_line_total($item);
	}
}
$discount = $booking ? 0 : store_promo_discount($subtotal);
if ($booking) {
	$payable = (int) ($booking['total'] ?? 0);
	$discount = max(0, $subtotal - $payable);
} else {
	$payable = max(0, $subtotal - $discount);
}
$applied = $booking ? null : store_promo();
$qr = ($booking && isset($_SESSION['pay_qr'][$bookingId]) && is_array($_SESSION['pay_qr'][$bookingId])) ? $_SESSION['pay_qr'][$bookingId] : null;
$returnedPaid = (string) ($_GET['ok'] ?? '') === '1';
$paid = $booking && ($returnedPaid || in_array((string) ($booking['status'] ?? ''), ['paid', 'confirmed'], true));
$step = $paid ? 3 : 2;

$fullName = trim((string) ($user['name'] ?? ($booking['guest_name'] ?? '')));
$nameParts = preg_split('/\s+/', $fullName, 2) ?: [];
$firstVal = (string) ($_POST['first_name'] ?? ($nameParts[0] ?? ''));
$lastVal = (string) ($_POST['last_name'] ?? ($nameParts[1] ?? ''));
$emailVal = (string) ($_POST['email'] ?? ($user['email'] ?? ($booking['email'] ?? '')));
$phoneVal = (string) ($_POST['phone'] ?? ($user['phone'] ?? ($booking['phone'] ?? '')));
$phoneVal = preg_replace('/^\+?63/', '', $phoneVal) ?? $phoneVal;
$hotelVal = (string) ($_POST['hotel'] ?? '');
$notesVal = (string) ($_POST['notes'] ?? '');

$cards = '';
$firstCard = null;
foreach ($sourceItems as $item) {
	if (!is_array($item)) {
		continue;
	}
	$product = store_product((string) ($item['product_id'] ?? ''));
	$tour = !empty($item['package_slug']) ? ke_tour((string) $item['package_slug']) : null;
	$name = (string) ($item['label'] ?? ($tour['name'] ?? ($product['name'] ?? 'Tour')));
	$image = ke_item_cover($item, (string) ($product['image'] ?? '/assets/downloaded/dest-cebu.jpg'));
	$dateRaw = (string) ($item['date'] ?? '');
	$dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $dateRaw);
	$dateLabel = $dateObj ? $dateObj->format('F j, Y') : $dateRaw;
	$place = trim((string) ($item['pickup'] ?? ''));
	if ($place === '' && is_array($tour)) {
		$place = ucfirst((string) ($tour['island'] ?? ''));
	}
	$view = [
		'name' => $name,
		'image' => $image,
		'date' => $dateLabel,
		'place' => $place,
		'guests' => max(1, (int) ($item['guests'] ?? 1)),
		'total' => store_line_total($item, $product),
	];
	if ($firstCard === null) {
		$firstCard = $view;
	}
	$cards .= '<p class="ke-secure-line"><span>' . store_h($name) . '</span><strong>' . store_money($view['total']) . '</strong></p>';
}
if ($discount > 0) {
	$cards .= '<p class="ke-secure-line"><span>Promo' . ($applied ? ' ' . store_h((string) $applied['code']) : '') . '</span><strong>−' . store_money($discount) . '</strong></p>';
}
$dueNow = $payable;
if ($booking && preg_match('/Due now ₱([0-9,]+)/', (string) ($booking['notes'] ?? ''), $dueShown)) {
	$dueNow = (int) str_replace(',', '', $dueShown[1]);
}

$photo = $firstCard['image'] ?? '/assets/downloaded/dest-cebu.jpg';
$heroPlace = $firstCard['place'] ?? 'Cebu';
$kicker = $heroPlace !== '' ? strtoupper($heroPlace) . ' TOURS' : 'CEBU TOURS';

$summary = '<aside class="ke-secure-sum">'
	. '<article class="ke-secure-tour">'
	. '<div class="ke-secure-photo" style="background-image:url(\'' . store_h($photo) . '\')">'
	. ($heroPlace !== '' ? '<span>' . store_h($heroPlace) . '</span>' : '')
	. '</div>'
	. '<div class="ke-secure-tour-body"><h2>' . store_h((string) ($firstCard['name'] ?? 'Your trip')) . '</h2>'
	. '<p>' . store_h((string) ($firstCard['date'] ?? '')) . '</p>'
	. '<p>' . (int) ($firstCard['guests'] ?? 1) . ' guest' . ((int) ($firstCard['guests'] ?? 1) === 1 ? '' : 's') . '</p>'
	. '</div></article>'
	. (!$booking ? '<form method="post" class="ke-secure-coupon"><input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '"><input type="text" name="code" placeholder="Enter your coupon code" value="' . store_h((string) ($applied['code'] ?? '')) . '"><button type="submit" name="promo" value="1">Apply</button></form>' : '')
	. '<div class="ke-secure-bill">' . $cards
	. '<p class="ke-secure-line"><span>Subtotal</span><strong>' . store_money($subtotal) . '</strong></p>'
	. '<p class="ke-secure-due"><span>Total amount</span><strong>' . store_money($payable) . '</strong></p>'
	. ($dueNow < $payable ? '<p class="ke-secure-line"><span>Due now</span><strong>' . store_money($dueNow) . '</strong></p>' : '')
	. '</div>'
	. '<div class="ke-secure-know"><h3>Good to know</h3><ul>'
	. '<li>Free cancellation up to 48 hours before the tour</li>'
	. '<li>Instant confirmation via email</li>'
	. '<li>Local Cebu support, 24/7</li>'
	. '</ul></div>'
	. '<p class="ke-secure-script">Travel Local<br>Support Local</p>'
	. '</aside>';

if ($error === '' && !empty($_SESSION['pay_error'])) {
	$error = (string) $_SESSION['pay_error'];
	unset($_SESSION['pay_error']);
}
$notice = $error !== '' ? '<p class="ke-secure-err">' . store_h($error) . '</p>' : '';
$left = '';
$halfNow = $payable >= 200 ? intdiv($payable, 2) : $payable;
if ($paid && $booking) {
	$left = '<section class="ke-secure-card ke-secure-done"><p class="ke-kicker">Confirmation</p><h2>Booking is confirmed</h2>'
		. '<p>Check your email every now and then, as our staff will connect with you shortly.</p>'
		. '<p>Reference <strong>' . store_h((string) $booking['id']) . '</strong></p>'
		. (str_contains((string) ($booking['notes'] ?? ''), 'Balance due') ? '<p>Half of the tour is paid. The remaining balance is due before the trip.</p>' : '')
		. '<a class="ke-secure-gold" href="/tours-and-packages.php">Back to tours</a></section>';
} elseif ($booking) {
	$cancelNote = (string) ($_GET['ok'] ?? '') === '0' ? '<p>Payment was cancelled. You can continue to PayMongo again.</p>' : '';
	$left = '<section class="ke-secure-card ke-secure-done"><p class="ke-kicker">Payment</p><h2>Continue to PayMongo</h2>'
		. $cancelNote
		. ($error !== '' ? '<p>' . store_h($error) . '</p>' : '<p>Your booking is saved. Proceed to payment to finish it.</p>')
		. '<form method="post" data-pay-full="' . (int) $dueNow . '" data-pay-half="' . (int) $dueNow . '"><input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
		. '<button class="ke-secure-gold" type="submit" name="action" value="pay">Proceed to payment</button></form></section>';
} else {
	$signin = $user
		? '<div class="ke-secure-return"><span>Signed in as ' . store_h((string) $user['name']) . '</span></div>'
		: '<div class="ke-secure-return"><span>Returning customer? Sign in for a faster checkout.</span><a href="/account/login.php?next=' . rawurlencode('/shop/checkout.php') . '">Sign in</a></div>';
	$left = $signin
		. '<form method="post" class="ke-secure-form" data-pay-full="' . (int) $payable . '" data-pay-half="' . (int) $halfNow . '">'
		. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
		. '<section class="ke-secure-card"><h2>Customer information</h2><p>We send the booking confirmation here.</p>'
		. '<label>Email address<input type="email" name="email" required value="' . store_h($emailVal) . '" placeholder="you@example.com"></label>'
		. '<div class="ke-secure-split"><label>First name<input type="text" name="first_name" required value="' . store_h($firstVal) . '"></label>'
		. '<label>Last name<input type="text" name="last_name" value="' . store_h($lastVal) . '"></label></div>'
		. '<label>Contact number<span class="ke-secure-phone"><em>+63</em><input type="tel" name="phone" required value="' . store_h($phoneVal) . '" placeholder="912 345 6789"></span></label>'
		. '<p class="ke-secure-hint">We use this number to send booking updates.</p></section>'
		. '<section class="ke-secure-card"><h2>Booking information</h2>'
		. '<label>Hotel name and address<input type="text" name="hotel" value="' . store_h($hotelVal) . '" placeholder="Hotel, Lahug, Cebu City"></label>'
		. '<label>Additional requests<textarea name="notes" rows="4" maxlength="500" placeholder="Pickup time, child seat, or food request">' . store_h($notesVal) . '</textarea></label>'
		. '</section>'
		. '<section class="ke-secure-card"><h2>Payment plan</h2>'
		. '<label class="ke-secure-plan"><input type="radio" name="plan" value="full" checked><span><strong>Full payment</strong><small>Pay ' . store_money($payable) . ' now.</small></span></label>'
		. '<label class="ke-secure-plan"><input type="radio" name="plan" value="half"' . ($payable < 200 ? ' disabled' : '') . '><span><strong>Half payment</strong><small>Pay ' . store_money($halfNow) . ' now. The rest is due before the tour.</small></span></label>'
		. '</section>'
		. '<section class="ke-secure-card"><h2>Payment</h2><p>Proceed to payment opens PayMongo for the amount you selected. After PayMongo receives it, you come back here and the booking is confirmed.</p>'
		. '<label class="ke-secure-terms"><input type="checkbox" name="terms" value="1"><span>I have read and agree to the <a href="/terms.html">Terms and Conditions</a> and <a href="/privacy-policy.html">Privacy Policy</a>.</span></label>'
		. '<button class="ke-secure-gold" type="submit" name="action" value="pay">Proceed to payment</button>'
		. '</section></form>';
}

$steps = '<ol class="ke-secure-steps">'
	. '<li><a href="/shop/cart.php"><i>1</i> Cart</a></li>'
	. '<li class="' . ($step === 2 ? 'is-on' : 'is-done') . '"><i>2</i> Checkout</li>'
	. '<li class="' . ($step === 3 ? 'is-on' : '') . '"><i>3</i> Confirmation</li>'
	. '</ol>';

$body = '<section class="ke-secure-hero" style="background-image:url(\'/assets/downloaded/dest-cebu.jpg\')">'
	. '<p>' . store_h($kicker) . '</p><h1>Secure <em>Checkout</em></h1>'
	. '<p>You are a few steps away from an unforgettable trip with Kuya Ely Tours.</p>'
	. $steps . '</section>'
	. $notice
	. '<div class="ke-secure-grid"><div class="ke-secure-main">' . $left . '</div>' . $summary . '</div>'
	. '<ul class="ke-secure-trust"><li>Secure payment</li><li>DOT accredited</li><li>No hidden charges</li></ul>';

$pixelIds = [];
$pixelNames = [];
foreach ($sourceItems as $pixelItem) {
	if (!is_array($pixelItem)) {
		continue;
	}
	$pixelIds[] = (string) ($pixelItem['package_slug'] ?? ($pixelItem['product_id'] ?? 'tour'));
	$pixelNames[] = (string) ($pixelItem['label'] ?? 'Tour');
}
$pixelName = $pixelNames !== [] ? implode(', ', $pixelNames) : 'Tour';
$pixelParams = [
	'value' => $paid ? (int) $dueNow : (int) $payable,
	'currency' => 'PHP',
	'content_type' => 'product',
	'content_ids' => $pixelIds !== [] ? $pixelIds : ['tour'],
	'content_name' => $pixelName,
	'num_items' => max(1, count($pixelIds)),
];
if ($paid && $booking) {
	store_pixel_once('purchase-' . (string) $booking['id'], 'Purchase', $pixelParams);
} elseif (!$paid) {
	store_pixel_once('checkout-' . md5(implode('|', $pixelIds) . ':' . $payable), 'InitiateCheckout', $pixelParams);
}

store_page('Secure checkout', $body, '', true, 'ke-site ke-dash ke-secure');
