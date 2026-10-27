<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';

$img = '/assets/downloaded/moments/';

$sections = [
	[
		'id' => 'overview',
		'title' => 'Our policy in short',
		'img' => $img . 'moment-6431.jpg',
		'alt' => 'Guests enjoying a Kuya Ely Tours island trip',
		'text' => 'Every Kuya Ely Tours booking is rebookable and movable, but it is not refundable. Once a tour, transfer or package is booked and paid, you can change it to a new date or a different tour, but we cannot return the payment.',
	],
	[
		'id' => 'rebook',
		'title' => 'Rebookable',
		'img' => $img . 'moment-6483.jpg',
		'alt' => 'Travelers choosing another tour in Cebu',
		'text' => 'If your plans change, you can rebook your payment to another Kuya Ely Tours package or service. Just tell us which tour you would like instead, and we will apply your payment to it. If the new tour costs more, you pay the difference.',
	],
	[
		'id' => 'move',
		'title' => 'Movable',
		'img' => $img . 'moment-6525.jpg',
		'alt' => 'A tour group on a new travel date',
		'text' => 'You can move your booking to another date that is available. Message us as early as you can so we can hold your vehicle and driver for the new schedule. Weather, road closures or other events we cannot control may also require a new date, and we will work with you to find a good one.',
	],
	[
		'id' => 'refund',
		'title' => 'Not refundable',
		'img' => $img . 'moment-6375.jpg',
		'alt' => 'Kuya Ely Tours vehicle ready for pickup',
		'text' => 'Payments made to Kuya Ely Tours are not refundable. Your vehicle, driver and supplier reservations are set aside for you as soon as you book, so we cannot return the money. Instead, we offer you the option to rebook or move your tour, so your payment is never lost.',
	],
	[
		'id' => 'insurance',
		'title' => 'Insurance covered with PAMI',
		'img' => $img . 'moment-6611.jpg',
		'alt' => 'Guests traveling safely with Kuya Ely Tours',
		'text' => 'Your safety matters to us. Our insurance policy is covered with PAMI, so you can travel with more peace of mind. If you have questions about what the coverage includes, contact us and we will gladly help.',
	],
	[
		'id' => 'help',
		'title' => 'How to rebook or move a tour',
		'img' => $img . 'moment-6458.jpg',
		'alt' => 'Kuya Ely Tours team ready to help',
		'text' => 'Send us your booking name, your tour and the new date or tour you want. You can WhatsApp us at +63 960 860 5034 or email info@kuyaelytours.com. Our team will confirm the change with you as soon as possible.',
	],
];

$opt = [
	'title' => 'Return Policy | Kuya Ely Tours',
	'desc' => 'Kuya Ely Tours return policy: bookings are rebookable and movable but not refundable. Insurance is covered with PAMI.',
	'canonical' => 'https://kuyaelytours.com/return-policy',
	'image' => 'https://kuyaelytours.com' . $img . 'moment-6431.jpg',
	'body' => 'ke-policy',
	'nav' => '',
	'extra_css' => '<style>
		body.ke-policy { background: #f4f7f8; }
		.ke-policy-wrap {
			max-width: 980px;
			margin: 0 auto;
			padding: 140px 20px 88px;
			color: #1b1f20;
			font-family: Inter, "Segoe UI", Arial, sans-serif;
		}
		.ke-policy-head { text-align: center; margin-bottom: 36px; }
		.ke-policy-head h1 {
			margin: 0 0 10px;
			font-family: "Bebas Neue", Impact, sans-serif;
			font-size: clamp(46px, 8vw, 74px);
			font-weight: 400;
			letter-spacing: 0.03em;
			line-height: 1;
			color: #122327;
		}
		.ke-policy-head p { margin: 0 auto; max-width: 620px; color: #3d4f53; font-size: 17px; line-height: 1.6; }
		.ke-policy-tags { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 20px; }
		.ke-policy-tags span {
			padding: 8px 18px;
			border-radius: 999px;
			background: #122327;
			color: #fff;
			font-size: 14px;
			font-weight: 700;
		}
		.ke-policy-tags span.gold { background: #F5C518; color: #122327; }
		.ke-policy-card {
			display: flex;
			align-items: stretch;
			margin-bottom: 22px;
			overflow: hidden;
			border-radius: 18px;
			background: #fff;
			box-shadow: 0 14px 40px rgba(18, 35, 39, 0.08);
		}
		.ke-policy-card:nth-of-type(even) { flex-direction: row-reverse; }
		.ke-policy-card img {
			flex: 0 0 42%;
			width: 42%;
			min-height: 240px;
			object-fit: cover;
			display: block;
		}
		.ke-policy-body { flex: 1; padding: 30px 32px; align-self: center; }
		.ke-policy-body h2 {
			margin: 0 0 10px;
			font-family: "Bebas Neue", Impact, sans-serif;
			font-size: 36px;
			font-weight: 400;
			letter-spacing: 0.03em;
			color: #122327;
		}
		.ke-policy-body p { margin: 0; color: #3d4f53; font-size: 16px; line-height: 1.7; }
		.ke-policy-body a { color: #122327; font-weight: 700; }
		.ke-policy-actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-top: 30px; }
		.ke-policy-actions a {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 48px;
			padding: 0 22px;
			border-radius: 999px;
			text-decoration: none;
			font-weight: 700;
			font-size: 15px;
		}
		.ke-policy-actions .primary { background: #F5C518; color: #122327; }
		.ke-policy-actions .ghost { border: 1px solid #122327; color: #122327; background: #fff; }
		@media (max-width: 767px) {
			.ke-policy-wrap { padding: 110px 14px 64px; }
			.ke-policy-card, .ke-policy-card:nth-of-type(even) { flex-direction: column; }
			.ke-policy-card img { flex: none; width: 100%; min-height: 0; height: 210px; }
			.ke-policy-body { padding: 22px 20px 26px; }
			.ke-policy-body h2 { font-size: 30px; }
			.ke-policy-actions a { width: 100%; }
		}
	</style>',
];

$html = '<section class="ke-policy-wrap">'
	. '<div class="ke-policy-head"><h1>Return Policy</h1>'
	. '<p>Please read how changes and payments work before you book with Kuya Ely Tours.</p>'
	. '<div class="ke-policy-tags"><span class="gold">Rebookable</span><span class="gold">Movable</span><span>Not refundable</span><span>Insurance: PAMI</span></div></div>';

foreach ($sections as $s) {
	$html .= '<article class="ke-policy-card" id="' . store_h($s['id']) . '">'
		. '<img src="' . store_h($s['img']) . '" alt="' . store_h($s['alt']) . '" loading="lazy" width="600" height="400">'
		. '<div class="ke-policy-body"><h2>' . store_h($s['title']) . '</h2><p>' . store_h($s['text']) . '</p></div>'
		. '</article>';
}

$html .= '<div class="ke-policy-actions">'
	. '<a class="primary" href="https://api.whatsapp.com/send?phone=639608605034">Message us on WhatsApp</a>'
	. '<a class="ghost" href="/terms">Terms and conditions</a>'
	. '<a class="ghost" href="/tours-and-packages.php">Browse tours</a>'
	. '</div></section>';

require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $html);
