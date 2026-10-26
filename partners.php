<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/marketing-chrome.php';

ob_start();
?>
	<div class="breadcumb-section style__two d-flex align-items-center" style="background-image: url(/assets/downloaded/dest-cebu.jpg);">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="breadcumb-content text-center">
						<h1>Partner with us</h1>
						<ul class="breadcumb-item">
							<li><a href="/"><i class="fa-solid fa-house"></i> Home </a></li>
							<li><i class="fa-solid fa-arrow-right-long"></i>Partner with us</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>

	<section class="ke-partner">
		<div class="container">
			<div class="ke-partner-intro">
				<p class="ke-kicker">For hotels, agencies, and operators</p>
				<h2>Tours and transport for your guests</h2>
				<p>Kuya Ely Tours works with hotels, resorts, travel agencies, and activity operators who need private tours and transfers around Cebu, Bohol, Siquijor, and Dumaguete.</p>
				<div class="ke-partner-actions">
					<a class="ke-partner-btn" href="/partner/apply.php">Apply as Partner</a>
					<a class="ke-partner-btn ke-partner-btn--ghost" href="/partner/login.php">Login as Partner</a>
				</div>
			</div>

			<ol class="ke-partner-steps">
				<li>
					<span>01</span>
					<h3>Apply</h3>
					<p>Send your business details. An application does not create a password.</p>
				</li>
				<li>
					<span>02</span>
					<h3>We review</h3>
					<p>The Kuya Ely office reads the application and decides on the partnership.</p>
				</li>
				<li>
					<span>03</span>
					<h3>We create the login</h3>
					<p>If approved, we create the partner email and password and send them to you.</p>
				</li>
				<li>
					<span>04</span>
					<h3>Sign in</h3>
					<p>Use Login as Partner. That opens your own partner page, separate from guest accounts.</p>
				</li>
			</ol>

			<div class="ke-partner-grid">
				<article>
					<span class="ke-partner-icon"><i class="fa-solid fa-hotel"></i></span>
					<h3>Hotels and resorts</h3>
					<p>Arrange island tours, van rental, and airport transfers for guests staying with you.</p>
				</article>
				<article>
					<span class="ke-partner-icon"><i class="fa-solid fa-map-location-dot"></i></span>
					<h3>Travel agencies</h3>
					<p>Add Cebu, Bohol, Siquijor, and Dumaguete trips to the packages you already sell.</p>
				</article>
				<article>
					<span class="ke-partner-icon"><i class="fa-solid fa-water"></i></span>
					<h3>Activities and attractions</h3>
					<p>Connect your experience with a local driver, pickup, and the rest of the day.</p>
				</article>
			</div>

			<div class="ke-partner-note">
				<p>Guest bookings stay on the regular sign in. Partner logins are created by our office, then used on the partner login page.</p>
				<div class="ke-partner-actions">
					<a class="ke-partner-btn" href="/partner/apply.php">Apply as Partner</a>
					<a class="ke-partner-btn ke-partner-btn--ghost" href="/partner/login.php">Login as Partner</a>
				</div>
			</div>
		</div>
	</section>
<?php
$html = ob_get_clean();
ke_marketing_page([
	'title' => 'Partner with us | Kuya Ely Tours & Transport Services',
	'desc' => 'Apply to partner with Kuya Ely Tours for private tours and transport around Cebu, Bohol, Siquijor, and Dumaguete. Partner logins are created by our office.',
	'canonical' => 'https://kuyaelytours.com/partners',
	'image' => 'https://kuyaelytours.com/assets/downloaded/dest-cebu.jpg',
	'body' => 'tour-page contact-page partner-page',
	'nav' => 'partners.php',
	'extra_css' => '<link rel="stylesheet" href="/assets/css/kuyaely-contact.css?v=2" type="text/css" media="all">'
		. '<link rel="stylesheet" href="/assets/css/kuyaely-partners.css?v=1" type="text/css" media="all">',
], $html);
