<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/tours-data.php';

$island = (string) ($_GET['island'] ?? '');
$type = (string) ($_GET['type'] ?? '');
$tab = (string) ($_GET['tab'] ?? '');
$vehicle = (string) ($_GET['vehicle'] ?? '');
$date = (string) ($_GET['date'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
if ($q !== '') {
	$ql = strtolower($q);
	if ($island === '') {
		foreach (['dumaguete', 'siquijor', 'bohol', 'cebu'] as $isl) {
			if (str_contains($ql, $isl)) {
				$island = $isl;
				break;
			}
		}
	}
	if ($type === '') {
		if (preg_match('/airport|\btransfer\b/', $ql)) {
			$type = 'transfer';
		} elseif (preg_match('/\b(van|suv|sedan|coaster|grandia|rental|car rental)\b/', $ql)) {
			$tab = $tab !== '' ? $tab : 'van';
		} elseif (preg_match('/\b(city|heritage|culture)\b/', $ql)) {
			$type = 'city';
		} elseif (preg_match('/island hopping|\bhopping\b|\bboat\b/', $ql)) {
			$type = 'island';
		} elseif (preg_match('/countryside|chocolate|tarsier|highland|falls/', $ql)) {
			$type = 'countryside';
		} elseif (preg_match('/\bbeach\b|panglao|salagdoong/', $ql)) {
			$type = 'beach';
		} else {
			$type = $q;
		}
	}
}

store_redirect(ke_search_best_url($island, $type, $tab, $vehicle, $date));
