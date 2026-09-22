<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
store_require_login('/account/profile.php');
$user = store_user();
if (!$user) {
	store_redirect('/account/login.php');
}

$meta = store_profile_meta((string) $user['id']);
$ok = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$user['name'] = trim((string) ($_POST['name'] ?? $user['name']));
	$user['phone'] = trim((string) ($_POST['phone'] ?? $user['phone']));
	$meta = [
		'whatsapp' => trim((string) ($_POST['whatsapp'] ?? '')),
		'address' => trim((string) ($_POST['address'] ?? '')),
		'emergency_name' => trim((string) ($_POST['emergency_name'] ?? '')),
		'emergency_phone' => trim((string) ($_POST['emergency_phone'] ?? '')),
		'destinations' => trim((string) ($_POST['destinations'] ?? $meta['destinations'] ?? '')),
		'travel_style' => trim((string) ($_POST['travel_style'] ?? $meta['travel_style'] ?? '')),
		'contact_method' => trim((string) ($_POST['contact_method'] ?? $meta['contact_method'] ?? '')),
		'deals' => isset($_POST['deals']) ? '1' : '0',
	];
	if ($user['name'] === '') {
		$error = 'Name is required.';
	} elseif (store_update_user($user)) {
		store_save_profile_meta((string) $user['id'], $meta);
		$ok = 'Account details saved.';
	} else {
		$error = 'Could not save your profile.';
	}
}

$phone = trim((string) ($user['phone'] ?? ''));
$whatsapp = trim((string) ($meta['whatsapp'] ?? ''));
if ($whatsapp === '') {
	$whatsapp = $phone;
}
$address = (string) ($meta['address'] ?? '');
$emergencyName = (string) ($meta['emergency_name'] ?? '');
$emergencyPhone = (string) ($meta['emergency_phone'] ?? '');
$destinations = (string) ($meta['destinations'] ?? 'cebu-bohol-siquijor-dumaguete');
$travelStyle = (string) ($meta['travel_style'] ?? 'family');
$contactMethod = (string) ($meta['contact_method'] ?? 'whatsapp');
$dealsOn = ($meta['deals'] ?? '1') !== '0';

$oauth = trim((string) ($user['oauth_provider'] ?? ''));
$signin = $oauth !== '' ? 'Signed in with ' . ucfirst($oauth) : 'Email and password';

$bookings = store_user_bookings((string) $user['id']);
$upcoming = array_values(array_filter($bookings, static function ($row) {
	return (string) ($row['status'] ?? '') !== 'cancelled';
}));
$cartCount = store_cart_count();
$panel = (string) ($_GET['panel'] ?? 'overview');
if (!in_array($panel, ['overview', 'travelers', 'wishlist', 'security'], true)) {
	$panel = 'overview';
}

$name = (string) $user['name'];
$first = store_first_name($name);
$email = (string) $user['email'];
$csrf = store_h(store_csrf_token());

function ke_profile_icon(string $name): string
{
	$icons = [
		'mail' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'phone' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.8h3.2l1 3.2-2 1.4a12 12 0 0 0 6.4 6.4l1.4-2 3.2 1V20a1.8 1.8 0 0 1-1.8 1.8A16.2 16.2 0 0 1 3.2 5.6 1.8 1.8 0 0 1 5 3.8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
		'pin' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.2 7-11.2A7 7 0 0 0 5 9.8C5 14.8 12 21 12 21Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="9.8" r="2.2" stroke="currentColor" stroke-width="1.7"/></svg>',
		'user' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M5 19.2c.8-3.2 3.4-5.2 7-5.2s6.2 2 7 5.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
		'wa' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19.05 4.91A9.82 9.82 0 0 0 12.04 2C6.5 2 2 6.48 2 12a9.93 9.93 0 0 0 1.3 4.92L2 22l5.23-1.37A10 10 0 0 0 12.04 22C17.56 22 22 17.52 22 12c0-2.65-1.03-5.14-2.95-7.09ZM12.04 20.15a8.18 8.18 0 0 1-4.17-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 1 1 6.96 3.86Zm4.52-6.16c-.25-.12-1.47-.72-1.7-.8-.22-.09-.39-.12-.55.12-.17.25-.64.8-.78.97-.14.16-.29.18-.54.06-.25-.13-1.05-.39-2-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.1-.51.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.55-.42h-.48c-.17 0-.43.06-.66.31-.22.25-.87.85-.87 2.07 0 1.22.89 2.4 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.47-.07 1.47-.6 1.67-1.18.2-.58.2-1.07.14-1.18-.06-.11-.22-.17-.47-.29Z"/></svg>',
		'cal' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 3.5v3M16 3.5v3M3.5 10h17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
		'plane' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 12.5 21 4l-3.8 16.2-4.6-5.1-3.2 3.4-.2-5.2L3 12.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
		'cart' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h15l-1.4 8.2a2 2 0 0 1-2 1.8H9.2a2 2 0 0 1-2-1.7L5.2 4H3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.4" fill="currentColor"/><circle cx="18" cy="20" r="1.4" fill="currentColor"/></svg>',
		'bag' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12l.8 12.2a2 2 0 0 1-2 2.1H7.2a2 2 0 0 1-2-2.1L6 8Z" stroke="currentColor" stroke-width="1.7"/><path d="M9 8V6.5A3 3 0 0 1 15 6.5V8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
		'heart' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
		'shield' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5 19 6.2v5.5c0 4.4-3 7.4-7 8.8-4-1.4-7-4.4-7-8.8V6.2L12 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>',
		'people' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="2.6" stroke="currentColor" stroke-width="1.7"/><circle cx="16.5" cy="9" r="2.1" stroke="currentColor" stroke-width="1.7"/><path d="M3.8 18.5c.7-2.8 3-4.4 5.2-4.4s4.5 1.6 5.2 4.4M13.4 14.4c1.6-.4 3.4.5 4.3 2.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
		'gear' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M12 3.6v2.2M12 18.2v2.2M3.6 12h2.2M18.2 12h2.2M6.1 6.1l1.6 1.6M16.3 16.3l1.6 1.6M17.9 6.1l-1.6 1.6M7.7 16.3l-1.6 1.6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>',
		'edit' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 16.8V20h3.2L18.6 8.6l-3.2-3.2L4 16.8Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M13.7 6.1 17.9 10.3" stroke="currentColor" stroke-width="1.7"/></svg>',
		'check' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M8 12.2 10.6 15 16 9.4" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'chev' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
	];
	return $icons[$name] ?? '';
}

function ke_profile_options(array $options, string $current): string
{
	$html = '';
	foreach ($options as $value => $label) {
		$sel = (string) $value === $current ? ' selected' : '';
		$html .= '<option value="' . store_h((string) $value) . '"' . $sel . '>' . store_h($label) . '</option>';
	}
	return $html;
}

$nav = [
	'overview' => ['/account/profile.php', 'Overview', 'user'],
	'bookings' => ['/account/bookings.php', 'My Bookings', 'cal'],
	'profile' => ['/account/profile.php#profile-details', 'Profile Details', 'people'],
	'travelers' => ['/account/profile.php?panel=travelers', 'Saved Travelers', 'people'],
	'wishlist' => ['/account/profile.php?panel=wishlist', 'Wishlist', 'heart'],
	'security' => ['/account/profile.php?panel=security', 'Security', 'shield'],
];
$activeNav = $panel === 'overview' ? 'overview' : $panel;
if ($panel === 'overview') {
	$activeNav = 'overview';
}

ob_start();
?>
<section class="ke-dash-hero">
	<div class="ke-dash-hero-copy">
		<p class="ke-dash-kicker">My account</p>
		<h1>Good to see you again, <em><?= store_h($first) ?>!</em></h1>
		<p class="ke-dash-tag">Same roads. More adventures.</p>
		<p class="ke-dash-lede">Manage your bookings, update your profile, and plan your next trip with Kuya Ely Tours.</p>
	</div>
	<div class="ke-dash-hero-art" aria-hidden="true">
		<span class="ke-dash-script">Explore<br>More<br>Together</span>
		<ul>
			<li>Cebu</li>
			<li>Bohol</li>
			<li>Siquijor</li>
			<li>Dumaguete</li>
			<li>and beyond</li>
		</ul>
	</div>
</section>

<section class="ke-dash-id">
	<div class="ke-dash-id-main">
		<span class="ke-dash-avatar"><?= store_h(store_initials($name)) ?></span>
		<div>
			<p class="ke-dash-badge"><?= ke_profile_icon('check') ?> Verified traveler</p>
			<h2><?= store_h($name) ?></h2>
			<p class="ke-dash-id-meta">
				<span><?= ke_profile_icon('mail') ?> <?= store_h($email) ?></span>
				<span><?= ke_profile_icon('phone') ?> <?= $phone !== '' ? store_h($phone) : 'Add a phone number' ?></span>
			</p>
		</div>
	</div>
	<div class="ke-dash-id-actions">
		<a class="ke-dash-ghost" href="#profile-details"><?= ke_profile_icon('edit') ?> Edit Profile</a>
		<blockquote>
			“Collect moments, not things.”
			<cite>— Kuya Ely Tours</cite>
		</blockquote>
	</div>
</section>

<?php if ($ok !== ''): ?>
	<p class="ke-ok ke-banner ke-dash-banner"><?= store_h($ok) ?></p>
<?php elseif ($error !== ''): ?>
	<p class="ke-err ke-banner ke-dash-banner"><?= store_h($error) ?></p>
<?php endif; ?>

<nav class="ke-dash-stats" aria-label="Account snapshot">
	<a href="/account/bookings.php">
		<span class="ke-dash-stat-ico"><?= ke_profile_icon('cal') ?></span>
		<span>
			<strong><?= count($bookings) ?></strong>
			<b>Total Bookings</b>
			<small>Your travel journeys with us</small>
		</span>
		<?= ke_profile_icon('chev') ?>
	</a>
	<a href="/account/bookings.php">
		<span class="ke-dash-stat-ico"><?= ke_profile_icon('plane') ?></span>
		<span>
			<strong><?= count($upcoming) ?></strong>
			<b>Upcoming Trips</b>
			<small>Adventures waiting for you</small>
		</span>
		<?= ke_profile_icon('chev') ?>
	</a>
	<a href="/shop/cart.php">
		<span class="ke-dash-stat-ico"><?= ke_profile_icon('cart') ?></span>
		<span>
			<strong><?= (int) $cartCount ?></strong>
			<b>Items in Cart</b>
			<small>Ready to book</small>
		</span>
		<?= ke_profile_icon('chev') ?>
	</a>
	<a href="https://wa.me/639209851802">
		<span class="ke-dash-stat-ico ke-dash-stat-wa"><?= ke_profile_icon('wa') ?></span>
		<span>
			<strong class="ke-dash-stat-label">Chat with Us</strong>
			<b>WhatsApp Support</b>
			<small>Get quick assistance</small>
		</span>
		<?= ke_profile_icon('chev') ?>
	</a>
</nav>

<div class="ke-dash-grid">
	<aside class="ke-dash-side">
		<nav aria-label="Account sections">
			<?php foreach ($nav as $key => $item): ?>
				<a href="<?= store_h($item[0]) ?>"<?= $key === $activeNav ? ' aria-current="page"' : '' ?>>
					<?= ke_profile_icon($item[2]) ?>
					<?= store_h($item[1]) ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<article class="ke-dash-promo">
			<p>New destinations are calling.</p>
			<p>Let Kuya Ely take you there.</p>
			<a class="ke-dash-gold" href="/tours-and-packages.php">Browse Tours <?= ke_profile_icon('chev') ?></a>
		</article>
	</aside>

	<div class="ke-dash-main">
		<?php if ($panel === 'travelers'): ?>
			<section class="ke-dash-card ke-dash-empty-card">
				<h2><?= ke_profile_icon('people') ?> Saved Travelers</h2>
				<p>Keep companions on file so the next booking is quicker. This list stays on your account only.</p>
				<div class="ke-dash-empty">
					<span><?= ke_profile_icon('people') ?></span>
					<strong>No saved travelers yet</strong>
					<p>Add names on your next checkout and they can appear here.</p>
				</div>
			</section>
		<?php elseif ($panel === 'wishlist'): ?>
			<section class="ke-dash-card ke-dash-empty-card">
				<h2><?= ke_profile_icon('heart') ?> Wishlist</h2>
				<p>Tours you want to take later will show up here.</p>
				<div class="ke-dash-empty">
					<span><?= ke_profile_icon('heart') ?></span>
					<strong>Your wishlist is empty</strong>
					<p>Browse the fleet and save a trip when you are ready.</p>
					<a class="ke-dash-gold" href="/tours-and-packages.php">Explore Tours <?= ke_profile_icon('chev') ?></a>
				</div>
			</section>
		<?php elseif ($panel === 'security'): ?>
			<section class="ke-dash-card ke-dash-empty-card">
				<h2><?= ke_profile_icon('shield') ?> Security</h2>
				<p class="ke-dash-card-lede">How you sign in to Kuya Ely Tours.</p>
				<dl class="ke-dash-secure">
					<div><dt>Sign-in method</dt><dd><?= store_h($signin) ?></dd></div>
					<div><dt>Email</dt><dd><?= store_h($email) ?></dd></div>
				</dl>
				<p class="ke-hint">Email cannot be changed here. Use a new account only if you need a different inbox.</p>
				<a class="ke-dash-ghost" href="/account/logout.php">Log out</a>
			</section>
		<?php else: ?>
			<form method="post" class="ke-dash-form ke-dash-form-grid">
			<input type="hidden" name="csrf" value="<?= $csrf ?>">
			<section class="ke-dash-card" id="profile-details">
				<h2><?= ke_profile_icon('user') ?> Profile Details</h2>
				<p class="ke-dash-card-lede">Keep your information up to date for a smoother booking experience.</p>
					<div class="ke-dash-fields">
						<label>Full Name
							<span class="ke-dash-input"><?= ke_profile_icon('user') ?><input type="text" name="name" value="<?= store_h($name) ?>" autocomplete="name" required></span>
						</label>
						<label>Email Address
							<span class="ke-dash-input"><?= ke_profile_icon('mail') ?><input type="email" value="<?= store_h($email) ?>" disabled></span>
						</label>
						<label>Phone Number
							<span class="ke-dash-input"><?= ke_profile_icon('phone') ?><input type="tel" name="phone" value="<?= store_h($phone) ?>" autocomplete="tel" placeholder="+63"></span>
						</label>
						<label>WhatsApp Number
							<span class="ke-dash-input ke-dash-input-wa"><?= ke_profile_icon('wa') ?><input type="tel" name="whatsapp" value="<?= store_h($whatsapp) ?>" placeholder="+63"></span>
						</label>
						<label class="ke-dash-span">Address
							<span class="ke-dash-input"><?= ke_profile_icon('pin') ?><input type="text" name="address" value="<?= store_h($address) ?>" autocomplete="street-address" placeholder="Lapu-Lapu City, Cebu, Philippines"></span>
						</label>
						<label>Emergency Contact Name
							<span class="ke-dash-input"><?= ke_profile_icon('user') ?><input type="text" name="emergency_name" value="<?= store_h($emergencyName) ?>" autocomplete="off"></span>
						</label>
						<label>Emergency Contact Number
							<span class="ke-dash-input"><?= ke_profile_icon('phone') ?><input type="tel" name="emergency_phone" value="<?= store_h($emergencyPhone) ?>" autocomplete="off"></span>
						</label>
					</div>
					<div class="ke-dash-form-actions">
						<button class="ke-dash-gold" type="submit"><?= ke_profile_icon('check') ?> Save Changes</button>
						<a class="ke-dash-cancel" href="/account/profile.php">Cancel</a>
					</div>
			</section>

			<section class="ke-dash-card ke-dash-prefs">
				<h2><?= ke_profile_icon('gear') ?> Travel Preferences</h2>
				<p class="ke-dash-card-lede">Help us personalize your travel experience.</p>
					<label>Preferred Destinations
						<select name="destinations">
							<?= ke_profile_options([
								'cebu-bohol-siquijor-dumaguete' => 'Cebu, Bohol, Siquijor, Dumaguete',
								'cebu' => 'Cebu',
								'bohol' => 'Bohol',
								'siquijor' => 'Siquijor',
								'dumaguete' => 'Dumaguete',
							], $destinations) ?>
						</select>
					</label>
					<div class="ke-dash-fields">
						<label>Travel Style
							<select name="travel_style">
								<?= ke_profile_options([
									'family' => 'Family Travel',
									'couple' => 'Couple',
									'friends' => 'Friends',
									'solo' => 'Solo',
									'corporate' => 'Corporate',
								], $travelStyle) ?>
							</select>
						</label>
						<label>Preferred Contact Method
							<select name="contact_method">
								<?= ke_profile_options([
									'whatsapp' => 'WhatsApp',
									'email' => 'Email',
									'phone' => 'Phone',
								], $contactMethod) ?>
							</select>
						</label>
					</div>
					<label class="ke-dash-check">
						<input type="checkbox" name="deals" value="1"<?= $dealsOn ? ' checked' : '' ?>>
						<span><strong>Receive travel deals and updates</strong><small>Be the first to know about new tours and promos.</small></span>
					</label>
			</section>
			</form>

			<section class="ke-dash-card ke-dash-trips">
				<div class="ke-dash-trips-head">
					<h2><?= ke_profile_icon('cal') ?> Upcoming Trips</h2>
					<a href="/account/bookings.php">View all</a>
				</div>
				<?php if (!$upcoming): ?>
					<div class="ke-dash-empty">
						<span><?= ke_profile_icon('bag') ?></span>
						<strong>No upcoming trips yet</strong>
						<p>Your next adventure is just a booking away!</p>
						<a class="ke-dash-gold" href="/tours-and-packages.php">Explore Tours <?= ke_profile_icon('chev') ?></a>
					</div>
				<?php else: ?>
					<ul>
						<?php foreach (array_slice($upcoming, 0, 3) as $booking): ?>
							<?php
							$items = is_array($booking['items'] ?? null) ? $booking['items'] : [];
							$firstItem = $items[0] ?? [];
							$product = store_product((string) ($firstItem['product_id'] ?? ''));
							$tripName = (string) ($product['name'] ?? 'Private trip');
							$tripDate = (string) ($firstItem['date'] ?? ($booking['created'] ?? ''));
							$status = store_booking_status_label((string) ($booking['status'] ?? ''));
							?>
							<li>
								<span><?= ke_profile_icon('bag') ?></span>
								<div>
									<strong><?= store_h($tripName) ?></strong>
									<small><?= store_h($tripDate) ?> · <?= store_h($status) ?></small>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</div>
</div>
<?php
store_page('My Account', ob_get_clean(), '', true, 'ke-site ke-dash');
