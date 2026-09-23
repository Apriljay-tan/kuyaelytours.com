<?php
declare(strict_types=1);

function store_tracking_allowed(): bool
{
	return (string) ($_COOKIE['ke_consent'] ?? '') === '1';
}

function store_gtm_head(): string
{
	if (!store_tracking_allowed()) {
		return '';
	}
	return <<<'HTML'
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T8SH1P8Z');</script>
<!-- End Google Tag Manager -->
HTML;
}

function store_gtm_body(): string
{
	if (!store_tracking_allowed()) {
		return '';
	}
	return <<<'HTML'
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T8SH1P8Z"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
HTML;
}

function store_pixel_head(): string
{
	if (!store_tracking_allowed()) {
		return '';
	}
	return <<<'HTML'
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1757833078759116');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1757833078759116&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
HTML;
}

function store_pixel_params(array $params): array
{
	$out = [];
	foreach ($params as $key => $value) {
		$key = preg_replace('/[^a-z0-9_]/i', '', (string) $key) ?? '';
		if ($key === '') {
			continue;
		}
		if (is_int($value) || is_float($value)) {
			$out[$key] = $value;
		} elseif (is_array($value)) {
			$list = [];
			foreach ($value as $item) {
				if (is_scalar($item)) {
					$list[] = (string) $item;
				}
			}
			$out[$key] = $list;
		} elseif (is_scalar($value)) {
			$out[$key] = (string) $value;
		}
	}
	return $out;
}

function store_pixel_push(string $event, array $params = []): void
{
	$allowed = ['ViewContent', 'Search', 'AddToCart', 'InitiateCheckout', 'AddPaymentInfo', 'Purchase', 'Lead', 'CompleteRegistration', 'Contact', 'Schedule', 'Subscribe', 'CustomizeProduct'];
	if (!in_array($event, $allowed, true)) {
		return;
	}
	if (!isset($_SESSION['ke_pixel']) || !is_array($_SESSION['ke_pixel'])) {
		$_SESSION['ke_pixel'] = [];
	}
	$_SESSION['ke_pixel'][] = ['event' => $event, 'params' => store_pixel_params($params)];
}

function store_pixel_once(string $key, string $event, array $params = []): void
{
	if (!isset($_SESSION['ke_pixel_once']) || !is_array($_SESSION['ke_pixel_once'])) {
		$_SESSION['ke_pixel_once'] = [];
	}
	if (!empty($_SESSION['ke_pixel_once'][$key])) {
		return;
	}
	$_SESSION['ke_pixel_once'][$key] = 1;
	if (count($_SESSION['ke_pixel_once']) > 40) {
		$_SESSION['ke_pixel_once'] = array_slice($_SESSION['ke_pixel_once'], -40, null, true);
	}
	store_pixel_push($event, $params);
}

function store_pixel_page_script(array $events = []): string
{
	if (!store_tracking_allowed()) {
		return '';
	}
	$clean = [];
	foreach ($events as $event) {
		if (!is_array($event)) {
			continue;
		}
		$name = (string) ($event['event'] ?? '');
		if ($name === '') {
			continue;
		}
		$clean[] = [
			'event' => $name,
			'params' => store_pixel_params((array) ($event['params'] ?? [])),
		];
	}
	$json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
	if ($json === false) {
		$json = '[]';
	}
	return '<script>window.kePixelEvents=' . $json . ';</script>'
		. '<script src="/assets/js/kuyaely-pixel.js?v=2" defer></script>';
}

function store_page(string $title, string $body, string $kicker = 'Kuya Ely Tours', bool $bare = false, string $mods = ''): void
{
	$user = store_user();
	$count = store_cart_count();
	$accountHref = $user ? '/account/bookings.php' : '/account/login.php';
	$accountLabel = $user ? store_h((string) $user['name']) : 'Account';
	$badge = $count > 0 ? '<span class="ke-cart-count">' . $count . '</span>' : '';
	$class = $bare ? 'ke-shop ke-app' : 'ke-shop';
	$mods = preg_replace('/[^a-z0-9\-\s]/i', '', $mods) ?? '';
	if ($mods !== '') {
		$class .= ' ' . trim($mods);
	}
	$useSite = (bool) preg_match('/\b(ke-site|ke-dash|ke-bag)\b/', $mods);
	echo '<!DOCTYPE html><html lang="en"><head>';
	echo store_gtm_head();
	echo store_pixel_head();
	echo '<meta charset="UTF-8">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
	echo '<meta name="robots" content="noindex, nofollow">';
	echo '<title>' . store_h($title) . ' | Kuya Ely Tours</title>';
	echo '<link rel="icon" type="image/png" sizes="56x56" href="/assets/images/fav-icon.png">';
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
	echo '<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:ital,opsz,wght@0,400;0,500;0,600;0,700;1,400&family=Satisfy&display=swap" rel="stylesheet">';
	echo '<link rel="stylesheet" href="/assets/css/kuyaely-shop.css?v=ui35">';
	echo '</head><body class="' . $class . '">';
	echo store_gtm_body();
	if ($useSite) {
		echo store_scene_html();
		echo store_site_header($user, $count);
	} else {
		echo '<header class="ke-shop-top"><a class="ke-shop-brand" href="/index.html">';
		echo '<img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours"><span>KUYA ELY</span></a>';
		echo '<nav class="ke-shop-icons">';
		echo '<a class="ke-text-link" href="/tours-and-packages.php">Tours and Packages</a>';
		echo '<a class="ke-icon-btn" href="' . $accountHref . '" aria-label="' . $accountLabel . '" title="' . $accountLabel . '">' . store_svg_user() . '</a>';
		echo '<a class="ke-icon-btn" href="/shop/cart.php" aria-label="Cart" title="Cart">' . store_svg_cart() . $badge . '</a>';
		echo '</nav></header>';
	}
	if ($bare) {
		echo '<main class="ke-shop-main ke-app-main">' . $body . '</main>';
	} else {
		echo '<main class="ke-shop-main"><p class="ke-kicker">' . store_h($kicker) . '</p>' . $body . '</main>';
	}
	if ($useSite) {
		echo store_site_footer();
	} else {
		echo '<footer class="ke-shop-foot">Kuya Ely Tours and Transport Services · Sitio Capilis, Suba-Basbas, Lapu-Lapu City · <a href="https://wa.me/639209851802">WhatsApp +63 920 985 1802</a> · <a href="/privacy-policy.html">Privacy Policy</a> · <a href="/terms.html">Terms and Conditions</a></footer>';
	}
	echo '<script src="/assets/js/kuyaely-chat.js?v=6" defer></script>';
	echo store_pixel_page_script();
	echo '</body></html>';
}

function store_scene_html(): string
{
	$photos = [
		'/assets/downloaded/dest-cebu.jpg',
		'/assets/downloaded/dest-bohol.jpg',
		'/assets/downloaded/dest-siquijor.jpg',
		'/assets/downloaded/dest-dumaguete.jpg',
	];
	$html = '<div class="ke-scene" aria-hidden="true">';
	foreach ($photos as $src) {
		$html .= '<span style="background-image:url(\'' . store_h($src) . '\')"></span>';
	}
	$html .= '</div><div class="ke-scene-veil"></div>';
	return $html;
}

function store_site_header(?array $user, int $cartCount): string
{
	$accountHref = $user ? '/account/profile.php' : '/account/login.php';
	$badge = $cartCount > 0 ? '<span class="ke-cart-count">' . $cartCount . '</span>' : '';
	$drop = $user
		? '<a href="/account/profile.php">Overview</a><a href="/account/bookings.php">My Bookings</a><a href="/shop/cart.php">Cart</a><a href="/account/logout.php">Log out</a>'
		: '<a href="/account/login.php">Sign in</a><a href="/account/register.php">Create account</a>';
	return '<header class="ke-gnav">'
		. '<div class="ke-gnav-inner">'
		. '<a class="ke-gnav-brand" href="/index.html"><img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours"><span><strong>KUYA ELY</strong><small>Tours and Transport Services</small></span></a>'
		. '<nav class="ke-gnav-links" aria-label="Primary">'
		. '<a href="/index.html">Home</a>'
		. '<a href="/tours-and-packages.php">Tours and Packages</a>'
		. '<a href="/about.html">About Us</a>'
		. '<a href="/galary.html">Travel Guide</a>'
		. '<a href="/contact.html">Contact</a>'
		. '</nav>'
		. '<div class="ke-gnav-actions">'
		. '<a class="ke-gnav-ico" href="/tours-and-packages.php" aria-label="Search tours">' . store_svg_search() . '</a>'
		. '<div class="ke-gnav-account">'
		. '<a class="ke-gnav-account-btn" href="' . $accountHref . '">' . store_svg_user() . '<span>My Account</span><i></i></a>'
		. '<div class="ke-gnav-drop">' . $drop . '</div></div>'
		. '<a class="ke-gnav-ico ke-gnav-cart" href="/shop/cart.php" aria-label="Cart">' . store_svg_cart() . $badge . '</a>'
		. '</div>'
		. '<button class="ke-gnav-toggle" type="button" aria-label="Open menu" onclick="this.closest(\'.ke-gnav\').classList.toggle(\'is-open\')"><span></span><span></span><span></span></button>'
		. '</div></header>';
}

function store_site_footer(): string
{
	return '<footer class="ke-gfoot">'
		. '<div class="ke-gfoot-inner">'
		. '<a class="ke-gnav-brand" href="/index.html"><img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours"><span><strong>KUYA ELY</strong><small>Tours and Transport Services</small></span></a>'
		. '<p class="ke-gfoot-tag">Safe Travels. Brighter Stories.</p>'
		. '<nav aria-label="Footer">'
		. '<a href="/index.html">Home</a>'
		. '<a href="/tours-and-packages.php">Tours and Packages</a>'
		. '<a href="/about.html">About Us</a>'
		. '<a href="/galary.html">Travel Guide</a>'
		. '<a href="/contact.html">Contact</a>'
		. '</nav>'
		. '<p class="ke-gfoot-meta">Cebu &middot; Bohol &middot; Siquijor &middot; Dumaguete &middot; And Beyond<br>'
		. '<a href="/privacy-policy.html">Privacy Policy</a> · <a href="/terms.html">Terms and Conditions</a><br>'
		. '© ' . date('Y') . ' Kuya Ely Tours and Transport Services. All rights reserved.</p>'
		. '<p class="ke-gfoot-script">Explore More Together</p>'
		. '</div></footer>';
}

function store_svg_search(): string
{
	return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.2" stroke="currentColor" stroke-width="1.8"/><path d="M16 16.5 20.2 20.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
}

function store_first_name(string $name): string
{
	$part = explode(' ', trim($name), 2)[0];
	return $part !== '' ? $part : 'there';
}

function store_initials(string $name): string
{
	$parts = preg_split('/\s+/', trim($name)) ?: [];
	$a = strtoupper(substr((string) ($parts[0] ?? 'K'), 0, 1));
	$b = isset($parts[1]) ? strtoupper(substr((string) $parts[1], 0, 1)) : '';
	return $a . $b;
}

function store_account_frame(string $title, string $active, string $html, string $notice = ''): void
{
	$user = store_user();
	if (!$user) {
		store_redirect('/account/login.php');
	}
	store_page($title, store_hello_band($user, $active) . $notice . $html, '', true, 'ke-site');
}

function store_hello_band(array $user, string $active): string
{
	$name = (string) ($user['name'] ?? '');
	$bookings = count(store_user_bookings((string) $user['id']));
	$cart = store_cart_count();
	$phone = trim((string) ($user['phone'] ?? ''));
	$tabs = [
		'bookings' => ['/account/bookings.php', 'My bookings'],
		'profile' => ['/account/profile.php', 'Account'],
		'cart' => ['/shop/cart.php', 'Cart' . ($cart > 0 ? ' (' . $cart . ')' : '')],
	];
	$nav = '';
	foreach ($tabs as $key => $tab) {
		$on = $key === $active ? ' aria-current="page"' : '';
		$nav .= '<a class="ke-tab" href="' . $tab[0] . '"' . $on . '>' . store_h($tab[1]) . '</a>';
	}
	$nav .= '<a class="ke-tab ke-tab-out" href="/account/logout.php">Log out</a>';
	$phoneLine = $phone !== '' ? store_h($phone) : 'Add a WhatsApp number';
	return '<section class="ke-hello">'
		. '<div class="ke-hello-id"><span class="ke-avatar" aria-hidden="true">' . store_h(store_initials($name)) . '</span>'
		. '<div><p class="ke-kicker">My account</p><h1>Hello, ' . store_h(store_first_name($name)) . '</h1>'
		. '<p class="ke-hello-meta">' . store_h((string) $user['email']) . ' · ' . $phoneLine . '</p></div></div>'
		. '<dl class="ke-hello-stats">'
		. '<div><dt>Bookings</dt><dd>' . $bookings . '</dd></div>'
		. '<div><dt>In cart</dt><dd>' . $cart . '</dd></div>'
		. '<div><dt>WhatsApp</dt><dd><a href="https://wa.me/639209851802">Message us</a></dd></div>'
		. '</dl></section><nav class="ke-tabs" aria-label="Account">' . $nav . '</nav>';
}

function store_auth_page(string $title, string $heading, string $lede, string $form, string $alt, string $error = '', string $visualTitle = 'Kuya Ely Tours', string $visualLede = 'Travel is the only purchase that enriches you in ways beyond the fare.'): void
{
	echo '<!DOCTYPE html><html lang="en"><head>';
	echo store_gtm_head();
	echo store_pixel_head();
	echo '<meta charset="UTF-8">';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
	echo '<meta name="robots" content="noindex, nofollow">';
	echo '<title>' . store_h($title) . ' | Kuya Ely Tours</title>';
	echo '<link rel="icon" type="image/png" sizes="56x56" href="/assets/images/fav-icon.png">';
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
	echo '<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:ital,opsz,wght@0,400;0,500;0,600;0,700;1,400&family=Satisfy&display=swap" rel="stylesheet">';
	echo '<link rel="stylesheet" href="/assets/css/kuyaely-shop.css?v=ui35">';
	echo '</head><body class="ke-auth">';
	echo store_gtm_body();
	echo '<div class="ke-auth-slides" aria-hidden="true">';
	echo '<span style="background-image:url(\'/assets/downloaded/dest-siquijor.jpg\')"></span>';
	echo '</div><div class="ke-auth-veil"></div>';
	echo '<div class="ke-auth-shell">';
	echo '<article class="ke-auth-card">';
	echo '<section class="ke-auth-visual">';
	echo '<a class="ke-auth-brand" href="/index.html"><img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours"><span>KUYA ELY TOURS</span></a>';
	echo '<div class="ke-auth-copy"><h2>' . store_h($visualTitle) . '</h2><p>' . store_h($visualLede) . '</p></div>';
	echo '</section>';
	echo '<section class="ke-auth-panel">';
	echo '<div class="ke-auth-mark" aria-hidden="true">' . store_svg_plane() . '</div>';
	echo '<p class="ke-kicker">Kuya Ely Tours</p>';
	echo '<h1>' . store_h($heading) . '</h1>';
	echo '<p class="lede">' . store_h($lede) . '</p>';
	if ($error !== '') {
		echo '<p class="ke-err">' . store_h($error) . '</p>';
	}
	echo $form;
	echo $alt;
	echo '</section></article></div>';
	echo '<script>(function(){document.querySelectorAll(".ke-pass-toggle").forEach(function(btn){btn.addEventListener("click",function(){var box=btn.closest(".ke-field-box");var input=box?box.querySelector("input"):null;if(!input)return;var show=input.type==="password";input.type=show?"text":"password";btn.setAttribute("aria-label",show?"Hide password":"Show password");btn.setAttribute("aria-pressed",show?"true":"false");btn.classList.toggle("is-on",show);});});})();</script>';
	echo '<script src="/assets/js/kuyaely-chat.js?v=6" defer></script>';
	echo store_pixel_page_script();
	echo '</body></html>';
}

function store_oauth_buttons(string $next): string
{
	$q = rawurlencode($next);
	return '<div class="ke-auth-or"><span>or continue with</span></div>'
		. '<div class="ke-social">'
		. '<a class="ke-social-btn ke-social-google" href="/account/oauth-start.php?provider=google&amp;next=' . $q . '" aria-label="Continue with Google" title="Google"><img class="ke-google-logo" src="/assets/images/LOGOS/GOOGLE.png" alt="Google" width="72" height="24"></a>'
		. '<a class="ke-social-btn" href="/account/oauth-start.php?provider=facebook&amp;next=' . $q . '" aria-label="Continue with Facebook" title="Facebook">' . store_svg_facebook() . '<span>Facebook</span></a>'
		. '</div>';
}

function store_field(string $label, string $name, string $type, string $value = '', string $icon = 'mail', bool $required = true, string $extra = ''): string
{
	$req = $required ? ' required' : '';
	$box = 'ke-field-box';
	$toggle = '';
	if ($type === 'password') {
		$box .= ' ke-field-box-pass';
		$toggle = '<button class="ke-pass-toggle" type="button" aria-label="Show password" aria-pressed="false">'
			. '<svg class="ke-pass-show" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.6-6.5 9.5-6.5S21.5 12 21.5 12 17.9 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.7"/></svg>'
			. '<svg class="ke-pass-hide" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M9.9 9.9A2.6 2.6 0 0 0 12 14.6M14.1 14.2A2.6 2.6 0 0 0 12 9.4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M4.2 7.6C2.8 9.2 2 10.8 2 10.8s3.6 6.5 10 6.5c1.5 0 2.9-.3 4.1-.8M19.6 15.7c1.2-1.2 2.4-2.8 2.4-2.8S18.4 6.3 12 6.3c-.7 0-1.4.1-2 .2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>'
			. '</button>';
	}
	return '<label class="ke-field"><span>' . store_h($label) . '</span><span class="' . $box . '">'
		. store_field_icon($icon)
		. '<input type="' . store_h($type) . '" name="' . store_h($name) . '" value="' . store_h($value) . '"' . $req . ' ' . $extra . '>'
		. $toggle
		. '</span></label>';
}

function store_field_icon(string $name): string
{
	if ($name === 'lock') {
		return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
	}
	if ($name === 'user') {
		return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M5 19.2c.8-3.2 3.4-5.2 7-5.2s6.2 2 7 5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
	}
	if ($name === 'phone') {
		return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.8h3.2l1 3.2-2 1.4a12 12 0 0 0 6.4 6.4l1.4-2 3.2 1V20a1.8 1.8 0 0 1-1.8 1.8A16.2 16.2 0 0 1 3.2 5.6 1.8 1.8 0 0 1 5 3.8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>';
	}
	return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function store_svg_user(): string
{
	return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M5 19.2c.8-3.2 3.4-5.2 7-5.2s6.2 2 7 5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
}

function store_svg_cart(): string
{
	return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h15l-1.4 8.2a2 2 0 0 1-2 1.8H9.2a2 2 0 0 1-2-1.7L5.2 4H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.4" fill="currentColor"/><circle cx="18" cy="20" r="1.4" fill="currentColor"/></svg>';
}

function store_svg_plane(): string
{
	return '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 12.5 21 4l-3.8 16.2-4.6-5.1-3.2 3.4-.2-5.2L3 12.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path class="ke-plane-path" d="M14 6c3 1 6 4 7 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-dasharray="2 3"/></svg>';
}

function store_svg_google(): string
{
	return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#EA4335" d="M12 10.2v3.6h5.1c-.2 1.2-1.5 3.6-5.1 3.6A5.9 5.9 0 1 1 12 6.1c1.7 0 2.8.7 3.4 1.3l2.5-2.4A9.5 9.5 0 0 0 12 2.5 9.5 9.5 0 0 0 2.5 12 9.5 9.5 0 0 0 12 21.5c5.5 0 9.1-3.9 9.1-9.4 0-.6 0-1-.1-1.5H12Z"/></svg>';
}

function store_svg_facebook(): string
{
	return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#1877F2" d="M13.6 21v-7.2h2.4l.4-2.8h-2.8V9.3c0-.8.2-1.4 1.4-1.4H16.5V5.4A19 19 0 0 0 14.2 5c-2.3 0-3.8 1.4-3.8 4v1.9H8v2.8h2.4V21h3.2Z"/></svg>';
}

function store_svg_apple(): string
{
	return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#111" d="M16.3 12.3c0-2.4 2-3.6 2.1-3.7-1.1-1.7-2.9-1.9-3.5-1.9-1.5-.2-2.9.9-3.6.9s-1.9-.9-3.2-.8c-1.6.1-3.1 1-3.9 2.5-1.7 2.9-.4 7.2 1.2 9.6.8 1.2 1.7 2.5 3 2.4 1.2-.1 1.6-.7 3.1-.7s1.8.7 3.1.7 2.1-1.2 2.8-2.3c.9-1.3 1.3-2.5 1.3-2.6-.1 0-2.4-.9-2.4-3.6Zm-2.2-6.5c.6-.8 1.1-1.9.9-3-1 .1-2.1.7-2.8 1.5-.6.7-1.2 1.8-.9 2.9 1.1.1 2.1-.6 2.8-1.4Z"/></svg>';
}
