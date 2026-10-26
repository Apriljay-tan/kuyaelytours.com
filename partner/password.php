<?php
declare(strict_types=1);

require dirname(__DIR__) . '/store/bootstrap.php';
require __DIR__ . '/chrome.php';

$user = store_require_partner();
$error = '';
$must = store_password_change_required();
$hash = (string) ($user['password_hash'] ?? '');
$email = (string) ($user['email'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	$current = (string) ($_POST['current'] ?? '');
	$next = (string) ($_POST['password'] ?? '');
	$again = (string) ($_POST['confirm'] ?? '');
	if (!$must && ($hash === '' || !password_verify($current, $hash))) {
		$error = 'The current password is not correct.';
	} elseif (strlen($next) < 10 || !preg_match('/[A-Za-z]/', $next) || !preg_match('/\d/', $next)) {
		$error = 'Use at least 10 characters, including a letter and a number.';
	} elseif ($email !== '' && strcasecmp($next, $email) === 0) {
		$error = 'Do not use your email as the password.';
	} elseif ($next !== $again) {
		$error = 'Those passwords do not match.';
	} elseif ($hash !== '' && password_verify($next, $hash)) {
		$error = 'Choose a different password than the temporary one.';
	} elseif (!store_set_user_password((string) $user['id'], $next)) {
		$error = 'Could not save that password. Please try again.';
	} else {
		session_regenerate_id(true);
		$_SESSION['store_csrf'] = bin2hex(random_bytes(16));
		$_SESSION['ke_partner_rotated'] = time();
		store_redirect('/partner/?ready=1');
	}
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	$error = 'Please try that again.';
}

partner_layout_start($must ? 'Choose a password' : 'Password', 'password.php');
?>
<?php if ($must): ?>
	<div class="alert alert-warning">This is the temporary password from your email. Choose one you will remember, then the partner dashboard opens.</div>
<?php endif; ?>
<?php if ($error !== ''): ?>
	<div class="alert alert-danger"><?php echo store_h($error); ?></div>
<?php endif; ?>
<div class="row">
	<div class="col-lg-6">
		<div class="card">
			<div class="card-body">
				<h5 class="card-title mb-1"><?php echo $must ? 'Choose a password' : 'Change password'; ?></h5>
				<p class="text-muted mb-3">This password is for <?php echo store_h($email); ?>.</p>
				<form method="post" action="/partner/password.php" autocomplete="off">
					<input type="hidden" name="csrf" value="<?php echo store_h(store_csrf_token()); ?>">
					<?php if (!$must): ?>
					<div class="form-group mb-3">
						<label class="form-label" for="current">Current password</label>
						<input class="form-control" type="password" id="current" name="current" autocomplete="current-password" required>
					</div>
					<?php endif; ?>
					<div class="form-group mb-3">
						<label class="form-label" for="password">New password</label>
						<input class="form-control" type="password" id="password" name="password" minlength="10" autocomplete="new-password" required>
					</div>
					<div class="form-group mb-3">
						<label class="form-label" for="confirm">Confirm password</label>
						<input class="form-control" type="password" id="confirm" name="confirm" minlength="10" autocomplete="new-password" required>
					</div>
					<p class="text-muted">Use at least 10 characters, including a letter and a number.</p>
					<button class="btn btn-primary" type="submit">Save password</button>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
partner_layout_end();
