<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require __DIR__ . '/chrome.php';

$bundle = partner_bundle();
$q = trim((string) ($_GET['q'] ?? ''));
$catalog = store_partner_catalog_rates();
$thumbs = [
	'cebu' => '/assets/downloaded/dest-cebu.jpg',
	'bohol' => '/assets/downloaded/dest-bohol.jpg',
	'siquijor' => '/assets/downloaded/dest-siquijor.jpg',
	'dumaguete' => '/assets/downloaded/dest-dumaguete.jpg',
];

$total = count($catalog);
$islands = [];
$lowest = null;
$pctSum = 0;
$pctCount = 0;
$latest = '';
$hasDay = false;
$hasMulti = false;
foreach ($catalog as $package) {
	$key = (string) ($package['island_key'] ?? '');
	if ($key !== '') {
		$islands[$key] = [
			'label' => (string) ($package['island'] ?? ucfirst($key)),
			'n' => (int) (($islands[$key]['n'] ?? 0) + 1),
		];
	}
	if ($lowest === null || (int) $package['rate'] < (int) $lowest['rate']) {
		$lowest = $package;
	}
	foreach ($package['tiers'] as $tier) {
		if ((int) ($tier['market'] ?? 0) > 0) {
			$pctSum += (int) ($tier['pct'] ?? 0);
			$pctCount++;
		}
	}
	$stamp = (string) ($package['updated'] ?? '');
	if ($stamp !== '' && $stamp > $latest) {
		$latest = $stamp;
	}
	$duration = (string) ($package['duration'] ?? '');
	if (preg_match('/^1\b/i', $duration)) {
		$hasDay = true;
	} else {
		$hasMulti = true;
	}
}
$avg = $pctCount > 0 ? (int) round($pctSum / $pctCount) : 0;
$updatedLabel = '';
if ($latest !== '') {
	$updatedAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $latest, new DateTimeZone('Asia/Manila'));
	$updatedLabel = $updatedAt ? $updatedAt->format('M j, Y') : '';
}
$islandLine = [];
foreach ($islands as $island) {
	$islandLine[] = (string) $island['label'];
}
$islandText = $islandLine ? implode(' · ', $islandLine) : 'On your rate sheet';

partner_layout_start('Rates & Packages', 'rates.php', 'Partner', false);
?>
<div class="ke-portal ke-rates">
	<div class="ke-rate-head">
		<div>
			<h1>Rates &amp; Packages</h1>
			<p>Market price is the public rate. Contracted rate is your partner rate, per person. The office sets the partner rate from each tour package.</p>
		</div>
		<div class="ke-rate-actions">
			<a class="ke-rate-btn" href="mailto:support@kuyaelytours.com?subject=Custom%20partner%20rate%20request"><i class="bx bx-envelope"></i> Request Custom Rate</a>
			<a class="ke-rate-btn" href="/partner/rates-pdf.php"><i class="bx bx-download"></i> Download PDF</a>
			<button class="ke-rate-btn is-solid" type="button" onclick="window.print()"><i class="bx bx-printer"></i> Print Rate Sheet</button>
		</div>
	</div>

	<div class="ke-rate-stats">
		<article class="ke-rate-stat">
			<span class="ke-rate-ico is-blue"><i class="bx bx-cube"></i></span>
			<div>
				<span>Total Packages</span>
				<strong><?php echo $total; ?></strong>
				<small><?php echo store_h($islandText); ?></small>
			</div>
		</article>
		<article class="ke-rate-stat">
			<span class="ke-rate-ico is-green">%</span>
			<div>
				<span>Lowest Contracted Rate</span>
				<strong><?php echo $lowest ? store_h(store_money((int) $lowest['rate'])) : '—'; ?></strong>
				<small><?php echo store_h($lowest ? (string) $lowest['name'] : 'No rates yet'); ?></small>
			</div>
		</article>
		<article class="ke-rate-stat">
			<span class="ke-rate-ico is-amber"><i class="bx bx-bar-chart-alt-2"></i></span>
			<div>
				<span>Average Partner Savings</span>
				<strong><?php echo $avg; ?>%</strong>
				<small>vs. market price</small>
			</div>
		</article>
		<article class="ke-rate-stat">
			<span class="ke-rate-ico is-purple"><i class="bx bx-file"></i></span>
			<div>
				<span>Rate Sheet</span>
				<strong class="is-date"><?php echo $updatedLabel !== '' ? 'Updated ' . store_h($updatedLabel) : '—'; ?></strong>
				<small><a href="/partner/rates-pdf.php">Download the latest rates</a></small>
			</div>
		</article>
	</div>

	<div class="ke-rate-bar">
		<div class="ke-rate-tabs" role="tablist">
			<button class="ke-rate-tab is-on" type="button" data-ke-tab="all">All Packages (<?php echo $total; ?>)</button>
			<?php foreach ($islands as $key => $island): ?>
				<button class="ke-rate-tab" type="button" data-ke-tab="<?php echo store_h($key); ?>"><?php echo store_h((string) $island['label']); ?> (<?php echo (int) $island['n']; ?>)</button>
			<?php endforeach; ?>
		</div>
		<label class="ke-rate-q">
			<i class="bx bx-search"></i>
			<input id="ke-rate-q" type="search" value="<?php echo store_h($q); ?>" placeholder="Search packages..." aria-label="Search packages">
		</label>
		<div class="ke-rate-selects">
			<select class="ke-rate-select" id="ke-rate-dest" aria-label="Destination">
				<option value="">All Destinations</option>
				<?php foreach ($islands as $key => $island): ?>
					<option value="<?php echo store_h($key); ?>"><?php echo store_h((string) $island['label']); ?></option>
				<?php endforeach; ?>
			</select>
			<select class="ke-rate-select" id="ke-rate-type" aria-label="Package type">
				<option value="">All Package Types</option>
				<?php if ($hasDay): ?><option value="day">Day tour</option><?php endif; ?>
				<?php if ($hasMulti): ?><option value="multi">Multi-day</option><?php endif; ?>
			</select>
			<select class="ke-rate-select" id="ke-rate-status" aria-label="Status">
				<option value="">All Status</option>
				<option value="active">Active</option>
			</select>
		</div>
	</div>

	<p class="ke-rate-empty" id="ke-rate-empty" hidden>No package matches that search.</p>
	<?php if (!$catalog): ?>
		<p class="ke-empty">Partner prices show here after they are saved on a tour package.</p>
	<?php endif; ?>

	<?php foreach ($catalog as $i => $package):
		$islandKey = (string) ($package['island_key'] ?? '');
		$duration = trim((string) ($package['duration'] ?? ''));
		$type = preg_match('/^1\b/i', $duration) ? 'day' : 'multi';
		$place = trim((string) ($package['place'] ?? ''));
		if ($place === '') {
			$place = trim((string) ($package['island'] ?? '') . ', Philippines');
		}
		$lead = trim((string) ($package['lead'] ?? ''));
		if (function_exists('mb_strlen') && mb_strlen($lead) > 180) {
			$lead = rtrim(mb_substr($lead, 0, 177)) . '...';
		} elseif (strlen($lead) > 180) {
			$lead = rtrim(substr($lead, 0, 177)) . '...';
		}
		$photo = (string) ($package['cover'] ?? '');
		if ($photo === '') {
			$photo = $thumbs[$islandKey] ?? '/assets/downloaded/dest-cebu.jpg';
		}
		$slug = strtolower(trim((string) ($package['slug'] ?? '')));
		$tourUrl = $slug !== '' ? '/tours/' . rawurlencode($slug) : '';
		$find = strtolower(implode(' ', [
			(string) ($package['name'] ?? ''),
			(string) ($package['island'] ?? ''),
			$slug,
			$place,
			$duration,
			$lead,
			implode(' ', (array) ($package['pills'] ?? [])),
		]));
		$open = $i === 0;
	?>
	<article class="ke-rate-card<?php echo $open ? ' is-open' : ''; ?>" data-island="<?php echo store_h($islandKey); ?>" data-type="<?php echo store_h($type); ?>" data-status="active" data-find="<?php echo store_h($find); ?>">
		<div class="ke-rate-top" role="button" tabindex="0" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>">
			<div class="ke-rate-photo">
				<img src="<?php echo store_h($photo); ?>" alt="">
				<span class="ke-rate-pin"><i class="bx bx-map"></i> <?php echo store_h($place); ?></span>
			</div>
			<div class="ke-rate-main">
				<?php if (trim((string) ($package['badge'] ?? '')) !== ''): ?><span class="ke-rate-badge"><?php echo store_h((string) $package['badge']); ?></span><?php endif; ?>
				<h2><?php if ($tourUrl !== ''): ?><a class="ke-rate-link" href="<?php echo store_h($tourUrl); ?>" target="_blank" rel="noopener"><?php echo store_h((string) $package['name']); ?></a><?php else: ?><?php echo store_h((string) $package['name']); ?><?php endif; ?></h2>
				<?php if ($lead !== ''): ?><p class="ke-rate-lead"><?php echo store_h($lead); ?></p><?php endif; ?>
				<div class="ke-rate-meta">
					<span><i class="bx bx-map"></i> <?php echo store_h($place); ?></span>
					<?php if ($duration !== ''): ?><span><i class="bx bx-time-five"></i> <?php echo store_h($duration); ?></span><?php endif; ?>
					<?php if ($tourUrl !== ''): ?><a class="ke-rate-open" href="<?php echo store_h($tourUrl); ?>" target="_blank" rel="noopener"><i class="bx bx-link-external"></i> View package</a><?php endif; ?>
					<?php foreach ((array) ($package['pills'] ?? []) as $pill): ?>
						<span class="ke-rate-tag"><?php echo store_h((string) $pill); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
			<span class="ke-rate-live">Active</span>
			<div class="ke-rate-side">
				<div>
					<span>From</span>
					<strong><?php echo store_h(store_money((int) $package['rate'])); ?></strong>
				</div>
				<div>
					<span>Partner Savings</span>
					<b><?php echo store_h(store_money((int) $package['save'])); ?> (<?php echo (int) $package['pct']; ?>%)</b>
				</div>
			</div>
			<button class="ke-rate-chevron" type="button" tabindex="-1" aria-label="Show rates"><i class="bx bx-chevron-down"></i></button>
		</div>
		<div class="ke-rate-sheet">
			<table>
				<thead>
					<tr>
						<th>Group Size</th>
						<th>Market Price</th>
						<th>Contracted Rate</th>
						<th>Partner Savings</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($package['tiers'] as $tier):
					$min = (int) $tier['min'];
					$max = (int) $tier['max'];
					$pax = $max >= 100 ? ($min . '+ pax') : ($min . '–' . $max . ' pax');
				?>
					<tr>
						<td><?php echo store_h($pax); ?></td>
						<td><?php echo store_h(store_money((int) $tier['market'])); ?></td>
						<td><strong><?php echo store_h(store_money((int) $tier['rate'])); ?></strong></td>
						<td class="is-save"><?php echo store_h(store_money((int) $tier['save'])); ?> (<?php echo (int) $tier['pct']; ?>%)</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</article>
	<?php endforeach; ?>
</div>
<script>
(function () {
	var root = document.querySelector('.ke-rates');
	if (!root) return;
	var cards = Array.prototype.slice.call(root.querySelectorAll('.ke-rate-card'));
	var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-ke-tab]'));
	var q = document.getElementById('ke-rate-q');
	var dest = document.getElementById('ke-rate-dest');
	var type = document.getElementById('ke-rate-type');
	var status = document.getElementById('ke-rate-status');
	var empty = document.getElementById('ke-rate-empty');
	var island = 'all';

	function paintTabs() {
		tabs.forEach(function (tab) {
			tab.classList.toggle('is-on', tab.getAttribute('data-ke-tab') === island);
		});
	}
	function apply() {
		var text = (q && q.value ? q.value : '').trim().toLowerCase();
		var kind = type ? type.value : '';
		var state = status ? status.value : '';
		var shown = 0;
		cards.forEach(function (card) {
			var ok = true;
			if (island !== 'all' && card.getAttribute('data-island') !== island) ok = false;
			if (kind && card.getAttribute('data-type') !== kind) ok = false;
			if (state && card.getAttribute('data-status') !== state) ok = false;
			if (text && (card.getAttribute('data-find') || '').indexOf(text) === -1) ok = false;
			card.hidden = !ok;
			if (ok) shown++;
		});
		if (empty) empty.hidden = shown !== 0;
	}
	function setIsland(key) {
		island = key || 'all';
		if (dest) dest.value = island === 'all' ? '' : island;
		paintTabs();
		apply();
	}
	tabs.forEach(function (tab) {
		tab.addEventListener('click', function () { setIsland(tab.getAttribute('data-ke-tab') || 'all'); });
	});
	if (dest) dest.addEventListener('change', function () { setIsland(dest.value || 'all'); });
	if (type) type.addEventListener('change', apply);
	if (status) status.addEventListener('change', apply);
	if (q) q.addEventListener('input', apply);
	var topSearch = document.querySelector('.ke-psearch');
	var topInput = topSearch ? topSearch.querySelector('input') : null;
	if (topSearch && topInput && q) {
		topSearch.addEventListener('submit', function (event) {
			event.preventDefault();
			q.value = topInput.value;
			apply();
		});
		topInput.addEventListener('input', function () {
			q.value = topInput.value;
			apply();
		});
	}
	cards.forEach(function (card) {
		var top = card.querySelector('.ke-rate-top');
		if (!top) return;
		function toggle() {
			var open = card.classList.toggle('is-open');
			top.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
		top.addEventListener('click', function (event) {
			if (event.target.closest('a')) return;
			toggle();
		});
		top.addEventListener('keydown', function (event) {
			if (event.target.closest('a')) return;
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault();
				toggle();
			}
		});
	});
	apply();
})();
</script>
<?php partner_layout_end(); ?>
