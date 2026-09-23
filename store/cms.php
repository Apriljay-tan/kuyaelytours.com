<?php
declare(strict_types=1);

function ke_cms_packages_file(): string
{
	return STORE_DATA . '/packages.json';
}

function ke_cms_packages(): array
{
	if (!function_exists('ke_tours')) {
		require_once __DIR__ . '/tours-data.php';
	}
	return array_values(ke_tours());
}

function ke_cms_save_packages(array $packages): bool
{
	$out = [];
	foreach ($packages as $row) {
		if (!is_array($row) || empty($row['slug'])) {
			continue;
		}
		$out[] = ke_package_normalize($row);
	}
	usort($out, static function ($a, $b) {
		return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
	});
	$ok = store_write_json('packages', $out);
	if ($ok && function_exists('ke_tours')) {
		ke_tours(true);
	}
	return $ok;
}

function ke_cms_list_to_text(array $items): string
{
	$lines = [];
	foreach ($items as $item) {
		if (is_string($item) || is_numeric($item)) {
			$line = trim((string) $item);
			if ($line !== '') {
				$lines[] = $line;
			}
		}
	}
	return implode("\n", $lines);
}

function ke_cms_itinerary_to_text(array $rows): string
{
	$lines = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$time = trim((string) ($row[0] ?? ''));
		$text = trim((string) ($row[1] ?? ''));
		$lines[] = $time === '' ? $text : ($time . '|' . $text);
	}
	return implode("\n", $lines);
}

function ke_cms_expect_to_text(array $rows): string
{
	$lines = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$lines[] = trim((string) ($row['title'] ?? '') . '|' . (string) ($row['text'] ?? '') . '|' . (string) ($row['img'] ?? ''), '|');
	}
	return implode("\n", $lines);
}

function ke_cms_faq_to_text(array $rows): string
{
	$lines = [];
	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}
		$q = trim((string) ($row['q'] ?? ''));
		$a = trim((string) ($row['a'] ?? ''));
		if ($q === '' && $a === '') {
			continue;
		}
		$lines[] = $q . '|' . $a;
	}
	return implode("\n", $lines);
}

function ke_cms_tiers_from_post(array $post): array
{
	$mins = $post['tier_min'] ?? [];
	$maxs = $post['tier_max'] ?? [];
	$fa = $post['tier_fa'] ?? [];
	$la = $post['tier_la'] ?? [];
	$fc = $post['tier_fc'] ?? [];
	$lc = $post['tier_lc'] ?? [];
	$rows = [];
	$split = !empty($post['split_local_foreign']);
	$count = max(count((array) $mins), count((array) $maxs), count((array) $fa));
	for ($i = 0; $i < $count; $i++) {
		$adult = (int) ($fa[$i] ?? 0);
		$child = (int) ($fc[$i] ?? 0);
		$rows[] = [
			'min' => (int) ($mins[$i] ?? 0),
			'max' => (int) ($maxs[$i] ?? 0),
			'foreign_adult' => $adult,
			'local_adult' => $split ? (int) ($la[$i] ?? 0) : $adult,
			'foreign_child' => $child,
			'local_child' => $split ? (int) ($lc[$i] ?? 0) : $child,
		];
	}
	return ke_normalize_price_tiers($rows, 0);
}

function ke_cms_addons_from_post(array $post): array
{
	$names = $post['addon_name'] ?? [];
	$prices = $post['addon_price'] ?? [];
	$hints = $post['addon_hint'] ?? [];
	$rows = [];
	$count = max(count((array) $names), count((array) $prices));
	for ($i = 0; $i < $count; $i++) {
		$rows[] = [
			'name' => (string) ($names[$i] ?? ''),
			'price' => (int) ($prices[$i] ?? 0),
			'hint' => (string) ($hints[$i] ?? ''),
		];
	}
	return ke_normalize_addons($rows);
}

function ke_cms_media_list(): array
{
	$root = ke_cms_upload_dir();
	if (!is_dir($root)) {
		return [];
	}
	$out = [];
	foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
		foreach (glob($dir . '/*') ?: [] as $file) {
			if (!is_file($file) || !preg_match('/\.(jpe?g|png|webp|gif|mp4|webm)$/i', $file)) {
				continue;
			}
			$rel = str_replace('\\', '/', substr($file, strlen(STORE_SITE)));
			if ($rel === '' || $rel[0] !== '/') {
				$rel = '/' . ltrim($rel, '/');
			}
			$out[] = [
				'path' => $rel,
				'name' => basename($file),
				'size' => (int) filesize($file),
				'mtime' => (int) filemtime($file),
				'video' => (bool) preg_match('/\.(mp4|webm)$/i', $file),
			];
		}
	}
	usort($out, static function ($a, $b) {
		return ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
	});
	return $out;
}

function ke_cms_slug(string $name): string
{
	$slug = strtolower(trim($name));
	$slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?: '';
	return trim($slug, '-');
}

function ke_cms_save_package(array $pkg): bool
{
	$pkg = ke_package_normalize($pkg);
	if ($pkg['slug'] === '') {
		return false;
	}
	if ($pkg['preview_token'] === '') {
		$pkg['preview_token'] = bin2hex(random_bytes(16));
	}
	$all = ke_cms_packages();
	$found = false;
	foreach ($all as $i => $row) {
		if (($row['slug'] ?? '') === $pkg['slug']) {
			$all[$i] = $pkg;
			$found = true;
			break;
		}
	}
	if (!$found) {
		$all[] = $pkg;
	}
	$ok = ke_cms_save_packages($all);
	if ($ok) {
		ke_cms_sync_catalog($pkg);
	}
	return $ok;
}

function ke_cms_delete_package(string $slug): bool
{
	$slug = strtolower($slug);
	$all = array_values(array_filter(ke_cms_packages(), static function ($row) use ($slug) {
		return ($row['slug'] ?? '') !== $slug;
	}));
	$ok = ke_cms_save_packages($all);
	if ($ok) {
		$catalog = store_read_json('catalog');
		$catalog = array_values(array_filter($catalog, static function ($item) use ($slug) {
			return ($item['id'] ?? '') !== ('pkg-' . $slug);
		}));
		store_write_json('catalog', $catalog);
	}
	return $ok;
}

function ke_cms_sync_catalog(array $pkg): void
{
	$id = 'pkg-' . $pkg['slug'];
	$islandPrice = 0;
	$island = store_product((string) ($pkg['product'] ?? 'tour-cebu'));
	if ($island) {
		$islandPrice = (int) ($island['price_from'] ?? 0);
	}
	$price = function_exists('ke_package_price') ? ke_package_price($pkg) : (int) ($pkg['price_from'] ?? 0);
	if ($price < 1) {
		$price = $islandPrice;
	}
	$image = ke_package_cover($pkg, (string) ($island['image'] ?? '/assets/downloaded/dest-cebu.jpg'));
	$item = [
		'id' => $id,
		'type' => 'tour',
		'name' => (string) $pkg['name'],
		'page' => '/tours/' . $pkg['slug'],
		'image' => $image,
		'price_from' => $price,
		'unit' => 'per guest',
		'description' => (string) ($pkg['lead'] ?? ''),
		'active' => !empty($pkg['active']),
	];
	$catalog = store_read_json('catalog');
	$found = false;
	foreach ($catalog as $i => $row) {
		if (($row['id'] ?? '') === $id) {
			$catalog[$i] = array_merge($row, $item);
			$found = true;
			break;
		}
	}
	if (!$found) {
		$catalog[] = $item;
	}
	store_write_json('catalog', $catalog);
}

function ke_cms_lines_to_list(string $text): array
{
	$out = [];
	foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
		$line = trim($line);
		if ($line !== '') {
			$out[] = $line;
		}
	}
	return $out;
}

function ke_cms_itinerary_from_text(string $text): array
{
	$out = [];
	foreach (ke_cms_lines_to_list($text) as $line) {
		$parts = array_map('trim', explode('|', $line, 2));
		if (count($parts) === 1) {
			$out[] = ['', $parts[0]];
		} else {
			$out[] = [$parts[0], $parts[1]];
		}
	}
	return $out;
}

function ke_cms_expect_from_text(string $text): array
{
	$out = [];
	foreach (ke_cms_lines_to_list($text) as $line) {
		$parts = array_map('trim', explode('|', $line, 3));
		$out[] = [
			'title' => $parts[0] ?? '',
			'text' => $parts[1] ?? '',
			'img' => $parts[2] ?? '',
		];
	}
	return $out;
}

function ke_cms_faq_from_text(string $text): array
{
	$out = [];
	foreach (ke_cms_lines_to_list($text) as $line) {
		$parts = array_map('trim', explode('|', $line, 2));
		$out[] = ['q' => $parts[0] ?? '', 'a' => $parts[1] ?? ''];
	}
	return $out;
}

function ke_cms_expect_from_post(array $post, array $files = []): array
{
	$titles = (array) ($post['expect_title'] ?? []);
	$texts = (array) ($post['expect_text'] ?? []);
	$imgs = (array) ($post['expect_img'] ?? []);
	$count = max(count($titles), count($texts), count($imgs));
	$out = [];
	for ($i = 0; $i < $count; $i++) {
		$img = trim((string) ($imgs[$i] ?? ''));
		if (!empty($files['expect_file']['name'][$i])) {
			$file = [
				'name' => $files['expect_file']['name'][$i] ?? '',
				'type' => $files['expect_file']['type'][$i] ?? '',
				'tmp_name' => $files['expect_file']['tmp_name'][$i] ?? '',
				'error' => $files['expect_file']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
				'size' => $files['expect_file']['size'][$i] ?? 0,
			];
			$up = ke_cms_upload($file);
			if (!empty($up['ok']) && empty($up['video']) && isset($up['path'])) {
				$img = (string) $up['path'];
			}
		}
		$title = trim((string) ($titles[$i] ?? ''));
		$text = trim((string) ($texts[$i] ?? ''));
		if ($title === '' && $text === '' && $img === '') {
			continue;
		}
		$out[] = ['title' => $title, 'text' => $text, 'img' => $img];
	}
	return $out;
}

function ke_cms_faq_from_post(array $post): array
{
	$qs = (array) ($post['faq_q'] ?? []);
	$as = (array) ($post['faq_a'] ?? []);
	$count = max(count($qs), count($as));
	$out = [];
	for ($i = 0; $i < $count; $i++) {
		$q = trim((string) ($qs[$i] ?? ''));
		$a = trim((string) ($as[$i] ?? ''));
		if ($q === '' && $a === '') {
			continue;
		}
		$out[] = ['q' => $q, 'a' => $a];
	}
	return $out;
}

function ke_cms_upload_dir(): string
{
	return STORE_SITE . '/assets/uploads';
}

function ke_cms_upload(array $file): array
{
	if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
		return ['ok' => false, 'error' => 'No file selected.'];
	}
	if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
		return ['ok' => false, 'error' => 'Upload failed.'];
	}
	$size = (int) ($file['size'] ?? 0);
	if ($size < 1 || $size > 20 * 1024 * 1024) {
		return ['ok' => false, 'error' => 'File must be under 20 MB.'];
	}
	$tmp = (string) ($file['tmp_name'] ?? '');
	if ($tmp === '' || !is_uploaded_file($tmp)) {
		return ['ok' => false, 'error' => 'Invalid upload.'];
	}
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime = (string) $finfo->file($tmp);
	$allow = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/webp' => 'webp',
		'image/gif' => 'gif',
		'video/mp4' => 'mp4',
		'video/webm' => 'webm',
	];
	if (!isset($allow[$mime])) {
		return ['ok' => false, 'error' => 'Use JPG, PNG, WEBP, GIF, MP4, or WEBM.'];
	}
	$year = date('Y');
	$dir = ke_cms_upload_dir() . '/' . $year;
	if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
		return ['ok' => false, 'error' => 'Could not create upload folder.'];
	}
	$name = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allow[$mime];
	$dest = $dir . '/' . $name;
	if (!move_uploaded_file($tmp, $dest)) {
		return ['ok' => false, 'error' => 'Could not save the file.'];
	}
	@chmod($dest, 0644);
	return [
		'ok' => true,
		'path' => '/assets/uploads/' . $year . '/' . $name,
		'mime' => $mime,
		'video' => str_starts_with($mime, 'video/'),
	];
}

function ke_cms_save_catalog_item(array $item): bool
{
	$id = (string) ($item['id'] ?? '');
	if ($id === '') {
		return false;
	}
	$catalog = store_read_json('catalog');
	$found = false;
	foreach ($catalog as $i => $row) {
		if (($row['id'] ?? '') === $id) {
			$catalog[$i] = array_merge($row, $item);
			$found = true;
			break;
		}
	}
	if (!$found) {
		$catalog[] = $item;
	}
	return store_write_json('catalog', $catalog);
}

function ke_island_meta(string $island): array
{
	$map = [
		'cebu' => [
			'label' => 'Cebu',
			'h1' => 'Cebu',
			'crumb' => 'Cebu Tours',
			'product' => 'tour-cebu',
			'tabs' => [['all', 'Cebu City'], ['moalboal', 'Moalboal'], ['oslob', 'Oslob']],
			'promo_script' => 'Cebu',
			'promo_blurb' => 'Islands, waterfalls, heritage sites, and unforgettable encounters — Cebu has it all. Let Kuya Ely take you there.',
			'promo_cta' => 'Explore Cebu',
			'hero' => '/assets/downloaded/dest-cebu.jpg',
		],
		'bohol' => [
			'label' => 'Bohol',
			'h1' => 'Bohol',
			'crumb' => 'Bohol Tours',
			'product' => 'tour-bohol',
			'tabs' => [['all', 'Countryside'], ['panglao', 'Panglao'], ['countryside', 'Loboc']],
			'promo_script' => 'Bohol',
			'promo_blurb' => 'Chocolate Hills, river cruises, tarsiers, and island hopping. Let Kuya Ely take you there.',
			'promo_cta' => 'Explore Bohol',
			'hero' => '/assets/downloaded/dest-bohol.jpg',
		],
		'siquijor' => [
			'label' => 'Siquijor',
			'h1' => 'Siquijor',
			'crumb' => 'Siquijor Tours',
			'product' => 'tour-siquijor',
			'tabs' => [['all', 'Island Tour'], ['falls', 'Waterfalls'], ['lazi', 'Lazi']],
			'promo_script' => 'Siquijor',
			'promo_blurb' => 'Waterfalls, mystic stops, and quiet beaches — Siquijor has it all with Kuya Ely.',
			'promo_cta' => 'Explore Siquijor',
			'hero' => '/assets/downloaded/dest-siquijor.jpg',
		],
		'dumaguete' => [
			'label' => 'Dumaguete',
			'h1' => 'Dumaguete',
			'crumb' => 'Dumaguete Tours',
			'product' => 'tour-dumaguete',
			'tabs' => [['all', 'City'], ['apo', 'Apo Island'], ['valencia', 'Valencia']],
			'promo_script' => 'Dumaguete',
			'promo_blurb' => 'City charm, Apo Island, and highland waterfalls — book your Negros day with Kuya Ely.',
			'promo_cta' => 'Explore Dumaguete',
			'hero' => '/assets/downloaded/dest-dumaguete.jpg',
		],
	];
	return $map[$island] ?? $map['cebu'];
}
