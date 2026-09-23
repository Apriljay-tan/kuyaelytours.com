<?php
declare(strict_types=1);

function ke_marketing_page(array $opt, string $main): void
{
	$title = store_h((string) ($opt['title'] ?? 'Kuya Ely Tours'));
	$desc = store_h((string) ($opt['desc'] ?? ''));
	$canonical = store_h((string) ($opt['canonical'] ?? 'https://kuyaelytours.com/'));
	$image = (string) ($opt['image'] ?? '/assets/downloaded/dest-cebu.jpg');
	if ($image !== '' && !str_starts_with($image, 'http') && $image[0] !== '/') {
		$image = '/' . $image;
	}
	$image = store_h($image);
	$body = store_h((string) ($opt['body'] ?? 'tour-page tour-detail'));
	$nav = (string) ($opt['nav'] ?? '');
	$extra = (string) ($opt['extra_css'] ?? '');
	$cebuCur = $nav === 'cebu-tour.html' ? ' aria-current="page"' : '';
	$boholCur = $nav === 'bohol-tour.html' ? ' aria-current="page"' : '';
	$siqCur = $nav === 'siquijor-tour.html' ? ' aria-current="page"' : '';
	$dumCur = $nav === 'dumaguete-tour.html' ? ' aria-current="page"' : '';
	$hubCur = ($nav === 'tours-and-packages.php' || $nav === 'tours') ? ' aria-current="page"' : '';
	$homeCur = ($nav === '' || $nav === 'index.html') ? ' aria-current="page"' : '';
	$aboutCur = ($nav === 'about.html' || $nav === 'our-story.html') ? ' aria-current="page"' : '';
	$rentCur = $nav === 'service.html' ? ' aria-current="page"' : '';
	$contactCur = $nav === 'contact.html' ? ' aria-current="page"' : '';
	$moreCur = ($nav === 'galary.html' || $nav === 'permits.html') ? ' aria-current="page"' : '';
?>
<!DOCTYPE HTML>
<html lang="en-US">

<head>
	<?= store_gtm_head() ?>
	<?= store_pixel_head() ?>
	<meta charset="UTF-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<title><?= $title ?></title>
	<meta name="description" content="<?= $desc ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="canonical" href="<?= $canonical ?>">
	<meta property="og:url" content="<?= $canonical ?>">
	<meta property="og:title" content="<?= $title ?>">
	<meta property="og:description" content="<?= $desc ?>">
	<meta property="og:type" content="website">
	<meta property="og:image" content="<?= $image ?>">

	<link rel="icon" type="image/png" sizes="56x56" href="/assets/images/fav-icon.png">
	<link rel="stylesheet" href="/assets/css/bootstrap.min.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/all.min.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/swiper-bundle.min.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/animation-text.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/animate.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/magnific-popup.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/meanmenu.min.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/odometer-theme.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/custom.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/bootstrap-icons.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/style.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/responsive.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/coustom-animation.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/scroll-up.css" type="text/css" media="all">
	<link rel="stylesheet" href="/assets/css/kuyaely.css?v=ui50" type="text/css" media="all">
	<script src="/assets/js/kuyaely-nav.js?v=27"></script>
	<?= $extra ?>
</head>

<body class="<?= $body ?>">
	<?= store_gtm_body() ?>
	<div class="preloader">
		<div class="loader"></div>
	</div>

	<div class="travelik-header-area ke-site-head" id="sticky-header">
		<div class="ke-topbar">
			<div class="ke-topbar-inner">
				<div class="ke-topbar-left">
					<span><i class="fa-solid fa-location-dot"></i> Cebu, Bohol, Siquijor &amp; Dumaguete</span>
					<a href="mailto:info@kuyaelytours.com"><i class="fa-solid fa-envelope"></i> info@kuyaelytours.com</a>
					<a href="tel:+639209851802"><i class="fa-solid fa-phone"></i> +63 920 985 1802</a>
					<span><i class="fa-regular fa-clock"></i> Open Daily: 6:00 AM – 10:00 PM</span>
				</div>
				<div class="ke-topbar-right">
					<div class="ke-topbar-socials">
						<a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
						<a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
						<a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
						<a href="#" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
					</div>
					<span class="ke-topbar-sep"></span>
					<details class="ke-mini-dd"><summary>PHP</summary><div>PHP</div></details>
					<details class="ke-mini-dd"><summary>EN</summary><div>EN</div></details>
				</div>
			</div>
		</div>
		<div class="ke-mainbar">
			<a class="brand-lockup" href="/index.html">
				<img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours and Transport Services">
				<span class="brand-text">
					<span class="brand-name">KUYA ELY</span>
					<span class="brand-sub">Tours and Transport Services</span>
				</span>
			</a>
			<nav class="header-menu ke-main-nav" aria-label="Primary">
				<ul class="nav_scroll">
					<li><a href="/index.html"<?= $homeCur ?>>Home</a></li>
					<li class="nav-about-dropdown"><a href="/about.html"<?= $aboutCur ?>>About Us<i class="fa-solid fa-chevron-down"></i></a>
						<ul class="sub_menu">
							<li><a href="/about.html">Who We Are</a></li>
							<li class="nav-story-item"><a href="/our-story.html">Our Story</a></li>
						</ul>
					</li>
					<li><a href="/tours-and-packages.php"<?= $hubCur ?>>Tours and Packages<i class="fa-solid fa-chevron-down"></i></a>
						<ul class="sub_menu">
							<li><a href="/cebu-tour.html"<?= $cebuCur ?>>Cebu Tour</a></li>
							<li><a href="/bohol-tour.html"<?= $boholCur ?>>Bohol Tour</a></li>
							<li><a href="/siquijor-tour.html"<?= $siqCur ?>>Siquijor Tour</a></li>
							<li><a href="/dumaguete-tour.html"<?= $dumCur ?>>Dumaguete Tour</a></li>
						</ul>
					</li>
					<li class="nav-rent-dropdown"><a href="/service.html"<?= $rentCur ?>>Car Rental<i class="fa-solid fa-chevron-down"></i></a>
						<ul class="sub_menu">
							<li><a href="/service.html">Van &amp; car rental</a></li>
							<li><a href="/service.html?type=transfer">Airport transfer</a></li>
						</ul>
					</li>
					<li class="nav-more-dropdown"><a href="#"<?= $moreCur ?>>More<i class="fa-solid fa-chevron-down"></i></a>
						<ul class="sub_menu">
							<li><a href="/galary.html">Gallery</a></li>
							<li><a href="/permits.html">Permits</a></li>
						</ul>
					</li>
					<li><a href="/contact.html"<?= $contactCur ?>>Contact</a></li>
				</ul>
			</nav>
			<div class="ke-main-actions">
				<form class="ke-head-search" action="/search-tours.php" method="get" role="search">
					<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
					<input type="search" name="q" placeholder="Search tours, destinations..." aria-label="Search tours">
				</form>
				<a class="ke-icon-btn ke-wish-btn" href="/account/bookings.php" aria-label="Saved trips"><i class="fa-regular fa-heart"></i></a>
				<a class="ke-icon-btn ke-cart-btn" href="/shop/cart.php" aria-label="Cart"><i class="fa-solid fa-bag-shopping"></i><span class="ke-cart-count" hidden>0</span></a>
				<details class="ke-account-dd">
					<summary>
						<i class="fa-regular fa-user"></i>
						<span class="ke-account-copy"><strong data-ke-account-title>Sign In</strong><small>My Account</small></span>
						<i class="fa-solid fa-chevron-down"></i>
					</summary>
					<div class="ke-account-menu" data-ke-account-menu>
						<a href="/account/login.php">Sign in</a>
						<a href="/account/register.php">Create account</a>
					</div>
				</details>
				<a class="ke-book-btn" href="/contact.html"><i class="fa-regular fa-calendar"></i> BOOK TOUR <span aria-hidden="true">→</span></a>
			</div>
		</div>
	</div>

	<div class="mobile-menu-area sticky d-sm-block d-md-block d-lg-none">
		<div class="mobile-menu">
			<div class="mobile-logo">
				<a class="brand-lockup" href="/index.html" title="Kuya Ely Tours">
					<img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours and Transport Services">
					<span class="brand-name">KUYA ELY</span>
				</a>
			</div>
			<nav class="header-menu">
				<ul class="nav_scroll">
					<li><a href="/index.html">Home</a></li>
					<li class="nav-about-dropdown"><a href="/about.html">About Us</a>
						<ul class="sub_menu">
							<li><a href="/about.html">Who We Are</a></li>
							<li class="nav-story-item"><a href="/our-story.html">Our Story</a></li>
						</ul>
					</li>
					<li><a href="/tours-and-packages.php"<?= $hubCur ?>>Tours and Packages</a>
						<ul class="sub_menu">
							<li><a href="/cebu-tour.html"<?= $cebuCur ?>>Cebu Tour</a></li>
							<li><a href="/bohol-tour.html"<?= $boholCur ?>>Bohol Tour</a></li>
							<li><a href="/siquijor-tour.html"<?= $siqCur ?>>Siquijor Tour</a></li>
							<li><a href="/dumaguete-tour.html"<?= $dumCur ?>>Dumaguete Tour</a></li>
						</ul>
					</li>
					<li><a href="/service.html">Car Rental</a></li>
					<li class="nav-more-dropdown"><a href="#">More</a>
						<ul class="sub_menu">
							<li><a href="/galary.html">Gallery</a></li>
							<li><a href="/permits.html">Permits</a></li>
						</ul>
					</li>
					<li><a href="/contact.html">Contact</a></li>
					<li class="mobile-cta"><a href="/contact.html">Book Tour</a></li>
					<li class="mobile-socials"><a href="#" class="fab fa-facebook-f" aria-label="Facebook"></a></li>
					<li class="mobile-socials"><a href="#" class="fab fa-instagram" aria-label="Instagram"></a></li>
					<li class="mobile-socials"><a href="#" class="fa-brands fa-x-twitter" aria-label="Twitter"></a></li>
					<li class="mobile-socials"><a href="#" class="fab fa-linkedin-in" aria-label="LinkedIn"></a></li>
				</ul>
			</nav>
		</div>
	</div>

	<div class="xs-sidebar-group info-group">
		<div class="xs-overlay xs-bg-black"></div>
		<div class="xs-sidebar-widget">
			<div class="sidebar-widget-container">
				<div class="widget-heading">
					<a href="#" class="close-side-widget">
						<i class="far fa-times-circle"></i>
					</a>
				</div>
				<div class="sidebar-textwidget">
					<div class="sidebar-info-contents">
						<div class="content-inner">
							<div class="nav-logo">
								<a class="brand-lockup" href="/index.html">
									<img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours and Transport Services">
									<span class="brand-name">KUYA ELY</span>
								</a>
							</div>
							<div class="content-box">
								<h2>About Us</h2>
								<p class="text">Kuya Ely Tours and Transport Services is your local partner for island tours and private transfers around Cebu, Bohol, Siquijor, and Dumaguete.</p>
								<a href="/contact.html" class="theme-btn btn-style-two"><span>Book a Tour</span></a>
								<ul class="social-box">
									<li class="facebook"><a href="#" class="fab fa-facebook-f" aria-label="Facebook"></a></li>
									<li class="twitter"><a href="#" class="fab fa-instagram" aria-label="Instagram"></a></li>
									<li class="linkedin"><a href="#" class="fa-brands fa-x-twitter" aria-label="Twitter"></a></li>
									<li class="youtube"><a href="#" class="fab fa-linkedin-in" aria-label="LinkedIn"></a></li>
								</ul>
							</div>
							<div class="contact-info">
								<h2>Contact Info</h2>
								<ul class="list-style-one">
									<li><span class="icon flaticon-email"></span>Pony Ville, Suba-Basbas, Lapu-Lapu City, Cebu</li>
									<li><span> <i class="bi bi-telephone-inbound"></i> </span><a href="tel:+639209851802">+63 920 985 1802</a></li>
									<li><span> <i class="bi bi-telephone-inbound"></i> </span><a href="tel:+639175550793">+63 917 555 0793</a></li>
									<li><span><i class="bi bi-geo-alt"></i></span><a href="mailto:info@kuyaelytours.com">info@kuyaelytours.com</a></li>
									<li><span><i class="bi bi-clock"></i></span>Daily tours by appointment</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


<?= $main ?>
<footer class="main-footer-one classic">
		<div class="main-footer-section">
			<div class="auto-container">
				<div class="row">
					<div class="col-xl-3 col-lg-3 col-md-6">
						<div class="footer-widget-content social">
							<div class="logo">
								<a class="brand-lockup" href="/index.html">
									<img src="/assets/downloaded/kuyaely_logo_web.png" alt="Kuya Ely Tours and Transport Services">
									<span class="brand-name">KUYA ELY</span>
								</a>
							</div>
							<div class="footer-desc">Kuya Ely Tours and Transport Services offers Cebu, Bohol, Siquijor, and Dumaguete tour packages plus private van and car rental.</div>
							<ul class="footer-social">
								<li><a href="#"><i class="fa-brands fa-facebook-f"></i></a></li>
								<li><a href="#"><i class="fa-brands fa-x-twitter"></i></a></li>
								<li><a href="#"><i class="fa-brands fa-pinterest-p"></i></a></li>
								<li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
							</ul>
						</div>
					</div>
					<div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
						<div class="footer-widget-content right">
							<h3 class="footer-title">Company</h3>
							<ul class="footer-menu">
								<li><a href="/about.html">about us <i class="fa-solid fa-arrow-right"></i></a></li>
								<li class="nav-story-item"><a href="/our-story.html">our story <i class="fa-solid fa-arrow-right"></i></a></li>
								<li><a href="/tours-and-packages.php">our tours <i class="fa-solid fa-arrow-right"></i></a></li>
								<li><a href="/service.html">car rental <i class="fa-solid fa-arrow-right"></i></a></li>
								<li><a href="/permits.html">permits <i class="fa-solid fa-arrow-right"></i></a></li>
																<li><a href="/contact.html">contact us <i class="fa-solid fa-arrow-right"></i></a></li>
								<li><a href="/privacy-policy.html">privacy policy <i class="fa-solid fa-arrow-right"></i></a></li>
								<li><a href="/terms.html">terms and conditions <i class="fa-solid fa-arrow-right"></i></a></li>
							</ul>
						</div>
					</div>
					<div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
						<div class="footer-widget-content">
							<h3 class="footer-title">Contact Us</h3>
							<div class="footer-content">
								<div class="location"><span>Our Address</span>Pony Ville, Suba-Basbas<br>Lapu-Lapu City, Cebu 6015</div>
								<ul class="contact-info">
									<li class="email-text"><a href="mailto:info@kuyaelytours.com">Send E-Mail</a></li>
									<li class="email-address"><a href="mailto:info@kuyaelytours.com">info@kuyaelytours.com</a></li>
									<li class="email-address"><a href="tel:+639209851802">+63 920 985 1802</a></li>
								</ul>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
						<div class="footer-widget-content">
							<h3 class="footer-title">Instagram post</h3>
							<div class="recent-post">
								<a href="/galary.html"><img src="/assets/downloaded/moments/moment-6336.jpg" alt="Kuya Ely guests in Siquijor"></a>
								<a href="/galary.html"><img src="/assets/downloaded/moments/moment-6375.jpg" alt="Kuya Ely forest tour"></a>
								<a href="/galary.html"><img src="/assets/downloaded/moments/moment-6420.jpg" alt="Kuya Ely church tour"></a>
								<a href="/galary.html"><img src="/assets/downloaded/moments/moment-6431.jpg" alt="Kuya Ely heritage stop"></a>
								<a href="/galary.html"><img src="/assets/downloaded/moments/moment-6435.jpg" alt="Kuya Ely convent tour"></a>
								<a href="/galary.html"><img src="/assets/downloaded/moments/moment-6525.jpg" alt="Kuya Ely beach tour"></a>
							</div>
						</div>
					</div>
				</div>
				<div class="footer-bottom">
					<div class="row align-items-center">
						<div class="col-lg-7 col-md-7">
							<div class="copyright-text">© Kuya Ely Tours 2026. Website developed by <a href="https://www.syntrixph.com" target="_blank" rel="noopener">Syntrix PH</a></div>
						</div>
						<div class="col-lg-5 col-md-5">
							<div class="footer-bottom-menu">
								<ul>
									<li><a href="/index.html">Home</a></li>
									<li><a href="/contact.html">Contact</a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>

	<div class="prgoress_indicator active-progress">
		<svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
			<path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"
				style="transition: stroke-dashoffset 10ms linear 0s; stroke-dasharray: 307.919, 307.919; stroke-dashoffset: 212.78;">
			</path>
		</svg>
	</div>

	<script src="/assets/js/jquery.js"></script>
	<script src="/assets/js/bootstrap.min.js"></script>
	<script src="/assets/js/waypoints.js"></script>
	<script src="/assets/js/jquery.counterup.min.js"></script>
	<script src="/assets/js/jquery.magnific-popup.min.js"></script>
	<script src="/assets/js/parallaxie.js"></script>
	<script src="/assets/js/gsap.min.js"></script>
	<script src="/assets/js/ScrollToPlugin.min.js"></script>
	<script src="/assets/js/ScrollTrigger.min.js"></script>
	<script src="/assets/js/SplitText.min.js"></script>
	<script>window.hoverEffect=window.hoverEffect||function(){return{next:function(){},previous:function(){}}};</script>
	<script src="/assets/js/wow.js"></script>
	<script src="/assets/js/jquery.meanmenu.js"></script>
	<script src="/assets/js/jquery.scrollUp.js"></script>
	<script src="/assets/js/jquery.barfiller.js"></script>
	<script src="/assets/js/animation-text.js"></script>
	<script src="/assets/js/swiper-bundle.min.js"></script>
	<script>
	(function () {
		var tabs = document.querySelectorAll(".tour-catalog .tour-dest-tabs a[data-area]");
		var cards = document.querySelectorAll(".ke-cat-grid .ke-cat-card");
		function show(area) {
			tabs.forEach(function (tab) {
				tab.classList.toggle("is-active", tab.getAttribute("data-area") === area);
			});
			cards.forEach(function (card) {
				var cardArea = card.getAttribute("data-area") || "";
				var on = area === "all" || cardArea === "promo" || cardArea === area;
				card.style.display = on ? "" : "none";
			});
		}
		tabs.forEach(function (tab) {
			tab.addEventListener("click", function (e) {
				e.preventDefault();
				show(tab.getAttribute("data-area") || "all");
			});
		});
		document.querySelectorAll(".ke-cat-grid .ke-cat-card:not(.ke-cat-promo)").forEach(function (card) {
			card.addEventListener("click", function (e) {
				if (e.target.closest("a, button")) return;
				var href = card.getAttribute("data-ke-href");
				if (!href) {
					var link = card.querySelector(".ke-cat-actions a, .ke-cat-body h3 a");
					href = link ? link.getAttribute("href") : "";
				}
				if (href) window.location.href = href;
			});
		});
		var loc = document.getElementById("location");
		if (loc) loc.addEventListener("change", function () {
			if (loc.value) window.location.href = "/" + loc.value + "-tour";
		});
		var act = document.getElementById("activity");
		if (act) act.addEventListener("change", function () {
			if (act.value) window.location.href = "/tours/" + act.value;
		});
		var explore = document.querySelector(".ke-cat-promo-copy a[data-area]");
		if (explore) explore.addEventListener("click", function (e) {
			e.preventDefault();
			show("all");
			var grid = document.getElementById("ke-cat-grid");
			if (grid) grid.scrollIntoView({ behavior: "smooth", block: "start" });
		});
	})();
	</script>
	<script src="/assets/js/main.js"></script>
	<?= store_pixel_page_script(is_array($opt['pixel'] ?? null) ? $opt['pixel'] : []) ?>
</body>

</html>

<?php
}
