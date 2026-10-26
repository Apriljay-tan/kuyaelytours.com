<?php
declare(strict_types=1);

require dirname(__DIR__) . '/store/bootstrap.php';

if (store_user() && store_is_partner(store_user())) {
	store_redirect(store_password_change_required() ? '/partner/password.php' : '/partner/');
}

$error = '';
$email = trim((string) ($_POST['email'] ?? ''));
if ((string) ($_GET['err'] ?? '') === 'role') {
	$error = 'Sign in with the partner login created by the Kuya Ely office.';
}
if ((string) ($_GET['expired'] ?? '') === '1') {
	$error = 'Your session ended. Sign in again.';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && store_csrf_ok()) {
	if (!store_login_allowed() || store_login_guard_blocked($email)) {
		$error = 'Please wait a few minutes and try again.';
	} else {
		$error = store_partner_login($email, (string) ($_POST['password'] ?? ''));
		if ($error === '') {
			store_login_clear();
			store_login_guard_ok($email);
			store_redirect(store_password_change_required() ? '/partner/password.php' : '/partner/');
		}
		store_login_fail();
		store_login_guard_fail($email);
	}
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	$error = 'Please try that again.';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-menu-color="brand" data-topbar-color="light">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title>Partner sign-in | Syntrix Flow</title>
	<link rel="icon" type="image/png" sizes="192x192" href="/admin/assets/favicon.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/admin/assets/apple-touch-icon.png">
	<link rel="manifest" href="/admin/manifest.webmanifest">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="Syntrix Flow">
	<meta name="theme-color" content="#ffffff">
	<link href="/admin/vendor/lunoz/css/style.min.css" rel="stylesheet" type="text/css">
	<link href="/admin/vendor/lunoz/css/icons.min.css" rel="stylesheet" type="text/css">
	<link href="/admin/assets/admin-app.css?v=18" rel="stylesheet" type="text/css">
	<script src="/admin/vendor/lunoz/js/config.js"></script>
</head>
<body class="auth-page ke-auth-shell d-flex justify-content-center align-items-center min-vh-100 p-5">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-xl-4 col-md-5">
				<div class="card ke-auth-card">
					<div class="card-body p-4">
						<div class="text-center auth-logo mb-4">
							<img src="/admin/assets/syntrixflow_logo_darkmode.png" alt="Syntrix Flow">
						</div>
						<h4 class="text-center mb-1">Partner sign-in</h4>
						<p class="text-muted text-center mb-4">Use the email and password created for you by the Kuya Ely office.</p>
						<?php if ($error !== ''): ?>
							<p class="ke-auth-error"><?php echo store_h($error); ?></p>
						<?php endif; ?>
						<form method="post" action="/partner/login.php" autocomplete="off">
							<input type="hidden" name="csrf" value="<?php echo store_h(store_csrf_token()); ?>">
							<div class="form-group mb-3">
								<label class="form-label" for="email">Email or username</label>
								<input class="form-control" type="text" id="email" name="email" value="<?php echo store_h($email); ?>" autocomplete="username" autocapitalize="off" spellcheck="false" required>
							</div>
							<div class="form-group mb-3">
								<label class="form-label" for="password">Password</label>
								<div class="ke-pass-wrap">
									<input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
									<button class="ke-pass-toggle" type="button" data-ke-pass="password" aria-label="Show password"><i class="bx bx-show" aria-hidden="true"></i><span>Show</span></button>
								</div>
							</div>
							<div class="form-group mb-0 text-center">
								<button class="btn btn-primary w-100" type="submit">Sign in</button>
							</div>
						</form>
						<p class="text-muted text-center mt-4 mb-1">No login yet? <a href="/partner/apply.php">Apply as Partner</a></p>
						<p class="text-muted text-center mb-0"><a href="/partners">Back to Partner with us</a></p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
		document.querySelectorAll('[data-ke-pass]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var input = document.getElementById(btn.getAttribute('data-ke-pass'));
				if (!input) return;
				var show = input.type === 'password';
				input.type = show ? 'text' : 'password';
				var icon = btn.querySelector('i');
				var label = btn.querySelector('span');
				if (icon) icon.className = show ? 'bx bx-hide' : 'bx bx-show';
				if (label) label.textContent = show ? 'Hide' : 'Show';
				btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
			});
		});
	</script>
	<script src="/admin/vendor/lunoz/js/vendor.min.js"></script>
	<script src="/admin/vendor/lunoz/js/app.js"></script>
</body>
</html>
