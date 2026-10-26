<?php
declare(strict_types=1);

require dirname(__DIR__) . '/store/bootstrap.php';
require __DIR__ . '/chrome.php';

$bundle = partner_bundle();
$user = $bundle['user'];
$profile = $bundle['profile'];
$desk = $bundle['desk'];
$bookings = $bundle['bookings'];
$name = trim((string) ($user['name'] ?? ''));
if ($name === '') {
	$name = 'partner';
}
$business = trim((string) ($profile['business_name'] ?? ''));
$email = trim((string) ($user['email'] ?? ''));
$phone = trim((string) ($profile['phone'] ?? $user['phone'] ?? ''));
$typeKey = (string) ($profile['partner_type'] ?? '');
$areaKey = (string) ($profile['area'] ?? '');
$typeLabel = store_partner_types()[$typeKey] ?? 'Partner';
$areaLabel = store_partner_areas()[$areaKey] ?? '—';
$status = (string) ($profile['status'] ?? 'approved');
$statusLabel = $status === 'approved' ? 'Active' : ($status === 'inactive' ? 'Inactive' : 'On file');
$website = trim((string) ($profile['website'] ?? ''));
$prices = store_partner_catalog_rates();
$ready = (string) ($_GET['ready'] ?? '') === '1';
$zone = new DateTimeZone('Asia/Manila');
$now = new DateTimeImmutable('now', $zone);
$months = [];
for ($i = 5; $i >= 0; $i--) {
	$point = $now->modify('first day of this month')->modify('-' . $i . ' months');
	$months[$point->format('Y-m')] = ['label' => $point->format('M'), 'n' => 0];
}
foreach ($bookings as $booking) {
	if (!is_array($booking)) {
		continue;
	}
	$key = substr((string) ($booking['created'] ?? ''), 0, 7);
	if (isset($months[$key]) && (string) ($booking['status'] ?? '') !== 'cancelled') {
		$months[$key]['n']++;
	}
}
$maxBars = 1;
foreach ($months as $month) {
	$maxBars = max($maxBars, (int) $month['n']);
}
$thumbs = [
	'cebu' => '/assets/downloaded/dest-cebu.jpg',
	'bohol' => '/assets/downloaded/dest-bohol.jpg',
	'siquijor' => '/assets/downloaded/dest-siquijor.jpg',
	'dumaguete' => '/assets/downloaded/dest-dumaguete.jpg',
];
$thumb = $thumbs[$areaKey] ?? '/assets/downloaded/dest-siquijor.jpg';
$commission = (string) ($desk['commission_type'] ?? 'fixed') === 'percent'
	? (int) ($desk['commission_value'] ?? 0) . '%'
	: store_money((int) ($desk['commission_value'] ?? 0));

partner_layout_start('Dashboard', 'index.php', 'Partner', false);
?>
<div class="ke-portal">
	<?php if ($ready): ?><div class="alert alert-success">Your new password is saved.</div><?php endif; ?>
	<section class="ke-hero" style="background-image:url('/assets/downloaded/dest-siquijor.jpg')">
		<div class="ke-hero-copy">
			<p class="ke-hero-kicker">PARTNER DASHBOARD</p>
			<h1>Welcome back, <?php echo store_h($name); ?></h1>
			<p>Here is your partnership, your contracted rates, and the bookings on this login.</p>
		</div>
		<div class="ke-hero-script">Explore more. Travel easier.</div>
	</section>

	<div class="ke-pstats">
		<article class="ke-pstat">
			<div class="ke-pstat-top"><span class="ke-pico is-green"><i class="bx bx-user"></i></span><span class="ke-pill<?php echo $status === 'approved' ? '' : ' is-wait'; ?>"><?php echo store_h($statusLabel); ?></span></div>
			<h2><?php echo store_h($statusLabel); ?></h2>
			<p><?php echo store_h($business !== '' ? $business : 'Active partnership'); ?></p>
		</article>
		<article class="ke-pstat">
			<div class="ke-pstat-top"><span class="ke-pico is-blue"><i class="bx bx-calendar"></i></span></div>
			<h2><?php echo count($bookings); ?></h2>
			<p>Total bookings on this login</p>
		</article>
		<article class="ke-pstat">
			<div class="ke-pstat-top"><span class="ke-pico is-amber"><i class="bx bx-wallet"></i></span></div>
			<h2><?php echo store_h($commission); ?></h2>
			<p>Agreed commission</p>
		</article>
		<article class="ke-pstat">
			<div class="ke-pstat-top"><span class="ke-pico is-purple"><i class="bx bx-package"></i></span></div>
			<h2><?php echo count($prices); ?></h2>
			<p>Contracted packages</p>
		</article>
	</div>

	<div class="ke-prow">
		<section class="ke-panel">
			<div class="ke-panel-head"><h2>My Contracted Rates</h2><a href="/partner/rates.php">View all packages</a></div>
			<table class="ke-table">
				<thead><tr><th>Package</th><th>Market</th><th>Contracted</th><th>Savings</th></tr></thead>
				<tbody>
				<?php if (!$prices): ?>
					<tr><td colspan="4" class="ke-empty">Partner prices show here after they are saved on a tour package.</td></tr>
				<?php endif; ?>
				<?php foreach (array_slice($prices, 0, 6) as $price):
					if (!is_array($price)) { continue; }
					$market = (int) ($price['market'] ?? 0);
					$rate = (int) ($price['rate'] ?? 0);
					$save = (int) ($price['save'] ?? 0);
					$pct = (int) ($price['pct'] ?? 0);
					$photo = (string) ($price['cover'] ?? '');
					if ($photo === '') {
						$photo = $thumbs[strtolower((string) ($price['island'] ?? ''))] ?? $thumb;
					}
				?>
					<tr>
						<td><div class="ke-pack"><img src="<?php echo store_h($photo); ?>" alt=""><span><strong><?php echo store_h((string) ($price['name'] ?? 'Package')); ?></strong><small><?php echo store_h((string) ($price['island'] ?? '')); ?> · starting rate</small></span></div></td>
						<td><?php echo store_h(store_money($market)); ?></td>
						<td><?php echo store_h(store_money($rate)); ?></td>
						<td class="ke-save"><?php echo store_h(store_money($save)); ?> (<?php echo (int) $pct; ?>%)</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>
		<section class="ke-panel">
			<div class="ke-panel-head"><h2>Recent Bookings</h2><a href="/partner/bookings.php">View all bookings</a></div>
			<table class="ke-table">
				<thead><tr><th>Booking</th><th>Package</th><th>Status</th><th>Amount</th></tr></thead>
				<tbody>
				<?php if (!$bookings): ?>
					<tr><td colspan="4" class="ke-empty">No bookings on this login yet.</td></tr>
				<?php endif; ?>
				<?php foreach (array_slice($bookings, 0, 5) as $booking):
					if (!is_array($booking)) { continue; }
				?>
					<tr>
						<td><strong><?php echo store_h((string) ($booking['id'] ?? '')); ?></strong><small class="ke-muted"><?php echo store_h((string) ($booking['guest_name'] ?? '')); ?></small></td>
						<td><?php echo store_h(partner_booking_label($booking)); ?></td>
						<td><?php echo partner_status_pill((string) ($booking['status'] ?? '')); ?></td>
						<td><?php echo store_h(store_money((int) ($booking['total'] ?? 0))); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>
		<aside class="ke-panel">
			<h2>Quick Actions</h2>
			<div class="ke-quick" style="margin-top:12px">
				<a href="/tours-and-packages.php">Create Booking <i class="bx bx-right-arrow-alt"></i></a>
				<a class="is-line" href="/partner/rates.php">Rate Sheet <i class="bx bx-download"></i></a>
				<a class="is-line" href="/partner/profile.php">Update Profile <i class="bx bx-user"></i></a>
				<a class="is-line" href="/partner/support.php">Contact Support <i class="bx bx-envelope"></i></a>
			</div>
		</aside>
	</div>

	<div class="ke-prow3">
		<section class="ke-panel">
			<div class="ke-panel-head"><h2>Commission</h2><a href="/partner/commissions.php">View details</a></div>
			<p class="ke-muted">Agreed with the office: <strong><?php echo store_h($commission); ?></strong><?php echo (string) ($desk['commission_type'] ?? '') === 'percent' ? ' of a booking' : ' per arrangement'; ?>.</p>
			<div class="ke-bars" aria-hidden="true">
				<?php $last = array_key_last($months); foreach ($months as $key => $month):
					$h = (int) round(((int) $month['n'] / $maxBars) * 100);
				?>
					<div class="ke-bar"><i class="<?php echo $key === $last ? 'is-now' : ''; ?>" style="height:<?php echo max(8, $h); ?>%"></i><b><?php echo store_h($month['label']); ?></b></div>
				<?php endforeach; ?>
			</div>
			<p class="ke-muted">Bars show bookings on this login for the last six months.</p>
		</section>
		<section class="ke-panel">
			<div class="ke-panel-head"><h2>Partnership Profile</h2><a href="/partner/profile.php">Edit profile</a></div>
			<div class="ke-profile-row"><i class="bx bx-buildings"></i><div><span>Business</span><strong><?php echo store_h($business !== '' ? $business : '—'); ?></strong></div></div>
			<div class="ke-profile-row"><i class="bx bx-user"></i><div><span>Contact</span><strong><?php echo store_h(trim((string) ($profile['contact_name'] ?? $name))); ?></strong></div></div>
			<div class="ke-profile-row"><i class="bx bx-envelope"></i><div><span>Email</span><strong><?php echo store_h($email !== '' ? $email : '—'); ?></strong></div></div>
			<div class="ke-profile-row"><i class="bx bx-phone"></i><div><span>Phone</span><strong><?php echo store_h($phone !== '' ? $phone : '—'); ?></strong></div></div>
			<div class="ke-profile-row"><i class="bx bx-category"></i><div><span>Type</span><strong><?php echo store_h($typeLabel); ?></strong></div></div>
			<div class="ke-profile-row"><i class="bx bx-map"></i><div><span>Area</span><strong><?php echo store_h($areaLabel); ?></strong></div></div>
			<div class="ke-profile-row"><i class="bx bx-globe"></i><div><span>Website</span><strong><?php echo store_h($website !== '' ? $website : '—'); ?></strong></div></div>
			<div class="ke-profile-row"><i class="bx bx-badge-check"></i><div><span>Status</span><strong><?php echo store_h($statusLabel); ?> partnership</strong></div></div>
		</section>
		<section class="ke-panel">
			<div class="ke-panel-head"><h2>Announcements</h2><a href="/partner/support.php">Support</a></div>
			<div class="ke-note-row"><i class="bx bx-purchase-tag"></i><div><strong>Contracted rates</strong><span>When the office adds a market price and contracted rate, it shows on this dashboard.</span></div></div>
			<div class="ke-note-row"><i class="bx bx-lock-alt"></i><div><strong>Your password</strong><span>Change the temporary password from the Password page. The office login stays separate.</span></div></div>
			<div class="ke-note-row"><i class="bx bx-envelope"></i><div><strong>Need the office?</strong><span>Write support@kuyaelytours.com and include your business name.</span></div></div>
		</section>
	</div>
</div>
<?php partner_layout_end(); ?>
