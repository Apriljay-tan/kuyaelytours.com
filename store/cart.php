<?php
declare(strict_types=1);

function store_cart(): array
{
	$cart = $_SESSION['cart'] ?? [];
	return is_array($cart) ? $cart : [];
}

function store_cart_count(): int
{
	return count(store_cart());
}

function store_cart_save(array $cart): void
{
	$_SESSION['cart'] = array_values($cart);
	if (function_exists('store_persist_user_cart')) {
		store_persist_user_cart();
	}
}

function store_cart_add(array $item): string
{
	$item['id'] = $item['id'] ?? store_id();
	$cart = store_cart();
	$cart[] = $item;
	store_cart_save($cart);
	return (string) $item['id'];
}

function store_cart_remove(string $id): void
{
	$cart = array_values(array_filter(store_cart(), static function ($item) use ($id) {
		return ($item['id'] ?? '') !== $id;
	}));
	store_cart_save($cart);
}

function store_cart_clear(): void
{
	store_cart_save([]);
	store_promo_clear();
}

function store_cart_total(): int
{
	$total = 0;
	foreach (store_cart() as $item) {
		$total += store_line_total($item);
	}
	return $total;
}

function store_promos(): array
{
	$rows = store_read_json('promos');
	return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
}

function store_promo_save_all(array $rows): bool
{
	return store_write_json('promos', array_values($rows));
}

function store_promo_find(string $code): ?array
{
	$code = strtoupper(trim($code));
	if ($code === '') {
		return null;
	}
	foreach (store_promos() as $row) {
		if (strtoupper((string) ($row['code'] ?? '')) === $code && !empty($row['active'])) {
			return $row;
		}
	}
	return null;
}

function store_promo(): ?array
{
	$promo = $_SESSION['promo'] ?? null;
	return is_array($promo) ? $promo : null;
}

function store_promo_clear(): void
{
	unset($_SESSION['promo']);
}

function store_promo_discount(int $subtotal): int
{
	$promo = store_promo();
	if (!$promo || $subtotal <= 0) {
		return 0;
	}
	$value = max(0, (int) ($promo['value'] ?? 0));
	if (($promo['type'] ?? '') === 'percent') {
		return (int) floor($subtotal * min(100, $value) / 100);
	}
	return min($subtotal, $value);
}

function store_cart_payable(): int
{
	return max(0, store_cart_total() - store_promo_discount(store_cart_total()));
}

function store_promo_apply(string $code): string
{
	$code = strtoupper(trim($code));
	if ($code === '') {
		store_promo_clear();
		return 'cleared';
	}
	$row = store_promo_find($code);
	if (!$row) {
		return 'invalid';
	}
	$_SESSION['promo'] = [
		'code' => strtoupper((string) $row['code']),
		'type' => (($row['type'] ?? '') === 'percent') ? 'percent' : 'amount',
		'value' => max(0, (int) ($row['value'] ?? 0)),
	];
	return 'ok';
}

function store_booking_grant(string $id): string
{
	$token = bin2hex(random_bytes(16));
	if (!isset($_SESSION['booking_access']) || !is_array($_SESSION['booking_access'])) {
		$_SESSION['booking_access'] = [];
	}
	$_SESSION['booking_access'][$id] = $token;
	return $token;
}

function store_booking_granted(string $id): bool
{
	$saved = $_SESSION['booking_access'][$id] ?? '';
	return is_string($saved) && $saved !== '';
}
