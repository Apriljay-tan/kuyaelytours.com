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
				<a class="is-active" href="/tours-and-packages.php">All islands</a>
				<a href="/cebu-tour">Cebu</a>
				<a href="/bohol-tour">Bohol</a>
				<a href="/siquijor-tour">Siquijor</a>
				<a href="/dumaguete-tour">Dumaguete</a>
			</div>

			<div class="row ke-hub-islands">
				<?php foreach ($islands as $key => $meta):
					$count = count($byIsland[$key] ?? []);
				?>
				<div class="col-md-6 col-lg-3">
					<a class="ke-hub-island" href="/<?= $h($key) ?>-tour">
						<img src="<?= $h((string) $meta['hero']) ?>" alt="<?= $h((string) $meta['label']) ?>">
						<span>
							<strong><?= $h((string) $meta['label']) ?></strong>
							<small><?= $count === 1 ? '1 package' : $count . ' packages' ?></small>
						</span>
					</a>
				</div>
				<?php endforeach; ?>
			</div>

			<div class="ke-hub-head">
				<p class="ke-cat-kicker">Featured</p>
				<h2>Tours guests book most</h2>
				<p>Open any island above for the full list, or start with a featured package here.</p>
			</div>

			<div class="ke-cat-grid" id="ke-cat-grid">
				<?php foreach ($featured as $pkg):
					$href = '/tours/' . $pkg['slug'];
					$island = (string) ($pkg['island'] ?? 'cebu');
					$img = (string) (($pkg['images'][0] ?? '') ?: ($islands[$island]['hero'] ?? '/assets/downloaded/dest-cebu.jpg'));
					$sku = ke_package_cart_id($pkg);
					$price = ke_package_price($pkg);
				?>
				<article class="ke-cat-card" data-area="<?= $h($island) ?>">
					<a class="ke-cat-photo" href="<?= $h($href) ?>">
						<img src="<?= $h($img) ?>" alt="<?= $h((string) $pkg['name']) ?>">
						<span class="ke-cat-badge"><?= $h(ucfirst($island)) ?></span>
					</a>
					<div class="ke-cat-body">
						<h3><a href="<?= $h($href) ?>"><?= $h((string) $pkg['name']) ?></a></h3>
						<div class="ke-cat-meta">
							<span><i class="fa-solid fa-location-dot"></i> <?= $h((string) ($pkg['place'] ?? ucfirst($island))) ?></span>
							<span><i class="fa-regular fa-clock"></i> <?= $h((string) ($pkg['duration'] ?? '1 Day')) ?></span>
							<?php if ($price > 0): ?>
								<span>From ₱<?= $h(number_format($price)) ?></span>
							<?php endif; ?>
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
	'extra_css' => '<link rel="stylesheet" href="/assets/css/kuyaely-tours.css?v=19" type="text/css" media="all"><style>
.ke-hub-islands{margin:8px -12px 36px}
.ke-hub-islands>[class*="col-"]{padding:12px}
.ke-hub-island{display:block;position:relative;overflow:hidden;border-radius:16px;color:#fff;text-decoration:none;min-height:210px;box-shadow:0 12px 28px rgba(10,18,32,.22)}
.ke-hub-island img{width:100%;height:210px;object-fit:cover;display:block;transform:scale(1.02);transition:transform .25s ease}
.ke-hub-island:hover img{transform:scale(1.08)}
.ke-hub-island span{position:absolute;left:0;right:0;bottom:0;padding:18px 16px 14px;background:linear-gradient(transparent,rgba(10,18,32,.88))}
.ke-hub-island strong{display:block;font-size:22px;letter-spacing:.04em}
.ke-hub-island small{display:block;color:rgba(255,255,255,.8);font-size:13px}
.ke-hub-head{margin:8px 0 18px}
.ke-hub-head h2{margin:0 0 8px;font-size:32px}
.ke-hub-head p{margin:0;color:#4a5568}
.ke-hub-tabs a.is-active{background:#F5C518;color:#122033}
</style>',
];
require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $html);
