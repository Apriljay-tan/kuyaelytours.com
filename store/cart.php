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
}

function store_cart_total(): int
{
	$total = 0;
	foreach (store_cart() as $item) {
		$total += store_line_total($item);
	}
	return $total;
}
