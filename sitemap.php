<?php
declare(strict_types=1);
require __DIR__ . '/store/bootstrap.php';
require __DIR__ . '/store/tours-data.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$pages = [
	['https://kuyaelytours.com/', 'weekly', '1.0'],
	['https://kuyaelytours.com/about', 'monthly', '0.8'],
	['https://kuyaelytours.com/our-story', 'monthly', '0.6'],
	['https://kuyaelytours.com/tours-and-packages.php', 'weekly', '0.9'],
	['https://kuyaelytours.com/cebu-tour', 'weekly', '0.9'],
	['https://kuyaelytours.com/bohol-tour', 'weekly', '0.9'],
	['https://kuyaelytours.com/siquijor-tour', 'weekly', '0.9'],
	['https://kuyaelytours.com/dumaguete-tour', 'weekly', '0.9'],
	['https://kuyaelytours.com/service', 'monthly', '0.8'],
	['https://kuyaelytours.com/galary', 'monthly', '0.5'],
	['https://kuyaelytours.com/permits', 'monthly', '0.5'],
	['https://kuyaelytours.com/contact', 'monthly', '0.7'],
	['https://kuyaelytours.com/privacy-policy', 'yearly', '0.3'],
	['https://kuyaelytours.com/terms', 'yearly', '0.3'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $page) {
	echo "\t<url><loc>" . htmlspecialchars($page[0], ENT_XML1) . '</loc><changefreq>' . $page[1] . '</changefreq><priority>' . $page[2] . "</priority></url>\n";
}
foreach (ke_tours() as $tour) {
	if (!is_array($tour) || (isset($tour['active']) && !$tour['active'])) {
		continue;
	}
	$slug = strtolower((string) ($tour['slug'] ?? ''));
	if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
		continue;
	}
	$loc = 'https://kuyaelytours.com/tours/' . $slug;
	echo "\t<url><loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>\n";
}
echo "</urlset>\n";
