<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
$staff = store_require_staff();

$id = trim((string) ($_GET['id'] ?? $_POST['id'] ?? ''));
if (!preg_match('/^[a-f0-9]{8,32}$/', $id)) {
	store_desk_flash('That trip was not found.', false);
	store_redirect('/desk/');
}
$booking = store_find_booking($id);
if (!$booking) {
	store_desk_flash('That trip was not found.', false);
	store_redirect('/desk/');
}

$account = store_find_user_id((string) ($booking['user_id'] ?? ''));
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	if (!store_csrf_ok()) {
		$error = 'Please try that again.';
	} else {
		$action = (string) ($_POST['action'] ?? '');
		if ($action === 'remove_file') {
			$file = store_find_booking_file((string) ($_POST['file_id'] ?? ''));
			if ($file && (string) ($file['booking_id'] ?? '') === $id) {
				store_delete_booking_file($file);
				store_desk_flash('File removed.');
			} else {
				store_desk_flash('That file was not found.', false);
			}
			store_redirect('/desk/trip.php?id=' . rawurlencode($id));
		} elseif ($action === 'reset_password') {
			$userId = (string) ($booking['user_id'] ?? '');
			$password = $userId !== '' ? store_desk_set_temp_password($userId) : '';
			if ($password === '') {
				store_desk_flash('Could not set a new password.', false);
			} else {
				store_desk_remember_password($id, $password);
				store_desk_flash('New temporary password is ready to copy.');
			}
			store_redirect('/desk/trip.php?id=' . rawurlencode($id));
		} elseif ($action === 'save') {
			$guest = trim((string) ($_POST['guest_name'] ?? ''));
			$phone = trim((string) ($_POST['phone'] ?? ''));
			$tour = trim((string) ($_POST['tour'] ?? ''));
			$date = trim((string) ($_POST['date'] ?? ''));
			$guestCount = (int) ($_POST['guests'] ?? 1);
			$total = store_desk_pesos((string) ($_POST['total'] ?? ''));
			$status = (string) ($_POST['status'] ?? '');
			if ($guest === '' || strlen($guest) > 140) {
				$error = 'Enter the client name.';
			} elseif (strlen($phone) > 40) {
				$error = 'That phone number is too long.';
			} elseif ($tour === '' || strlen($tour) > 140) {
				$error = 'Enter the tour name.';
			} elseif ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
				$error = 'Use a real travel date.';
			} elseif ($guestCount < 1 || $guestCount > 99) {
				$error = 'Guest count must be from 1 to 99.';
			} elseif ($total < 0) {
				$error = 'Enter the total in pesos, or leave it blank.';
			} elseif (!in_array($status, ['confirmed', 'paid', 'awaiting_payment', 'pending_request', 'cancelled'], true)) {
				$error = 'Choose a status.';
			} else {
				$items = is_array($booking['items'] ?? null) ? $booking['items'] : [];
				if (!isset($items[0]) || !is_array($items[0])) {
					$items = [[]];
				}
				$items[0]['label'] = $tour;
				$items[0]['date'] = $date;
				$items[0]['guests'] = $guestCount;
				$items[0]['line_total'] = $total;
				if (trim((string) ($items[0]['product_id'] ?? '')) === '') {
					$items[0]['cover'] = store_desk_cover($tour);
				}
				$booking['items'] = $items;
				$booking['guest_name'] = $guest;
				$booking['phone'] = $phone;
				$booking['total'] = $total;
				$booking['status'] = $status;
				$booking['driver_name'] = substr(trim((string) ($_POST['driver_name'] ?? '')), 0, 140);
				$booking['driver_phone'] = substr(trim((string) ($_POST['driver_phone'] ?? '')), 0, 40);
				$booking['driver_vehicle'] = substr(trim((string) ($_POST['driver_vehicle'] ?? '')), 0, 140);
				$booking['pickup_time'] = substr(trim((string) ($_POST['pickup_time'] ?? '')), 0, 120);
				if ($status !== 'awaiting_payment' && trim((string) ($booking['pay_method'] ?? '')) === '') {
					$booking['pay_method'] = 'desk';
				}
				if ($account) {
					$account['name'] = $guest;
					$account['phone'] = $phone;
				}
				$saved = store_update_booking($booking);
				if ($saved && $account) {
					store_update_user($account);
				}
				$uploadError = '';
				if ($saved) {
					$staffId = (string) ($staff['id'] ?? '');
					foreach (['quotation', 'confirmation', 'receipt'] as $kind) {
						if (!isset($_FILES[$kind]) || !is_array($_FILES[$kind])) {
							continue;
						}
						$err = store_booking_file_save($booking, $kind, $_FILES[$kind], $staffId);
						if ($err !== '') {
							$uploadError = $err;
						}
					}
				}
				if (!$saved) {
					$error = 'Could not save this trip.';
				} else {
					store_desk_flash($uploadError !== '' ? 'Trip saved. ' . $uploadError : 'Trip saved.', $uploadError === '');
					store_redirect('/desk/trip.php?id=' . rawurlencode($id));
				}
			}
		}
	}
}

$booking = store_find_booking($id) ?: $booking;
$account = store_find_user_id((string) ($booking['user_id'] ?? ''));
$first = store_desk_first_item($booking);
$guest = (string) ($account['name'] ?? ($booking['guest_name'] ?? ''));
$phone = (string) ($account['phone'] ?? ($booking['phone'] ?? ''));
$email = (string) ($account['email'] ?? ($booking['email'] ?? ''));
$tour = (string) ($first['label'] ?? store_desk_trip_label($booking));
$date = (string) ($first['date'] ?? '');
$guests = (string) max(1, (int) ($first['guests'] ?? 1));
$total = (string) (int) ($booking['total'] ?? 0);
$status = (string) ($booking['status'] ?? 'confirmed');
if ($error !== '' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string) ($_POST['action'] ?? '') === 'save') {
	$guest = trim((string) ($_POST['guest_name'] ?? $guest));
	$phone = trim((string) ($_POST['phone'] ?? $phone));
	$tour = trim((string) ($_POST['tour'] ?? $tour));
	$date = trim((string) ($_POST['date'] ?? $date));
	$guests = trim((string) ($_POST['guests'] ?? $guests));
	$total = trim((string) ($_POST['total'] ?? $total));
	$status = (string) ($_POST['status'] ?? $status);
	$booking['driver_name'] = substr(trim((string) ($_POST['driver_name'] ?? '')), 0, 140);
	$booking['driver_phone'] = substr(trim((string) ($_POST['driver_phone'] ?? '')), 0, 40);
	$booking['driver_vehicle'] = substr(trim((string) ($_POST['driver_vehicle'] ?? '')), 0, 140);
	$booking['pickup_time'] = substr(trim((string) ($_POST['pickup_time'] ?? '')), 0, 120);
}
$csrf = store_h(store_csrf_token());
$password = store_desk_saved_password($id);
$statuses = [
	'confirmed' => 'Confirmed',
	'paid' => 'Paid',
	'awaiting_payment' => 'Awaiting payment',
	'pending_request' => 'Pending request',
	'cancelled' => 'Cancelled',
];
$options = '';
foreach ($statuses as $value => $label) {
	$options .= '<option value="' . store_h($value) . '"' . ($status === $value ? ' selected' : '') . '>' . store_h($label) . '</option>';
}
$files = [];
foreach (store_booking_files($id) as $file) {
	$files[(string) ($file['kind'] ?? '')] = $file;
}
$fileRows = '';
foreach (['quotation', 'confirmation', 'receipt'] as $kind) {
	$current = $files[$kind] ?? null;
	$fileRows .= '<div class="ke-desk-file"><div><strong>' . store_h(store_file_kind_label($kind)) . '</strong>';
	if ($current) {
		$fileRows .= '<a href="/account/file.php?id=' . store_h((string) $current['id']) . '">Download current file</a>';
	} else {
		$fileRows .= '<span>No file yet</span>';
	}
	$fileRows .= '</div><label>Replace<input type="file" name="' . store_h($kind) . '" accept=".pdf,.png,.jpg,.jpeg,.webp"></label>';
	if ($current) {
		$fileRows .= '<button class="ke-desk-btn ghost" type="submit" form="ke-remove-' . store_h($kind) . '">Remove</button>';
	}
	$fileRows .= '</div>';
}
$removeForms = '';
foreach (['quotation', 'confirmation', 'receipt'] as $kind) {
	if (empty($files[$kind]['id'])) {
		continue;
	}
	$removeForms .= '<form id="ke-remove-' . store_h($kind) . '" method="post"><input type="hidden" name="csrf" value="' . $csrf . '">'
		. '<input type="hidden" name="id" value="' . store_h($id) . '">'
		. '<input type="hidden" name="action" value="remove_file">'
		. '<input type="hidden" name="file_id" value="' . store_h((string) $files[$kind]['id']) . '"></form>';
}
$others = '';
if ($account) {
	foreach (store_user_bookings((string) $account['id']) as $other) {
		$otherId = (string) ($other['id'] ?? '');
		if ($otherId === '' || $otherId === $id) {
			continue;
		}
		$others .= '<li><a href="/desk/trip.php?id=' . store_h($otherId) . '">' . store_h(store_desk_trip_label($other)) . '</a></li>';
	}
}
$again = '/desk/add.php?name=' . rawurlencode($guest) . '&email=' . rawurlencode($email) . '&phone=' . rawurlencode($phone);
$body = '';
if ($error !== '') {
	$body .= '<p class="ke-desk-err">' . store_h($error) . '</p>';
}
$body .= '<section class="ke-desk-card"><h2>Message for chat</h2>';
if ($password !== '') {
	$body .= '<p class="ke-desk-note">Copy this password now. It stays on this page until you sign out or create another one.</p>';
}
$body .= '<textarea id="ke-login-script" class="ke-desk-script">' . store_h(store_desk_login_message($email, $password)) . '</textarea>'
	. '<p class="ke-desk-actions"><button class="ke-desk-btn" type="button" id="ke-copy-login">Copy message</button></p>'
	. '<form method="post"><input type="hidden" name="csrf" value="' . $csrf . '"><input type="hidden" name="id" value="' . store_h($id) . '">'
	. '<input type="hidden" name="action" value="reset_password"><button class="ke-desk-btn ghost" type="submit">Create a new temporary password</button></form>'
	. '</section>';
$body .= '<form method="post" enctype="multipart/form-data" class="ke-desk-form">'
	. '<input type="hidden" name="csrf" value="' . $csrf . '">'
	. '<input type="hidden" name="id" value="' . store_h($id) . '">'
	. '<input type="hidden" name="action" value="save">'
	. '<section class="ke-desk-card"><h2>Client</h2><div class="ke-desk-grid">'
	. '<label>Name on their account<input name="guest_name" value="' . store_h($guest) . '" required maxlength="140"></label>'
	. '<label>Phone<input name="phone" value="' . store_h($phone) . '" maxlength="40"></label>'
	. '<label>Email<input value="' . store_h($email) . '" disabled></label>'
	. '<p class="ke-desk-ref">Booking ref KE-' . store_h(strtoupper($id)) . '</p>'
	. '</div></section>'
	. '<section class="ke-desk-card"><h2>Trip</h2><div class="ke-desk-grid">'
	. '<label>Tour<input name="tour" value="' . store_h($tour) . '" required maxlength="140"></label>'
	. '<label>Travel date<input type="date" name="date" value="' . store_h($date) . '"></label>'
	. '<label>Guests<input type="number" name="guests" min="1" max="99" value="' . store_h($guests) . '" required></label>'
	. '<label>Total (PHP)<input name="total" inputmode="decimal" value="' . store_h($total) . '"></label>'
	. '<label>Status<select name="status">' . $options . '</select></label>'
	. '</div></section>'
	. '<section class="ke-desk-card"><h2>Driver</h2><div class="ke-desk-grid">'
	. '<label>Driver name<input name="driver_name" value="' . store_h((string) ($booking['driver_name'] ?? '')) . '" maxlength="140"></label>'
	. '<label>Driver phone<input name="driver_phone" value="' . store_h((string) ($booking['driver_phone'] ?? '')) . '" maxlength="40"></label>'
	. '<label>Vehicle<input name="driver_vehicle" value="' . store_h((string) ($booking['driver_vehicle'] ?? '')) . '" maxlength="140" placeholder="Toyota Hiace"></label>'
	. '<label>Pickup<input name="pickup_time" value="' . store_h((string) ($booking['pickup_time'] ?? '')) . '" maxlength="120" placeholder="6:00 AM, hotel name"></label>'
	. '</div></section>'
	. '<section class="ke-desk-card"><h2>Papers</h2><p class="ke-desk-lead">PDF or photo, up to 8 MB each. A new upload replaces the old one.</p>'
	. $fileRows
	. '</section>'
	. '<button class="ke-desk-btn" type="submit">Save trip</button>'
	. '</form>'
	. $removeForms
	. '<section class="ke-desk-card"><h2>This client</h2><p><a href="' . store_h($again) . '">Add another trip for this email</a></p>';
if ($others !== '') {
	$body .= '<ul class="ke-desk-others">' . $others . '</ul>';
}
$body .= '</section>';
$body .= '<script>document.getElementById("ke-copy-login").addEventListener("click",function(){var box=document.getElementById("ke-login-script");var btn=this;var text=box.value;if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(text).then(function(){btn.textContent="Copied";});return;}box.select();document.execCommand("copy");btn.textContent="Copied";});</script>';
store_desk_page((string) ($booking['guest_name'] ?? 'Trip'), $body);
