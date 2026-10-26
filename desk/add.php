<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
store_require_staff();

$name = trim((string) ($_POST['name'] ?? $_GET['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? $_GET['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? $_GET['phone'] ?? ''));
$tour = trim((string) ($_POST['tour'] ?? ''));
$date = trim((string) ($_POST['date'] ?? ''));
$guests = trim((string) ($_POST['guests'] ?? '1'));
$totalRaw = trim((string) ($_POST['total'] ?? ''));
$status = (string) ($_POST['status'] ?? 'confirmed');
$note = trim((string) ($_POST['note'] ?? ''));
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	if (!store_csrf_ok()) {
		$error = 'Please try that again.';
	} else {
		$guestCount = (int) $guests;
		$total = store_desk_pesos($totalRaw);
		if ($tour === '') {
			$error = 'Enter the tour name.';
		} elseif ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			$error = 'Use a real travel date.';
		} elseif ($guestCount < 1 || $guestCount > 99) {
			$error = 'Guest count must be from 1 to 99.';
		} elseif ($total < 0) {
			$error = 'Enter the total in pesos, or leave it blank.';
		} else {
			$opened = store_desk_open_client($name, $email, $phone);
			$user = $opened['user'];
			if (!$user) {
				$error = (string) $opened['error'];
			} else {
				$booking = store_desk_create_trip($user, $tour, $date, $guestCount, $total, $status, $note);
				if (!$booking) {
					$error = $opened['created']
						? 'The account was saved, but the trip was not. Submit again with the same email.'
						: 'Could not save that trip. Please try again.';
				} else {
					if (!empty($opened['created']) && (string) $opened['password'] !== '') {
						store_desk_remember_password((string) $booking['id'], (string) $opened['password']);
						store_desk_flash('Account created. Copy the login message and send it in chat.');
					} else {
						store_desk_flash('This email already has an account for ' . (string) $user['name'] . '. The trip was added to that profile.');
					}
					store_redirect('/desk/trip.php?id=' . rawurlencode((string) $booking['id']));
				}
			}
		}
	}
}

$statuses = [
	'confirmed' => 'Confirmed',
	'paid' => 'Paid',
	'awaiting_payment' => 'Awaiting payment',
];
$options = '';
foreach ($statuses as $value => $label) {
	$options .= '<option value="' . store_h($value) . '"' . ($status === $value ? ' selected' : '') . '>' . store_h($label) . '</option>';
}
$csrf = store_h(store_csrf_token());
$body = '<p class="ke-desk-lead">This creates their login without signing you out. If the email already has an account, the trip is added to that profile.</p>';
if ($error !== '') {
	$body .= '<p class="ke-desk-err">' . store_h($error) . '</p>';
}
$body .= '<form method="post" class="ke-desk-form" autocomplete="off">'
	. '<input type="hidden" name="csrf" value="' . $csrf . '">'
	. '<div class="ke-desk-grid">'
	. '<label>Full name<input name="name" value="' . store_h($name) . '" required maxlength="140"></label>'
	. '<label>Email<input type="email" name="email" value="' . store_h($email) . '" required maxlength="190"></label>'
	. '<label>Phone<input name="phone" value="' . store_h($phone) . '" maxlength="40" placeholder="+63"></label>'
	. '<label>Tour<input name="tour" value="' . store_h($tour) . '" required maxlength="140" placeholder="Bohol countryside day tour"></label>'
	. '<label>Travel date<input type="date" name="date" value="' . store_h($date) . '"></label>'
	. '<label>Guests<input type="number" name="guests" min="1" max="99" value="' . store_h($guests === '' ? '1' : $guests) . '" required></label>'
	. '<label>Total (PHP)<input name="total" inputmode="decimal" value="' . store_h($totalRaw) . '" placeholder="12500"></label>'
	. '<label>Status<select name="status">' . $options . '</select></label>'
	. '</div>'
	. '<label>Note for the office<textarea name="note" maxlength="2000">' . store_h($note) . '</textarea></label>'
	. '<button class="ke-desk-btn" type="submit">Create profile and trip</button>'
	. '</form>';
store_desk_page('Add client', $body);
