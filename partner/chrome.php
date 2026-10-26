<?php
declare(strict_types=1);

function partner_menu(): array
{
	return [
		['href' => '/partner/', 'file' => 'index.php', 'label' => 'Dashboard', 'icon' => 'bx-home-smile', 'group' => 'Partner portal'],
		['href' => '/partner/bookings.php', 'file' => 'bookings.php', 'label' => 'My Bookings', 'icon' => 'bx-calendar', 'group' => 'Partner portal'],
		['href' => '/partner/rates.php', 'file' => 'rates.php', 'label' => 'Rates & Packages', 'icon' => 'bx-purchase-tag', 'group' => 'Partner portal'],
		['href' => '/partner/commissions.php', 'file' => 'commissions.php', 'label' => 'Commissions', 'icon' => 'bx-wallet', 'group' => 'Partner portal'],
		['href' => '/partner/profile.php', 'file' => 'profile.php', 'label' => 'Profile', 'icon' => 'bx-user', 'group' => 'Partner portal'],
		['href' => '/partner/password.php', 'file' => 'password.php', 'label' => 'Password', 'icon' => 'bx-lock-alt', 'group' => 'Partner portal'],
		['href' => '/partner/support.php', 'file' => 'support.php', 'label' => 'Support', 'icon' => 'bx-support', 'group' => 'Partner portal'],
	];
}

function partner_initials(string $name): string
{
	$parts = preg_split('/\s+/', trim($name)) ?: [];
	$first = strtoupper(substr((string) ($parts[0] ?? 'P'), 0, 1));
	$second = strtoupper(substr((string) ($parts[1] ?? ''), 0, 1));
	return $first . $second;
}

function partner_nav(string $active): string
{
	$html = '';
	$last = '';
	foreach (partner_menu() as $item) {
		$group = (string) $item['group'];
		if ($group !== $last) {
			$html .= '<li class="menu-title">' . store_h($group) . '</li>';
			$last = $group;
		}
		$on = $active === $item['file'] ? ' active' : '';
		$html .= '<li class="menu-item">'
			. '<a href="' . store_h((string) $item['href']) . '" class="menu-link waves-effect waves-light' . $on . '">'
			. '<span class="menu-icon"><i class="bx ' . store_h((string) $item['icon']) . '"></i></span>'
			. '<span class="menu-text"> ' . store_h((string) $item['label']) . ' </span>'
			. '</a></li>';
	}
	return $html;
}

function partner_bundle(): array
{
	$user = store_require_partner();
	$profile = store_partner_for_user($user) ?? [];
	$pid = (string) ($profile['id'] ?? '');
	$desk = $pid !== '' ? store_partner_desk($pid) : store_partner_desk_defaults();
	$bookings = store_user_bookings((string) ($user['id'] ?? ''));
	return ['user' => $user, 'profile' => $profile, 'desk' => $desk, 'bookings' => $bookings];
}

function partner_booking_items(array $booking): array
{
	$items = $booking['items'] ?? [];
	if (is_string($items)) {
		$decoded = json_decode($items, true);
		$items = is_array($decoded) ? $decoded : [];
	}
	return is_array($items) ? $items : [];
}

function partner_booking_label(array $booking): string
{
	$first = partner_booking_items($booking)[0] ?? [];
	if (!is_array($first)) {
		$first = [];
	}
	$label = trim((string) ($first['label'] ?? $first['name'] ?? ''));
	return $label !== '' ? $label : 'Tour';
}

function partner_status_pill(string $status): string
{
	$map = [
		'confirmed' => ['Confirmed', ''],
		'paid' => ['Paid', ''],
		'pending_request' => ['Pending', 'is-wait'],
		'awaiting_payment' => ['Pending', 'is-wait'],
		'cancelled' => ['Cancelled', 'is-off'],
	];
	[$label, $class] = $map[$status] ?? [ucfirst($status), 'is-muted'];
	return '<span class="ke-pill ' . $class . '">' . store_h($label) . '</span>';
}

function partner_layout_start(string $title, string $active = 'index.php', string $kicker = 'Partner', bool $showTitle = true): void
{
	$pageTitle = store_h($title);
	$kickerH = store_h($kicker);
	$user = store_user();
	$name = trim((string) ($user['name'] ?? 'Partner'));
	if ($name === '') {
		$name = 'Partner';
	}
	$initials = store_h(partner_initials($name));
	$nameH = store_h($name);
	$logoOnDark = '/admin/assets/syntrixflow_logo_darkmode.png';
	$logoOnLight = '/admin/assets/syntrixflow_logo_lightmode.png';
	$logoMark = '/admin/assets/favicon.png';
	$titleBlock = '';
	if ($showTitle) {
		$titleBlock = '<div class="py-3 py-lg-4"><div class="row"><div class="col-lg-6"><p class="text-muted mb-1">' . $kickerH . '</p><h4 class="page-title mb-0">' . $pageTitle . '</h4></div></div></div>';
	}
	echo '<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-menu-color="brand" data-topbar-color="light">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex, nofollow">
	<title>' . $pageTitle . ' | Syntrix Flow</title>
	<link rel="icon" type="image/png" sizes="192x192" href="' . $logoMark . '">
	<link rel="apple-touch-icon" sizes="180x180" href="/admin/assets/apple-touch-icon.png">
	<link rel="manifest" href="/admin/manifest.webmanifest">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-title" content="Syntrix Flow">
	<meta name="theme-color" content="#ffffff">
	<link href="/admin/vendor/lunoz/css/style.min.css" rel="stylesheet" type="text/css">
	<link href="/admin/vendor/lunoz/css/icons.min.css" rel="stylesheet" type="text/css">
	<link href="/admin/assets/admin-app.css?v=20" rel="stylesheet" type="text/css">
	<link href="/partner/portal.css?v=4" rel="stylesheet" type="text/css">
	<script src="/admin/vendor/lunoz/js/config.js"></script>
</head>
<body class="ke-portal-body">
<div class="layout-wrapper">
	<div class="main-menu">
		<div class="logo-box">
			<a href="/partner/" class="logo-light">
				<img src="' . $logoOnDark . '" alt="Syntrix Flow" class="logo-lg">
				<img src="' . $logoMark . '" alt="Syntrix Flow" class="logo-sm">
			</a>
			<a href="/partner/" class="logo-dark">
				<img src="' . $logoOnLight . '" alt="Syntrix Flow" class="logo-lg">
				<img src="' . $logoMark . '" alt="Syntrix Flow" class="logo-sm">
			</a>
		</div>
		<div data-simplebar>
			<ul class="app-menu">' . partner_nav($active) . '</ul>
		</div>
		<a class="ke-admin-credit" href="https://www.syntrixph.com" target="_blank" rel="noopener">
			<span class="ke-admin-credit-kicker">App developed by</span>
			<strong>Syntrix PH</strong>
			<span>by Dev Jed Tan</span>
		</a>
	</div>
	<div class="page-content">
		<div class="navbar-custom">
			<div class="topbar">
				<div class="topbar-menu ke-top-start d-flex align-items-center">
					<button class="button-toggle-menu" type="button" aria-label="Menu"><i class="mdi mdi-menu"></i></button>
					<form class="ke-psearch d-none d-md-flex" action="/partner/rates.php" method="get">
						<i class="bx bx-search"></i>
						<input type="search" name="q" value="' . store_h((string) ($_GET['q'] ?? '')) . '" placeholder="Search packages, destinations, or tour names..." aria-label="Search rates and packages">
					</form>
				</div>
				<ul class="topbar-menu d-flex align-items-center gap-2">
					<li class="d-none d-md-inline-block">
						<a class="nav-link" href="" data-bs-toggle="fullscreen"><i class="mdi mdi-fullscreen font-size-24"></i></a>
					</li>
					<li>
						<a class="ke-puser" href="/partner/profile.php">
							<span class="ke-puser-av">' . $initials . '</span>
							<span class="d-none d-sm-block"><strong>' . $nameH . '</strong><small>Partner</small></span>
						</a>
					</li>
					<li class="dropdown">
						<a class="nav-link dropdown-toggle nav-user me-0 waves-effect waves-light" data-bs-toggle="dropdown" href="#" role="button" aria-label="Account">
							<i class="mdi mdi-account-circle font-size-24"></i>
						</a>
						<div class="dropdown-menu dropdown-menu-end profile-dropdown">
							<div class="dropdown-header noti-title"><h6 class="text-overflow m-0">Partner</h6></div>
							<a href="/partner/profile.php" class="dropdown-item notify-item"><i class="bx bx-user"></i><span>Profile</span></a>
							<a href="/partner/password.php" class="dropdown-item notify-item"><i class="bx bx-lock-alt"></i><span>Password</span></a>
							<a href="/" class="dropdown-item notify-item"><i class="bx bx-globe"></i><span>View website</span></a>
							<div class="dropdown-divider"></div>
							<a href="/partner/logout.php" class="dropdown-item notify-item"><i class="fe-log-out"></i><span>Sign out</span></a>
						</div>
					</li>
				</ul>
			</div>
		</div>
		<div class="px-3">
			<div class="container-fluid">' . $titleBlock . '
				<div class="ke-admin-page">';
}

function partner_layout_end(): void
{
	echo '</div>
			</div>
		</div>
	</div>
</div>
<script>try{sessionStorage.setItem("themeMode","light");document.documentElement.setAttribute("data-bs-theme","light");}catch(e){}</script>
<script src="/admin/vendor/lunoz/js/vendor.min.js"></script>
<script src="/admin/vendor/lunoz/js/app.js"></script>
<script>try{document.documentElement.setAttribute("data-bs-theme","light");sessionStorage.setItem("themeMode","light");}catch(e){}</script>
</body>
</html>';
}
