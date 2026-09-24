<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/tours-data.php';
require __DIR__ . '/store/cms.php';

$island = strtolower(trim((string) ($_GET['island'] ?? 'cebu')));
if (!in_array($island, ['cebu', 'bohol', 'siquijor', 'dumaguete'], true)) {
	store_redirect('/cebu-tour');
}

$previewToken = (string) ($_GET['preview'] ?? '');
$meta = ke_island_meta($island);
$packages = [];
foreach (ke_tours() as $tour) {
	if (($tour['island'] ?? '') !== $island) {
		continue;
	}
	$listed = !isset($tour['active']) || $tour['active'];
	if (!$listed && !ke_package_preview_ok($tour, $previewToken)) {
		continue;
	}
	$packages[] = $tour;
}

$product = store_product((string) $meta['product']) ?: ['price_from' => 0];
$canonical = 'https://kuyaelytours.com/' . $island . '-tour';
$title = $meta['label'] . ' Tour Packages | Kuya Ely Tours & Transport Services';
$desc = 'Private ' . $meta['label'] . ' tour packages with Kuya Ely Tours — hotel pickup, a private van, and local pacing.';
$bodyClass = 'tour-page tour-catalog tour-' . $island;
$navCurrent = $island . '-tour.html';
$h = static function (string $v): string {
	return store_h($v);
};

ob_start();
?>
	<div class="breadcumb-section style__two ke-cat-hero">
		<p class="ke-cat-aside">More Than Just a Destination</p>
		<div class="container">
			<div class="ke-cat-hero-copy">
				<p class="ke-cat-kicker">Explore the Beauty of</p>
				<h1><?= $h((string) $meta['h1']) ?> <span>Tours</span></h1>
				<p class="ke-cat-sub">Islands • Adventure • Culture • Unforgettable Experiences</p>
				<ul class="breadcumb-item">
					<li><a href="/index.html"><i class="fa-solid fa-house"></i> Home </a></li>
					<li><i class="fa-solid fa-arrow-right-long"></i><?= $h((string) $meta['crumb']) ?></li>
				</ul>
			</div>
		</div>
	</div>

	<div class="tour__section style__two">
		<div class="container">
			<?php if ($previewToken !== ''): ?>
			<p class="ke-preview-bar">Preview — unpublished packages in this list are visible only with this link. <a href="/admin/packages.php">Back to packages</a></p>
			<?php endif; ?>
			<div class="tour-dest-tabs" role="tablist">
				<?php foreach ($meta['tabs'] as $i => $tab): ?>
					<a<?= $i === 0 ? ' class="is-active"' : '' ?> href="#ke-cat-grid" data-area="<?= $h((string) $tab[0]) ?>"><?= $h((string) $tab[1]) ?></a>
				<?php endforeach; ?>
			</div>
			<div class="ke-cat-grid" id="ke-cat-grid">
				<?php foreach ($packages as $pkg):
					$href = '/tours/' . $pkg['slug'];
					if ($previewToken !== '') {
						$href .= '?preview=' . rawurlencode($previewToken);
					}
					$img = ke_package_cover($pkg, (string) $meta['hero']);
					$sku = ke_package_cart_id($pkg);
					$teaser = ke_package_teaser($pkg);
					?>
				<article class="ke-cat-card" data-area="<?= $h((string) ($pkg['area'] ?? 'all')) ?>" data-ke-href="<?= $h($href) ?>" data-ke-book="<?= $h(ke_package_book_attr($pkg)) ?>">
					<a class="ke-cat-photo" href="<?= $h($href) ?>">
						<img src="<?= $h($img) ?>" alt="<?= $h((string) $pkg['name']) ?>">
						<span class="ke-cat-badge"><?= $h((string) ($pkg['badge'] ?? '')) ?></span>
						<?php if ($teaser !== ''): ?>
						<span class="ke-cat-price"><?= $h($teaser) ?></span>
						<?php endif; ?>
					</a>
					<div class="ke-cat-body">
						<h3><a href="<?= $h($href) ?>"><?= $h((string) $pkg['name']) ?></a></h3>
						<div class="ke-cat-meta">
							<span><i class="fa-solid fa-location-dot"></i> <?= $h((string) ($pkg['place'] ?? '')) ?></span>
							<span><i class="fa-regular fa-clock"></i> <?= $h((string) ($pkg['duration'] ?? '1 Day')) ?></span>
							<span class="ke-cat-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><em>(<?= $h((string) ($pkg['rating'] ?? '5.0')) ?>)</em></span>
						</div>
						<p><?= $h((string) ($pkg['lead'] ?? '')) ?></p>
						<div class="ke-cat-actions">
							<a href="<?= $h($href) ?>">View Details</a>
							<button type="button" class="is-book" data-ke-cart data-ke-product="<?= $h($sku) ?>" data-ke-notes="<?= $h((string) $pkg['name']) ?>" data-ke-next="/shop/checkout.php">Book Now</button>
							<button type="button" data-ke-cart data-ke-product="<?= $h($sku) ?>" data-ke-notes="<?= $h((string) $pkg['name']) ?>"><i class="fa-solid fa-cart-shopping"></i> Add to Cart</button>
						</div>
					</div>
				</article>
				<?php endforeach; ?>
				<article class="ke-cat-card ke-cat-promo" data-area="promo">
					<img src="<?= $h((string) $meta['hero']) ?>" alt="<?= $h((string) $meta['label']) ?>">
					<div class="ke-cat-promo-copy">
						<span class="ke-pin"><i class="fa-solid fa-location-dot"></i></span>
						<p class="ke-script"><?= $h((string) $meta['promo_script']) ?></p>
						<h3>Different Places Same Amazing Feeling</h3>
						<p><?= $h((string) $meta['promo_blurb']) ?></p>
						<a href="#ke-cat-grid" data-area="all"><?= $h((string) $meta['promo_cta']) ?></a>
					</div>
				</article>
			</div>
		</div>
	</div>

	<div class="travelling-offer-section style__two">
		<div class="auto-container">
			<div class="row align-items-center">
				<div class="col-lg-4 col-md-6">
					<div class="travelling-title">
						<h2><span class="ke-offer-kicker">Let's travel together</span>book your next escape <br> with kuya ely</h2>
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="news-img">
						<img src="/assets/downloaded/boking-img.png" alt="">
					</div>
				</div>
				<div class="col-lg-4 col-md-6">
					<div class="subscribe-signle-box right-warpper style__two">
						<div class="sign-up">
							<form action="/send-inquiry.php" method="POST">
								<div class="sign-up-form">
									<div class="form-input-bx">
										<input type="email" name="email" placeholder="Enter Your E-Mail" required="">
										<span><i class="fa-regular fa-envelope"></i></span>
										<button type="submit">SUBSCRIBE</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
$html = ob_get_clean();
$opt = [
	'title' => $title,
	'desc' => $desc,
	'canonical' => $canonical,
	'image' => (string) $meta['hero'],
	'body' => $bodyClass,
	'nav' => $navCurrent,
	'extra_css' => '<link rel="stylesheet" href="/assets/css/kuyaely-tours.css?v=29" type="text/css" media="all">',
	'pixel' => [[
		'event' => 'ViewContent',
		'params' => [
			'content_name' => (string) $meta['label'] . ' tours',
			'content_category' => $island,
			'content_type' => 'product_group',
		],
	]],
];
require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $html);
