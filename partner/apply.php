<?php
declare(strict_types=1);

require dirname(__DIR__) . '/store/bootstrap.php';
require dirname(__DIR__) . '/store/marketing-chrome.php';

$sent = (string) ($_GET['sent'] ?? '') === '1';
$mailState = '';
if ($sent) {
	$mailState = (string) ($_SESSION['ke_partner_mail'] ?? '');
	unset($_SESSION['ke_partner_mail']);
}
$error = '';
$values = [
	'business_name' => '',
	'contact_name' => '',
	'email' => '',
	'phone' => '',
	'partner_type' => '',
	'area' => '',
	'website' => '',
	'message' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	foreach ($values as $key => $_) {
		$values[$key] = trim((string) ($_POST[$key] ?? ''));
	}
	if (!store_csrf_ok()) {
		$error = 'Please try that again.';
	} else {
		$error = store_partner_apply($_POST);
		if ($error === '') {
			store_redirect('/partner/apply.php?sent=1');
		}
	}
}

$h = static function (string $value): string {
	return store_h($value);
};

$typeOptions = '';
foreach (store_partner_types() as $key => $label) {
	$sel = $values['partner_type'] === $key ? ' selected' : '';
	$typeOptions .= '<option value="' . $h($key) . '"' . $sel . '>' . $h($label) . '</option>';
}
$areaOptions = '';
foreach (store_partner_areas() as $key => $label) {
	$sel = $values['area'] === $key ? ' selected' : '';
	$areaOptions .= '<option value="' . $h($key) . '"' . $sel . '>' . $h($label) . '</option>';
}

ob_start();
?>
	<div class="breadcumb-section style__two d-flex align-items-center" style="background-image: url(/assets/downloaded/dest-bohol.jpg);">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="breadcumb-content text-center">
						<h1>Apply as Partner</h1>
						<ul class="breadcumb-item">
							<li><a href="/"><i class="fa-solid fa-house"></i> Home </a></li>
							<li><a href="/partners">Partner with us</a></li>
							<li><i class="fa-solid fa-arrow-right-long"></i>Apply</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>

	<section class="ke-partner">
		<div class="container">
			<div class="ke-partner-apply">
				<div class="ke-partner-apply-copy">
					<p class="ke-kicker">Application</p>
					<h2>Tell us about your business</h2>
					<p>Share who you are and how you would like to work with Kuya Ely. This form does not create a login. If the partnership is approved, the office creates your email and password and sends them to you.</p>
					<p><a href="/partner/login.php">Already have a partner login?</a></p>
				</div>
				<div class="ke-form" id="apply">
<?php if ($sent): ?>
					<h2>Application received</h2>
					<p class="ke-form-note"><?php if ($mailState === '1'): ?>We emailed a confirmation to you, and our office received the application as well.<?php elseif ($mailState === '0'): ?>We saved your application. The confirmation email could not be sent just now, but the office still has it.<?php else: ?>Thank you. We will review your application.<?php endif; ?> A partner login is created only after we approve the partnership, and that temporary password arrives by email.</p>
					<p><a class="ke-partner-btn" href="/partners">Back to Partner with us</a></p>
<?php else: ?>
					<h2>Apply as Partner</h2>
					<p class="ke-form-note">Use the contact email you check often. That is where we will send the login if you are approved.</p>
<?php if ($error !== ''): ?>
					<p class="ke-partner-error"><?= $h($error) ?></p>
<?php endif; ?>
					<form method="post" action="/partner/apply.php" autocomplete="off">
						<input type="hidden" name="csrf" value="<?= $h(store_csrf_token()) ?>">
						<div class="ke-form-grid">
							<div class="ke-field">
								<label for="ke-business">Business name</label>
								<input id="ke-business" type="text" name="business_name" maxlength="160" required value="<?= $h($values['business_name']) ?>">
							</div>
							<div class="ke-field">
								<label for="ke-contact">Contact person</label>
								<input id="ke-contact" type="text" name="contact_name" maxlength="140" required value="<?= $h($values['contact_name']) ?>">
							</div>
							<div class="ke-field">
								<label for="ke-email">Email</label>
								<input id="ke-email" type="email" name="email" maxlength="190" required autocomplete="email" value="<?= $h($values['email']) ?>">
							</div>
							<div class="ke-field">
								<label for="ke-phone">Phone / WhatsApp</label>
								<input id="ke-phone" type="tel" name="phone" maxlength="40" required autocomplete="tel" value="<?= $h($values['phone']) ?>">
							</div>
							<div class="ke-field">
								<label for="ke-type">Partner type</label>
								<select id="ke-type" name="partner_type" required>
									<option value="">Select an option</option>
									<?= $typeOptions ?>
								</select>
							</div>
							<div class="ke-field">
								<label for="ke-area">Area</label>
								<select id="ke-area" name="area" required>
									<option value="">Select an option</option>
									<?= $areaOptions ?>
								</select>
							</div>
							<div class="ke-field is-full">
								<label for="ke-web">Website <span>(optional)</span></label>
								<input id="ke-web" type="text" name="website" maxlength="190" placeholder="https://" value="<?= $h($values['website']) ?>">
							</div>
							<div class="ke-field is-full">
								<label for="ke-message">What would you like to offer together?</label>
								<textarea id="ke-message" name="message" maxlength="2000" required><?= $h($values['message']) ?></textarea>
							</div>
						</div>
						<div class="ke-form-actions">
							<button class="ke-submit" type="submit">Submit application
								<svg width="16" height="12" viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M14.96 4.9808C12.8146 4.9808 10.8592 3.0272 10.8592 0.88V0H9.0992V0.88C9.0992 2.44112 9.78384 3.90544 10.8583 4.9808H0V6.7408H10.8583C9.78384 7.81616 9.0992 9.28048 9.0992 10.8416V11.7216H10.8592V10.8416C10.8592 8.69528 12.8146 6.7408 14.96 6.7408H15.84V4.9808H14.96Z" fill="currentColor"></path>
								</svg>
							</button>
						</div>
					</form>
<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
<?php
$html = ob_get_clean();
ke_marketing_page([
	'title' => 'Apply as Partner | Kuya Ely Tours & Transport Services',
	'desc' => 'Apply to partner with Kuya Ely Tours. The office reviews applications and creates partner logins after approval.',
	'canonical' => 'https://kuyaelytours.com/partner/apply.php',
	'image' => 'https://kuyaelytours.com/assets/downloaded/dest-bohol.jpg',
	'body' => 'tour-page contact-page partner-page',
	'nav' => 'partners.php',
	'extra_css' => '<link rel="stylesheet" href="/assets/css/kuyaely-contact.css?v=2" type="text/css" media="all">'
		. '<link rel="stylesheet" href="/assets/css/kuyaely-partners.css?v=1" type="text/css" media="all">',
], $html);
