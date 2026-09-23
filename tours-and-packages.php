<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/tours-data.php';
require __DIR__ . '/store/cms.php';

$islands = [
	'cebu' => ke_island_meta('cebu'),
	'bohol' => ke_island_meta('bohol'),
	'siquijor' => ke_island_meta('siquijor'),
	'dumaguete' => ke_island_meta('dumaguete'),
];

$byIsland = [];
foreach (array_keys($islands) as $key) {
	$byIsland[$key] = [];
}
foreach (ke_tours() as $tour) {
	if (isset($tour['active']) && !$tour['active']) {
		continue;
	}
	$island = (string) ($tour['island'] ?? '');
	if (!isset($byIsland[$island])) {
		continue;
	}
	$byIsland[$island][] = $tour;
}

$featured = [];
foreach ($byIsland as $rows) {
	foreach (array_slice($rows, 0, 2) as $tour) {
		$featured[] = $tour;
	}
}

$h = static function (string $v): string {
	return store_h($v);
};

ob_start();
?>
	<div class="breadcumb-section style__two ke-cat-hero">
		<p class="ke-cat-aside">More Than Just a Destination</p>
		<div class="container">
			<div class="ke-cat-hero-copy">
				<p class="ke-cat-kicker">Private days · Four islands</p>
				<h1>Tours and <span>Packages</span></h1>
				<p class="ke-cat-sub">Cebu · Bohol · Siquijor · Dumaguete — pick an island, then choose a package.</p>
				<ul class="breadcumb-item">
					<li><a href="/index.html"><i class="fa-solid fa-house"></i> Home </a></li>
					<li><i class="fa-solid fa-arrow-right-long"></i>Tours and Packages</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="tour__section style__two">
		<div class="container">
			<div class="tour-dest-tabs ke-hub-tabs" role="navigation" aria-label="Island tours">
				<a href="/cebu-tour">Cebu</a>
				<a href="/bohol-tour">Bohol</a>
				<a href="/siquijor-tour">Siquijor</a>
				<a href="/dumaguete-tour">Dumaguete</a>
			</div>

			<div class="ke-hub-head">
				<p class="ke-cat-kicker">Featured</p>
				<h2>Tours guests book most</h2>
				<p>Start with a featured package, or open an island above for the full list.</p>
			</div>

			<div class="ke-cat-grid" id="ke-cat-grid">
				<?php foreach ($featured as $pkg):
					$href = '/tours/' . $pkg['slug'];
					$island = (string) ($pkg['island'] ?? 'cebu');
					$img = (string) (($pkg['images'][0] ?? '') ?: ($islands[$island]['hero'] ?? '/assets/downloaded/dest-cebu.jpg'));
					$sku = ke_package_cart_id($pkg);
					$price = ke_package_price($pkg);
					$teaser = function_exists('ke_package_teaser') ? ke_package_teaser($pkg) : '';
					if ($teaser === '' && $price > 0) {
						$teaser = 'From ₱' . number_format($price);
					}
				?>
				<article class="ke-cat-card" data-area="<?= $h($island) ?>" data-ke-book="<?= $h(ke_package_book_attr($pkg)) ?>">
					<a class="ke-cat-photo" href="<?= $h($href) ?>">
						<img src="<?= $h($img) ?>" alt="<?= $h((string) $pkg['name']) ?>">
						<span class="ke-cat-badge"><?= $h(ucfirst($island)) ?></span>
						<?php if ($teaser !== ''): ?>
						<span class="ke-cat-price"><?= $h($teaser) ?></span>
						<?php endif; ?>
					</a>
					<div class="ke-cat-body">
						<h3><a href="<?= $h($href) ?>"><?= $h((string) $pkg['name']) ?></a></h3>
						<div class="ke-cat-meta">
							<span><i class="fa-solid fa-location-dot"></i> <?= $h((string) ($pkg['place'] ?? ucfirst($island))) ?></span>
							<span><i class="fa-regular fa-clock"></i> <?= $h((string) ($pkg['duration'] ?? '1 Day')) ?></span>
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
			</div>
		</div>
	</div>
<?php
$html = ob_get_clean();
$opt = [
	'title' => 'Tours and Packages | Kuya Ely Tours',
	'desc' => 'Private Cebu, Bohol, Siquijor, and Dumaguete tour packages with hotel pickup and a private van. Browse featured trips or open an island.',
	'canonical' => 'https://kuyaelytours.com/tours-and-packages.php',
	'image' => '/assets/downloaded/dest-cebu.jpg',
	'body' => 'tour-page tour-catalog tour-hub',
	'nav' => 'tours-and-packages.php',
	'pixel' => [[
		'event' => 'ViewContent',
		'params' => [
			'content_name' => 'Tours and Packages',
			'content_type' => 'product_group',
		],
	]],
	'extra_css' => '<link rel="stylesheet" href="/assets/css/kuyaely-tours.css?v=27" type="text/css" media="all"><style>
.ke-hub-head{margin:4px 0 22px}
.ke-hub-head h2{margin:0 0 8px;font-size:32px}
.ke-hub-head p{margin:0;color:#4a5568}
.ke-hub-tabs{margin-bottom:28px}
@media (max-width:767px){
.ke-hub-head h2{font-size:26px}
}
</style>',
];
require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $html);
