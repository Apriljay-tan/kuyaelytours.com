<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/tours-data.php';

$slug = strtolower(trim((string) ($_GET['p'] ?? '')));
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
	store_redirect('/cebu-tour');
}

$tour = ke_tour($slug);
$previewToken = (string) ($_GET['preview'] ?? '');
$isPreview = ke_package_preview_ok($tour, $previewToken);
if (!$tour || (isset($tour['active']) && !$tour['active'] && !$isPreview)) {
	store_redirect('/cebu-tour');
}

$prefDate = (string) ($_GET['date'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $prefDate) || $prefDate < store_today()) {
	$prefDate = '';
}

$product = store_product((string) $tour['product']) ?: ['price_from' => 0, 'unit' => 'per guest'];
$price = ke_package_price($tour);
$cartId = ke_package_cart_id($tour);
$videoUrl = trim((string) ($tour['video_url'] ?? ''));
$pickups = ke_tour_pickups($tour);
$addons = ke_tour_addons($tour);
$tiers = ke_tour_price_tiers($tour);
$fromPrice = (int) (ke_quote_booking($tour, [])['from'] ?: $price);
$ageAdult = (string) ($tour['age_adult'] ?? '4 years old & above');
$ageChild = (string) ($tour['age_child'] ?? '3 years old');
$bookData = [
	'from' => $fromPrice,
	'tiers' => $tiers,
];
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
	<?php if ($isPreview): ?>
	<div class="ke-preview-bar">Preview only — this package is not published yet. <a href="/admin/package-edit.php?slug=<?= store_h($slug) ?>">Back to editor</a></div>
	<?php endif; ?>
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
						<div class="ke-td-gallery-main">
							<a href="<?= store_h($images[0]) ?>">
								<img src="<?= store_h($images[0]) ?>" alt="<?= store_h($tour['name']) ?>">
							</a>
							<?php if ($videoUrl !== ''): ?>
								<a class="ke-td-watch" href="<?= store_h($videoUrl) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-play"></i> Watch Video</a>
							<?php endif; ?>
						</div>
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
					<div class="ke-bookbox" data-book="<?= store_h(json_encode($bookData, JSON_UNESCAPED_UNICODE)) ?>">
						<div class="ke-bookbox-head">
							<p class="ke-bookbox-from">From <?= store_h('₱' . number_format($fromPrice)) ?> <span>/pax</span></p>
							<p>Price per person varies by group size</p>
						</div>
						<form class="ke-bookbox-form" id="ke-bookbox-form" action="/shop/add-to-cart.php" method="post">
							<input type="hidden" name="csrf" value="<?= store_h(store_csrf_token()) ?>">
							<input type="hidden" name="product_id" value="<?= store_h($cartId) ?>">
							<input type="hidden" name="package_slug" value="<?= store_h((string) $tour['slug']) ?>">
							<input type="hidden" name="notes" value="<?= store_h((string) $tour['name']) ?>">
							<input type="hidden" name="guests" value="1">
							<label class="ke-bookbox-field">
								<span>Select Pickup Location</span>
								<select name="pickup" required>
									<?php foreach ($pickups as $i => $stop): ?>
										<option value="<?= store_h($stop) ?>"<?= $i === 0 ? ' selected' : '' ?>><?= store_h($stop) ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="ke-bookbox-field">
								<span>Booking Date</span>
								<input type="date" id="arrive1" name="arrive" min="<?= store_h(store_today()) ?>" value="<?= store_h($prefDate) ?>">
								<input type="hidden" name="date" value="">
							</label>
							<?php
							$guestRows = [
								['foreign_adult', 'Foreign Adult', $ageAdult],
								['local_adult', 'Local Adult', $ageAdult],
								['foreign_child', 'Foreign Child', $ageChild],
								['local_child', 'Local Child', $ageChild],
							];
							foreach ($guestRows as $row):
							?>
							<div class="ke-bookbox-guest">
								<div>
									<strong><?= store_h($row[1]) ?></strong>
									<small><?= store_h($row[2]) ?></small>
								</div>
								<div class="ke-step">
									<button type="button" data-step="<?= $row[0] ?>" data-dir="-">−</button>
									<input type="text" name="<?= $row[0] ?>" value="0" readonly inputmode="numeric">
									<button type="button" data-step="<?= $row[0] ?>" data-dir="+">+</button>
								</div>
							</div>
							<?php endforeach; ?>
							<?php if ($addons): ?>
							<div class="ke-bookbox-addons">
								<p>Add-ons</p>
								<?php foreach ($addons as $addon): ?>
									<label>
										<input class="ke-addon" type="checkbox" name="addons[]" value="<?= store_h((string) $addon['id']) ?>" data-price="<?= (int) $addon['price'] ?>">
										<span><?= store_h((string) $addon['name']) ?> (+<?= store_h('₱' . number_format((int) $addon['price'])) ?>)</span>
									</label>
								<?php endforeach; ?>
							</div>
							<?php endif; ?>
							<button type="button" class="ke-bookbox-book is-book" data-ke-cart data-ke-product="<?= store_h($cartId) ?>" data-ke-notes="<?= store_h((string) $tour['name']) ?>" data-ke-next="/shop/checkout.php">Book now</button>
							<button type="button" class="ke-bookbox-cart is-cart" data-ke-cart data-ke-product="<?= store_h($cartId) ?>" data-ke-notes="<?= store_h((string) $tour['name']) ?>">Add to cart</button>
							<p class="ke-td-note">Secure your trip with a small deposit. Full payment can be settled on the day of the tour.</p>
						</form>
					</div>
					<script>
					(function () {
						var box = document.querySelector(".ke-bookbox");
						if (!box) return;
						var data = {};
						try { data = JSON.parse(box.getAttribute("data-book") || "{}"); } catch (e) { data = {}; }
						function qty(name) {
							var el = box.querySelector('[name="' + name + '"]');
							return parseInt(el && el.value, 10) || 0;
						}
						function pax() {
							return qty("foreign_adult") + qty("local_adult") + qty("foreign_child") + qty("local_child");
						}
						function tierFor(n) {
							var rows = data.tiers || [];
							n = Math.max(1, n);
							for (var i = 0; i < rows.length; i++) {
								if (n >= rows[i].min && n <= rows[i].max) return rows[i];
							}
							return rows.length ? rows[rows.length - 1] : null;
						}
						function money(n) {
							return "₱" + Number(n || 0).toLocaleString();
						}
						function refresh() {
							var n = pax();
							var t = tierFor(n);
							var total = 0;
							if (t) {
								total += qty("foreign_adult") * (t.foreign_adult || 0);
								total += qty("local_adult") * (t.local_adult || 0);
								total += qty("foreign_child") * (t.foreign_child || 0);
								total += qty("local_child") * (t.local_child || 0);
							}
							box.querySelectorAll(".ke-addon:checked").forEach(function (el) {
								total += parseInt(el.getAttribute("data-price"), 10) || 0;
							});
							var head = box.querySelector(".ke-bookbox-from");
							if (head) {
								if (n < 1) head.innerHTML = "From " + money(data.from || 0) + " <span>/pax</span>";
								else head.innerHTML = money(total) + " <span>total</span>";
							}
							var guests = box.querySelector('[name="guests"]');
							if (guests) guests.value = String(Math.max(1, n));
							var arrive = box.querySelector('[name="arrive"]');
							var date = box.querySelector('[name="date"]');
							if (arrive && date) date.value = arrive.value || "";
						}
						box.addEventListener("click", function (e) {
							var btn = e.target.closest("[data-step]");
							if (!btn) return;
							e.preventDefault();
							var input = box.querySelector('[name="' + btn.getAttribute("data-step") + '"]');
							if (!input) return;
							var v = parseInt(input.value, 10) || 0;
							v += btn.getAttribute("data-dir") === "-" ? -1 : 1;
							if (v < 0) v = 0;
							if (v > 30) v = 30;
							input.value = String(v);
							refresh();
						});
						box.addEventListener("change", refresh);
						refresh();
					})();
					</script>
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
	'extra_css' => '<link rel="stylesheet" href="/assets/css/kuyaely-tours.css?v=19" type="text/css" media="all">',
];
require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $detailHtml);
