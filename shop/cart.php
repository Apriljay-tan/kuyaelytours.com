<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	if (!empty($_POST['remove'])) {
		store_cart_remove((string) $_POST['remove']);
	}
	if (!empty($_POST['clear'])) {
		store_cart_clear();
	}
	if (isset($_POST['promo'])) {
		store_redirect('/shop/cart.php?promo=1');
	}
	store_redirect('/shop/cart.php');
}

$err = (string) ($_GET['err'] ?? '');
$notice = '';
if ($err === 'blocked') {
	$notice = '<p class="ke-err ke-banner ke-dash-banner">That vehicle is already confirmed on the selected date. Choose another date or van.</p>';
}
if (($_GET['promo'] ?? '') === '1') {
	$notice .= '<p class="ke-ok ke-banner ke-dash-banner">Promo codes are confirmed by Kuya Ely with your booking. Your cart total is unchanged for now.</p>';
}

$items = store_cart();
$count = count($items);
$csrf = store_h(store_csrf_token());
$total = store_cart_total();

function ke_cart_date(string $ymd): string
{
	$d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
	return $d ? $d->format('M j, Y') : $ymd;
}

function ke_cart_badge(array $product): string
{
	$type = (string) ($product['type'] ?? '');
	$name = (string) ($product['name'] ?? '');
	if (stripos($name, 'hop') !== false) {
		return 'Island Hopping';
	}
	if ($type === 'transfer') {
		return 'Transfer';
	}
	if ($type === 'vehicle') {
		return 'Private Van';
	}
	return 'Day Tour';
}

$hero = '<section class="ke-dash-hero ke-bag-hero">'
	. '<div class="ke-dash-hero-copy">'
	. '<p class="ke-dash-kicker ke-bag-crumb"><a href="/index.html">Home</a> <span>&rarr;</span> Cart</p>'
	. '<h1>Your <em>Cart</em></h1>'
	. '<p class="ke-dash-lede">Almost there! Review your selections and proceed to create more unforgettable memories with Kuya Ely.</p>'
	. '</div>'
	. '<div class="ke-dash-hero-art" aria-hidden="true">'
	. '<span class="ke-dash-script">Explore<br>More<br>Together</span>'
	. '<ul><li>Cebu</li><li>Bohol</li><li>Siquijor</li><li>Dumaguete</li><li>and beyond</li></ul>'
	. '</div></section>';

if ($count === 0) {
	$body = $hero . $notice . '<section class="ke-cart-empty ke-bag-empty">'
		. '<span class="ke-cart-empty-icon" aria-hidden="true">' . store_svg_cart() . '</span>'
		. '<h2>Your cart is empty</h2>'
		. '<p>Add a Cebu, Bohol, Siquijor, or Dumaguete tour, or a private van, then come back to check out.</p>'
		. '<a class="ke-dash-gold" href="/shop/catalog.php">Browse Tours</a>'
		. '</section>';
	store_page('Your Cart', $body, '', true, 'ke-site ke-dash ke-bag');
	exit;
}

$inCart = [];
foreach ($items as $item) {
	$inCart[(string) ($item['product_id'] ?? '')] = true;
}

$rows = '';
foreach ($items as $item) {
	$product = store_product((string) ($item['product_id'] ?? ''));
	$name = (string) ($product['name'] ?? 'Item');
	$image = (string) ($product['image'] ?? '/assets/downloaded/dest-cebu.jpg');
	$desc = (string) ($product['description'] ?? '');
	$unit = (string) ($product['unit'] ?? '');
	$guests = max(1, (int) ($item['guests'] ?? 1));
	$date = (string) ($item['date'] ?? '');
	$vehicle = (string) ($item['vehicle'] ?? '');
	$line = store_line_total($item, $product);
	$from = (int) ($product['price_from'] ?? 0);
	$edit = (string) ($product['page'] ?? '/shop/catalog.php');
	$chips = [];
	$badge = ke_cart_badge($product ?: []);
	$chips[] = $badge;
	if ($unit !== '') {
		$chips[] = $unit;
	}
	if ($vehicle !== '') {
		$chips[] = $vehicle;
	}
	$chipHtml = '';
	foreach (array_slice($chips, 0, 3) as $chip) {
		$chipHtml .= '<span>' . store_h($chip) . '</span>';
	}
	$per = $from > 0 && $guests > 1 && ($product['type'] ?? '') === 'tour'
		? store_money($from) . ' x ' . $guests . ' pax'
		: ($unit !== '' ? store_money($from) . ' ' . $unit : '');
	$meta = '';
	if ($date !== '') {
		$meta .= '<span>' . store_h(ke_cart_date($date)) . '</span>';
	}
	$meta .= '<span>' . $guests . ' guest' . ($guests === 1 ? '' : 's') . '</span>';
	if ($vehicle !== '') {
		$meta .= '<span>Pickup: ' . store_h($vehicle) . '</span>';
	}

	$rows .= '<article class="ke-bag-item">'
		. '<div class="ke-bag-photo"><img src="' . store_h($image) . '" alt=""><b>' . store_h($badge) . '</b></div>'
		. '<div class="ke-bag-copy">'
		. '<div class="ke-bag-copy-top"><div><h2>' . store_h($name) . '</h2>'
		. ($desc !== '' ? '<p>' . store_h($desc) . '</p>' : '')
		. '</div><div class="ke-bag-price"><strong>' . store_money($line) . '</strong>'
		. ($per !== '' ? '<small>' . store_h($per) . '</small>' : '') . '</div></div>'
		. '<p class="ke-bag-meta">' . $meta . '</p>'
		. '<div class="ke-bag-chips">' . $chipHtml . '</div>'
		. '<div class="ke-bag-item-actions">'
		. '<a href="' . store_h($edit) . '">Edit</a>'
		. '<form method="post"><input type="hidden" name="csrf" value="' . $csrf . '">'
		. '<button type="submit" name="remove" value="' . store_h((string) $item['id']) . '">Remove</button></form>'
		. '</div></div></article>';
}

$addons = '';
$shown = 0;
foreach (store_catalog() as $product) {
	$id = (string) ($product['id'] ?? '');
	if ($id === '' || isset($inCart[$id]) || $shown >= 3) {
		continue;
	}
	$shown++;
	$addons .= '<article class="ke-bag-addon">'
		. '<img src="' . store_h((string) ($product['image'] ?? '')) . '" alt="">'
		. '<div><h3>' . store_h((string) $product['name']) . '</h3>'
		. '<p>' . store_h((string) ($product['description'] ?? '')) . '</p>'
		. '<div class="ke-bag-addon-foot"><strong>' . store_money((int) ($product['price_from'] ?? 0)) . '</strong>'
		. '<small>' . store_h((string) ($product['unit'] ?? '')) . '</small>'
		. '<form method="post" action="/shop/add-to-cart.php">'
		. '<input type="hidden" name="csrf" value="' . $csrf . '">'
		. '<input type="hidden" name="product_id" value="' . store_h($id) . '">'
		. '<input type="hidden" name="next" value="/shop/cart.php">'
		. '<button class="ke-bag-add" type="submit">+ Add</button></form></div></div></article>';
}

$sum = '<aside class="ke-bag-sum">'
	. '<h2>Trip Summary</h2>'
	. '<p class="ke-bag-sum-lede">Review your booking details.</p>'
	. '<p class="ke-sum-line"><span>Subtotal (' . $count . ' item' . ($count === 1 ? '' : 's') . ')</span><span>' . store_money($total) . '</span></p>'
	. '<p class="ke-total">Total <span>' . store_money($total) . '</span></p>'
	. '<p class="ke-bag-rate">Kuya Ely confirms the final rate, van, and date.</p>'
	. '<form method="post" class="ke-bag-promo"><label>Promo Code<small>Have a promo code?</small></label>'
	. '<input type="hidden" name="csrf" value="' . $csrf . '">'
	. '<div><input type="text" name="code" placeholder="Enter promo code"><button type="submit" name="promo" value="1">Apply</button></div></form>'
	. '<a class="ke-dash-gold ke-bag-checkout" href="/shop/checkout.php">Proceed to Checkout</a>'
	. '<p class="trust">Secure Booking<br>Your information is encrypted and protected.</p>'
	. '</aside>'
	. '<aside class="ke-bag-help">'
	. '<h2>Need Help?</h2>'
	. '<p>Chat with our team on WhatsApp for quick assistance.</p>'
	. '<a class="ke-dash-gold" href="https://wa.me/639209851802">Chat on WhatsApp</a>'
	. '<ul><li>Real People</li><li>Fast Response</li><li>Travel Experts</li></ul>'
	. '</aside>';

$body = $hero . $notice
	. '<div class="ke-bag-layout">'
	. '<div class="ke-bag-main">'
	. '<div class="ke-bag-head"><h2>Selected Tours &amp; Services (' . $count . ')</h2>'
	. '<form method="post"><input type="hidden" name="csrf" value="' . $csrf . '">'
	. '<button class="ke-bag-clear" type="submit" name="clear" value="1">Clear Cart</button></form></div>'
	. $rows
	. ($addons !== '' ? '<section class="ke-bag-addons"><div class="ke-bag-head"><h2>Recommended Add-ons</h2><a href="/shop/catalog.php">View more add-ons</a></div><div class="ke-bag-addon-grid">' . $addons . '</div></section>' : '')
	. '</div><div class="ke-bag-side">' . $sum . '</div></div>';

store_page('Your Cart', $body, '', true, 'ke-site ke-dash ke-bag');
