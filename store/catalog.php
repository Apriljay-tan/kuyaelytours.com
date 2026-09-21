<?php
declare(strict_types=1);

function store_catalog(): array
{
	$items = store_read_json('catalog');
	return array_values(array_filter($items, static function ($item) {
		return !empty($item['active']);
	}));
}

function store_product(string $id): ?array
{
	foreach (store_read_json('catalog') as $item) {
		if (($item['id'] ?? '') === $id) {
			return $item;
		}
	}
	return null;
}

function store_line_total(array $item, ?array $product = null): int
{
	if (isset($item['line_total']) && $item['line_total'] !== '') {
		return max(0, (int) $item['line_total']);
	}
	$product = $product ?: store_product((string) ($item['product_id'] ?? ''));
	$price = (int) ($product['price_from'] ?? 0);
	$qty = max(1, (int) ($item['guests'] ?? $item['qty'] ?? 1));
	if (($product['type'] ?? '') === 'vehicle' || ($product['type'] ?? '') === 'transfer') {
		return $price;
	}
	return $price * $qty;
}
