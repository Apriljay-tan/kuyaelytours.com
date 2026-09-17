<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/tours-data.php';

$slug = strtolower(trim((string) ($_GET['p'] ?? '')));
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
	store_redirect('/cebu-tour');
}

$tour = ke_tour($slug);
if (!$tour) {
	store_redirect('/cebu-tour');
}

$product = store_product((string) $tour['product']) ?: ['price_from' => 0, 'unit' => 'per guest'];
$price = (int) ($product['price_from'] ?? 0);
$related = ke_tour_related($slug, 3);
$images = array_values($tour['images'] ?? []);
if (!$images) {
	$images = ['/assets/downloaded/dest-cebu.jpg'];
}
while (count($images) < 4) {
	$images[] = $images[0];
}
$island = (string) $tour['island'];
$islandLabel = ucfirst($island);
$catalog = (string) $tour['catalog'];
$canonical = 'https://kuyaelytours.com/tours/' . $slug;
$title = $tour['name'] . ' | Kuya Ely Tours';
$desc = (string) $tour['lead'];
$bodyClass = 'tour-page tour-detail tour-' . $island;
$navCurrent = $island . '-tour.html';

function ke_stars(): string
{
	return str_repeat('<i class="fa-solid fa-star"></i>', 5);
}

ob_start();
?>
	<section class="ke-td">
		<div class="container">
			<div class="ke-td-wrap">
				<div class="ke-td-main">
					<p class="ke-td-crumb">
						<a href="/">Home</a>
						<span>•</span>
						<a href="/<?= store_h($island) ?>-tour"><?= store_h($islandLabel) ?> Tours</a>
						<span>•</span>
						<span><?= store_h($tour['name']) ?></span>
					</p>
					<h1 class="ke-td-title"><?= store_h($tour['name']) ?></h1>
					<p class="ke-td-lead"><?= store_h($tour['lead']) ?></p>
					<div class="ke-td-meta">
						<span class="ke-td-stars"><?= ke_stars() ?> <?= store_h((string) $tour['rating']) ?> (<?= store_h((string) $tour['reviews']) ?> reviews)</span>
						<span><i class="fa-regular fa-clock"></i><?= store_h((string) $tour['duration']) ?></span>
						<span><i class="fa-solid fa-location-dot"></i><?= store_h((string) $tour['place']) ?></span>
					</div>
					<div class="ke-td-pills">
						<?php foreach ($tour['pills'] as $pill): ?>
							<span><i class="fa-solid fa-circle-check"></i><?= store_h($pill) ?></span>
						<?php endforeach; ?>
					</div>
					<div class="ke-td-gallery">
						<a class="ke-td-gallery-main" href="<?= store_h($images[0]) ?>">
							<img src="<?= store_h($images[0]) ?>" alt="<?= store_h($tour['name']) ?>">
						</a>
						<div class="ke-td-thumbs">
							<a href="<?= store_h($images[1]) ?>"><img src="<?= store_h($images[1]) ?>" alt=""></a>
							<a href="<?= store_h($images[2]) ?>"><img src="<?= store_h($images[2]) ?>" alt=""></a>
							<a href="<?= store_h($images[3]) ?>"><img src="<?= store_h($images[3]) ?>" alt=""></a>
							<a href="/galary.html">
								<img src="<?= store_h($images[0]) ?>" alt="">
								<span class="ke-td-more">+ Photos</span>
							</a>
						</div>
					</div>

					<div class="ke-td-block">
						<h2>Overview</h2>
						<p><?= store_h($tour['overview']) ?></p>
					</div>
					<div class="ke-td-block">
						<h2>Activity Highlights</h2>
						<ul class="ke-td-hi">
							<?php foreach ($tour['highlights'] as $item): ?>
								<li><i class="fa-solid fa-circle-check"></i><span><?= store_h($item) ?></span></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="ke-td-block">
						<h2>What to Expect</h2>
						<div class="ke-td-expect">
							<?php foreach ($tour['expect'] as $ex): ?>
								<article>
									<img src="<?= store_h((string) $ex['img']) ?>" alt="<?= store_h((string) $ex['title']) ?>">
									<div>
										<h3><?= store_h((string) $ex['title']) ?></h3>
										<p><?= store_h((string) $ex['text']) ?></p>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="ke-td-block ke-td-split">
						<div class="ke-td-box">
							<h2>What's Included</h2>
							<ul>
								<?php foreach ($tour['included'] as $item): ?>
									<li><i class="fa-solid fa-check ok"></i><span><?= store_h($item) ?></span></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="ke-td-box">
							<h2>Not Included</h2>
							<ul>
								<?php foreach ($tour['excluded'] as $item): ?>
									<li><i class="fa-solid fa-xmark no"></i><span><?= store_h($item) ?></span></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
					<div class="ke-td-block">
						<h2>Sample Itinerary</h2>
						<ul class="ke-td-itin">
							<?php foreach ($tour['itinerary'] as $row): ?>
								<li><strong><?= store_h((string) $row[0]) ?></strong><span><?= store_h((string) $row[1]) ?></span></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="ke-td-block ke-td-split">
						<div class="ke-td-box">
							<h2>Travel Tips &amp; Reminders</h2>
							<ul>
								<?php foreach ($tour['tips'] as $item): ?>
									<li><i class="fa-solid fa-circle-check ok"></i><span><?= store_h($item) ?></span></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div class="ke-td-box">
							<h2>Activity Policy</h2>
							<ul>
								<?php foreach ($tour['policy'] as $item): ?>
									<li><i class="fa-solid fa-circle-info ok"></i><span><?= store_h($item) ?></span></li>
								<?php endforeach; ?>
							</ul>
							<p style="margin:8px 0 12px"><a href="/terms.html">Read our full Terms and Conditions</a></p>
						</div>
					</div>
					<div class="ke-td-block">
						<h2>Frequently Asked Questions</h2>
						<div class="ke-td-faq">
							<?php foreach ($tour['faq'] as $faq): ?>
								<details>
									<summary><?= store_h((string) $faq['q']) ?></summary>
									<p><?= store_h((string) $faq['a']) ?></p>
								</details>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="ke-td-block">
						<h2>You may also be interested in</h2>
						<div class="ke-td-related">
							<?php foreach ($related as $rel): ?>
								<article>
									<a href="/tours/<?= store_h((string) $rel['slug']) ?>">
										<img src="<?= store_h((string) ($rel['images'][0] ?? $images[0])) ?>" alt="<?= store_h((string) $rel['name']) ?>">
										<div>
											<h3><?= store_h((string) $rel['name']) ?></h3>
											<p><?= store_h((string) $rel['place']) ?></p>
											<span>View Details</span>
										</div>
									</a>
								</article>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<aside class="ke-td-side">
					<p class="ke-td-quote"><?= store_h((string) $tour['quote'][0]) ?> <span><?= store_h((string) $tour['quote'][1]) ?></span></p>
					<div class="ke-td-book">
						<p class="ke-td-price">From <?= store_h('₱' . number_format($price)) ?> <small>/pax</small></p>
						<p class="rate"><?= ke_stars() ?> <?= store_h((string) $tour['rating']) ?> (<?= store_h((string) $tour['reviews']) ?> reviews)</p>
						<dl>
							<dt>Duration</dt>
							<dd><?= store_h((string) $tour['duration']) ?></dd>
							<dt>Location</dt>
							<dd><?= store_h((string) $tour['place']) ?></dd>
							<dt>Tour type</dt>
							<dd>Shared or Private Tour</dd>
							<dt></dt>
							<dd>Instant Confirmation</dd>
						</dl>
						<form id="dreamit-form" action="/shop/add-to-cart.php" method="post">
							<label for="arrive1">Select Date</label>
							<input type="date" id="arrive1" name="arrive">
							<label for="group">Guests</label>
							<select name="group" id="group">
								<?php for ($g = 1; $g <= 10; $g++): ?>
									<option value="<?= $g ?>"<?= $g === 2 ? ' selected' : '' ?>><?= $g ?> <?= $g === 1 ? 'Adult' : 'Adults' ?></option>
								<?php endfor; ?>
							</select>
							<button type="button" class="is-book" data-ke-cart data-ke-product="<?= store_h((string) $tour['product']) ?>" data-ke-notes="<?= store_h((string) $tour['name']) ?>" data-ke-next="/shop/checkout.php">Book Now</button>
							<button type="button" class="is-cart" data-ke-cart data-ke-product="<?= store_h((string) $tour['product']) ?>" data-ke-notes="<?= store_h((string) $tour['name']) ?>"><i class="fa-solid fa-cart-shopping"></i> Add to Cart</button>
						</form>
						<p class="ke-td-note">Secure your trip with a small deposit. Full payment can be settled on the day of the tour.</p>
					</div>
					<div class="ke-td-promo">
						<img src="<?= store_h($images[0]) ?>" alt="">
						<div>
							<h3><?= store_h((string) $tour['promo_title']) ?></h3>
							<p><?= store_h((string) $tour['promo_text']) ?></p>
							<p><a class="is-cart" href="/about.html" style="display:inline-flex;margin-top:10px;padding:10px 16px;border:1px solid #F5C518;border-radius:10px;color:#F5C518;text-decoration:none;font-family:var(--title-font);letter-spacing:1px;text-transform:uppercase;">Travel Responsibly</a></p>
						</div>
					</div>
					<h2 style="font-size:22px;margin:0 0 10px">Customer Photos</h2>
					<div class="ke-td-photos">
						<img src="<?= store_h($images[0]) ?>" alt="">
						<img src="<?= store_h($images[1]) ?>" alt="">
						<img src="<?= store_h($images[2]) ?>" alt="">
						<img src="<?= store_h($images[3]) ?>" alt="">
					</div>
					<p style="margin:0 0 12px"><a href="/galary.html">See All Photos</a></p>
					<div class="ke-td-review">
						<p class="ke-td-stars"><?= ke_stars() ?></p>
						<p>“<?= store_h((string) $tour['review']['quote']) ?>”</p>
						<p><strong><?= store_h((string) $tour['review']['name']) ?></strong><br><?= store_h((string) $tour['review']['date']) ?></p>
					</div>
				</aside>
			</div>
		</div>
	</section>
<?php
$detailHtml = ob_get_clean();

$opt = [
	'title' => $title,
	'desc' => $desc,
	'canonical' => $canonical,
	'image' => $images[0],
	'body' => $bodyClass,
	'nav' => $navCurrent,
	'extra_css' => '<link rel="stylesheet" href="/assets/css/kuyaely-tours.css?v=13" type="text/css" media="all">',
];
require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $detailHtml);
