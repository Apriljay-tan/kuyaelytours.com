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

function ke_book_date(string $ymd): string
{
	$d = DateTimeImmutable::createFromFormat('Y-m-d', $ymd);
	return $d ? $d->format('M j, Y') : $ymd;
}

function ke_book_items(array $booking): array
{
	$items = $booking['items'] ?? [];
	return is_array($items) ? $items : [];
}

function ke_book_bucket(array $booking): string
{
	$status = (string) ($booking['status'] ?? '');
	if ($status === 'cancelled') {
		return 'cancelled';
	}
	if ($status === 'awaiting_payment') {
		return 'pending';
	}
	$date = (string) (ke_book_items($booking)[0]['date'] ?? '');
	$today = store_today();
	if (in_array($status, ['paid', 'confirmed'], true) && $date !== '' && $date < $today) {
		return 'completed';
	}
	return 'upcoming';
}

$all = store_user_bookings((string) $user['id']);
$counts = ['all' => count($all), 'upcoming' => 0, 'completed' => 0, 'pending' => 0, 'cancelled' => 0];
foreach ($all as $booking) {
	$counts[ke_book_bucket($booking)]++;
}
$filter = (string) ($_GET['filter'] ?? 'all');
if (!isset($counts[$filter]) || $filter === '') {
	$filter = 'all';
}

$notice = '';
if (($_GET['placed'] ?? '') === '1') {
	$notice = '<p class="ke-ok ke-banner ke-dash-banner">Request sent. We emailed a confirmation and our team will reply with the final rate.</p>';
}
if (($_GET['paid'] ?? '') === '1') {
	$notice = '<p class="ke-ok ke-banner ke-dash-banner">Payment recorded. Staff will still confirm the van and date.</p>';
}
if (($_GET['voucher'] ?? '') === '1') {
	$notice = '<p class="ke-ok ke-banner ke-dash-banner">Vouchers are emailed after Kuya Ely confirms your trip. Check info@kuyaelytours.com messages, including spam.</p>';
}

$name = (string) $user['name'];
$first = store_first_name($name);
$email = (string) $user['email'];
$phone = trim((string) ($user['phone'] ?? ''));
$csrf = store_h(store_csrf_token());
$cartCount = store_cart_count();

$filters = [
	'all' => 'All',
	'upcoming' => 'Upcoming',
	'completed' => 'Completed',
	'pending' => 'Pending Payment',
	'cancelled' => 'Cancelled',
];

ob_start();
?>
<p class="ke-bk-crumb"><a href="/index.html">Home</a> <span>/</span> My Account</p>

<section class="ke-bk-hello">
	<div class="ke-bk-hello-id">
		<span class="ke-dash-avatar"><?= store_h(store_initials($name)) ?></span>
		<div>
			<h1>Hello, <?= store_h($first) ?></h1>
			<p><?= store_h($email) ?><?= $phone !== '' ? ' &middot; ' . store_h($phone) : '' ?></p>
			<p class="ke-bk-welcome">Welcome back! Manage your bookings and account here.</p>
		</div>
	</div>
	<dl class="ke-bk-stats">
		<div><dt>Total Bookings</dt><dd><?= (int) $counts['all'] ?></dd></div>
		<div><dt>Upcoming</dt><dd><?= (int) $counts['upcoming'] ?></dd></div>
		<div><dt>Completed</dt><dd><?= (int) $counts['completed'] ?></dd></div>
	</dl>
</section>

<nav class="ke-bk-tabs" aria-label="Account">
	<a href="/account/bookings.php" aria-current="page">My bookings</a>
	<a href="/account/profile.php">Account</a>
	<a href="/shop/cart.php">Cart<?= $cartCount > 0 ? ' (' . (int) $cartCount . ')' : '' ?></a>
</nav>

<?= $notice ?>

<div class="ke-bk-grid">
	<section class="ke-bk-panel">
		<h2>My Bookings</h2>
		<p class="ke-bk-lede">Manage your tours, payments, and travel details.</p>
		<nav class="ke-bk-filters" aria-label="Filter bookings">
			<?php foreach ($filters as $key => $label): ?>
				<a href="/account/bookings.php?filter=<?= store_h($key) ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?>><?= store_h($label) ?> (<?= (int) $counts[$key] ?>)</a>
			<?php endforeach; ?>
		</nav>

		<?php
		$shown = 0;
		foreach ($all as $booking):
			$bucket = ke_book_bucket($booking);
			if ($filter !== 'all' && $bucket !== $filter) {
				continue;
			}
			$shown++;
			$status = (string) ($booking['status'] ?? '');
			$items = ke_book_items($booking);
			$firstItem = $items[0] ?? [];
			$product = store_product((string) ($firstItem['product_id'] ?? ''));
			$title = (string) ($product['name'] ?? 'Private trip');
			$image = (string) ($product['image'] ?? '/assets/downloaded/dest-cebu.jpg');
			$date = (string) ($firstItem['date'] ?? '');
			$guests = max(1, (int) ($firstItem['guests'] ?? 1));
			$pickup = trim((string) ($firstItem['vehicle'] ?? ''));
			$id = (string) $booking['id'];
			$pill = $status === 'awaiting_payment' ? 'Pending' : store_booking_status_label($status);
			?>
			<article class="ke-bk-row" id="b-<?= store_h($id) ?>">
				<img src="<?= store_h($image) ?>" alt="">
				<div class="ke-bk-copy">
					<div class="ke-bk-copy-top">
						<div>
							<h3><?= store_h($title) ?></h3>
							<p>Booking Ref: KE-<?= store_h(strtoupper($id)) ?></p>
						</div>
						<span class="ke-bk-pill ke-bk-pill-<?= store_h($status) ?>"><?= store_h($pill) ?></span>
					</div>
					<p class="ke-bk-meta">
						<?php if ($date !== ''): ?><span><?= store_h(ke_book_date($date)) ?></span><?php endif; ?>
						<span><?= $guests ?> guest<?= $guests === 1 ? '' : 's' ?></span>
						<?php if ($pickup !== ''): ?><span><?= store_h($pickup) ?></span><?php endif; ?>
					</p>
				</div>
				<div class="ke-bk-pay">
					<p>Total Amount<strong><?= store_money((int) $booking['total']) ?></strong></p>
					<div class="ke-bk-actions">
						<?php if ($status === 'awaiting_payment'): ?>
							<a class="ke-dash-gold" href="/shop/pay.php?booking=<?= store_h($id) ?>">Complete payment</a>
							<form method="post">
								<input type="hidden" name="csrf" value="<?= $csrf ?>">
								<button class="ke-bk-cancel" type="submit" name="cancel" value="<?= store_h($id) ?>">Cancel request</button>
							</form>
						<?php elseif ($status === 'pending_request'): ?>
							<a class="ke-bk-ghost" href="/shop/catalog.php">View details</a>
							<form method="post">
								<input type="hidden" name="csrf" value="<?= $csrf ?>">
								<button class="ke-bk-cancel" type="submit" name="cancel" value="<?= store_h($id) ?>">Cancel request</button>
							</form>
						<?php elseif (in_array($status, ['paid', 'confirmed'], true)): ?>
							<a class="ke-bk-ghost" href="<?= store_h((string) ($product['page'] ?? '/shop/catalog.php')) ?>">View details</a>
							<a class="ke-dash-gold" href="/shop/catalog.php">Book again</a>
						<?php else: ?>
							<a class="ke-dash-gold" href="/shop/catalog.php">Book again</a>
						<?php endif; ?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>

		<?php if ($shown === 0): ?>
			<div class="ke-bk-empty">
				<strong><?= $counts['all'] === 0 ? 'No trips on file yet' : 'No bookings in this view' ?></strong>
				<p>Add a tour or van, then Book now or Pay now.</p>
				<a class="ke-dash-gold" href="/shop/catalog.php">Browse tours</a>
			</div>
		<?php endif; ?>
	</section>

	<aside class="ke-bk-side">
		<section class="ke-bk-help">
			<h2>Need Help?</h2>
			<p>We're here for you! Get in touch with our support team via WhatsApp.</p>
			<a class="ke-bk-wa" href="https://wa.me/639209851802">Message us on WhatsApp</a>
			<p class="ke-bk-hours">Available daily · 8:00 AM – 8:00 PM<br>Fast response. Real people. Better travels.</p>
		</section>
		<section class="ke-bk-quick">
			<h2>Quick Actions</h2>
			<a href="/shop/catalog.php"><span>Browse tours<small>Find your next adventure</small></span></a>
			<a href="/account/bookings.php?voucher=1"><span>Download voucher<small>Sent by email after confirmation</small></span></a>
			<a href="/contact.html"><span>Contact support<small>Get help from our team</small></span></a>
		</section>
		<a class="ke-bk-promo" href="/shop/catalog.php">
			<img src="/assets/downloaded/dest-siquijor.jpg" alt="">
			<span>Travel More<br>Explore the Philippines</span>
		</a>
	</aside>
</div>
<?php
store_page('My bookings', ob_get_clean(), '', true, 'ke-site ke-dash ke-bk');
