<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

// PayPal may append a transaction id when it sends the customer back. Show it only as a reference.
$tx = preg_replace('/[^A-Za-z0-9]/', '', (string) ($_GET['tx'] ?? ''));
$tx = is_string($tx) ? substr($tx, 0, 24) : '';

$opt = [
	'title' => 'Thank you for your payment | Kuya Ely Tours',
	'desc' => 'Your payment to Kuya Ely Tours was received. Here is what happens next.',
	'canonical' => 'https://kuyaelytours.com/payment-thank-you.php',
	'body' => 'ke-thanks',
	'nav' => '',
	'extra_css' => '<style>
		body.ke-thanks { background: #f4f7f8; }
		.ke-thanks-wrap {
			max-width: 720px;
			margin: 0 auto;
			padding: 140px 20px 88px;
			color: #1b1f20;
			font-family: Inter, "Segoe UI", Arial, sans-serif;
		}
		.ke-thanks-card {
			background: #fff;
			border-radius: 18px;
			padding: 40px 32px;
			text-align: center;
			box-shadow: 0 14px 40px rgba(18, 35, 39, 0.08);
		}
		.ke-thanks-tick {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 72px;
			height: 72px;
			margin-bottom: 18px;
			border-radius: 50%;
			background: #F5C518;
			color: #122327;
			font-size: 38px;
			font-weight: 800;
			line-height: 1;
		}
		.ke-thanks-card h1 {
			margin: 0 0 10px;
			font-family: "Bebas Neue", Impact, sans-serif;
			font-size: clamp(44px, 8vw, 68px);
			font-weight: 400;
			letter-spacing: 0.03em;
			line-height: 1;
			color: #122327;
		}
		.ke-thanks-card > p {
			margin: 0 auto 8px;
			max-width: 520px;
			color: #3d4f53;
			font-size: 17px;
			line-height: 1.6;
		}
		.ke-thanks-ref {
			display: inline-block;
			margin-top: 10px;
			padding: 6px 14px;
			border-radius: 999px;
			background: #f4f7f8;
			color: #3d4f53;
			font-size: 14px;
		}
		.ke-thanks-steps {
			margin: 30px 0 0;
			padding: 22px 24px;
			border-radius: 14px;
			background: #f4f7f8;
			text-align: left;
		}
		.ke-thanks-steps h2 {
			margin: 0 0 10px;
			font-size: 17px;
			font-weight: 800;
			color: #122327;
		}
		.ke-thanks-steps ol {
			margin: 0;
			padding-left: 20px;
			color: #3d4f53;
			font-size: 15px;
			line-height: 1.7;
		}
		.ke-thanks-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			justify-content: center;
			margin-top: 28px;
		}
		.ke-thanks-actions a {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 48px;
			padding: 0 20px;
			border-radius: 999px;
			text-decoration: none;
			font-weight: 700;
			font-size: 15px;
		}
		.ke-thanks-actions .primary { background: #F5C518; color: #122327; }
		.ke-thanks-actions .ghost { border: 1px solid #122327; color: #122327; background: #fff; }
		@media (max-width: 575px) {
			.ke-thanks-wrap { padding: 110px 14px 64px; }
			.ke-thanks-card { padding: 30px 18px; }
			.ke-thanks-actions a { width: 100%; }
		}
	</style>',
];

$ref = $tx !== '' ? '<span class="ke-thanks-ref">PayPal reference: ' . store_h($tx) . '</span>' : '';

$html = '<section class="ke-thanks-wrap"><div class="ke-thanks-card">'
	. '<span class="ke-thanks-tick" aria-hidden="true">&#10003;</span>'
	. '<h1>Thank you!</h1>'
	. '<p>We received your payment. PayPal is sending a receipt to your email, so please check your inbox and spam folder.</p>'
	. $ref
	. '<div class="ke-thanks-steps"><h2>What happens next</h2><ol>'
	. '<li>We confirm your booking and send the details to your email.</li>'
	. '<li>We message you on WhatsApp or email with your pickup time and driver.</li>'
	. '<li>Any balance can be settled on the day of the tour.</li>'
	. '</ol></div>'
	. '<div class="ke-thanks-actions">'
	. '<a class="primary" href="https://api.whatsapp.com/send?phone=639608605034">Message us on WhatsApp</a>'
	. '<a class="ghost" href="/tours-and-packages.php">Browse more tours</a>'
	. '<a class="ghost" href="/">Back to home</a>'
	. '</div>'
	. '</div></section>';

require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $html);
