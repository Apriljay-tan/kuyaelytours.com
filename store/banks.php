<?php
declare(strict_types=1);

function store_banks_file(): string
{
	return dirname(STORE_ROOT) . '/assets/data/banks.json';
}

function store_banks_bundle(): array
{
	$file = store_banks_file();
	$empty = [
		'note' => 'Transfer the amount on this page, then send a photo of the receipt to info@kuyaelytours.com or +63 920 985 1802. The booking is confirmed after the payment shows in the account.',
		'banks' => [],
	];
	if (!is_readable($file)) {
		return $empty;
	}
	$data = json_decode((string) file_get_contents($file), true);
	if (!is_array($data)) {
		return $empty;
	}
	$banks = [];
	foreach ((array) ($data['banks'] ?? []) as $row) {
		if (!is_array($row)) {
			continue;
		}
		$name = trim((string) ($row['name'] ?? ''));
		$number = trim((string) ($row['account_number'] ?? ''));
		if ($name === '' || $number === '') {
			continue;
		}
		$logo = trim((string) ($row['logo'] ?? ''));
		if ($logo !== '' && (!str_starts_with($logo, '/assets/') || str_contains($logo, '..'))) {
			$logo = '';
		}
		$banks[] = [
			'name' => $name,
			'account_name' => trim((string) ($row['account_name'] ?? '')),
			'account_number' => $number,
			'logo' => $logo,
		];
	}
	$note = trim((string) ($data['note'] ?? ''));
	if ($note === '') {
		$note = $empty['note'];
	}
	return ['note' => $note, 'banks' => $banks];
}

function store_banks_save(array $bundle): bool
{
	$dir = dirname(store_banks_file());
	if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
		return false;
	}
	$json = json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	if ($json === false) {
		return false;
	}
	return file_put_contents(store_banks_file(), $json . "\n", LOCK_EX) !== false;
}
