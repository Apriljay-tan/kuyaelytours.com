<?php
declare(strict_types=1);

function ke_tour_defaults(string $island, string $product): array
{
	$pickup = [
		'cebu' => 'Cebu City or Mactan hotel pickup',
		'bohol' => 'Tagbilaran or Panglao hotel pickup',
		'siquijor' => 'Siquijor hotel pickup',
		'dumaguete' => 'Dumaguete hotel pickup',
	][$island] ?? 'Hotel pickup';

	return [
		'island' => $island,
		'product' => $product,
		'catalog' => '/' . $island . '-tour',
		'duration' => '1 Day',
		'rating' => '5.0',
		'reviews' => '24',
		'pills' => ['1 Day', 'Shared or Private Tour', 'Instant Confirmation', 'Perfect for Families & Friends'],
		'included' => [
			$pickup . ' and drop-off',
			'Private air-conditioned van',
			'Licensed local driver-guide',
			'Environmental and tourism fees as listed on your confirmation',
			'Bottled water',
		],
		'excluded' => [
			'Meals (optional add-on)',
			'Underwater camera rental',
			'Tips for guides and boatmen',
			'Entrance fees not listed on your confirmation',
			'Other activities not mentioned',
		],
		'tips' => [
			'Bring extra clothes and a towel.',
			'Use reef-safe sunscreen.',
			'Follow guide instructions at all times.',
			'Respect marine life and keep a safe distance.',
			'Best time to visit is during dry season (November to May).',
		],
		'policy' => [
			'Free cancellation up to 24 hours before the tour.',
			'Cancellations within 24 hours are non-refundable.',
			'Tours may be rescheduled due to weather conditions for your safety.',
			'Minimum number of participants may apply.',
		],
		'faq' => ke_tour_faq($island),
		'quote' => ['Bigger encounters', 'Brighter memories'],
		'promo_title' => 'A closer look a brighter tomorrow',
		'promo_text' => 'Responsible tourism helps protect these amazing creatures for generations to come.',
		'review' => [
			'quote' => 'An unforgettable experience! Kuya Ely’s team was so organized and friendly. Swimming with the whale sharks was a dream come true.',
			'name' => 'Maria S.',
			'date' => 'Visited April 2024',
		],
	];
}

function ke_tour_faq(string $island = 'cebu'): array
{
	$family = 'Most day tours welcome families. Young children should be comfortable in the water for marine activities. Tell us ages when you book so we can plan the pace.';
	$pickup = 'Yes, hotel pickup and drop-off are included from the usual tourist areas on that island. Stays outside the usual zone may have a small add-on — message us after you book.';
	$private = 'Yes. Packages can run private for your family or group. Choose guests on this page, then Book Now or Add to Cart.';
	$weather = 'If the coast guard or local operators stop the activity, we reschedule or suggest a nearby alternative. Safety comes first.';
	$combo = 'Yes. Many guests pair nearby stops on the same island. Browse related tours below or message us after checkout.';
	$bring = 'Swimwear, extra clothes, a towel, reef-safe sunscreen, a hat, cash for extras, and a dry bag for phones. We provide bottled water on the van.';

	$first = $island === 'cebu'
		? ['q' => 'Is it safe to swim with whale sharks?', 'a' => 'Yes. Briefings are given before water activities, and local guides stay with the group. Follow the rules, wear a life vest when required, and keep a respectful distance from wildlife.']
		: ['q' => 'Is this tour suitable for families?', 'a' => $family];
	$duration = $island === 'cebu'
		? ['q' => 'How long is the whale shark interaction?', 'a' => 'In-water time is typically 30–45 minutes, depending on conditions and local rules. The full day includes travel, briefing, and optional nearby stops.']
		: ['q' => 'How long does the day run?', 'a' => 'Most packages are a full day, including hotel pickup and drop-off. Sample times are on this page and may shift with weather, traffic, or ferry schedules.'];

	return [
		$first,
		['q' => 'What should I bring?', 'a' => $bring],
		['q' => 'Can children join?', 'a' => $family],
		['q' => 'Is hotel pick-up included?', 'a' => $pickup],
		$duration,
		['q' => 'Do you have a private option?', 'a' => $private],
		['q' => 'What happens if the weather is bad?', 'a' => $weather],
		['q' => 'Do you offer combo tours?', 'a' => $combo],
	];
}

function ke_tour(string $slug): ?array
{
	$slug = strtolower(trim($slug));
	$all = ke_tours();
	if (isset($all[$slug])) {
		return $all[$slug];
	}
	foreach ($all as $tour) {
		if (in_array($slug, (array) ($tour['aliases'] ?? []), true)) {
			return $tour;
		}
	}
	return null;
}

function ke_tour_related(string $slug, int $limit = 3): array
{
	$tour = ke_tour($slug);
	if (!$tour) {
		return [];
	}
	$out = [];
	foreach (ke_tours() as $otherSlug => $other) {
		if ($otherSlug === $slug) {
			continue;
		}
		if (($other['island'] ?? '') !== ($tour['island'] ?? '')) {
			continue;
		}
		if (isset($other['active']) && !$other['active']) {
			continue;
		}
		$out[] = $other;
		if (count($out) >= $limit) {
			break;
		}
	}
	return $out;
}

function ke_tour_area_map(): array
{
	return [
		'cebu-city-heritage-tour' => 'cebu-city',
		'temple-of-leah-sirao-tour' => 'cebu-city',
		'south-cebu-island-tour' => 'cebu-city',
		'moalboal-sardine-run' => 'moalboal',
		'kawasan-falls-day-tour' => 'moalboal',
		'oslob-whale-shark-experience' => 'oslob',
		'oslob-simala-tour' => 'oslob',
		'mactan-island-hopping' => 'cebu-city',
		'bohol-countryside-tour' => 'countryside',
		'chocolate-hills-loboc-cruise' => 'countryside',
		'panglao-island-tour' => 'panglao',
		'balicasag-island-hopping' => 'panglao',
		'hinagdanan-cave-beach' => 'panglao',
		'bohol-combined-day-tour' => 'countryside',
		'siquijor-island-tour' => 'island',
		'cambugahay-falls' => 'falls',
		'enchanted-balete-tree' => 'island',
		'lazi-church-convent' => 'lazi',
		'cave-swimming-adventure' => 'falls',
		'salagdoong-beach-day' => 'beach',
		'dumaguete-city-tour' => 'city',
		'apo-island-snorkeling' => 'apo',
		'twin-lakes-day-tour' => 'valencia',
		'casaroro-falls' => 'valencia',
		'valencia-highlands' => 'valencia',
		'sidlakang-negros-experience' => 'city',
	];
}

function ke_default_pickups(string $island): array
{
	$map = [
		'cebu' => [
			'Cebu City',
			'Mactan / Lapu-lapu City',
			'Mactan & drop-off Moalboal',
			'Mactan & drop-off Oslob',
			'Mandaue City',
			'Talisay City',
			'Moalboal',
			'Moalboal & drop-off Mactan',
			'Moalboal & drop-off Oslob',
			'Ronda',
			'Badian',
			'Argao',
			'Alegria',
			'Oslob',
			'Oslob & drop-off Cebu City',
			'Oslob & drop-off Moalboal',
			'Cuartel (port from Bohol)',
			'Sumilon Island Port',
			'Liloan Port (port from Dumaguete)',
			'Santander',
			'Boljoon',
		],
		'bohol' => [
			'Tagbilaran City',
			'Panglao / Alona',
			'Bohol-Panglao Airport',
			'Loboc',
			'Carmen (Chocolate Hills)',
			'Tagbilaran port',
		],
		'siquijor' => [
			'Siquijor port',
			'Larena',
			'San Juan',
			'Lazi',
			'Maria',
			'Siquijor town',
		],
		'dumaguete' => [
			'Dumaguete City',
			'Dumaguete Airport / Sibulan',
			'Valencia',
			'Malatapay / Zamboanguita',
			'Apo Island jump-off',
			'Dumaguete port',
		],
	];
	return $map[$island] ?? $map['cebu'];
}

function ke_child_price_mode_value(string $mode): string
{
	return in_array($mode, ['fixed', 'amount', 'percent'], true) ? $mode : 'fixed';
}

function ke_child_rate(int $adult, int $entered, string $mode): int
{
	$adult = max(0, $adult);
	$entered = max(0, $entered);
	if ($mode === 'amount') {
		return max(0, $adult - $entered);
	}
	if ($mode === 'percent') {
		$entered = min(100, $entered);
		return max(0, (int) round($adult * (100 - $entered) / 100));
	}
	return $entered > 0 ? $entered : $adult;
}

function ke_normalize_price_tiers($rows, int $fallback = 0): array
{
	$out = [];
	if (!is_array($rows)) {
		return $out;
	}
	foreach ($rows as $row) {
		if (is_string($row)) {
			$parts = array_map('trim', explode('|', $row));
			$row = [
				'min' => (int) ($parts[0] ?? 0),
				'max' => (int) ($parts[1] ?? 0),
				'foreign_adult' => (int) ($parts[2] ?? 0),
				'local_adult' => (int) ($parts[3] ?? 0),
				'foreign_child' => (int) ($parts[4] ?? 0),
				'local_child' => (int) ($parts[5] ?? 0),
			];
		}
		if (!is_array($row)) {
			continue;
		}
		$min = max(1, (int) ($row['min'] ?? 0));
		$max = max($min, (int) ($row['max'] ?? 0));
		$fa = (int) ($row['foreign_adult'] ?? 0);
		$la = (int) ($row['local_adult'] ?? $fa);
		$hasChildOff = array_key_exists('foreign_child_off', $row) || array_key_exists('local_child_off', $row);
		$fcOff = array_key_exists('foreign_child_off', $row) ? max(0, (int) $row['foreign_child_off']) : null;
		$lcOff = array_key_exists('local_child_off', $row) ? max(0, (int) $row['local_child_off']) : null;
		$fc = (int) ($row['foreign_child'] ?? $fa);
		$lc = (int) ($row['local_child'] ?? $la);
		if ($fa < 1 && $la < 1 && $fallback < 1) {
			continue;
		}
		if ($fa < 1) {
			$fa = $fallback;
		}
		if ($la < 1) {
			$la = $fa;
		}
		if ($fc < 1 && !$hasChildOff) {
			$fc = $fa;
		}
		if ($lc < 1 && !$hasChildOff) {
			$lc = $la;
		}
		$tier = [
			'min' => $min,
			'max' => $max,
			'foreign_adult' => $fa,
			'local_adult' => $la,
			'foreign_child' => max(0, $fc),
			'local_child' => max(0, $lc),
		];
		if ($fcOff !== null) {
			$tier['foreign_child_off'] = $fcOff;
		}
		if ($lcOff !== null) {
			$tier['local_child_off'] = $lcOff;
		}
		$out[] = $tier;
	}
	return $out;
}

function ke_normalize_addons($rows): array
{
	$out = [];
	if (!is_array($rows)) {
		return $out;
	}
	foreach ($rows as $row) {
		if (is_string($row)) {
			$parts = array_map('trim', explode('|', $row, 3));
			$row = ['name' => $parts[0] ?? '', 'price' => (int) ($parts[1] ?? 0), 'hint' => $parts[2] ?? ''];
		}
		if (!is_array($row)) {
			continue;
		}
		$name = trim((string) ($row['name'] ?? ''));
		if ($name === '') {
			continue;
		}
		$id = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name) ?: 'addon');
		$out[] = [
			'id' => (string) ($row['id'] ?? $id),
			'name' => $name,
			'price' => max(0, (int) ($row['price'] ?? 0)),
			'hint' => trim((string) ($row['hint'] ?? '')),
		];
	}
	return $out;
}

function ke_tour_pickups(array $tour): array
{
	$saved = array_values(array_filter(array_map('strval', (array) ($tour['pickups'] ?? []))));
	return $saved ?: ke_default_pickups((string) ($tour['island'] ?? 'cebu'));
}

function ke_tour_price_tiers(array $tour): array
{
	$tiers = ke_normalize_price_tiers($tour['price_tiers'] ?? [], 0);
	$mode = ke_child_price_mode_value((string) ($tour['child_price_mode'] ?? 'fixed'));
	if ($mode !== 'fixed') {
		foreach ($tiers as $i => $tier) {
			$foreignOff = array_key_exists('foreign_child_off', $tier) ? (int) $tier['foreign_child_off'] : 0;
			$localOff = array_key_exists('local_child_off', $tier) ? (int) $tier['local_child_off'] : $foreignOff;
			$tiers[$i]['foreign_child'] = ke_child_rate((int) $tier['foreign_adult'], $foreignOff, $mode);
			$tiers[$i]['local_child'] = ke_child_rate((int) $tier['local_adult'], $localOff, $mode);
		}
	}
	if ($tiers) {
		return $tiers;
	}
	$fallback = ke_package_price($tour);
	if ($fallback < 1) {
		return [];
	}
	return [[
		'min' => 1,
		'max' => 20,
		'foreign_adult' => $fallback,
		'local_adult' => $fallback,
		'foreign_child' => $fallback,
		'local_child' => $fallback,
	]];
}

function ke_tour_addons(array $tour): array
{
	return ke_normalize_addons($tour['addons'] ?? []);
}

function ke_tier_for_pax(array $tiers, int $pax): ?array
{
	if (!$tiers) {
		return null;
	}
	$pax = max(1, $pax);
	foreach ($tiers as $tier) {
		if ($pax >= (int) $tier['min'] && $pax <= (int) $tier['max']) {
			return $tier;
		}
	}
	$last = $tiers[array_key_last($tiers)];
	if ($pax > (int) $last['max']) {
		return $last;
	}
	return $tiers[0];
}

function ke_package_split_local(array $tour): bool
{
	if (!array_key_exists('split_local_foreign', $tour)) {
		return true;
	}
	return (bool) $tour['split_local_foreign'];
}

function ke_quote_booking(array $tour, array $qty, array $addonIds = []): array
{
	$counts = [
		'foreign_adult' => max(0, (int) ($qty['foreign_adult'] ?? 0)),
		'local_adult' => max(0, (int) ($qty['local_adult'] ?? 0)),
		'foreign_child' => max(0, (int) ($qty['foreign_child'] ?? 0)),
		'local_child' => max(0, (int) ($qty['local_child'] ?? 0)),
	];
	$pax = array_sum($counts);
	$tiers = ke_tour_price_tiers($tour);
	$tier = ke_tier_for_pax($tiers, $pax > 0 ? $pax : 1);
	$rates = $tier ?: ['foreign_adult' => 0, 'local_adult' => 0, 'foreign_child' => 0, 'local_child' => 0];
	if (!ke_package_split_local($tour)) {
		$rates['local_adult'] = (int) ($rates['foreign_adult'] ?? 0);
		$rates['local_child'] = (int) ($rates['foreign_child'] ?? 0);
	}
	$guestTotal = 0;
	foreach ($counts as $key => $n) {
		$guestTotal += $n * (int) ($rates[$key] ?? 0);
	}
	$from = 0;
	$fromKeys = ke_package_split_local($tour) ? ['foreign_adult', 'local_adult'] : ['foreign_adult'];
	foreach ($tiers as $row) {
		foreach ($fromKeys as $key) {
			$v = (int) ($row[$key] ?? 0);
			if ($v > 0 && ($from === 0 || $v < $from)) {
				$from = $v;
			}
		}
	}
	$addons = [];
	$addonTotal = 0;
	$want = [];
	foreach ($addonIds as $id) {
		$want[strtolower(trim((string) $id))] = true;
	}
	foreach (ke_tour_addons($tour) as $addon) {
		$key = strtolower((string) $addon['id']);
		$name = strtolower((string) $addon['name']);
		if (isset($want[$key]) || isset($want[$name])) {
			$addons[] = $addon;
			$addonTotal += (int) $addon['price'];
		}
	}
	return [
		'pax' => $pax,
		'counts' => $counts,
		'rates' => $rates,
		'from' => $from,
		'guest_total' => $guestTotal,
		'addons' => $addons,
		'addon_total' => $addonTotal,
		'total' => $guestTotal + $addonTotal,
		'per_person' => $pax > 0 ? (int) round($guestTotal / $pax) : $from,
	];
}

function ke_package_normalize(array $row): array
{
	$island = (string) ($row['island'] ?? 'cebu');
	$product = (string) ($row['product'] ?? ('tour-' . $island));
	$slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', (string) ($row['slug'] ?? '')));
	$base = ke_tour_defaults($island, $product);
	$areas = ke_tour_area_map();
	$tiers = ke_normalize_price_tiers($row['price_tiers'] ?? [], (int) ($row['price_from'] ?? 0));
	$merged = array_merge($base, $row, [
		'slug' => $slug,
		'island' => $island,
		'product' => $product,
		'catalog' => '/' . $island . '-tour',
		'area' => (string) ($row['area'] ?? $areas[$slug] ?? 'all'),
		'active' => array_key_exists('active', $row) ? (bool) $row['active'] : true,
		'sort' => (int) ($row['sort'] ?? 0),
		'price_from' => (int) ($row['price_from'] ?? 0),
		'price_teaser' => trim((string) ($row['price_teaser'] ?? '')),
		'cover' => trim((string) ($row['cover'] ?? '')),
		'preview_token' => preg_replace('/[^a-f0-9]/', '', (string) ($row['preview_token'] ?? '')) ?: '',
		'aliases' => array_values(array_unique(array_filter(array_map(static function ($alias) use ($slug) {
			$alias = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/', '-', (string) $alias)));
			$alias = trim($alias, '-');
			return ($alias !== '' && $alias !== $slug) ? $alias : '';
		}, (array) ($row['aliases'] ?? []))))),
		'video_url' => (string) ($row['video_url'] ?? ''),
		'images' => array_values(array_filter((array) ($row['images'] ?? []))),
		'guest_photos' => array_values(array_unique(array_filter(array_map('strval', (array) ($row['guest_photos'] ?? []))))),
		'pickups' => array_values(array_filter(array_map('strval', (array) ($row['pickups'] ?? [])))),
		'price_tiers' => $tiers,
		'addons' => ke_normalize_addons($row['addons'] ?? []),
		'split_local_foreign' => array_key_exists('split_local_foreign', $row) ? (bool) $row['split_local_foreign'] : true,
		'child_price_mode' => ke_child_price_mode_value((string) ($row['child_price_mode'] ?? 'fixed')),
		'age_adult' => (string) ($row['age_adult'] ?? '4 years old & above'),
		'age_child' => (string) ($row['age_child'] ?? '3 years old'),
	]);
	return $merged;
}

function ke_package_cover(array $tour, string $fallback = ''): string
{
	$cover = trim((string) ($tour['cover'] ?? ''));
	if ($cover !== '') {
		return $cover;
	}
	$first = trim((string) (($tour['images'][0] ?? '') ?: $fallback));
	return $first;
}

function ke_item_cover(array $item, string $fallback = '/assets/downloaded/dest-cebu.jpg'): string
{
	$slug = strtolower(trim((string) ($item['package_slug'] ?? '')));
	if ($slug !== '' && function_exists('ke_tour')) {
		$tour = ke_tour($slug);
		if (is_array($tour)) {
			$cover = ke_package_cover($tour, '');
			if ($cover !== '') {
				return $cover;
			}
		}
	}
	$saved = trim((string) ($item['cover'] ?? ''));
	if ($saved !== '') {
		return $saved;
	}
	return $fallback !== '' ? $fallback : '/assets/downloaded/dest-cebu.jpg';
}

function ke_package_teaser(array $tour): string
{
	$custom = trim((string) ($tour['price_teaser'] ?? ''));
	if ($custom !== '') {
		return $custom;
	}
	$price = ke_package_price($tour);
	if ($price < 1) {
		return '';
	}
	return 'From ₱' . number_format($price) . ' / pax';
}

function ke_package_preview_ok(?array $tour, string $token): bool
{
	if (!$tour || $token === '') {
		return false;
	}
	$known = (string) ($tour['preview_token'] ?? '');
	return $known !== '' && hash_equals($known, $token);
}

function ke_package_price(array $tour): int
{
	$min = 0;
	$priceKeys = ke_package_split_local($tour) ? ['foreign_adult', 'local_adult'] : ['foreign_adult'];
	foreach (ke_normalize_price_tiers($tour['price_tiers'] ?? [], 0) as $tier) {
		foreach ($priceKeys as $key) {
			$v = (int) ($tier[$key] ?? 0);
			if ($v > 0 && ($min === 0 || $v < $min)) {
				$min = $v;
			}
		}
	}
	if ($min > 0) {
		return $min;
	}
	$own = (int) ($tour['price_from'] ?? 0);
	if ($own > 0) {
		return $own;
	}
	$product = function_exists('store_product') ? store_product((string) ($tour['product'] ?? '')) : null;
	return (int) ($product['price_from'] ?? 0);
}

function ke_package_cart_id(array $tour): string
{
	$slug = (string) ($tour['slug'] ?? '');
	if ($slug !== '' && (int) ($tour['price_from'] ?? 0) > 0 && function_exists('store_product') && store_product('pkg-' . $slug)) {
		return 'pkg-' . $slug;
	}
	return (string) ($tour['product'] ?? 'tour-cebu');
}

function ke_package_book_attr(array $tour): string
{
	$json = json_encode([
		'slug' => (string) ($tour['slug'] ?? ''),
		'split' => ke_package_split_local($tour),
		'ageAdult' => (string) ($tour['age_adult'] ?? '5 years old & above'),
		'ageChild' => (string) ($tour['age_child'] ?? 'Below 5 years old'),
		'pickups' => array_values(array_filter(array_map('strval', ke_tour_pickups($tour)))),
	], JSON_UNESCAPED_UNICODE);
	return is_string($json) ? $json : '{}';
}

function ke_tours(bool $reload = false): array
{
	static $tours = null;
	if ($reload) {
		$tours = null;
	}
	if ($tours !== null) {
		return $tours;
	}
	$dir = defined('STORE_DATA') ? STORE_DATA : (__DIR__ . '/data');
	$file = $dir . '/packages.json';
	if (is_readable($file)) {
		$rows = json_decode((string) file_get_contents($file), true);
		if (is_array($rows) && $rows) {
			$indexed = [];
			foreach ($rows as $row) {
				if (!is_array($row) || empty($row['slug'])) {
					continue;
				}
				$pkg = ke_package_normalize($row);
				if ($pkg['slug'] !== '') {
					$indexed[$pkg['slug']] = $pkg;
				}
			}
			if ($indexed) {
				uasort($indexed, static function ($a, $b) {
					return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
				});
				$tours = $indexed;
				return $tours;
			}
		}
	}
	$tours = ke_tours_builtin();
	if (defined('STORE_DATA') && function_exists('store_write_json')) {
		store_write_json('packages', array_values($tours));
	}
	return $tours;
}

function ke_tours_builtin(): array
{
	static $tours = null;
	if ($tours !== null) {
		return $tours;
	}

	$raw = [
		// Cebu
		['cebu-city-heritage-tour', 'cebu', 'tour-cebu', 'Cebu City Heritage Tour', 'Cebu City, Philippines', 'Culture & Heritage',
			'Walk Magellan’s Cross, Basilica del Santo Niño, Fort San Pedro, and Cebu’s old streets with a private van.',
			['/assets/downloaded/moments/moment-6431.jpg', '/assets/downloaded/moments/moment-6420.jpg', '/assets/downloaded/moments/moment-6435.jpg', '/assets/downloaded/dest-cebu.jpg'],
			'Explore Cebu’s rich history and sacred landmarks in one easy city day. This private heritage loop is paced for photos, churches, and a relaxed lunch stop — perfect if you just landed or want culture before the beaches.',
			['Magellan’s Cross and Basilica del Santo Niño', 'Fort San Pedro and the old Spanish core', 'Heritage streets with a local driver-guide', 'Perfect for first-time visitors', 'Photo stops at iconic landmarks', 'Easy add-on to a Mactan stay'],
			[
				['img' => '/assets/downloaded/moments/moment-6431.jpg', 'title' => 'Heritage Core', 'text' => 'Start at Magellan’s Cross and the Basilica, the heart of Cebu’s faith story.'],
				['img' => '/assets/downloaded/moments/moment-6420.jpg', 'title' => 'Fort San Pedro', 'text' => 'Walk the oldest Spanish fort in the Philippines before the city gets busy.'],
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'City Views', 'text' => 'Finish with a lookout or Taoist Temple stop if time and traffic allow.'],
			],
			[['03:00 PM', 'Optional Taoist Temple or topside view (traffic dependent)'], ['08:00 AM', 'Hotel pickup in Cebu City or Mactan'], ['09:00 AM', 'Magellan’s Cross and Basilica del Santo Niño'], ['10:30 AM', 'Fort San Pedro'], ['12:00 PM', 'Lunch in the city (own account)'], ['01:30 PM', 'Heritage streets and photo stops']],
		],
		['temple-of-leah-sirao-tour', 'cebu', 'tour-cebu', 'Temple of Leah & Sirao Tour', 'Cebu City, Cebu', 'City Tour',
			'Visit the Temple of Leah and the colorful Sirao Garden — Cebu’s little Amsterdam in the hills.',
			['/assets/downloaded/moments/moment-6435.jpg', '/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/moments/moment-6431.jpg', '/assets/downloaded/moments/moment-6375.jpg'],
			'Ride up into the Cebu hills for Roman-inspired terraces at Temple of Leah and a flower garden day at Sirao. Cooler air, big views, and plenty of photo time — a favorite half-to-full day from the city.',
			['Temple of Leah grand stairways and views', 'Sirao Garden flower terraces', 'Private van up the hills', 'Ideal for couples and families', 'Plenty of photo stops', 'Easy to pair with a city heritage loop'],
			[
				['img' => '/assets/downloaded/moments/moment-6435.jpg', 'title' => 'Temple of Leah', 'text' => 'Walk the terraces and look back over Cebu City from the hills.'],
				['img' => '/assets/downloaded/moments/moment-6375.jpg', 'title' => 'Sirao Garden', 'text' => 'Colorful blooms and “little Amsterdam” paths for unhurried photos.'],
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'Hillside Drive', 'text' => 'Scenic road time with your driver — no jeepney transfers.'],
			],
			[['08:00 AM', 'Hotel pickup'], ['09:00 AM', 'Temple of Leah'], ['11:00 AM', 'Sirao Garden'], ['12:30 PM', 'Lunch nearby (own account)'], ['02:00 PM', 'Optional extra lookout if time allows'], ['04:00 PM', 'Return to hotel']],
		],
		['south-cebu-island-tour', 'cebu', 'tour-cebu', 'South Cebu Island Tour', 'South Cebu, Philippines', 'Island Hopping',
			'Discover Pescador, the Twins, and Panagsama Beach on a south Cebu island day.',
			['/assets/downloaded/popular-img1.png', '/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/moments/moment-6611.jpg'],
			'A classic south Cebu water day: van to Moalboal, then a boat around Pescador Island, the Twins, and a beach stop. Snorkel when the sea is calm, then ride home with a private van.',
			['Boat around Pescador and the Twins', 'Snorkeling when conditions allow', 'Panagsama Beach time', 'Private van from Cebu or Mactan', 'Good for mixed-age groups', 'Combine with sardine run on request'],
			[
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'Island Hopping', 'text' => 'Hop Pescador and the Twin rocks with a local boat crew.'],
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Snorkel Stops', 'text' => 'Enter the water with a vest and a guide when the current is friendly.'],
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'South Cebu Coast', 'text' => 'Scenic drive down the island with hotel pickup and drop-off.'],
			],
			[['05:00 AM', 'Early hotel pickup for the southbound drive'], ['08:00 AM', 'Arrive Moalboal / Panagsama'], ['08:30 AM', 'Boat briefing and island hopping'], ['12:00 PM', 'Lunch on shore (own account)'], ['01:30 PM', 'Beach or extra snorkel time'], ['03:00 PM', 'Drive back to Cebu City / Mactan']],
		],
		['moalboal-sardine-run', 'cebu', 'tour-cebu', 'Moalboal Sardine Run', 'Moalboal, Cebu', 'Marine Adventure',
			'Swim with millions of sardines and experience Cebu’s world-famous underwater wonder.',
			['/assets/downloaded/popular-img1.png', '/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/moments/moment-6611.jpg'],
			'Moalboal’s sardine tornado is one of the easiest big marine spectacles in the Philippines — often just off the wall at Panagsama. We handle the van, briefing, and local boat or shore entry so you can focus on the water.',
			['Swim near the sardine school with a guide', 'Life vest and briefing included in the water plan', 'Optional turtle spotting', 'Private van from Cebu City or Mactan', 'Photo and video opportunities', 'Pair with Kawasan Falls the same day on request'],
			[
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'Sardine Wall', 'text' => 'Enter near Panagsama and watch the school move as one.'],
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Guided Swim', 'text' => 'Stay with your guide, keep hands off the reef, and enjoy the show.'],
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'Moalboal Day', 'text' => 'Time on shore after the swim, then a private ride home.'],
			],
			[['05:00 AM', 'Hotel pickup'], ['08:00 AM', 'Arrive Moalboal and gear up'], ['08:30 AM', 'Sardine run swim'], ['10:30 AM', 'Shore time / optional extra snorkel'], ['12:00 PM', 'Lunch (own account)'], ['01:30 PM', 'Drive back or continue to Kawasan if pre-arranged']],
		],
		['kawasan-falls-day-tour', 'cebu', 'tour-cebu', 'Kawasan Falls Day Tour', 'Badian, Cebu', 'Nature & Adventure',
			'Experience the iconic turquoise falls and a thrilling canyoneering add-on when you want it.',
			['/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/moments/moment-6611.jpg', '/assets/downloaded/popular-img1.png', '/assets/downloaded/moments/moment-6525.jpg'],
			'Kawasan’s blue-green basins are the postcard of south Cebu. Ride with us from the city, walk in, swim the pools, and add canyoneering if you booked it. Pace is flexible for families who only want the falls.',
			['Turquoise falls and natural pools', 'Optional canyoneering (pre-booked)', 'Private van from Cebu or Mactan', 'Life vest for water sections', 'Great for families and groups', 'Combine with Moalboal on a long day'],
			[
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'Kawasan Basins', 'text' => 'Swim the famous turquoise pools at a pace that suits your group.'],
				['img' => '/assets/downloaded/moments/moment-6611.jpg', 'title' => 'Canyoneering Option', 'text' => 'Jumps, slides, and a river walk — confirm fitness when you book.'],
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'South Cebu Drive', 'text' => 'Scenic road time with pickup and drop-off included.'],
			],
			[['05:00 AM', 'Hotel pickup'], ['08:00 AM', 'Arrive Badian / Kawasan area'], ['08:30 AM', 'Falls walk-in or canyoneering start'], ['12:00 PM', 'Lunch nearby (own account)'], ['01:30 PM', 'Extra swim or photos'], ['03:00 PM', 'Return drive']],
		],
		['oslob-whale-shark-experience', 'cebu', 'tour-cebu', 'Oslob Whale Shark Watching Experience', 'Oslob, Cebu', 'Wildlife Encounter',
			'Swim with the gentle giants of Oslob and experience South Cebu’s top adventure.',
			['/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/popular-img1.png', '/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/moments/moment-6431.jpg'],
			'Get up close and personal with the magnificent whale sharks of Oslob, Cebu! This once-in-a-lifetime experience lets you swim alongside the world’s largest fish in their natural habitat. Perfect for nature lovers and adventure seekers, this tour also gives you a chance to explore the beautiful coastline of South Cebu.',
			['Swim with gentle whale sharks in Oslob', 'Guided and safe experience with local experts', 'Explore the beautiful South Cebu coastline', 'Perfect for families, couples and groups', 'Photo and video opportunities', 'Combine with nearby attractions (optional)'],
			[
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'Meet the Gentle Giants', 'text' => 'Swim alongside whale sharks in a safe and guided environment.'],
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Scenic Boat Ride', 'text' => 'Enjoy a short and scenic boat ride to the whale shark viewing area.'],
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'Explore South Cebu', 'text' => 'Optional add-on nearby attractions like Sumilon Island or Tumalog Falls.'],
			],
			[['03:00 AM', 'Pick-up from your hotel in Cebu City'], ['06:00 AM', 'Arrive in Oslob, Cebu'], ['06:30 AM', 'Whale shark watching experience (swimming time)'], ['08:00 AM', 'Optional side trip: Sumilon Island / Tumalog Falls (add-on)'], ['12:00 PM', 'Lunch break (own account)'], ['01:00 PM', 'Depart for Cebu City'], ['05:00 PM', 'Estimated arrival in Cebu City']],
			['included' => [
				'Round-trip air-conditioned van (Cebu City)',
				'Whale shark watching fee',
				'Life vest and snorkeling gear',
				'Licensed local guide and boat assistance',
				'Environmental & tourism fees',
				'Bottled water',
				'Pick-up and drop-off (Cebu City)',
			], 'excluded' => [
				'Meals (optional add-on)',
				'Underwater camera rental',
				'Tumalog Falls entrance fee',
				'Tips for guides and boatmen',
				'Other activities not mentioned',
			], 'reviews' => '1524'],
		],
		['oslob-simala-tour', 'cebu', 'tour-cebu', 'Oslob & Simala Tour', 'Oslob & Sibonga, Cebu', 'Pilgrimage & Tour',
			'A perfect combination of adventure and faith. Meet the whale sharks in Oslob and visit Simala Shrine.',
			['/assets/downloaded/moments/moment-6420.jpg', '/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/popular-img1.png', '/assets/downloaded/moments/moment-6431.jpg'],
			'Start with the whale sharks in Oslob, then continue to the castle-like Simala Shrine in Sibonga. One southbound day that mixes marine encounter and pilgrimage — a favorite for families and church groups.',
			['Whale shark encounter in Oslob', 'Simala Shrine visit in Sibonga', 'Private van for the long south drive', 'Time for photos and prayer', 'Good for families and groups', 'Optional Tumalog Falls if time allows'],
			[
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'Oslob Morning', 'text' => 'Early water time with the whale sharks while the bay is calm.'],
				['img' => '/assets/downloaded/moments/moment-6420.jpg', 'title' => 'Simala Shrine', 'text' => 'Walk the shrine grounds and take in the castle architecture.'],
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'South Cebu Road', 'text' => 'We handle the long drive so you can rest between stops.'],
			],
			[['03:00 AM', 'Hotel pickup'], ['06:00 AM', 'Oslob whale shark experience'], ['08:30 AM', 'Optional Tumalog (if pre-booked)'], ['11:00 AM', 'Simala Shrine'], ['01:00 PM', 'Lunch (own account)'], ['05:00 PM', 'Back in Cebu City / Mactan']],
		],
		['mactan-island-hopping', 'cebu', 'tour-cebu', 'Mactan Island Hopping', 'Mactan, Cebu', 'Island Hopping',
			'Explore Mactan’s crystal-clear waters and nearby islands — perfect for beach lovers.',
			['/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/popular-img1.png', '/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/moments/moment-6611.jpg'],
			'Stay close to Mactan for a boat day: sandbars, snorkeling, and island stops without the long south drive. Ideal if you are based in Lapu-Lapu or have a late flight.',
			['Boat hopping around Mactan', 'Snorkel stops when the sea is calm', 'Beach and sandbar time', 'Short transfer from Mactan hotels', 'Good for families', 'Flexible afternoon end time'],
			[
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Bangka Day', 'text' => 'Ride a local boat between islands and sandbars.'],
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'Snorkel Time', 'text' => 'Vests on, reef rules on, photos in the clear shallows.'],
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'Mactan Easy', 'text' => 'Quick pickup from Lapu-Lapu or nearby Cebu hotels.'],
			],
			[['08:00 AM', 'Hotel pickup in Mactan / nearby'], ['09:00 AM', 'Boat briefing and first stop'], ['11:00 AM', 'Snorkel or sandbar'], ['12:30 PM', 'Lunch on the boat or shore (own account)'], ['02:30 PM', 'Last swim'], ['04:00 PM', 'Back to hotel']],
		],

		// Bohol
		['bohol-countryside-tour', 'bohol', 'tour-bohol', 'Bohol Countryside Tour', 'Bohol, Philippines', 'Nature & Heritage',
			'Tarsiers, heritage churches, and countryside views with a private van and hotel pickup.',
			['/assets/downloaded/dest-bohol.jpg', '/assets/downloaded/moments/moment-6420.jpg', '/assets/downloaded/moments/moment-6431.jpg', '/assets/downloaded/moments/moment-6611.jpg'],
			'The classic Bohol loop: tarsier sanctuary, countryside roads, a heritage church stop, and time for the hills or river if you want a fuller day. Private van, local pacing, hotel pickup.',
			['Tarsier sanctuary visit', 'Heritage church photo stop', 'Countryside roads in a private van', 'Great for first-time Bohol guests', 'Easy add-on to Chocolate Hills', 'Family-friendly pace'],
			[
				['img' => '/assets/downloaded/dest-bohol.jpg', 'title' => 'Countryside Roads', 'text' => 'See Bohol’s karst hills and villages from a private van.'],
				['img' => '/assets/downloaded/moments/moment-6431.jpg', 'title' => 'Tarsiers', 'text' => 'Quiet viewing at a sanctuary — no touching, plenty of photos.'],
				['img' => '/assets/downloaded/moments/moment-6420.jpg', 'title' => 'Heritage Stop', 'text' => 'Church and plaza time on the way through the island.'],
			],
			[['07:00 AM', 'Hotel pickup in Tagbilaran or Panglao'], ['08:30 AM', 'Tarsier sanctuary'], ['10:00 AM', 'Heritage church / countryside'], ['12:00 PM', 'Lunch (own account)'], ['01:30 PM', 'Optional Chocolate Hills or river add-on'], ['05:00 PM', 'Return to hotel']],
		],
		['chocolate-hills-loboc-cruise', 'bohol', 'tour-bohol', 'Chocolate Hills & Loboc Cruise', 'Carmen & Loboc, Bohol', 'Iconic Landscape',
			'See the Chocolate Hills and cruise the Loboc River with lunch on the water.',
			['/assets/downloaded/dest-bohol.jpg', '/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/moments/moment-6431.jpg', '/assets/downloaded/popular-img1.png'],
			'Bohol’s two icons in one day: the Chocolate Hills viewpoint and a floating lunch cruise on the Loboc River. We time the drive so you are not rushed between Carmen and Loboc.',
			['Chocolate Hills viewpoint', 'Loboc River cruise', 'Lunch on the water (as arranged)', 'Private van between stops', 'Signature Bohol photos', 'Works well for families'],
			[
				['img' => '/assets/downloaded/dest-bohol.jpg', 'title' => 'Chocolate Hills', 'text' => 'Walk the viewpoint and take in the cone karst landscape.'],
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Loboc Cruise', 'text' => 'Float the river with lunch and local music when operating.'],
				['img' => '/assets/downloaded/moments/moment-6431.jpg', 'title' => 'Island Day', 'text' => 'Tarsier or church add-on if your group wants a fuller loop.'],
			],
			[['07:00 AM', 'Hotel pickup'], ['09:00 AM', 'Chocolate Hills'], ['11:30 AM', 'Loboc boarding'], ['12:00 PM', 'River cruise lunch'], ['02:30 PM', 'Optional extra stop'], ['05:00 PM', 'Back to hotel']],
		],
		['panglao-island-tour', 'bohol', 'tour-bohol', 'Panglao Island Tour', 'Panglao, Bohol', 'Beach Day',
			'Beaches, sandbars, and easy island time around Panglao.',
			['/assets/downloaded/popular-img1.png', '/assets/downloaded/dest-bohol.jpg', '/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/moments/moment-6611.jpg'],
			'A lighter Bohol day for guests based in Panglao: beach hops, a cave or church if you like, and no long countryside drive. Ideal after a late arrival or before a morning flight.',
			['Panglao beach time', 'Optional Hinagdanan Cave', 'Short transfers', 'Relaxed photo stops', 'Good for couples', 'Private van on call'],
			[
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'Beach Hops', 'text' => 'Alona and nearby coves at a pace you choose.'],
				['img' => '/assets/downloaded/moments/moment-6611.jpg', 'title' => 'Island Corners', 'text' => 'Cave, church, or viewpoint if you want more than sand.'],
				['img' => '/assets/downloaded/dest-bohol.jpg', 'title' => 'Panglao Easy', 'text' => 'Stay close to your hotel with a private driver.'],
			],
			[['08:30 AM', 'Hotel pickup'], ['09:00 AM', 'First beach or viewpoint'], ['11:00 AM', 'Optional cave'], ['12:30 PM', 'Lunch (own account)'], ['02:30 PM', 'Second beach'], ['04:30 PM', 'Back to hotel']],
		],
		['balicasag-island-hopping', 'bohol', 'tour-bohol', 'Balicasag Island Hopping', 'Balicasag, Bohol', 'Island Hopping',
			'Snorkel the marine sanctuary and hop nearby islands off Panglao.',
			['/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/popular-img1.png', '/assets/downloaded/dest-bohol.jpg', '/assets/downloaded/moments/moment-6611.jpg'],
			'Boat out from Panglao to Balicasag’s sanctuary walls — turtles, drop-offs, and clear water when the sea is kind. Virgin Island sandbar is a common second stop. We arrange the boat and van.',
			['Balicasag marine sanctuary', 'Snorkeling with a local crew', 'Optional Virgin Island sandbar', 'Life vest on the boat', 'Panglao pickup', 'Great for snorkelers'],
			[
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Sanctuary Wall', 'text' => 'Snorkel above the drop-off with a guide in the water.'],
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'Sandbar Stop', 'text' => 'Time on a sandbar when tides and weather allow.'],
				['img' => '/assets/downloaded/dest-bohol.jpg', 'title' => 'Panglao Boat Day', 'text' => 'Short hop from the hotel, long memories in the water.'],
			],
			[['07:30 AM', 'Hotel pickup and boat terminal'], ['08:30 AM', 'Balicasag snorkel'], ['11:00 AM', 'Virgin Island / second stop'], ['12:30 PM', 'Lunch (own account)'], ['02:30 PM', 'Return boat'], ['03:30 PM', 'Hotel drop-off']],
		],
		['hinagdanan-cave-beach', 'bohol', 'tour-bohol', 'Hinagdanan Cave & Beach', 'Panglao, Bohol', 'Cave & Beach',
			'Explore the underground cave pool, then unwind on a nearby beach.',
			['/assets/downloaded/moments/moment-6611.jpg', '/assets/downloaded/dest-bohol.jpg', '/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/popular-img1.png'],
			'Hinagdanan’s skylight cave is a short, striking stop — then we roll to a Panglao beach so the day does not end underground. Easy timing for families and first-time visitors.',
			['Hinagdanan Cave walk-in', 'Beach time after the cave', 'Short Panglao transfers', 'Photo-friendly chambers', 'Family-friendly', 'Private van included'],
			[
				['img' => '/assets/downloaded/moments/moment-6611.jpg', 'title' => 'Cave Pool', 'text' => 'Descend into the limestone chamber and look up at the skylight.'],
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Beach Unwind', 'text' => 'Shake off the cave cool with sand and a swim.'],
				['img' => '/assets/downloaded/dest-bohol.jpg', 'title' => 'Panglao Day', 'text' => 'All stops stay on the island — no long countryside drive.'],
			],
			[['08:30 AM', 'Hotel pickup'], ['09:00 AM', 'Hinagdanan Cave'], ['10:30 AM', 'Beach time'], ['12:30 PM', 'Lunch (own account)'], ['02:30 PM', 'Optional extra beach'], ['04:00 PM', 'Return']],
		],
		['bohol-combined-day-tour', 'bohol', 'tour-bohol', 'Bohol Combined Day Tour', 'Bohol, Philippines', 'Full Day',
			'Countryside highlights plus Panglao in one private full-day tour.',
			['/assets/downloaded/dest-bohol.jpg', '/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/moments/moment-6611.jpg', '/assets/downloaded/moments/moment-6431.jpg'],
			'If you only have one full day on Bohol, this is the loop: tarsiers, Chocolate Hills, a river or church stop, then Panglao beach time. We start early and keep the van yours all day.',
			['Countryside + Panglao in one day', 'Tarsiers and Chocolate Hills', 'Private van the whole day', 'Flexible last beach stop', 'Best for short stays', 'Tell us must-see stops at checkout'],
			[
				['img' => '/assets/downloaded/dest-bohol.jpg', 'title' => 'Hills & Countryside', 'text' => 'Cover the inland icons while you still have energy.'],
				['img' => '/assets/downloaded/moments/moment-6431.jpg', 'title' => 'Tarsiers', 'text' => 'A quiet sanctuary stop on the way to Carmen.'],
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Panglao Finish', 'text' => 'End on the beach so the day does not finish in traffic.'],
			],
			[['06:30 AM', 'Early pickup'], ['08:00 AM', 'Tarsiers'], ['10:00 AM', 'Chocolate Hills'], ['12:00 PM', 'Loboc or lunch stop'], ['03:00 PM', 'Panglao beach'], ['05:30 PM', 'Hotel drop-off']],
		],

		// Siquijor
		['siquijor-island-tour', 'siquijor', 'tour-siquijor', 'Siquijor Island Tour', 'Siquijor, Philippines', 'Island Loop',
			'The classic island loop — waterfalls, churches, and hidden corners with hotel pickup.',
			['/assets/downloaded/dest-siquijor.jpg', '/assets/downloaded/about-siquijor-1.png', '/assets/downloaded/moments/moment-6420.jpg', '/assets/downloaded/moments/moment-6375.jpg'],
			'See Siquijor in a day without rushing the magic: Cambugahay, Lazi, the balete tree, and a beach or cave if you still have light. Private van, island pace, hotel pickup.',
			['Full island loop with a driver', 'Waterfalls and heritage stops', 'Balete tree spring', 'Flexible beach finish', 'Perfect first day on Siquijor', 'Photo time at every stop'],
			[
				['img' => '/assets/downloaded/about-siquijor-1.png', 'title' => 'Cambugahay', 'text' => 'Terraced turquoise falls and optional rope swings.'],
				['img' => '/assets/downloaded/moments/moment-6420.jpg', 'title' => 'Lazi Heritage', 'text' => 'San Isidro Labrador Church and the old convent.'],
				['img' => '/assets/downloaded/moments/moment-6375.jpg', 'title' => 'Island Corners', 'text' => 'Balete tree, beaches, or a cave pool if you want them.'],
			],
			[['08:00 AM', 'Hotel pickup'], ['09:00 AM', 'Cambugahay Falls'], ['11:00 AM', 'Lazi Church & Convent'], ['12:30 PM', 'Lunch (own account)'], ['02:00 PM', 'Balete tree or Salagdoong'], ['05:00 PM', 'Return']],
		],
		['cambugahay-falls', 'siquijor', 'tour-siquijor', 'Cambugahay Falls', 'Lazi, Siquijor', 'Nature & Adventure',
			'Swim the terraced turquoise falls and try the rope swings.',
			['/assets/downloaded/about-siquijor-1.png', '/assets/downloaded/dest-siquijor.jpg', '/assets/downloaded/moments/moment-6611.jpg', '/assets/downloaded/moments/moment-6525.jpg'],
			'Cambugahay is Siquijor’s crowd-pleaser: three main basins, clear water, and rope swings if you want them. We pick you up, wait while you swim, and continue to another stop if you like.',
			['Terraced turquoise pools', 'Optional rope swings', 'Short walk from the road', 'Great for families', 'Pair with Lazi or Salagdoong', 'Private van wait time included'],
			[
				['img' => '/assets/downloaded/about-siquijor-1.png', 'title' => 'The Basins', 'text' => 'Swim the famous terraces and pick a pool that fits your group.'],
				['img' => '/assets/downloaded/moments/moment-6611.jpg', 'title' => 'Rope Swings', 'text' => 'Optional — skip them if you just want a calm swim.'],
				['img' => '/assets/downloaded/dest-siquijor.jpg', 'title' => 'Lazi Side', 'text' => 'Easy to add church, convent, or a beach after.'],
			],
			[['08:30 AM', 'Hotel pickup'], ['09:30 AM', 'Cambugahay swim time'], ['12:00 PM', 'Lunch nearby'], ['01:30 PM', 'Optional extra stop'], ['04:00 PM', 'Hotel drop-off']],
		],
		['enchanted-balete-tree', 'siquijor', 'tour-siquijor', 'Enchanted Balete Tree', 'Lazi, Siquijor', 'Heritage Stop',
			'Visit the centuries-old balete tree and its natural spring pool.',
			['/assets/downloaded/moments/moment-6375.jpg', '/assets/downloaded/dest-siquijor.jpg', '/assets/downloaded/about-siquijor-1.png', '/assets/downloaded/moments/moment-6420.jpg'],
			'The old balete and its spring pool are a short, storied stop on the Lazi side. Come for the tree, the fish spa, and the folklore — then continue to falls or church with the same van.',
			['Centuries-old balete tree', 'Natural spring pool', 'Easy roadside stop', 'Pairs with Cambugahay', 'Photo and folklore stop', 'Private van included'],
			[
				['img' => '/assets/downloaded/moments/moment-6375.jpg', 'title' => 'The Balete', 'text' => 'Stand under the canopy and see why the island tells stories here.'],
				['img' => '/assets/downloaded/about-siquijor-1.png', 'title' => 'Spring Pool', 'text' => 'Optional foot spa in the natural spring.'],
				['img' => '/assets/downloaded/moments/moment-6420.jpg', 'title' => 'Lazi Combo', 'text' => 'Church and falls are minutes away in the same loop.'],
			],
			[['08:30 AM', 'Pickup'], ['09:30 AM', 'Balete tree'], ['11:00 AM', 'Cambugahay or Lazi Church'], ['12:30 PM', 'Lunch'], ['03:00 PM', 'Beach or return']],
		],
		['lazi-church-convent', 'siquijor', 'tour-siquijor', 'Lazi Church & Convent', 'Lazi, Siquijor', 'Pilgrimage & Heritage',
			'San Isidro Labrador Church and one of the oldest convents in the Philippines.',
			['/assets/downloaded/moments/moment-6420.jpg', '/assets/downloaded/dest-siquijor.jpg', '/assets/downloaded/moments/moment-6431.jpg', '/assets/downloaded/about-siquijor-1.png'],
			'Lazi’s coral-stone church and vast convent are a quiet counterpoint to the waterfalls. Dress respectfully, take your time, then swim at Cambugahay on the way back.',
			['San Isidro Labrador Church', 'Historic convent', 'Coral-stone architecture', 'Pilgrimage-friendly pace', 'Combine with Cambugahay', 'Private van from your hotel'],
			[
				['img' => '/assets/downloaded/moments/moment-6420.jpg', 'title' => 'The Church', 'text' => 'Step into one of Siquijor’s most important heritage sites.'],
				['img' => '/assets/downloaded/moments/moment-6431.jpg', 'title' => 'The Convent', 'text' => 'Walk one of the oldest and largest convents in the country.'],
				['img' => '/assets/downloaded/about-siquijor-1.png', 'title' => 'Falls After', 'text' => 'Cambugahay is right there if you want water after stone.'],
			],
			[['08:30 AM', 'Pickup'], ['10:00 AM', 'Lazi Church & Convent'], ['12:00 PM', 'Lunch in Lazi'], ['01:30 PM', 'Cambugahay or balete'], ['04:30 PM', 'Return']],
		],
		['cave-swimming-adventure', 'siquijor', 'tour-siquijor', 'Cave Swimming Adventure', 'Siquijor, Philippines', 'Cave Adventure',
			'Cool off in Siquijor cave pools on a private day tour.',
			['/assets/downloaded/moments/moment-6611.jpg', '/assets/downloaded/dest-siquijor.jpg', '/assets/downloaded/about-siquijor-1.png', '/assets/downloaded/moments/moment-6525.jpg'],
			'Cantabon and other cave pools are cooler, quieter alternatives to the main falls. We match the cave to your group’s comfort with stairs and lighting, then add a beach if you want sun after.',
			['Guided cave pool swim', 'Local cave briefing', 'Private van between sites', 'Better for heat-of-the-day', 'Not a technical spelunk', 'Tell us if anyone dislikes tight spaces'],
			[
				['img' => '/assets/downloaded/moments/moment-6611.jpg', 'title' => 'Cave Pool', 'text' => 'Swim in cool freshwater under the limestone.'],
				['img' => '/assets/downloaded/about-siquijor-1.png', 'title' => 'Island Mix', 'text' => 'Add Cambugahay or a beach so the day has contrast.'],
				['img' => '/assets/downloaded/dest-siquijor.jpg', 'title' => 'Private Pace', 'text' => 'Your van waits — no joining a large walk-in group unless you want to.'],
			],
			[['08:30 AM', 'Pickup'], ['09:30 AM', 'Cave briefing and swim'], ['12:00 PM', 'Lunch'], ['01:30 PM', 'Falls or beach'], ['04:30 PM', 'Return']],
		],
		['salagdoong-beach-day', 'siquijor', 'tour-siquijor', 'Salagdoong Beach Day', 'Maria, Siquijor', 'Beach Day',
			'Cliff jumping, white sand, and a relaxed beach day on the east coast.',
			['/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/dest-siquijor.jpg', '/assets/downloaded/popular-img1.png', '/assets/downloaded/about-siquijor-1.png'],
			'Salagdoong is the east-coast beach day: pale sand, a diving platform if you want it, and simple shore time. We pick you up, wait, and can still squeeze Cambugahay on the way.',
			['White-sand beach time', 'Optional cliff / platform jump', 'East-coast scenery', 'Easy family day', 'Cottages when available', 'Private van included'],
			[
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Beach Time', 'text' => 'Swim, snorkel the shallows, and take the afternoon slow.'],
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'Platform Jump', 'text' => 'Optional — only if you are comfortable with heights.'],
				['img' => '/assets/downloaded/dest-siquijor.jpg', 'title' => 'East Coast', 'text' => 'A different side of Siquijor from the west-coast hotels.'],
			],
			[['08:30 AM', 'Pickup'], ['10:00 AM', 'Salagdoong Beach'], ['12:30 PM', 'Lunch on site or nearby'], ['03:00 PM', 'Optional Cambugahay'], ['05:00 PM', 'Return']],
		],

		// Dumaguete
		['dumaguete-city-tour', 'dumaguete', 'tour-dumaguete', 'Dumaguete City Tour', 'Dumaguete, Negros Oriental', 'City Tour',
			'Rizal Boulevard, heritage streets, and the university town with a private van.',
			['/assets/downloaded/dest-dumaguete.jpg', '/assets/downloaded/moments/moment-6431.jpg', '/assets/downloaded/moments/moment-6420.jpg', '/assets/downloaded/moments/moment-6375.jpg'],
			'Dumaguete is made for a walking-and-van mix: the boulevard, Silliman, the cathedral, and a quiet café stop. We keep it unhurried so you actually see the city, not just pass it.',
			['Rizal Boulevard stroll', 'Heritage and campus stops', 'Private van between sites', 'Café / merienda time', 'Good arrival-day tour', 'Easy to pair with Valencia'],
			[
				['img' => '/assets/downloaded/dest-dumaguete.jpg', 'title' => 'The Boulevard', 'text' => 'Sea air and the city’s favorite evening stretch — we go by day too.'],
				['img' => '/assets/downloaded/moments/moment-6431.jpg', 'title' => 'Heritage Streets', 'text' => 'Churches, campus edges, and photo corners with a local driver.'],
				['img' => '/assets/downloaded/moments/moment-6375.jpg', 'title' => 'City Easy', 'text' => 'Short hops, no parking stress, back to your hotel when you like.'],
			],
			[['08:30 AM', 'Hotel pickup'], ['09:00 AM', 'Boulevard and nearby heritage'], ['11:00 AM', 'Campus / cathedral stops'], ['12:30 PM', 'Lunch (own account)'], ['02:30 PM', 'Optional market or viewpoint'], ['04:00 PM', 'Return']],
		],
		['apo-island-snorkeling', 'dumaguete', 'tour-dumaguete', 'Apo Island Snorkeling', 'Apo Island, Negros Oriental', 'Marine Adventure',
			'World-class snorkeling with turtles and coral gardens off Dumaguete.',
			['/assets/downloaded/popular-img1.png', '/assets/downloaded/dest-dumaguete.jpg', '/assets/downloaded/moments/moment-6525.jpg', '/assets/downloaded/moments/moment-6611.jpg'],
			'Apo Island is why many people fly to Dumaguete. We arrange the van to the jump-off, boat, sanctuary fees, and a snorkel plan that stays respectful of turtles and coral. Weather and coast guard rules apply.',
			['Apo Island sanctuary snorkel', 'Turtle encounters when they appear', 'Boat from the mainland jump-off', 'Life vest and briefing', 'Dumaguete hotel pickup', 'Marine-park rules enforced'],
			[
				['img' => '/assets/downloaded/popular-img1.png', 'title' => 'Coral Gardens', 'text' => 'Some of the best easy snorkeling in the Visayas.'],
				['img' => '/assets/downloaded/moments/moment-6525.jpg', 'title' => 'Turtle Time', 'text' => 'Keep your distance, no chasing, no touching.'],
				['img' => '/assets/downloaded/dest-dumaguete.jpg', 'title' => 'Island Crossing', 'text' => 'Van plus boat — we time the day around sea conditions.'],
			],
			[['06:00 AM', 'Hotel pickup'], ['07:30 AM', 'Boat to Apo Island'], ['08:30 AM', 'Snorkel sessions'], ['12:00 PM', 'Lunch on the island (own account)'], ['02:00 PM', 'Return boat'], ['04:00 PM', 'Hotel drop-off']],
		],
		['twin-lakes-day-tour', 'dumaguete', 'tour-dumaguete', 'Twin Lakes Day Tour', 'San Jose, Negros Oriental', 'Nature Escape',
			'Balinsasayao and Danao — twin highland lakes above Dumaguete.',
			['/assets/downloaded/dest-dumaguete.jpg', '/assets/downloaded/moments/moment-6375.jpg', '/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/moments/moment-6611.jpg'],
			'Leave the boulevard heat for forest air and two crater lakes. Walk the ridge, boat if operating, and eat a simple highland lunch. A quiet contrast to Apo Island.',
			['Twin crater lakes', 'Highland forest air', 'Viewpoint walks', 'Cooler than the coast', 'Optional boat on the lake', 'Private van from Dumaguete'],
			[
				['img' => '/assets/downloaded/dest-dumaguete.jpg', 'title' => 'The Lakes', 'text' => 'Balinsasayao and Danao sit above the city in a protected landscape.'],
				['img' => '/assets/downloaded/moments/moment-6375.jpg', 'title' => 'Ridge Walk', 'text' => 'Short walks and lookouts — tell us if you want more hiking.'],
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'Highland Day', 'text' => 'Pack a light jacket; it can be cooler than the boulevard.'],
			],
			[['07:30 AM', 'Pickup'], ['09:00 AM', 'Twin Lakes park'], ['12:00 PM', 'Lunch in the highlands'], ['02:00 PM', 'Extra viewpoint or Casaroro add-on'], ['04:30 PM', 'Return']],
		],
		['casaroro-falls', 'dumaguete', 'tour-dumaguete', 'Casaroro Falls', 'Valencia, Negros Oriental', 'Nature & Adventure',
			'Hike to one of Negros Oriental’s most dramatic waterfalls.',
			['/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/dest-dumaguete.jpg', '/assets/downloaded/moments/moment-6611.jpg', '/assets/downloaded/moments/moment-6375.jpg'],
			'Casaroro is a real hike: stairs, river, and a tall fall at the end. We provide the van and wait time; you bring shoes with grip. Not a stroll — say so if anyone in the group prefers Twin Lakes instead.',
			['Dramatic waterfall hike', 'Valencia highlands setting', 'Private van to the trailhead', 'Swim at the basin if safe', 'Best in drier months', 'Pair with Twin Lakes on a long day'],
			[
				['img' => '/assets/downloaded/dest-cebu.jpg', 'title' => 'The Falls', 'text' => 'A tall drop in a forest amphitheater — worth the stairs.'],
				['img' => '/assets/downloaded/moments/moment-6611.jpg', 'title' => 'The Hike', 'text' => 'Expect steps, some wet rock, and a slower pace down.'],
				['img' => '/assets/downloaded/dest-dumaguete.jpg', 'title' => 'Valencia', 'text' => 'Close to Dumaguete, worlds away from the boulevard.'],
			],
			[['07:30 AM', 'Pickup'], ['08:30 AM', 'Trailhead'], ['09:00 AM', 'Hike in'], ['11:00 AM', 'Falls time'], ['12:30 PM', 'Hike out and lunch'], ['03:30 PM', 'Return or Twin Lakes add-on']],
		],
		['valencia-highlands', 'dumaguete', 'tour-dumaguete', 'Valencia Highlands', 'Valencia, Negros Oriental', 'Highlands',
			'Forest air, views, and a quieter highland day just outside the city.',
			['/assets/downloaded/moments/moment-6375.jpg', '/assets/downloaded/dest-dumaguete.jpg', '/assets/downloaded/dest-cebu.jpg', '/assets/downloaded/moments/moment-6435.jpg'],
			'A gentler highland day than Casaroro: viewpoints, forest roads, a café or garden stop, and time to breathe. Good for families who want nature without a hard hike.',
			['Highland viewpoints', 'Cooler forest roads', 'Café or garden stop', 'Light walking only', 'Close to Dumaguete', 'Easy half or full day'],
			[
				['img' => '/assets/downloaded/moments/moment-6375.jpg', 'title' => 'Forest Air', 'text' => 'Leave the city heat for Valencia’s ridges.'],
				['img' => '/assets/downloaded/moments/moment-6435.jpg', 'title' => 'Lookouts', 'text' => 'Photo stops over the coast and inland hills.'],
				['img' => '/assets/downloaded/dest-dumaguete.jpg', 'title' => 'Easy Nature', 'text' => 'No mandatory long hike — add Casaroro only if you ask.'],
			],
			[['08:00 AM', 'Pickup'], ['09:00 AM', 'Highland viewpoints'], ['11:30 AM', 'Café / garden'], ['01:30 PM', 'Optional extra lookout'], ['04:00 PM', 'Return']],
		],
		['sidlakang-negros-experience', 'dumaguete', 'tour-dumaguete', 'Sidlakang Negros Experience', 'Dumaguete, Negros Oriental', 'Culture & Food',
			'Local crafts, food, and culture around Dumaguete with a driver-guide.',
			['/assets/downloaded/dest-dumaguete.jpg', '/assets/downloaded/moments/moment-6431.jpg', '/assets/downloaded/moments/moment-6420.jpg', '/assets/downloaded/moments/moment-6435.jpg'],
			'A culture-forward Dumaguete day: crafts, pasalubong, a market or center stop, and the food the city is known for. Tell us if you want vegetarian, spicy, or souvenir time and we shape the loop.',
			['Local crafts and pasalubong', 'Food stops you actually want', 'Heritage streets', 'Private van between bites', 'Good rainy-day plan', 'Pairs with a city heritage morning'],
			[
				['img' => '/assets/downloaded/dest-dumaguete.jpg', 'title' => 'City Flavor', 'text' => 'Silvanas, boulevard merienda, and whatever is in season.'],
				['img' => '/assets/downloaded/moments/moment-6431.jpg', 'title' => 'Craft Stops', 'text' => 'Bring space in the bag — we know the reliable shops.'],
				['img' => '/assets/downloaded/moments/moment-6420.jpg', 'title' => 'Heritage Mix', 'text' => 'Church and campus corners between meals.'],
			],
			[['09:00 AM', 'Pickup'], ['09:30 AM', 'Heritage / crafts'], ['12:00 PM', 'Lunch highlight'], ['02:00 PM', 'Pasalubong and café'], ['04:30 PM', 'Return']],
		],
	];

	$tours = [];
	$sort = 0;
	foreach ($raw as $row) {
		$sort += 10;
		[$slug, $island, $product, $name, $place, $badge, $lead, $images, $overview, $highlights, $expect, $itinerary] = $row;
		$extra = (isset($row[12]) && is_array($row[12])) ? $row[12] : [];
		$base = ke_tour_defaults($island, $product);
		$tours[$slug] = array_merge($base, [
			'slug' => $slug,
			'name' => $name,
			'place' => $place,
			'badge' => $badge,
			'lead' => $lead,
			'images' => $images,
			'overview' => $overview,
			'highlights' => $highlights,
			'expect' => $expect,
			'itinerary' => $itinerary,
			'sort' => $sort,
		], is_array($extra) && isset($extra['included']) ? $extra : []);
		$tours[$slug] = ke_package_normalize($tours[$slug]);
	}

	return $tours;
}

function ke_search_best_tour(string $island, string $type): ?array
{
	$keywords = [
		'city' => ['city', 'heritage', 'temple', 'culture', 'downtown', 'boulevard', 'sirao', 'leah'],
		'island' => ['island hopping', 'hopping', 'boat', 'pescador', 'mactan island', 'balicasag', 'apo island', 'snorkeling'],
		'countryside' => ['countryside', 'chocolate', 'hills', 'tarsier', 'highland', 'kawasan', 'casaroro', 'twin lakes', 'valencia', 'falls'],
		'beach' => ['beach', 'panglao', 'salagdoong', 'panagsama'],
	];
	if (isset($keywords[$type])) {
		$words = $keywords[$type];
	} else {
		$parts = preg_split('/[^a-z0-9]+/', strtolower($type)) ?: [];
		$words = [];
		foreach ($parts as $word) {
			if (strlen($word) >= 3) {
				$words[] = $word;
			}
		}
	}
	$best = null;
	$bestScore = -1;
	foreach (ke_tours() as $tour) {
		if (isset($tour['active']) && !$tour['active']) {
			continue;
		}
		$tIsland = (string) ($tour['island'] ?? '');
		if ($island !== '' && $tIsland !== $island) {
			continue;
		}
		$blob = strtolower(implode(' ', [
			(string) ($tour['name'] ?? ''),
			(string) ($tour['badge'] ?? ''),
			(string) ($tour['lead'] ?? ''),
			(string) ($tour['overview'] ?? ''),
			(string) ($tour['place'] ?? ''),
		]));
		$score = 0;
		if ($island !== '' && $tIsland === $island) {
			$score += 40;
		}
		foreach ($words as $word) {
			if ($word !== '' && strpos($blob, $word) !== false) {
				$score += 25;
			}
		}
		$score += max(0, 15 - (int) floor(((int) ($tour['sort'] ?? 0)) / 20));
		if ($score > $bestScore) {
			$bestScore = $score;
			$best = $tour;
		}
	}
	return $best;
}

function ke_search_best_url(string $island, string $type, string $tab = '', string $vehicle = '', string $date = ''): string
{
	$islands = ['cebu', 'bohol', 'siquijor', 'dumaguete'];
	$island = strtolower(trim($island));
	$type = strtolower(trim($type));
	$tab = strtolower(trim($tab));
	$vehicle = strtolower(trim($vehicle));
	if ($island === '0') {
		$island = '';
	}
	if (!in_array($island, $islands, true)) {
		$island = '';
	}
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		$date = '';
	}

	$vanTypes = ['van', 'suv', 'sedan', 'coaster', 'grandia', 'commuter', 'innova', 'fortuner', 'vios'];
	$isVan = $tab === 'hotel' || $tab === 'van' || in_array($type, $vanTypes, true) || $vehicle !== '';
	$isTransfer = $type === 'transfer';
	if ($isVan || $isTransfer) {
		$q = [];
		if ($island !== '') {
			$q['island'] = $island;
		}
		if ($vehicle !== '') {
			$q['vehicle'] = $vehicle;
		} elseif (in_array($type, $vanTypes, true)) {
			$q['vehicle'] = $type;
		}
		if ($date !== '') {
			$q['date'] = $date;
		}
		if ($isTransfer) {
			$q['type'] = 'transfer';
		}
		return '/service.html' . ($q ? ('?' . http_build_query($q)) : '');
	}

	$q = [];
	if ($date !== '') {
		$q['date'] = $date;
	}
	if ($tab === 'visa' || $tab === 'private') {
		$q['private'] = '1';
	}

	$best = null;
	if ($island !== '' || $type !== '') {
		$best = ke_search_best_tour($island, $type);
	}
	if (is_array($best) && ($best['slug'] ?? '') !== '') {
		$url = '/tours/' . $best['slug'];
		return $q ? ($url . '?' . http_build_query($q)) : $url;
	}
	if ($island !== '') {
		$url = '/' . $island . '-tour';
		return $q ? ($url . '?' . http_build_query($q)) : $url;
	}
	return '/tours-and-packages.php' . ($q ? ('?' . http_build_query($q)) : '');
}
