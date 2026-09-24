<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/tours-data.php';

$slug = strtolower(trim((string) ($_GET['p'] ?? '')));
if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
	store_redirect('/cebu-tour');
}

$tour = ke_tour($slug);
if (is_array($tour) && (string) ($tour['slug'] ?? '') !== $slug) {
	$qs = $_GET;
	unset($qs['p']);
	$target = '/tours/' . rawurlencode((string) $tour['slug']);
	if ($qs) {
		$target .= '?' . http_build_query($qs);
	}
	header('Location: ' . $target, true, 301);
	exit;
}
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
$images = array_values(array_filter(array_map('strval', (array) ($tour['images'] ?? []))));
$guestPhotos = array_values(array_unique(array_filter(array_map('strval', (array) ($tour['guest_photos'] ?? [])))));
$packPhotos = [];
foreach (array_merge($images, $guestPhotos) as $src) {
	if ($src !== '' && !in_array($src, $packPhotos, true)) {
		$packPhotos[] = $src;
	}
}
$pickups = ke_tour_pickups($tour);
$addons = ke_tour_addons($tour);
$tiers = ke_tour_price_tiers($tour);
$fromPrice = (int) (ke_quote_booking($tour, [])['from'] ?: $price);
$splitLocal = ke_package_split_local($tour);
$ageAdult = $splitLocal ? (string) ($tour['age_adult'] ?? '4 years old & above') : '5 years old & above';
$ageChild = $splitLocal ? (string) ($tour['age_child'] ?? '3 years old') : 'Below 5 years old';
$bookData = [
	'from' => $fromPrice,
	'tiers' => $tiers,
	'split' => $splitLocal,
];
$related = ke_tour_related($slug, 3);
if (!$images) {
	$images = ['/assets/downloaded/dest-cebu.jpg'];
	$packPhotos = $images;
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

function ke_tour_video_html(string $url): string
{
	$url = trim($url);
	if ($url === '') {
		return '';
	}
	if (preg_match('~(?:youtube\.com/watch\?v=|youtube\.com/embed/|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $match)) {
		return '<iframe src="https://www.youtube.com/embed/' . store_h($match[1]) . '" title="Tour video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
	}
	if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $match)) {
		return '<iframe src="https://player.vimeo.com/video/' . store_h($match[1]) . '" title="Tour video" allowfullscreen></iframe>';
	}
	if (preg_match('~\.(mp4|webm)(\?|$)~i', $url)) {
		return '<video controls playsinline src="' . store_h($url) . '"></video>';
	}
	return '<p><a href="' . store_h($url) . '" target="_blank" rel="noopener">Watch video</a></p>';
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
								<button class="ke-td-watch" type="button" data-ke-photos><i class="fa-solid fa-play"></i> Watch Video</button>
							<?php endif; ?>
						</div>
						<div class="ke-td-thumbs">
							<a href="<?= store_h($images[1]) ?>"><img src="<?= store_h($images[1]) ?>" alt=""></a>
							<a href="<?= store_h($images[2]) ?>"><img src="<?= store_h($images[2]) ?>" alt=""></a>
							<a href="<?= store_h($images[3]) ?>"><img src="<?= store_h($images[3]) ?>" alt=""></a>
							<button class="ke-td-open-photos" type="button" data-ke-photos>
								<img src="<?= store_h($images[0]) ?>" alt="">
								<span class="ke-td-more">+ Photos</span>
							</button>
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
					<div class="ke-mbook" data-mbook>
						<div class="ke-mbook-bar">
							<div class="ke-mbook-price">
								<strong class="ke-mbook-from">From <?= store_h('₱' . number_format($fromPrice)) ?> <span>/pax</span></strong>
								<span class="ke-mbook-sub">Price per person varies by group size</span>
							</div>
							<button type="button" class="ke-mbook-open">Book now</button>
						</div>
						<div class="ke-mbook-sheet" hidden>
							<div class="ke-mbook-backdrop" data-mbook-close></div>
							<div class="ke-mbook-panel" role="dialog" aria-modal="true" aria-label="Book this tour">
								<button type="button" class="ke-mbook-close" data-mbook-close aria-label="Close">×</button>
								<div class="ke-bookbox" data-book="<?= store_h(json_encode($bookData, JSON_UNESCAPED_UNICODE)) ?>">
									<div class="ke-bookbox-head">
										<p class="ke-bookbox-from">From <?= store_h('₱' . number_format($fromPrice)) ?> <span>/pax</span></p>
										<p class="ke-bookbox-sub">Price per person varies by group size</p>
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
										$guestRows = $splitLocal ? [
											['foreign_adult', 'Foreign Adult', $ageAdult],
											['local_adult', 'Local Adult', $ageAdult],
											['foreign_child', 'Foreign Child', $ageChild],
											['local_child', 'Local Child', $ageChild],
										] : [
											['foreign_adult', 'Adult', $ageAdult],
											['foreign_child', 'Child', $ageChild],
										];
										foreach ($guestRows as $row):
										?>
										<div class="ke-bookbox-guest" data-guest-type="<?= store_h($row[0]) ?>">
											<div>
												<strong><?= store_h($row[1]) ?></strong>
												<small><?= store_h($row[2]) ?></small>
												<em class="ke-bookbox-unit" data-unit="<?= store_h($row[0]) ?>">—</em>
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
										<div class="ke-bookbox-breakdown" hidden>
											<div class="ke-bookbox-lines"></div>
											<p class="ke-bookbox-total">Booking cost: <strong></strong></p>
										</div>
										<button type="button" class="ke-bookbox-book is-book" data-ke-cart data-ke-product="<?= store_h($cartId) ?>" data-ke-notes="<?= store_h((string) $tour['name']) ?>" data-ke-next="/shop/checkout.php">Book now</button>
										<button type="button" class="ke-bookbox-cart is-cart" data-ke-cart data-ke-product="<?= store_h($cartId) ?>" data-ke-notes="<?= store_h((string) $tour['name']) ?>">Add to cart</button>
										<p class="ke-td-note">Secure your trip with a small deposit. Full payment can be settled on the day of the tour.</p>
									</form>
								</div>
							</div>
						</div>
					</div>
					<script>
					(function () {
						var root = document.querySelector("[data-mbook]");
						var box = document.querySelector(".ke-bookbox");
						if (!box) return;
						var sheet = root ? root.querySelector(".ke-mbook-sheet") : null;
						var openBtn = root ? root.querySelector(".ke-mbook-open") : null;
						var barFrom = root ? root.querySelector(".ke-mbook-from") : null;
						var barSub = root ? root.querySelector(".ke-mbook-sub") : null;
						var mq = window.matchMedia("(max-width: 991px)");
						var data = {};
						try { data = JSON.parse(box.getAttribute("data-book") || "{}"); } catch (e) { data = {}; }
						function isMobile() { return mq.matches; }
						function openSheet() {
							if (!sheet || !isMobile()) return;
							if (sheet.parentNode !== document.body) document.body.appendChild(sheet);
							sheet.hidden = false;
							document.body.classList.add("ke-mbook-open");
							var closeEl = sheet.querySelector(".ke-mbook-close");
							if (closeEl) closeEl.focus();
						}
						function closeSheet() {
							if (!sheet) return;
							sheet.hidden = true;
							document.body.classList.remove("ke-mbook-open");
							if (openBtn && isMobile()) openBtn.focus();
						}
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
						var labels = data.split === false ? {
							foreign_adult: "Adult",
							local_adult: "Adult",
							foreign_child: "Child",
							local_child: "Child"
						} : {
							foreign_adult: "Foreign Adult",
							local_adult: "Local Adult",
							foreign_child: "Foreign Child",
							local_child: "Local Child"
						};
						var types = ["foreign_adult", "local_adult", "foreign_child", "local_child"];
						function syncBar(headHtml, subText) {
							if (barFrom) barFrom.innerHTML = headHtml;
							if (barSub) barSub.textContent = subText;
						}
						function refresh() {
							var n = pax();
							var t = tierFor(Math.max(1, n));
							var total = 0;
							var lines = [];
							types.forEach(function (type) {
								var q = qty(type);
								var rate = t ? (t[type] || 0) : 0;
								var unitEl = box.querySelector('[data-unit="' + type + '"]');
								if (unitEl) {
									unitEl.textContent = (q > 0 && t) ? (money(rate) + " / pax") : "";
								}
								if (q > 0 && t) {
									var sub = q * rate;
									total += sub;
									lines.push(labels[type] + " (" + q + ") × " + money(rate) + " = " + money(sub));
								}
							});
							box.querySelectorAll(".ke-addon:checked").forEach(function (el) {
								var addonPrice = parseInt(el.getAttribute("data-price"), 10) || 0;
								total += addonPrice;
								var label = (el.parentElement && el.parentElement.querySelector("span"))
									? el.parentElement.querySelector("span").textContent.replace(/\s*\(\+₱[\d,]+\)$/, "").trim()
									: "Add-on";
								lines.push(label + " = " + money(addonPrice));
							});
							var breakdown = box.querySelector(".ke-bookbox-breakdown");
							var linesEl = box.querySelector(".ke-bookbox-lines");
							var totalEl = box.querySelector(".ke-bookbox-total strong");
							if (breakdown && linesEl) {
								if (n >= 1) {
									linesEl.innerHTML = lines.map(function (line) {
										return "<div>" + line + "</div>";
									}).join("");
									if (totalEl) totalEl.textContent = money(total);
									breakdown.hidden = false;
								} else {
									linesEl.innerHTML = "";
									if (totalEl) totalEl.textContent = "";
									breakdown.hidden = true;
								}
							}
							var head = box.querySelector(".ke-bookbox-from");
							var sub = box.querySelector(".ke-bookbox-sub") || box.querySelector(".ke-bookbox-head p:last-child");
							var headHtml = "From " + money(data.from || 0) + " <span>/pax</span>";
							var subText = "Price per person varies by group size";
							if (head) {
								if (n < 1) {
									head.innerHTML = headHtml;
									if (sub) sub.textContent = subText;
								} else {
									var primary = 0;
									if (t) {
										if (qty("foreign_adult") > 0) primary = t.foreign_adult || 0;
										else if (qty("local_adult") > 0) primary = t.local_adult || 0;
										else {
											for (var i = 0; i < types.length; i++) {
												if (qty(types[i]) > 0) {
													primary = t[types[i]] || 0;
													break;
												}
											}
										}
									}
									headHtml = money(primary) + " <span>/pax</span>";
									subText = "Booking cost: " + money(total);
									head.innerHTML = headHtml;
									if (sub) sub.textContent = subText;
								}
							}
							syncBar(headHtml, subText);
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
						if (openBtn) {
							openBtn.addEventListener("click", function (e) {
								e.preventDefault();
								openSheet();
							});
						}
						if (root) {
							root.querySelectorAll("[data-mbook-close]").forEach(function (el) {
								el.addEventListener("click", function (e) {
									e.preventDefault();
									closeSheet();
								});
							});
						}
						document.addEventListener("keydown", function (e) {
							if (e.key === "Escape" && sheet && !sheet.hidden) closeSheet();
						});
						function onViewportChange() {
							if (!isMobile()) closeSheet();
						}
						if (mq.addEventListener) mq.addEventListener("change", onViewportChange);
						else if (mq.addListener) mq.addListener(onViewportChange);
						refresh();
						if (/[?&]need=date(?:&|$)/.test(location.search)) {
							window.alert("Choose a booking date first.");
							if (isMobile()) openSheet();
							var dateInput = box.querySelector('[name="arrive"]');
							if (dateInput) dateInput.focus();
						}
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
					<?php if ($guestPhotos): ?>
					<h2 style="font-size:22px;margin:0 0 10px">Customer Photos</h2>
					<div class="ke-td-photos">
						<?php foreach ($guestPhotos as $guestPhoto): ?>
							<button type="button" data-ke-photos><img src="<?= store_h($guestPhoto) ?>" alt="Guest photo"></button>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
					<div class="ke-td-review">
						<p class="ke-td-stars"><?= ke_stars() ?></p>
						<p>“<?= store_h((string) $tour['review']['quote']) ?>”</p>
						<p><strong><?= store_h((string) $tour['review']['name']) ?></strong><br><?= store_h((string) $tour['review']['date']) ?></p>
					</div>
				</aside>
			</div>
		</div>
	</section>
	<dialog id="ke-pack-photos" class="ke-pack-modal">
		<div class="ke-pack-modal-bar">
			<strong><?= store_h((string) $tour['name']) ?></strong>
			<button type="button" data-ke-photos-close>Close</button>
		</div>
		<div class="ke-pack-modal-body">
			<?php if ($videoUrl !== ''): ?>
				<div class="ke-pack-modal-video"><?= ke_tour_video_html($videoUrl) ?></div>
			<?php endif; ?>
			<div class="ke-pack-modal-grid">
				<?php foreach ($packPhotos as $photo): ?>
					<img src="<?= store_h($photo) ?>" alt="<?= store_h((string) $tour['name']) ?>">
				<?php endforeach; ?>
			</div>
		</div>
	</dialog>
	<script>
	(function () {
		var modal = document.getElementById('ke-pack-photos');
		if (!modal) return;
		function stopMedia() {
			modal.querySelectorAll('video').forEach(function (video) { video.pause(); });
			modal.querySelectorAll('iframe').forEach(function (frame) {
				var src = frame.getAttribute('src');
				if (src) frame.setAttribute('src', src);
			});
		}
		document.querySelectorAll('[data-ke-photos]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				if (modal.showModal) modal.showModal();
			});
		});
		var closeBtn = modal.querySelector('[data-ke-photos-close]');
		if (closeBtn) closeBtn.addEventListener('click', function () { modal.close(); });
		modal.addEventListener('click', function (e) {
			if (e.target === modal) modal.close();
		});
		modal.addEventListener('close', stopMedia);
	})();
	</script>
<?php
$detailHtml = ob_get_clean();

$opt = [
	'title' => $title,
	'desc' => $desc,
	'canonical' => $canonical,
	'image' => $images[0],
	'body' => $bodyClass,
	'nav' => $navCurrent,
	'extra_css' => '<link rel="stylesheet" href="/assets/css/kuyaely-tours.css?v=30" type="text/css" media="all"><link rel="stylesheet" href="/assets/css/kuyaely-tour-detail.css?v=10" type="text/css" media="all">',
	'pixel' => [[
		'event' => 'ViewContent',
		'params' => [
			'content_name' => (string) $tour['name'],
			'content_ids' => [(string) $tour['slug']],
			'content_type' => 'product',
			'content_category' => (string) ($tour['island'] ?? ''),
			'value' => (int) $fromPrice,
			'currency' => 'PHP',
		],
	]],
];
require __DIR__ . '/store/marketing-chrome.php';
ke_marketing_page($opt, $detailHtml);
