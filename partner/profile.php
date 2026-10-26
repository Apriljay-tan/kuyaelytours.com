<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require __DIR__ . '/chrome.php';

$bundle = partner_bundle();
$user = $bundle['user'];
$profile = $bundle['profile'];
$desk = $bundle['desk'];
$partnerId = trim((string) ($profile['id'] ?? ''));
$notice = '';
$error = '';
if (isset($_SESSION['ke_partner_profile_ok'])) {
	$notice = (string) $_SESSION['ke_partner_profile_ok'];
	unset($_SESSION['ke_partner_profile_ok']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$action = (string) ($_POST['action'] ?? '');
	if ($partnerId === '') {
		$error = 'The office has not finished this partner record yet.';
	} elseif ($action === 'remove-logo') {
		store_partner_logo_delete(store_partner_logo_path($desk));
		$desk['logo'] = '';
		store_partner_desk_put($partnerId, $desk);
		$_SESSION['ke_partner_profile_ok'] = 'Logo removed.';
		store_redirect('/partner/profile.php');
	} elseif ($action === 'save-logo') {
		$saved = store_partner_save_logo(is_array($_FILES['logo'] ?? null) ? $_FILES['logo'] : []);
		if ($saved['error'] !== '') {
			$error = $saved['error'];
		} else {
			$previous = store_partner_logo_path($desk);
			$desk['logo'] = $saved['path'];
			if (store_partner_desk_put($partnerId, $desk)) {
				if ($previous !== '' && $previous !== $saved['path']) {
					store_partner_logo_delete($previous);
				}
				$_SESSION['ke_partner_profile_ok'] = 'Logo saved. It now shows on your profile and in the office partner page.';
				store_redirect('/partner/profile.php');
			}
			store_partner_logo_delete($saved['path']);
			$desk['logo'] = $previous;
			$error = 'The logo could not be saved.';
		}
	} elseif ($action === 'save-visible') {
		$desk['visible'] = (string) ($_POST['visible'] ?? '0') === '1' ? '1' : '0';
		if (store_partner_desk_put($partnerId, $desk)) {
			$_SESSION['ke_partner_profile_ok'] = $desk['visible'] === '1'
				? 'Your profile is visible.'
				: 'Your profile is hidden.';
			store_redirect('/partner/profile.php');
		}
		$error = 'That setting could not be saved.';
	} elseif ($action === 'save-profile') {
		$business = store_partner_clean((string) ($_POST['business_name'] ?? ''), 160);
		$contact = store_partner_clean((string) ($_POST['contact_name'] ?? ''), 140);
		$phone = store_partner_clean((string) ($_POST['phone'] ?? ''), 40);
		$website = store_partner_clean((string) ($_POST['website'] ?? ''), 190);
		$address = store_partner_clean((string) ($_POST['address'] ?? ''), 180);
		$notes = store_partner_clean((string) ($_POST['notes'] ?? ''), 500);
		$type = (string) ($_POST['partner_type'] ?? '');
		$area = (string) ($_POST['area'] ?? '');
		if (strlen($business) < 2 || $contact === '') {
			$error = 'Enter the business name and the contact person.';
		} else {
			if (!isset(store_partner_types()[$type])) {
				$type = (string) ($profile['partner_type'] ?? 'other');
			}
			if (!isset(store_partner_areas()[$area])) {
				$area = (string) ($profile['area'] ?? 'several');
			}
			$changed = $business !== (string) ($profile['business_name'] ?? '')
				|| $contact !== (string) ($profile['contact_name'] ?? '')
				|| $phone !== (string) ($profile['phone'] ?? '')
				|| $website !== (string) ($profile['website'] ?? '')
				|| $type !== (string) ($profile['partner_type'] ?? '')
				|| $area !== (string) ($profile['area'] ?? '');
			$profile['business_name'] = $business;
			$profile['contact_name'] = $contact;
			$profile['phone'] = $phone;
			$profile['website'] = $website;
			$profile['partner_type'] = $type;
			$profile['area'] = $area;
			if ($changed && !store_partner_save_row($profile)) {
				$error = 'The profile could not be saved.';
			} else {
				$userId = (string) ($user['id'] ?? '');
				if ($userId !== '' && ($contact !== (string) ($user['name'] ?? '') || $phone !== (string) ($user['phone'] ?? ''))) {
					$fresh = store_find_user_id($userId);
					if ($fresh) {
						$fresh['name'] = $contact;
						$fresh['phone'] = $phone;
						store_update_user($fresh);
					}
				}
				$desk['address'] = $address;
				$desk['notes'] = $notes;
				if (store_partner_desk_put($partnerId, $desk)) {
					$_SESSION['ke_partner_profile_ok'] = 'Profile saved. The office partner page uses these details.';
					store_redirect('/partner/profile.php');
				}
				$error = 'The profile could not be saved.';
			}
		}
	} else {
		$error = 'Please try that again.';
	}
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	$error = 'Please try that again.';
}

$bundle = partner_bundle();
$user = $bundle['user'];
$profile = $bundle['profile'];
$desk = $bundle['desk'];
if ($error !== '' && (string) ($_POST['action'] ?? '') === 'save-profile') {
	$profile['business_name'] = (string) ($_POST['business_name'] ?? $profile['business_name'] ?? '');
	$profile['contact_name'] = (string) ($_POST['contact_name'] ?? $profile['contact_name'] ?? '');
	$profile['phone'] = (string) ($_POST['phone'] ?? $profile['phone'] ?? '');
	$profile['website'] = (string) ($_POST['website'] ?? $profile['website'] ?? '');
	$profile['partner_type'] = (string) ($_POST['partner_type'] ?? $profile['partner_type'] ?? '');
	$profile['area'] = (string) ($_POST['area'] ?? $profile['area'] ?? '');
	$desk['address'] = (string) ($_POST['address'] ?? $desk['address'] ?? '');
	$desk['notes'] = (string) ($_POST['notes'] ?? $desk['notes'] ?? '');
}

$name = trim((string) ($user['name'] ?? ''));
$business = trim((string) ($profile['business_name'] ?? ''));
$email = trim((string) ($user['email'] ?? $profile['email'] ?? ''));
$phone = trim((string) ($profile['phone'] ?? $user['phone'] ?? ''));
$contact = trim((string) ($profile['contact_name'] ?? $name));
$typeKey = (string) ($profile['partner_type'] ?? '');
$areaKey = (string) ($profile['area'] ?? '');
$typeLabel = store_partner_types()[$typeKey] ?? 'Partner';
$areaLabel = store_partner_areas()[$areaKey] ?? '—';
$website = trim((string) ($profile['website'] ?? ''));
$address = trim((string) ($desk['address'] ?? ''));
$notes = trim((string) ($desk['notes'] ?? ''));
$logo = store_partner_logo_path($desk);
$visible = (string) ($desk['visible'] ?? '1') === '1';
$status = (string) ($profile['status'] ?? '');
$statusLabel = $status === 'approved' ? 'Active Partner' : ($status === 'declined' ? 'Inactive' : 'Pending');
$statusClass = $status === 'approved' ? 'is-on' : ($status === 'declined' ? 'is-off' : 'is-wait');
$regions = [
	'cebu' => 'Cebu, Philippines',
	'bohol' => 'Bohol, Philippines',
	'siquijor' => 'Siquijor, Philippines',
	'dumaguete' => 'Dumaguete, Philippines',
	'several' => 'Cebu, Philippines',
];
$region = $regions[$areaKey] ?? 'Cebu, Philippines';
$joined = '—';
$created = trim((string) ($profile['created'] ?? ''));
if ($created !== '') {
	try {
		$joined = (new DateTimeImmutable($created))->format('M j, Y');
	} catch (Throwable $e) {
		$joined = '—';
	}
}
$initials = partner_initials($business !== '' ? $business : ($contact !== '' ? $contact : 'Partner'));
$about = 'Trusted travel partner offering amazing Cebu tours and island experiences for local and international guests.';

partner_layout_start('Profile Settings', 'profile.php', 'Partner', false);
?>
<div class="ke-set">
	<header class="ke-set-top">
		<div>
			<h1>Profile Settings</h1>
			<p>Manage your business information, preferences, and account settings. Keep your details up to date so guests can easily reach you.</p>
		</div>
		<nav class="ke-set-crumb" aria-label="Breadcrumb">
			<a href="/partner/"><i class="bx bx-home-alt"></i> Dashboard</a>
			<span>&rsaquo;</span>
			<strong>Profile Settings</strong>
		</nav>
	</header>

	<?php if ($notice !== ''): ?><div class="ke-set-alert is-ok"><?php echo store_h($notice); ?></div><?php endif; ?>
	<?php if ($error !== ''): ?><div class="ke-set-alert is-bad"><?php echo store_h($error); ?></div><?php endif; ?>

	<section class="ke-set-hero">
		<div class="ke-set-id">
			<button class="ke-set-avatar" id="ke-logo-cam" type="button" aria-label="Choose a logo">
				<?php if ($logo !== ''): ?>
					<img src="<?php echo store_h($logo); ?>" alt="">
				<?php else: ?>
					<span><?php echo store_h($initials); ?></span>
				<?php endif; ?>
				<i class="bx bx-camera"></i>
			</button>
			<div class="ke-set-id-copy">
				<div class="ke-set-name">
					<h2><?php echo store_h($business !== '' ? $business : 'Partner'); ?></h2>
					<?php if ($status === 'approved'): ?><i class="bx bxs-badge-check" aria-label="Verified"></i><?php endif; ?>
					<span class="ke-set-pill"><i class="bx bx-award"></i> Partner</span>
				</div>
				<p class="ke-set-loc"><i class="bx bx-map-pin"></i> <?php echo store_h($areaLabel); ?></p>
				<p class="ke-set-about"><?php echo store_h($about); ?></p>
			</div>
		</div>
		<div class="ke-set-hero-actions">
			<button class="ke-set-btn is-primary" type="submit" form="ke-business"><i class="bx bx-pencil"></i> Edit Profile</button>
			<a class="ke-set-btn is-line" href="/partner/support.php"><i class="bx bx-headphone"></i> Contact Support</a>
		</div>
	</section>

	<div class="ke-set-grid">
		<div class="ke-set-col">
			<form class="ke-set-card" id="ke-business" method="post">
				<input type="hidden" name="csrf" value="<?php echo store_h(store_csrf_token()); ?>">
				<input type="hidden" name="action" value="save-profile">
				<div class="ke-set-card-head">
					<div class="ke-set-card-title">
						<span class="ke-set-ico"><i class="bx bx-buildings"></i></span>
						<div>
							<h3>Business Information</h3>
							<p>This information will be used across the partner portal and may be visible to your guests.</p>
						</div>
					</div>
					<button class="ke-set-edit" type="submit"><i class="bx bx-pencil"></i> Edit</button>
				</div>
				<div class="ke-set-fields">
					<label class="ke-set-field">
						<span><i class="bx bx-buildings"></i> Business Name</span>
						<input id="business_name" name="business_name" type="text" value="<?php echo store_h($business); ?>" maxlength="160" required>
					</label>
					<label class="ke-set-field">
						<span><i class="bx bx-user"></i> Contact Person</span>
						<input name="contact_name" type="text" value="<?php echo store_h($contact); ?>" maxlength="140" required>
					</label>
					<label class="ke-set-field">
						<span><i class="bx bx-envelope"></i> Email Address</span>
						<input type="email" value="<?php echo store_h($email); ?>" readonly>
					</label>
					<label class="ke-set-field">
						<span><i class="bx bx-phone"></i> Phone Number</span>
						<input name="phone" type="text" value="<?php echo store_h($phone); ?>" maxlength="40">
					</label>
					<label class="ke-set-field">
						<span><i class="bx bx-briefcase"></i> Business Type</span>
						<select name="partner_type">
							<?php foreach (store_partner_types() as $key => $label): ?>
								<option value="<?php echo store_h($key); ?>"<?php echo $typeKey === $key ? ' selected' : ''; ?>><?php echo store_h($label); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="ke-set-field">
						<span><i class="bx bx-map"></i> Service Area</span>
						<select name="area">
							<?php foreach (store_partner_areas() as $key => $label): ?>
								<option value="<?php echo store_h($key); ?>"<?php echo $areaKey === $key ? ' selected' : ''; ?>><?php echo store_h($label); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="ke-set-field is-wide">
						<span><i class="bx bx-map-pin"></i> Business Address</span>
						<input name="address" type="text" value="<?php echo store_h($address); ?>" maxlength="180" placeholder="—">
					</label>
					<label class="ke-set-field is-wide">
						<span><i class="bx bx-globe"></i> Website</span>
						<input name="website" type="text" value="<?php echo store_h($website); ?>" maxlength="190" placeholder="—">
					</label>
					<label class="ke-set-field is-wide">
						<span><i class="bx bx-note"></i> Additional Notes</span>
						<textarea name="notes" maxlength="500" placeholder="Add any additional information about your business..."><?php echo store_h($notes); ?></textarea>
					</label>
				</div>
			</form>

			<section class="ke-set-card">
				<div class="ke-set-card-head">
					<div class="ke-set-card-title">
						<span class="ke-set-ico"><i class="bx bx-image"></i></span>
						<div>
							<h3>Company Logo</h3>
							<p>Upload your business logo. This will be displayed on your partner page and in the office dashboard.</p>
						</div>
					</div>
				</div>
				<form id="ke-logo-form" method="post" enctype="multipart/form-data">
					<input type="hidden" name="csrf" value="<?php echo store_h(store_csrf_token()); ?>">
					<input type="hidden" name="action" value="save-logo">
					<div class="ke-set-logo">
						<div class="ke-set-thumb" id="ke-logo-thumb">
							<?php if ($logo !== ''): ?>
								<img src="<?php echo store_h($logo); ?>" alt="">
							<?php else: ?>
								<span><?php echo store_h($initials); ?></span>
							<?php endif; ?>
						</div>
						<label class="ke-set-drop" id="ke-drop">
							<input class="ke-set-file" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
							<i class="bx bx-cloud-upload"></i>
							<strong>Drag and drop your logo here</strong>
							<em id="ke-logo-name">or click to browse files</em>
						</label>
						<div class="ke-set-logo-actions">
							<button class="ke-set-upload" type="submit"><i class="bx bx-upload"></i> Upload New Logo</button>
							<button class="ke-set-remove" type="submit" form="ke-logo-remove"<?php echo $logo === '' ? ' disabled' : ''; ?>><i class="bx bx-trash"></i> Remove Logo</button>
						</div>
					</div>
					<p class="ke-set-hint">JPG, PNG, WEBP, or GIF. Max file size: 2 MB.</p>
				</form>
				<form id="ke-logo-remove" method="post">
					<input type="hidden" name="csrf" value="<?php echo store_h(store_csrf_token()); ?>">
					<input type="hidden" name="action" value="remove-logo">
				</form>
			</section>
		</div>

		<aside class="ke-set-col">
			<section class="ke-set-card">
				<div class="ke-set-card-head">
					<div class="ke-set-card-title">
						<span class="ke-set-ico"><i class="bx bx-bar-chart-alt-2"></i></span>
						<div>
							<h3>Account Overview</h3>
							<p>Your partnership details and account status.</p>
						</div>
					</div>
				</div>
				<div class="ke-set-stats">
					<div><span><i class="bx bx-id-card"></i> Partnership Status</span><b class="ke-set-live <?php echo store_h($statusClass); ?>"><?php echo store_h($statusLabel); ?></b></div>
					<div><span><i class="bx bx-calendar"></i> Date Joined</span><b><?php echo store_h($joined); ?></b></div>
					<div><span><i class="bx bx-map-pin"></i> Region</span><b><?php echo store_h($region); ?></b></div>
					<div><span><i class="bx bx-user"></i> Partner Type</span><b><?php echo store_h($typeLabel); ?></b></div>
				</div>
			</section>

			<section class="ke-set-card">
				<div class="ke-set-card-head">
					<div class="ke-set-card-title">
						<span class="ke-set-ico"><i class="bx bx-cog"></i></span>
						<div>
							<h3>Preferences</h3>
							<p>Manage how your profile appears and other preferences.</p>
						</div>
					</div>
				</div>
				<form class="ke-set-pref" method="post">
					<input type="hidden" name="csrf" value="<?php echo store_h(store_csrf_token()); ?>">
					<input type="hidden" name="action" value="save-visible">
					<span class="ke-set-ico is-soft"><i class="bx bx-show"></i></span>
					<div>
						<strong>Profile Visibility</strong>
						<p>Show your business information on our partner directory and to potential guests.</p>
					</div>
					<label class="ke-set-switch">
						<input type="checkbox" name="visible" value="1"<?php echo $visible ? ' checked' : ''; ?> onchange="this.form.submit()">
						<span></span>
					</label>
				</form>
			</section>

			<section class="ke-set-card">
				<div class="ke-set-card-head">
					<div class="ke-set-card-title">
						<span class="ke-set-ico"><i class="bx bx-shield-quarter"></i></span>
						<div>
							<h3>Security</h3>
							<p>Keep your account secure with a strong password.</p>
						</div>
					</div>
				</div>
				<div class="ke-set-secure">
					<span class="ke-set-ico is-soft"><i class="bx bx-lock-alt"></i></span>
					<div>
						<strong>Password</strong>
						<p>Update your password regularly to keep your account safe.</p>
					</div>
					<a class="ke-set-pass" href="/partner/password.php">Change Password</a>
				</div>
			</section>
		</aside>
	</div>
</div>
<script>
(function () {
	var input = document.getElementById('logo');
	var drop = document.getElementById('ke-drop');
	var name = document.getElementById('ke-logo-name');
	var cam = document.getElementById('ke-logo-cam');
	var thumb = document.getElementById('ke-logo-thumb');
	function paint(file) {
		if (!file) return;
		if (name) name.textContent = file.name;
		if (!thumb || !/^image\//.test(file.type)) return;
		var reader = new FileReader();
		reader.onload = function () {
			thumb.innerHTML = '<img alt="" src="' + reader.result + '">';
			if (cam) {
				var img = cam.querySelector('img');
				if (!img) {
					img = document.createElement('img');
					img.alt = '';
					var hold = cam.querySelector('span');
					if (hold) hold.replaceWith(img);
					else cam.insertBefore(img, cam.firstChild);
				}
				img.src = reader.result;
			}
		};
		reader.readAsDataURL(file);
	}
	if (input && drop) {
		drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.classList.add('is-over'); });
		drop.addEventListener('dragleave', function () { drop.classList.remove('is-over'); });
		drop.addEventListener('drop', function (e) {
			e.preventDefault();
			drop.classList.remove('is-over');
			if (e.dataTransfer && e.dataTransfer.files.length) {
				input.files = e.dataTransfer.files;
				paint(input.files[0]);
			}
		});
		input.addEventListener('change', function () { paint(input.files && input.files[0]); });
	}
	if (cam && input) cam.addEventListener('click', function () { input.click(); });
})();
</script>
<?php partner_layout_end(); ?>
