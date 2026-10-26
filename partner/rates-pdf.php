<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require __DIR__ . '/chrome.php';

partner_bundle();
$catalog = store_partner_catalog_rates();

function partner_pdf_text(string $text): string
{
	$text = str_replace(['₱', '–', '—', '’', '“', '”', '&'], ['PHP ', '-', '-', "'", '"', '"', 'and'], $text);
	$clean = preg_replace('/[^\x20-\x7E]/', '', $text);
	return is_string($clean) ? $clean : '';
}

function partner_pdf_escape(string $text): string
{
	return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], partner_pdf_text($text));
}

function partner_pdf_build(array $lines): string
{
	$lines = $lines ?: [''];
	$chunks = array_chunk($lines, 52);
	$ids = [];
	$next = 4;
	foreach ($chunks as $ignored) {
		$ids[] = ['page' => $next++, 'stream' => $next++];
	}
	$kids = [];
	foreach ($ids as $id) {
		$kids[] = $id['page'] . ' 0 R';
	}
	$objects = [
		1 => '<< /Type /Catalog /Pages 2 0 R >>',
		2 => '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($ids) . ' >>',
		3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
	];
	foreach ($chunks as $index => $chunk) {
		$stream = "BT /F1 9 Tf 40 800 Td 13 TL\n";
		foreach ($chunk as $line) {
			$stream .= '(' . partner_pdf_escape($line) . ") '\n";
		}
		$stream .= 'ET';
		$objects[$ids[$index]['stream']] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
		$objects[$ids[$index]['page']] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Contents ' . $ids[$index]['stream'] . ' 0 R /Resources << /Font << /F1 3 0 R >> >> >>';
	}
	ksort($objects);
	$pdf = "%PDF-1.4\n";
	$offsets = [];
	$max = 0;
	foreach ($objects as $id => $body) {
		$offsets[$id] = strlen($pdf);
		$max = max($max, (int) $id);
		$pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
	}
	$xref = strlen($pdf);
	$size = $max + 1;
	$pdf .= "xref\n0 $size\n";
	$pdf .= "0000000000 65535 f \n";
	for ($id = 1; $id <= $max; $id++) {
		$pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
	}
	$pdf .= "trailer << /Size $size /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
	return $pdf;
}

$lines = [
	'Kuya Ely Tours  -  Partner rate sheet',
	'Market price is the public rate. Contracted rate is your partner rate, per person.',
	'',
];
if (!$catalog) {
	$lines[] = 'No partner prices have been published yet.';
}
foreach ($catalog as $package) {
	$lines[] = partner_pdf_text((string) $package['name']);
	$meta = trim((string) ($package['island'] ?? ''));
	$duration = trim((string) ($package['duration'] ?? ''));
	if ($duration !== '') {
		$meta .= ($meta !== '' ? '  |  ' : '') . $duration;
	}
	if ($meta !== '') {
		$lines[] = $meta;
	}
	$lines[] = 'Group size          Market         Contracted     Savings';
	foreach ($package['tiers'] as $tier) {
		$min = (int) $tier['min'];
		$max = (int) $tier['max'];
		$pax = $max >= 100 ? ($min . '+ pax') : ($min . '-' . $max . ' pax');
		$lines[] = sprintf(
			'%-18s %-14s %-14s %s',
			$pax,
			'PHP ' . number_format((int) $tier['market']),
			'PHP ' . number_format((int) $tier['rate']),
			'PHP ' . number_format((int) $tier['save']) . ' (' . (int) $tier['pct'] . '%)'
		);
	}
	$lines[] = '';
}

$pdf = partner_pdf_build($lines);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="kuya-ely-partner-rates.pdf"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: no-store');
echo $pdf;
