<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
store_require_staff();

$q = trim((string) ($_GET['q'] ?? ''));
if (strlen($q) > 80) {
	$q = substr($q, 0, 80);
}
$needle = strtolower($q);
$rows = [];
foreach (store_bookings() as $booking) {
	if (!is_array($booking)) {
		continue;
	}
	$first = store_desk_first_item($booking);
	$blob = strtolower(implode(' ', [
		(string) ($booking['guest_name'] ?? ''),
		(string) ($booking['email'] ?? ''),
		(string) ($booking['phone'] ?? ''),
		(string) ($booking['id'] ?? ''),
		(string) ($first['label'] ?? ''),
		(string) ($first['date'] ?? ''),
	]));
	if ($needle !== '' && !str_contains($blob, $needle)) {
		continue;
	}
	$rows[] = $booking;
	if (count($rows) >= 80) {
		break;
	}
}

$body = '<p class="ke-desk-lead">Add a client who booked by message or phone. Send them the login, then put their quotation, confirmation, receipt, and driver on the trip.</p>';
$body .= '<div class="ke-desk-tools"><form method="get" class="ke-desk-search"><label>Search<input type="search" name="q" value="'
	. store_h($q) . '" placeholder="Name, email, phone, or tour"></label><button class="ke-desk-btn" type="submit">Search</button></form>'
	. '<a class="ke-desk-btn" href="/desk/add.php">Add client</a></div>';

if (!$rows) {
	$body .= '<div class="ke-desk-empty"><strong>' . ($q === '' ? 'No trips on file yet' : 'No trips match that search') . '</strong>'
		. '<p>Create a profile, then attach the trip papers.</p>'
		. '<a class="ke-desk-btn" href="/desk/add.php">Add client</a></div>';
	store_desk_page('Trips', $body);
	exit;
}

$body .= '<div class="ke-desk-scroll"><table class="ke-desk-table"><thead><tr><th>Client</th><th>Trip</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead><tbody>';
foreach ($rows as $booking) {
	$first = store_desk_first_item($booking);
	$date = trim((string) ($first['date'] ?? ''));
	$id = (string) ($booking['id'] ?? '');
	$body .= '<tr><td><strong>' . store_h((string) ($booking['guest_name'] ?? '')) . '</strong><br><span>'
		. store_h((string) ($booking['email'] ?? '')) . '</span></td><td>'
		. store_h(store_desk_trip_label($booking)) . '</td><td>'
		. store_h($date !== '' ? $date : 'Date not set') . '</td><td>'
		. store_h(store_booking_status_label((string) ($booking['status'] ?? ''))) . '</td><td>'
		. store_h(store_money((int) ($booking['total'] ?? 0))) . '</td><td><a href="/desk/trip.php?id='
		. store_h($id) . '">Open</a></td></tr>';
}
$body .= '</tbody></table></div>';
store_desk_page('Trips', $body);
