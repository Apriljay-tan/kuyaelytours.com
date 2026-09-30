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
	if ((string) ($booking['pay_method'] ?? '') === 'paypal') {
		$url = store_paypal_checkout($booking, $due);
		if ($url === '') {
			$error = store_paypal_last_error() !== '' ? store_paypal_last_error() : 'PayPal did not open.';
		} else {
			store_pay_away($url, $bookingId);
		}
	} elseif ((string) ($booking['pay_method'] ?? '') === 'bank_transfer') {
		store_redirect('/shop/thank-you.php?booking=' . rawurlencode($bookingId));
	} else {
		$url = store_paymongo_checkout($booking, $due);
		if ($url === '') {
			$error = store_paymongo_last_error() !== '' ? store_paymongo_last_error() : 'PayMongo did not open.';
		} else {
			store_pay_away($url, $bookingId);
		}
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
		$plan = (string) ($_POST['plan'] ?? 'full');
		$plan = in_array($plan, ['down', 'half'], true) ? 'down' : 'full';
	if ($user && $email === '') {
		$email = (string) $user['email'];
	}
	if ($user && $phone === '') {
		$phone = (string) ($user['phone'] ?? '');
	}
	$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';
	$phoneOk = $phone !== ''
		&& (bool) preg_match('/^\+?[0-9][0-9\s().-]{6,22}$/', $phone)
		&& strlen($phoneDigits) >= 8
		&& strlen($phoneDigits) <= 15;
	if ($first === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
		$error = 'Enter your name, email, and mobile number.';
	} elseif (!$phoneOk) {
		$error = 'Enter a mobile number with its country code, such as +63 or +1.';
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
		$downNow = intdiv($payable * 30, 100);
		$downOk = $downNow >= 100 && $downNow < $payable;
		$due = ($plan === 'down' && $downOk) ? $downNow : $payable;
		$noteLines = [];
		if ($hotel !== '') {
			$noteLines[] = 'Hotel: ' . $hotel;
		}
		if ($notes !== '') {
			$noteLines[] = $notes;
		}
		if ($due < $payable) {
			$noteLines[] = 'Payment plan: 30% down payment. Due now ₱' . number_format($due) . '. Balance due ₱' . number_format($payable - $due) . '.';
		} else {
			$noteLines[] = 'Payment plan: full payment.';
		}
		$via = (string) ($_POST['pay_via'] ?? 'qrph');
		if (!in_array($via, ['qrph', 'paypal', 'bank'], true)) {
			$via = 'qrph';
		}
		if ($via === 'paypal' && !store_paypal_ready()) {
			$error = 'PayPal is not available yet. Choose QR Ph or bank transfer.';
		} else {
		$status = 'awaiting_payment';
		$method = $via === 'bank' ? 'bank_transfer' : ($via === 'paypal' ? 'paypal' : 'pay_now');
		if ($via === 'bank') {
			$noteLines[] = 'Payment method: bank transfer or GCash.';
		} elseif ($via === 'paypal') {
			$noteLines[] = 'Payment method: PayPal.';
		}
		$discount = store_promo_discount(store_cart_total());
		$code = (string) (store_promo()['code'] ?? '');
		$created = store_create_booking($guest, $items, $status, $method, implode("\n", $noteLines), $discount, $code);
		if (!$created) {
			$error = 'Could not save this booking. Try another date or van.';
		} else {
			store_booking_grant((string) $created['id']);
			store_cart_clear();
			$_SESSION['pay_due'][(string) $created['id']] = $due;
			if ($via === 'bank') {
				store_booking_mail($created, false);
				store_booking_mail($created, true);
				store_replace('/shop/thank-you.php?booking=' . rawurlencode((string) $created['id']));
			}
			if ($via === 'paypal') {
				$url = store_paypal_checkout($created, $due);
				if ($url !== '') {
					store_pay_away($url, (string) $created['id']);
				}
				$_SESSION['pay_error'] = store_paypal_last_error() !== '' ? store_paypal_last_error() : 'PayPal did not open.';
				store_redirect('/shop/checkout.php?booking=' . rawurlencode((string) $created['id']));
			}
			$url = store_paymongo_checkout($created, $due);
			if ($url !== '') {
				store_pay_away($url, (string) $created['id']);
			}
			$_SESSION['pay_error'] = store_paymongo_last_error() !== '' ? store_paymongo_last_error() : 'PayMongo did not open.';
			store_redirect('/shop/checkout.php?booking=' . rawurlencode((string) $created['id']));
		}
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
$paid = $booking && in_array((string) ($booking['status'] ?? ''), ['paid', 'confirmed'], true);
$step = $paid ? 3 : 2;

$fullName = trim((string) ($user['name'] ?? ($booking['guest_name'] ?? '')));
$nameParts = preg_split('/\s+/', $fullName, 2) ?: [];
$firstVal = (string) ($_POST['first_name'] ?? ($nameParts[0] ?? ''));
$lastVal = (string) ($_POST['last_name'] ?? ($nameParts[1] ?? ''));
$emailVal = (string) ($_POST['email'] ?? ($user['email'] ?? ($booking['email'] ?? '')));
$phoneVal = (string) ($_POST['phone'] ?? ($user['phone'] ?? ($booking['phone'] ?? '')));
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

$downPreview = intdiv($payable * 30, 100);
$downOk = $downPreview >= 100 && $downPreview < $payable;
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
	. '<p class="ke-secure-line ke-due-now"' . ($dueNow < $payable ? '' : ' hidden') . ' id="ke-due-now"><span>Due now</span><strong>' . store_money($dueNow < $payable ? $dueNow : $downPreview) . '</strong></p>'
	. '</div>'
	. '<div class="ke-secure-know"><h3>Good to know</h3><ul>'
	. '<li>Changes and refunds depend on notice, supplier rules, and whether a vehicle is already reserved</li>'
	. '<li>QR Ph and PayPal send a confirmation after the payment is received</li>'
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
if ($paid && $booking) {
	$left = '<section class="ke-secure-card ke-secure-done"><p class="ke-kicker">Confirmation</p><h2>Booking is confirmed</h2>'
		. '<p>Check your email every now and then, as our staff will connect with you shortly.</p>'
		. '<p>Reference <strong>' . store_h((string) $booking['id']) . '</strong></p>'
		. (str_contains((string) ($booking['notes'] ?? ''), 'Balance due') ? '<p>Your down payment is paid. The remaining balance is due before the trip.</p>' : '')
		. '<a class="ke-secure-gold" href="/tours-and-packages.php">Back to tours</a></section>';
} elseif ($booking) {
	$returnFlag = (string) ($_GET['ok'] ?? '');
	$savedMethod = (string) ($booking['pay_method'] ?? '');
	if ($savedMethod === 'bank_transfer') {
		$left = '<section class="ke-secure-card ke-secure-done"><p class="ke-kicker">Bank transfer</p><h2>Booking is saved</h2>'
			. '<p>Reference <strong>' . store_h((string) $booking['id']) . '</strong>. Send the transfer, then the receipt.</p>'
			. '<a class="ke-secure-gold" href="/shop/thank-you.php?booking=' . rawurlencode((string) $booking['id']) . '">View bank details</a></section>';
	} else {
		$payLabel = $savedMethod === 'paypal' ? 'PayPal' : 'PayMongo';
		$cancelNote = $returnFlag === '0' ? '<p>Payment was cancelled. You can continue to ' . store_h($payLabel) . ' again.</p>' : '';
		if ($returnFlag === 'pending') {
			$cancelNote = '<p>' . store_h($payLabel) . ' has not confirmed this payment yet. If you already paid, wait a moment and refresh this page.</p>';
		}
		$retryLabel = $savedMethod === 'paypal' ? 'Pay with PayPal' : 'Pay with QR Ph';
		$left = '<section class="ke-secure-card ke-secure-done"><p class="ke-kicker">Payment</p><h2>Continue to ' . store_h($payLabel) . '</h2>'
			. $cancelNote
			. '<p>Your booking is saved. Proceed to payment to finish it.</p>'
			. '<form method="post" data-pay-full="' . (int) $dueNow . '" data-pay-half="' . (int) $dueNow . '"><input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
			. '<button class="' . ($savedMethod === 'paypal' ? 'ke-pay-btn ke-pay-btn-paypal' : 'ke-pay-btn ke-pay-btn-qr') . '" type="submit" name="action" value="pay">' . store_h($retryLabel) . '</button></form></section>';
	}
} else {
	$signin = $user
		? '<div class="ke-secure-return"><span>Signed in as ' . store_h((string) $user['name']) . '</span></div>'
		: '<div class="ke-secure-return"><span>Returning customer? Sign in for a faster checkout.</span><a href="/account/login.php?next=' . rawurlencode('/shop/checkout.php') . '">Sign in</a></div>';
	$left = $signin
		. '<form method="post" class="ke-secure-form" data-pay-full="' . (int) $payable . '" data-pay-half="' . (int) $downPreview . '">'
		. '<input type="hidden" name="csrf" value="' . store_h(store_csrf_token()) . '">'
		. '<section class="ke-secure-card"><h2>Customer information</h2><p>We send the booking confirmation here.</p>'
		. '<label>Email address<input type="email" name="email" required value="' . store_h($emailVal) . '" placeholder="you@example.com"></label>'
		. '<div class="ke-secure-split"><label>First name<input type="text" name="first_name" required value="' . store_h($firstVal) . '"></label>'
		. '<label>Last name<input type="text" name="last_name" value="' . store_h($lastVal) . '"></label></div>'
		. '<label>Contact number<input type="tel" name="phone" required value="' . store_h($phoneVal) . '" placeholder="+63 912 345 6789" autocomplete="tel" inputmode="tel"></label>'
		. '<p class="ke-secure-hint">Include the country code. We use this number to send booking updates.</p></section>'
		. '<section class="ke-secure-card"><h2>Booking information</h2>'
		. '<label>Hotel name and address<input type="text" name="hotel" value="' . store_h($hotelVal) . '" placeholder="Hotel, Lahug, Cebu City"></label>'
		. '<label>Additional requests<textarea name="notes" rows="4" maxlength="500" placeholder="Pickup time, child seat, or food request">' . store_h($notesVal) . '</textarea></label>'
		. '</section>'
		. '<section class="ke-secure-card"><h2>Payment plan</h2>'
		. ($downOk
			? '<div class="ke-plan-switch">'
				. '<label><input type="radio" name="plan" value="down"><span><strong>30% down</strong><small>Pay ' . store_money($downPreview) . ' now</small></span></label>'
				. '<label class="is-on"><input type="radio" name="plan" value="full" checked><span><strong>Full payment</strong><small>Pay ' . store_money($payable) . ' now</small></span></label>'
				. '</div><p class="ke-plan-note" id="ke-plan-note" hidden>The rest is due before the tour.</p>'
			: '<p>Pay ' . store_money($payable) . ' now.</p><input type="hidden" name="plan" value="full">')
		. '</section>'
		. '<section class="ke-secure-card ke-paypick"><h2>How do you want to pay?</h2>'
		. '<div class="ke-pay-cards">'
		. '<label class="ke-pay-card"><input type="radio" name="pay_via" value="paypal"><span class="ke-pay-brand"><img src="/assets/img/pay/paypal.svg" alt="PayPal"><img src="/assets/img/pay/cards.svg" alt="Visa, Mastercard, Discover, American Express"></span></label>'
		. '<label class="ke-pay-card is-on"><input type="radio" name="pay_via" value="qrph" checked><span class="ke-pay-brand"><img src="/assets/img/pay/qrph.svg" alt="QR Ph"></span></label>'
		. '<label class="ke-pay-card"><input type="radio" name="pay_via" value="bank"><span class="ke-pay-brand"><img src="/assets/img/pay/bank.svg" alt="Bank transfer or GCash"><strong>Bank transfer or GCash</strong></span></label>'
		. '</div>'
		. '<p class="ke-pay-panel" data-pay-panel="paypal">Pay with your debit or credit card using the PayPal button below. No PayPal account needed.</p>'
		. '<p class="ke-pay-panel" data-pay-panel="qrph" hidden>Pay with QR Ph. GCash, Maya, and bank apps can scan it.</p>'
		. '<p class="ke-pay-panel" data-pay-panel="bank" hidden>Pay directly into our bank or GCash. The next page shows the total and the accounts.</p>'
		. '<button class="ke-pay-btn ke-pay-btn-paypal" type="submit" name="action" value="pay" data-pay-btn="paypal">Pay with <img src="/assets/img/pay/paypal.svg" alt="PayPal"></button>'
		. '<button class="ke-pay-btn ke-pay-btn-card" type="submit" name="action" value="pay" data-pay-btn="paypal">Debit or Credit Card</button>'
		. '<p class="ke-pay-powered" data-pay-panel="paypal">Powered by <img src="/assets/img/pay/paypal.svg" alt="PayPal"></p>'
		. '<button class="ke-pay-btn ke-pay-btn-qr" type="submit" name="action" value="pay" data-pay-btn="qrph" hidden>Pay with QR Ph</button>'
		. '<button class="ke-pay-btn ke-pay-btn-bank" type="submit" name="action" value="pay" data-pay-btn="bank" hidden>Complete Your Order Now</button>'
		. '</section></form>'
		. '<script>(function(){var form=document.querySelector(".ke-secure-form");if(!form)return;function syncPay(){var picked=(form.querySelector("input[name=pay_via]:checked")||{}).value||"qrph";form.querySelectorAll("[data-pay-panel],[data-pay-btn]").forEach(function(el){var key=el.getAttribute("data-pay-panel")||el.getAttribute("data-pay-btn");el.hidden=key!==picked;});form.querySelectorAll(".ke-pay-card").forEach(function(el){var input=el.querySelector("input");el.classList.toggle("is-on",!!(input&&input.checked));});}function syncPlan(){var plan=form.querySelector("input[name=plan]:checked");var down=!!(plan&&plan.value==="down");form.querySelectorAll(".ke-plan-switch label").forEach(function(el){var input=el.querySelector("input");el.classList.toggle("is-on",!!(input&&input.checked));});var due=document.getElementById("ke-due-now");var note=document.getElementById("ke-plan-note");if(due)due.hidden=!down;if(note)note.hidden=!down;}form.querySelectorAll("input[name=pay_via]").forEach(function(el){el.addEventListener("change",syncPay);});form.querySelectorAll("input[name=plan]").forEach(function(el){el.addEventListener("change",syncPlan);});syncPay();syncPlan();})();</script>';
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
	$lead = json_encode([
		'event' => 'generate_lead',
		'form_name' => 'checkout_booking',
		'tour_package' => $pixelName,
	], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
	$leadKey = json_encode('generate_lead:' . (string) $booking['id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
	if ($lead !== false && $leadKey !== false) {
		$body .= '<script>window.dataLayer=window.dataLayer||[];(function(){var key=' . $leadKey . ';try{if(sessionStorage.getItem(key))return;sessionStorage.setItem(key,"1");}catch(e){}window.dataLayer.push(' . $lead . ');})();</script>';
	}
} elseif (!$paid) {
	store_pixel_once('checkout-' . md5(implode('|', $pixelIds) . ':' . $payable), 'InitiateCheckout', $pixelParams);
}

store_page('Secure checkout', $body, '', true, 'ke-site ke-dash ke-secure');
