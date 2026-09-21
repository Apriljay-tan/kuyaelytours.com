<?php
declare(strict_types=1);

require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/tours-data.php';

$island = (string) ($_GET['island'] ?? '');
$type = (string) ($_GET['type'] ?? '');
$tab = (string) ($_GET['tab'] ?? '');
$vehicle = (string) ($_GET['vehicle'] ?? '');
$date = (string) ($_GET['date'] ?? '');

store_redirect(ke_search_best_url($island, $type, $tab, $vehicle, $date));
