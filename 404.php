<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';

http_response_code(404);
header('X-Robots-Tag: noindex, nofollow');

$opt = [
	'title' => 'Page not found | Kuya Ely Tours',
	'desc' => 'That page is not on Kuya Ely Tours. Browse the tours or head back home.',
	'canonical' => 'https://kuyaelytours.com/',
	'body' => 'ke-missing',
	'nav' => '',
	'extra_css' => '<style>
		body.ke-missing { background: #f4f7f8; }
		.ke-missing-wrap {
			max-width: 680px;
			margin: 0 auto;
			padding: 140px 20px 88px;
			text-align: center;
			color: #1b1f20;
			font-family: Inter, "Segoe UI", Arial, sans-serif;
		}
		.ke-missing-wrap p.ke-code {
			margin: 0 0 8px;
			color: #8a6a00;
			font-weight: 800;
			letter-spacing: 0.16em;
			font-size: 13px;
		}
		.ke-missing-wrap h1 {
			margin: 0 0 12px;
			font-family: "Bebas Neue", Impact, sans-serif;
			font-size: clamp(52px, 9vw, 84px);
			font-weight: 400;
			letter-spacing: 0.03em;
			line-height: 0.95;
			color: #122327;
		}
		.ke-missing-wrap p {
			margin: 0 auto 28px;
			max-width: 460px;
			color: #3d4f53;
			font-size: 17px;
			line-height: 1.6;
		}
		.ke-missing-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			justify-content: center;
		}
		.ke-missing-actions a {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 48px;
			padding: 0 18px;
			border-radius: 999px;
			text-decoration: none;
			font-weight: 700;
			font-size: 15px;
		}
		.ke-missing-actions .primary { background: #F5C518; color: #122327; }
		.ke-missing-actions .ghost { border: 1px solid #122327; color: #122327; background: #fff; }
	</style>',
];

$html = '<section class="ke-missing-wrap">'
	. '<p class="ke-code">404</p>'
	. '<h1>This page is not here</h1>'
	. '<p>The link may be old, or the address was typed wrong. Pick a tour, or message us and we will point you the right way.</p>'
	. '<div class="ke-missing-actions">'
	. '<a class="primary" href="/">Back to home</a>'
	. '<a class="ghost" href="/tours-and-packages.php">Tours and packages</a>'
	. '<a class="ghost" href="/contact">Contact</a>'
	. '</div>'
	. '</section>';

require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $html);
