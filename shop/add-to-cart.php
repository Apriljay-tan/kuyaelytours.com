<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require dirname(__DIR__) . '/store/tours-data.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !store_csrf_ok()) {
	store_redirect('/tours-and-packages.php');
}

$productId = trim((string) ($_POST['product_id'] ?? ''));
$product = store_product($productId);
if (!$product) {
	store_redirect('/tours-and-packages.php');
}

$vehicle = trim((string) ($_POST['vehicle'] ?? ($product['vehicle'] ?? '')));
$date = trim((string) ($_POST['date'] ?? ''));
if ($date === '') {
	$date = trim((string) ($_POST['arrive'] ?? ''));
}
$slugEarly = strtolower(trim((string) ($_POST['package_slug'] ?? '')));
$dateOk = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
if ($slugEarly !== '' && preg_match('/^[a-z0-9-]+$/', $slugEarly) && !$dateOk) {
	store_redirect('/tours/' . $slugEarly . '?need=date');
}
if (!$dateOk) {
	$date = store_today();
}
if (store_is_blocked($vehicle, $date)) {
	store_redirect('/shop/cart.php?err=blocked');
}

$item = [
	'product_id' => $productId,
	'date' => $date,
	'guests' => max(1, (int) ($_POST['guests'] ?? 1)),
	'vehicle' => $vehicle,
	'notes' => trim((string) ($_POST['notes'] ?? '')),
];

$slug = strtolower(trim((string) ($_POST['package_slug'] ?? '')));
if ($slug !== '' && preg_match('/^[a-z0-9-]+$/', $slug)) {
	$tour = ke_tour($slug);
	if ($tour) {
		$qty = [
			'foreign_adult' => max(0, (int) ($_POST['foreign_adult'] ?? 0)),
			'local_adult' => max(0, (int) ($_POST['local_adult'] ?? 0)),
			'foreign_child' => max(0, (int) ($_POST['foreign_child'] ?? 0)),
			'local_child' => max(0, (int) ($_POST['local_child'] ?? 0)),
		];
		if (array_sum($qty) < 1) {
			$qty['foreign_adult'] = max(1, (int) ($_POST['guests'] ?? 1));
		}
		$addonIds = $_POST['addons'] ?? [];
		if (!is_array($addonIds)) {
			$addonIds = $addonIds !== '' ? [$addonIds] : [];
		}
		$quote = ke_quote_booking($tour, $qty, $addonIds);
		$pickup = trim((string) ($_POST['pickup'] ?? ''));
		$bits = [$tour['name']];
		if ($pickup !== '') {
			$bits[] = 'Pickup: ' . $pickup;
		}
		$guestBits = [];
		$labels = ke_package_split_local($tour) ? [
			'foreign_adult' => 'Foreign adult',
			'local_adult' => 'Local adult',
			'foreign_child' => 'Foreign child',
			'local_child' => 'Local child',
		] : [
			'foreign_adult' => 'Adult',
			'local_adult' => 'Adult',
			'foreign_child' => 'Child',
			'local_child' => 'Child',
		];
		foreach ($labels as $key => $label) {
			if ($qty[$key] > 0) {
				$guestBits[] = $qty[$key] . ' ' . $label;
			}
		}
		if ($guestBits) {
			$bits[] = implode(', ', $guestBits);
		}
		foreach ($quote['addons'] as $addon) {
			$bits[] = $addon['name'] . ' (+₱' . number_format((int) $addon['price']) . ')';
		}
		$item['package_slug'] = $slug;
		$item['label'] = (string) $tour['name'];
		$item['cover'] = ke_package_cover($tour);
		$item['pickup'] = $pickup;
		$item['pax'] = $qty;
		$item['addons'] = array_map(static function ($a) {
			return $a['name'];
		}, $quote['addons']);
		$item['guests'] = max(1, (int) $quote['pax']);
		$item['line_total'] = (int) $quote['total'];
		$item['notes'] = implode(' · ', $bits);
	}
}

$pixelName = (string) ($item['label'] ?? ($product['name'] ?? 'Tour'));
$pixelId = (string) ($item['package_slug'] ?? $productId);
$pixelValue = (int) ($item['line_total'] ?? ($product['price_from'] ?? 0));
store_pixel_push('AddToCart', [
	'content_name' => $pixelName,
	'content_ids' => [$pixelId !== '' ? $pixelId : 'tour'],
	'content_type' => 'product',
	'value' => $pixelValue,
	'currency' => 'PHP',
	'num_items' => max(1, (int) ($item['guests'] ?? 1)),
]);
store_cart_add($item);

$next = store_safe_next((string) ($_POST['next'] ?? '/shop/cart.php'), '/shop/cart.php');
store_redirect($next);
