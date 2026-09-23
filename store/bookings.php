<?php
declare(strict_types=1);

function store_booking_status_label(string $status): string
{
	$map = [
		'pending_request' => 'Pending request',
		'awaiting_payment' => 'Awaiting payment',
		'paid' => 'Paid',
		'confirmed' => 'Confirmed',
		'cancelled' => 'Cancelled',
	];
	return $map[$status] ?? $status;
}

function store_create_booking(array $user, array $items, string $status, string $payMethod, string $notes = '', int $discount = 0, string $promoCode = ''): ?array
{
	if (!$items) {
		return null;
	}
	foreach ($items as $item) {
		$vehicle = (string) ($item['vehicle'] ?? '');
		$date = (string) ($item['date'] ?? '');
		if (store_is_blocked($vehicle, $date)) {
			return null;
		}
	}
	$total = 0;
	foreach ($items as $item) {
		$total += store_line_total($item);
	}
	$discount = max(0, min($total, $discount));
	$total -= $discount;
	$promoCode = strtoupper(trim($promoCode));
	if ($promoCode !== '') {
		$notes = trim($notes . "\nPromo " . $promoCode . ($discount > 0 ? ' −' . $discount : ''));
	}
	$booking = [
		'id' => store_id(),
		'user_id' => (string) ($user['id'] ?? ''),
		'status' => $status,
		'pay_method' => $payMethod,
		'total' => $total,
		'guest_name' => $user['name'],
		'email' => $user['email'],
		'phone' => $user['phone'] ?? '',
		'notes' => $notes,
		'items' => $items,
		'created' => store_now(),
	];
	if (!store_save_booking($booking)) {
		return null;
	}
	return $booking;
}

function store_user_bookings(string $userId): array
{
	$userId = trim($userId);
	if ($userId === '') {
		return [];
	}
	$db = store_db();
	if ($db) {
		try {
			$st = $db->prepare('SELECT * FROM ke_bookings WHERE user_id = ? ORDER BY created DESC');
			$st->execute([$userId]);
			$rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
			return array_map('store_decode_booking_row', $rows);
		} catch (Throwable $e) {
			return [];
		}
	}
	return array_values(array_filter(store_bookings(), static function ($row) use ($userId) {
		return ($row['user_id'] ?? '') === $userId;
	}));
}

function store_confirm_booking(array $booking): bool
{
	$booking['status'] = 'confirmed';
	if (!store_update_booking($booking)) {
		return false;
	}
	foreach ($booking['items'] ?? [] as $item) {
		$vehicle = (string) ($item['vehicle'] ?? '');
		$date = (string) ($item['date'] ?? '');
		if ($vehicle !== '' && $date !== '') {
			store_block_vehicle($vehicle, $date, (string) $booking['id']);
		}
	}
	return true;
}

function store_cancel_booking(array $booking): bool
{
	$booking['status'] = 'cancelled';
	store_unblock_booking((string) $booking['id']);
	return store_update_booking($booking);
}

function store_booking_mail(array $booking, bool $toGuest): void
{
	$mailer = STORE_SITE . '/ke-mail.php';
	if (!is_file($mailer)) {
		return;
	}
	require_once $mailer;
	$lines = [
		'Booking ' . $booking['id'],
		'Status: ' . store_booking_status_label((string) $booking['status']),
		'Name: ' . $booking['guest_name'],
		'Email: ' . $booking['email'],
		'Phone: ' . ($booking['phone'] ?: 'Not provided'),
		'Total: ' . store_money((int) $booking['total']),
		'',
	];
	foreach ($booking['items'] ?? [] as $item) {
		$product = store_product((string) ($item['product_id'] ?? ''));
		$lines[] = '- ' . ($product['name'] ?? $item['product_id']) . ' / ' . ($item['date'] ?? 'date TBA') . ' / guests ' . ($item['guests'] ?? '1');
	}
	$body = implode("\r\n", $lines);
	if ($toGuest && function_exists('ke_send_mail') && function_exists('ke_confirm_html')) {
		$details = [
			'name' => $booking['guest_name'],
			'email' => $booking['email'],
			'phone' => $booking['phone'],
			'service' => 'Website booking ' . $booking['id'],
			'date' => (string) (($booking['items'][0]['date'] ?? '') ?: ''),
			'guests' => (string) (($booking['items'][0]['guests'] ?? '') ?: ''),
			'message' => $body,
		];
		ke_send_mail(
			(string) $booking['email'],
			'We received your Kuya Ely Tours booking',
			ke_confirm_text($details),
			KE_MAIL_FROM,
			KE_MAIL_BRAND,
			ke_confirm_html($details),
			true
		);
		return;
	}
	if (function_exists('ke_send_mail')) {
		ke_send_mail(KE_MAIL_TO, 'New website booking ' . $booking['id'], $body, (string) $booking['email']);
	}
}
