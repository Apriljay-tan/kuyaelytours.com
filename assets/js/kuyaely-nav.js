(function () {
	if (/^\/admin(\/|$)/i.test(location.pathname)) {
		return;
	}
	function consentValue() {
		var match = document.cookie.match(/(?:^|; )ke_consent=([^;]*)/);
		return match ? decodeURIComponent(match[1]) : "";
	}
	function setConsent(value) {
		var secure = location.protocol === "https:" ? "; Secure" : "";
		document.cookie = "ke_consent=" + value + "; Path=/; Max-Age=15552000; SameSite=Lax" + secure;
	}
	function loadGtm() {
		if (document.querySelector("script[src*='gtm.js?id=GTM-T8GFHP8Z']")) return;
		(function (w, d, s, l, i) {
			w[l] = w[l] || [];
			w[l].push({ "gtm.start": new Date().getTime(), event: "gtm.js" });
			var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l !== "dataLayer" ? "&l=" + l : "";
			j.async = true;
			j.src = "https://www.googletagmanager.com/gtm.js?id=" + i + dl;
			f.parentNode.insertBefore(j, f);
		})(window, document, "script", "dataLayer", "GTM-T8GFHP8Z");
	}
	function loadPixel() {
		if (window.__kePixelLoaded || window.fbq) return;
		window.__kePixelLoaded = true;
		if (!window.fbq) {
			(function (f, b, e, v, n, t, s) {
				if (f.fbq) return;
				n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); };
				if (!f._fbq) f._fbq = n;
				n.push = n;
				n.loaded = true;
				n.version = "2.0";
				n.queue = [];
				t = b.createElement(e);
				t.async = true;
				t.src = v;
				s = b.getElementsByTagName(e)[0];
				s.parentNode.insertBefore(t, s);
			})(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");
			window.fbq("init", "1757833078759116");
			window.fbq("track", "PageView");
		}
	}
	function showBanner() {
		if (document.getElementById("ke-consent")) return;
		var bar = document.createElement("div");
		bar.id = "ke-consent";
		bar.setAttribute("role", "dialog");
		bar.setAttribute("aria-label", "Cookie notice");
		bar.innerHTML = '<p>By continuing to use the website, you will be agreeing to our <a href="/privacy-policy">Privacy Policy</a> and <a href="/privacy-policy#cookies">Cookie Policy</a>.</p><button type="button" data-ke-consent="1">I agree</button><button type="button" data-ke-consent="0" aria-label="Close">&times;</button>';
		var style = document.createElement("style");
		style.textContent = "#ke-consent{position:fixed;z-index:10060;left:0;right:0;bottom:0;display:flex;flex-wrap:wrap;gap:14px 16px;align-items:center;justify-content:center;min-height:64px;padding:14px 56px;background:#10262c;color:#fff;border-top:1px solid rgba(245,197,24,.4);box-shadow:0 -10px 28px rgba(0,0,0,.35);font-family:Inter,Segoe UI,Arial,sans-serif}#ke-consent p{margin:0;max-width:760px;font-size:15px;line-height:1.45;text-align:center}#ke-consent a{color:#F5C518;text-decoration:underline;text-underline-offset:2px}#ke-consent [data-ke-consent='1']{flex:none;min-height:40px;padding:0 22px;border:0;border-radius:8px;background:#F5C518;color:#122327;font:700 15px/1 Inter,Segoe UI,Arial,sans-serif;cursor:pointer}#ke-consent [data-ke-consent='1']:hover{background:#ffd84a}#ke-consent [data-ke-consent='0']{position:absolute;right:12px;top:50%;transform:translateY(-50%);width:36px;height:36px;border:0;background:transparent;color:#fff;font-size:26px;line-height:1;cursor:pointer}#ke-consent [data-ke-consent='0']:hover{color:#F5C518}@media (max-width:720px){#ke-consent{padding:14px 48px 16px 16px}#ke-consent p{flex:1 1 100%}}";
		document.head.appendChild(style);
		document.body.appendChild(bar);
		bar.addEventListener("click", function (ev) {
			var btn = ev.target.closest("[data-ke-consent]");
			if (!btn) return;
			var value = btn.getAttribute("data-ke-consent");
			setConsent(value === "1" ? "1" : "0");
			if (value === "1") {
				window.location.reload();
				return;
			}
			bar.remove();
		});
	}
	loadGtm();
	if (consentValue() === "1") {
		loadPixel();
	} else if (consentValue() !== "0") {
		if (document.body) showBanner();
		else document.addEventListener("DOMContentLoaded", showBanner);
	}
})();

(function () {
	var KEY = "kuyaelyStoryNav";

	function unlockStoryNav() {
		try {
			localStorage.setItem(KEY, "1");
		} catch (e) {}
		document.documentElement.classList.add("show-story-nav");
	}

	function isUnlocked() {
		try {
			return localStorage.getItem(KEY) === "1";
		} catch (e) {
			return false;
		}
	}

	if (isUnlocked() || /(?:^|\/)our-story\.html$/i.test(window.location.pathname)) {
		unlockStoryNav();
	}

	document.addEventListener("click", function (e) {
		var link = e.target.closest("a.ke-story-btn");
		if (!link) return;
		unlockStoryNav();
	});
})();

(function () {
	var SERVICE_MAP = {
		cebu: "Cebu Tour",
		bohol: "Bohol Tour",
		siquijor: "Siquijor Tour",
		dumaguete: "Dumaguete Tour",
		van: "Car Rental",
		suv: "Car Rental",
		sedan: "Car Rental",
		coaster: "Car Rental"
	};

	function fieldText(el) {
		if (!el) return "";
		if (el.tagName === "SELECT") {
			var opt = el.options[el.selectedIndex];
			return opt ? String(opt.text || opt.value || "").trim() : "";
		}
		return String(el.value || "").trim();
	}

	function searchFromDreamit(form) {
		var active = form.querySelector(".add-bg.active") || form;
		var locationEl = active.querySelector('[name="location"], [name="children"], [name="pickup"]');
		var activityEl = active.querySelector('[name="activity"], [name="place"], [name="vehicle"]');
		var dateEl = active.querySelector('[name="arrive"]');
		var locVal = locationEl ? String(locationEl.value || "") : "";
		var actVal = activityEl ? String(activityEl.value || "") : "";
		if (locVal === "0") locVal = "";
		var tab = document.querySelector(".bokking-tabs .tab.active") || document.querySelector(".tab.active");
		var tabId = tab ? String(tab.getAttribute("data-tab") || "") : "";
		var tabLabel = tab ? String(tab.textContent || "").replace(/\s+/g, " ").trim() : "";
		if (!tabId) {
			if (/van/i.test(tabLabel)) tabId = "hotel";
			else if (/private/i.test(tabLabel)) tabId = "visa";
			else if (/tours/i.test(tabLabel)) tabId = "travel";
		}
		var q = new URLSearchParams();
		if (locVal) q.set("island", locVal);
		if (actVal) q.set("type", actVal);
		if (dateEl && dateEl.value) q.set("date", dateEl.value);
		if (tabId) q.set("tab", tabId);
		if (tabId === "hotel" && actVal) q.set("vehicle", actVal);
		window.location.href = "/search-tours.php?" + q.toString();
	}

	document.addEventListener("submit", function (e) {
		var form = e.target;
		if (!form || form.id !== "dreamit-form") return;
		e.preventDefault();
		searchFromDreamit(form);
	});

	document.addEventListener("DOMContentLoaded", function () {
		document.querySelectorAll('form[action="send-inquiry.php"]').forEach(function (form) {
			if (!form.querySelector('[name="page"]')) {
				var page = document.createElement("input");
				page.type = "hidden";
				page.name = "page";
				page.value = window.location.pathname || window.location.href;
				form.appendChild(page);
			}
			if (!form.querySelector('[name="company"]')) {
				var hp = document.createElement("input");
				hp.type = "text";
				hp.name = "company";
				hp.className = "ke-honeypot";
				hp.tabIndex = -1;
				hp.autocomplete = "off";
				hp.setAttribute("aria-hidden", "true");
				form.appendChild(hp);
			}
		});

		var params = new URLSearchParams(window.location.search);
		var serviceEl = document.getElementById("ke-service");
		var dateEl = document.getElementById("ke-date");
		var guestsEl = document.getElementById("ke-guests");
		var msgEl = document.getElementById("ke-message");
		if (serviceEl) {
			var service = params.get("Service") || params.get("service") || "";
			if (service) {
				for (var i = 0; i < serviceEl.options.length; i++) {
					if (serviceEl.options[i].value === service) {
						serviceEl.value = service;
						break;
					}
				}
			}
		}
		if (dateEl && (params.get("Travel Date") || params.get("date"))) {
			dateEl.value = params.get("Travel Date") || params.get("date");
		}
		if (guestsEl && (params.get("Guests") || params.get("group"))) {
			guestsEl.value = params.get("Guests") || params.get("group");
		}
		if (msgEl && (params.get("Message") || params.get("message")) && !msgEl.value) {
			msgEl.value = params.get("Message") || params.get("message");
		}

		var sent = params.get("sent");
		var formBox = document.querySelector(".ke-form") || document.querySelector(".sign-up");
		if (sent === "0" && formBox && !formBox.querySelector(".ke-form-banner")) {
			var banner = document.createElement("p");
			banner.className = "ke-form-banner is-err";
			banner.textContent = "The request could not be sent. Please call or WhatsApp +63 920 985 1802, or email info@kuyaelytours.com.";
			formBox.insertBefore(banner, formBox.firstChild);
		}
	});
})();
(function () {
	function todayISO() {
		var t = new Date();
		var m = String(t.getMonth() + 1).padStart(2, "0");
		var d = String(t.getDate()).padStart(2, "0");
		return t.getFullYear() + "-" + m + "-" + d;
	}

	function openDatePicker(input) {
		if (!input) return;
		input.focus();
		if (typeof input.showPicker === "function") {
			try {
				input.showPicker();
			} catch (e) {}
		}
	}

	document.addEventListener("DOMContentLoaded", function () {
		var min = todayISO();
		document.querySelectorAll('.travel-boking input[type="date"], .ke-form input[type="date"], .ke-td-book input[type="date"]').forEach(function (el) {
			el.min = min;
			el.setAttribute("autocomplete", "off");
		});
		document.querySelectorAll("form#dreamit-form .booking-button button[type='submit']").forEach(function (btn) {
			btn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Search';
		});
	});

	document.addEventListener("click", function (e) {
		var box = e.target.closest(".travel-boking .booking-input-box");
		if (!box) return;
		var input = box.querySelector('input[type="date"]');
		if (!input) return;
		openDatePicker(input);
	});
})();
(function () {
	var PRODUCT = {
		cebu: "tour-cebu",
		bohol: "tour-bohol",
		siquijor: "tour-siquijor",
		dumaguete: "tour-dumaguete",
		grandia: "van-grandia",
		commuter: "van-commuter",
		innova: "van-innova",
		fortuner: "van-fortuner",
		vios: "van-vios",
		coaster: "van-coaster",
		van: "van-grandia",
		suv: "van-fortuner",
		sedan: "van-vios",
		transfer: "transfer-airport"
	};

	function iconSvgUser() {
		return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M5 19.2c.8-3.2 3.4-5.2 7-5.2s6.2 2 7 5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
	}
	function iconSvgCart() {
		return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8h10l-.8 11.2a2 2 0 0 1-2 1.8H9.8a2 2 0 0 1-2-1.8L7 8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 8V7a3 3 0 0 1 6 0v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
	}
	function injectNav(data) {
		document.querySelectorAll(".ke-shop-link, .ke-header-icons").forEach(function (el) { el.remove(); });
		var name = (data && data.name) ? String(data.name).trim() : "";
		var logged = !!(data && (data.logged_in || name));
		var first = name.split(/\s+/)[0] || "Account";
		var accountHref = logged ? "/account/bookings.php" : "/account/login.php";
		var accountLabel = logged ? first : "Sign in";
		var count = parseInt(data && data.count, 10) || 0;
		var badge = count > 0 ? '<span class="ke-cart-count">' + count + "</span>" : "";
		document.querySelectorAll(".ke-cart-count").forEach(function (el) {
			el.textContent = String(count);
			if (count > 0) el.removeAttribute("hidden");
			else el.setAttribute("hidden", "");
		});
		document.querySelectorAll("[data-ke-account-title]").forEach(function (el) {
			el.textContent = logged ? first : "Sign In";
		});
		var menuHtml = logged
			? '<a href="/account/bookings.php">My bookings</a><a href="/account/profile.php">Profile</a><a href="/account/logout.php">Sign out</a>'
			: '<a href="/account/login.php">Sign in</a><a href="/account/register.php">Create account</a>';
		document.querySelectorAll("[data-ke-account-menu]").forEach(function (el) {
			el.innerHTML = menuHtml;
		});
		if (!document.querySelector(".ke-site-head")) {
			var icons = '<div class="ke-header-icons">'
				+ '<a class="ke-icon-btn" href="' + accountHref + '" aria-label="' + accountLabel + '" title="' + accountLabel + '">' + iconSvgUser() + "</a>"
				+ '<a class="ke-icon-btn" href="/shop/cart.php" aria-label="Cart" title="Cart">' + iconSvgCart() + badge + "</a>"
				+ "</div>";
			document.querySelectorAll(".header-right-wrapper").forEach(function (wrap) {
				var sidebar = wrap.querySelector(".header-sidebar");
				if (sidebar) sidebar.insertAdjacentHTML("beforebegin", icons);
				else wrap.insertAdjacentHTML("afterbegin", icons);
			});
		}
		document.querySelectorAll(".mobile-menu").forEach(function (bar) {
			bar.insertAdjacentHTML("beforeend", '<div class="ke-header-icons is-mobile">'
				+ '<a class="ke-icon-btn" href="' + accountHref + '" aria-label="' + accountLabel + '" title="' + accountLabel + '">' + iconSvgUser() + "</a>"
				+ '<a class="ke-icon-btn" href="/shop/cart.php" aria-label="Cart" title="Cart">' + iconSvgCart() + badge + "</a>"
				+ "</div>");
		});
	}
	function postCart(fields, next) {
		var body = new URLSearchParams();
		Object.keys(fields || {}).forEach(function (k) {
			var v = fields[k];
			if (v === undefined || v === null) return;
			if (Array.isArray(v)) {
				v.forEach(function (item) { body.append(k, String(item)); });
			} else {
				body.set(k, String(v));
			}
		});
		body.set("next", next || "/shop/cart.php");
		return fetch("/shop/add-to-cart.php", {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body: body.toString()
		}).then(function () {
			window.location.href = next || "/shop/cart.php";
		});
	}

	window.keShopReady = false; fetch("/shop/nav-data.php", { credentials: "same-origin" })
		.then(function (r) { return r.json(); })
		.then(function (data) { window.keShopReady = true;
			injectNav(data);

			function loggedIn() {
				return !!(data && (data.logged_in || data.name));
			}
			function goLogin(pending) {
				pending = pending || {};
				pending.ts = Date.now();
				try {
					sessionStorage.setItem("kePendingCart", JSON.stringify(pending));
				} catch (err) {}
				var next = window.location.pathname + window.location.search;
				window.location.href = "/account/login.php?next=" + encodeURIComponent(next || "/cebu-tour");
			}

			try {
				var raw = sessionStorage.getItem("kePendingCart");
				if (raw && loggedIn()) {
					sessionStorage.removeItem("kePendingCart");
					var pending = JSON.parse(raw);
					var fresh = pending && pending.ts && (Date.now() - pending.ts < 20 * 60 * 1000);
					if (fresh && pending.fields) {
						pending.fields.csrf = data.csrf;
						postCart(pending.fields, pending.next || "/shop/cart.php");
					}
				}
			} catch (err) {}

			var quickBook = null;
			function todayIso() {
				var d = new Date();
				var m = String(d.getMonth() + 1);
				var day = String(d.getDate());
				if (m.length < 2) m = "0" + m;
				if (day.length < 2) day = "0" + day;
				return d.getFullYear() + "-" + m + "-" + day;
			}
			function quickQty(name) {
				var el = quickBook && quickBook.querySelector('[name="' + name + '"]');
				return parseInt(el && el.value, 10) || 0;
			}
			function quickMoney(n) {
				return "₱" + Number(n || 0).toLocaleString("en-US");
			}
			function quickTier(tiers, n) {
				n = Math.max(1, n);
				for (var i = 0; i < tiers.length; i++) {
					if (n >= tiers[i].min && n <= tiers[i].max) return tiers[i];
				}
				return tiers.length ? tiers[tiers.length - 1] : null;
			}
			function refreshQuickQuote() {
				if (!quickBook) return;
				var book = quickBook._book || {};
				var tiers = Array.isArray(book.tiers) ? book.tiers : [];
				var types = ["foreign_adult", "local_adult", "foreign_child", "local_child"];
				var counts = {};
				var pax = 0;
				types.forEach(function (type) {
					counts[type] = quickQty(type);
					pax += counts[type];
				});
				var tier = quickTier(tiers, pax > 0 ? pax : 1);
				var rates = [];
				var total = 0;
				types.forEach(function (type) {
					var rate = tier ? (tier[type] || 0) : 0;
					var unit = quickBook.querySelector('[data-qb-unit="' + type + '"]');
					if (unit) unit.textContent = rate > 0 ? (quickMoney(rate) + " / pax") : "";
					if (counts[type] > 0 && rate > 0) {
						total += counts[type] * rate;
						if (rates.indexOf(rate) < 0) rates.push(rate);
					}
				});
				var fromEl = quickBook.querySelector(".ke-qb-from");
				if (fromEl) {
					fromEl.textContent = book.from > 0 ? ("From " + quickMoney(book.from) + " / pax") : "";
				}
				var perEl = quickBook.querySelector(".ke-qb-perpax");
				var totalEl = quickBook.querySelector(".ke-qb-total");
				if (pax < 1) {
					if (perEl) perEl.textContent = book.from > 0 ? (quickMoney(book.from) + " / pax") : "—";
					if (totalEl) totalEl.textContent = "—";
					return;
				}
				var per = rates.length === 1 ? rates[0] : Math.round(total / pax);
				if (perEl) perEl.textContent = quickMoney(per) + " / pax";
				if (totalEl) totalEl.textContent = quickMoney(total);
			}
			function ensureQuickBook() {
				if (quickBook) return quickBook;
				if (!document.getElementById("ke-quick-book-css")) {
					var style = document.createElement("style");
					style.id = "ke-quick-book-css";
					style.textContent = ""
						+ "dialog#ke-quick-book{width:min(880px,calc(100vw - 48px))!important;max-height:none!important;margin:auto!important;padding:0!important;border:0!important;border-radius:20px!important;background:#10262c!important;color:#fff!important;overflow:hidden!important;box-shadow:0 28px 70px rgba(0,0,0,.5)!important}"
						+ "dialog#ke-quick-book::backdrop{background:rgba(6,16,18,.45)!important;backdrop-filter:blur(10px)!important;-webkit-backdrop-filter:blur(10px)!important}"
						+ "dialog#ke-quick-book .ke-bookbox{margin:0!important;border:0!important;border-radius:20px!important;background:#10262c!important;color:#fff!important;overflow:hidden!important}"
						+ "dialog#ke-quick-book .ke-bookbox,dialog#ke-quick-book .ke-bookbox *{font-family:Inter,Segoe UI,Arial,sans-serif!important;letter-spacing:0!important;text-transform:none!important;box-sizing:border-box}"
						+ "dialog#ke-quick-book .ke-qb-top{display:flex!important;align-items:flex-start!important;justify-content:space-between!important;gap:16px!important;padding:20px 22px 16px!important;border-bottom:1px solid rgba(245,197,24,.22)!important}"
						+ "dialog#ke-quick-book .ke-qb-top p{margin:4px 0 0!important;color:rgba(255,255,255,.72)!important;font-size:13px!important;font-weight:500!important;line-height:1.4!important}"
						+ "dialog#ke-quick-book .ke-qb-top .ke-bookbox-from{margin:0!important;color:#fff!important;font-size:22px!important;font-weight:700!important;line-height:1.25!important}"
						+ "dialog#ke-quick-book .ke-qb-from{margin:6px 0 0!important;color:#F5C518!important;font-size:18px!important;font-weight:700!important;line-height:1.2!important}"
						+ "dialog#ke-quick-book .ke-qb-close{flex:0 0 auto!important;width:36px!important;height:36px!important;border-radius:50%!important;border:1px solid rgba(245,197,24,.5)!important;background:#0c1f24!important;color:#F5C518!important;font-size:22px!important;line-height:1!important;cursor:pointer!important;padding:0!important}"
						+ "dialog#ke-quick-book .ke-bookbox-form{display:block!important;padding:18px 22px 22px!important;background:transparent!important}"
						+ "dialog#ke-quick-book .ke-qb-cols{display:grid!important;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr)!important;gap:16px!important;align-items:start!important}"
						+ "dialog#ke-quick-book .ke-qb-side,dialog#ke-quick-book .ke-qb-guests{background:rgba(255,255,255,.04)!important;border:1px solid rgba(245,197,24,.2)!important;border-radius:16px!important}"
						+ "dialog#ke-quick-book .ke-qb-side{padding:16px 16px 4px!important}"
						+ "dialog#ke-quick-book .ke-qb-guests{padding:4px 16px!important}"
						+ "dialog#ke-quick-book .ke-bookbox-field{display:flex!important;flex-direction:column!important;gap:6px!important;margin:0 0 14px!important;width:100%!important}"
						+ "dialog#ke-quick-book .ke-bookbox-field span{display:block!important;color:rgba(255,255,255,.72)!important;font-size:13px!important;font-weight:600!important}"
						+ "dialog#ke-quick-book .ke-bookbox-field select,dialog#ke-quick-book .ke-bookbox-field input[type=date]{display:block!important;width:100%!important;min-height:46px!important;height:46px!important;border-radius:10px!important;border:1px solid rgba(255,255,255,.16)!important;background:#0c1f24!important;color:#fff!important;padding:0 12px!important;color-scheme:dark}"
						+ "dialog#ke-quick-book .ke-bookbox-guest{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:12px!important;width:100%!important;padding:11px 0!important;border-top:1px solid rgba(255,255,255,.08)!important}"
						+ "dialog#ke-quick-book .ke-bookbox-guest:first-child{border-top:0!important}"
						+ "dialog#ke-quick-book .ke-bookbox-guest strong{display:block!important;color:#fff!important;font-size:15px!important;font-weight:700!important}"
						+ "dialog#ke-quick-book .ke-bookbox-guest small{display:block!important;color:#F5C518!important;font-size:12px!important;margin-top:2px!important}"
						+ "dialog#ke-quick-book .ke-step{display:flex!important;align-items:center!important;gap:8px!important;flex:0 0 auto!important}"
						+ "dialog#ke-quick-book .ke-step button{width:34px!important;height:34px!important;min-height:0!important;border-radius:50%!important;border:1px solid #F5C518!important;background:transparent!important;color:#F5C518!important;font-size:20px!important;line-height:1!important;padding:0!important;cursor:pointer!important}"
						+ "dialog#ke-quick-book .ke-step input{width:36px!important;height:auto!important;min-height:0!important;border:0!important;background:transparent!important;color:#fff!important;text-align:center!important;font-size:16px!important;font-weight:700!important;padding:0!important}"
						+ "dialog#ke-quick-book .ke-bookbox-unit{display:block!important;margin-top:3px!important;color:#fff!important;font-size:12px!important;font-style:normal!important;font-weight:600!important}"
						+ "dialog#ke-quick-book .ke-qb-sum{display:grid!important;grid-template-columns:1fr 1fr!important;gap:10px!important;margin:16px 0 0!important;padding:0!important;background:transparent!important;border:0!important}"
						+ "dialog#ke-quick-book .ke-qb-sum p{display:flex!important;justify-content:space-between!important;align-items:center!important;gap:12px!important;margin:0!important;padding:12px 14px!important;border-radius:12px!important;background:rgba(245,197,24,.08)!important;border:1px solid rgba(245,197,24,.22)!important;color:#fff!important;font-size:14px!important;font-weight:600!important}"
						+ "dialog#ke-quick-book .ke-qb-sum strong{color:#F5C518!important;font-weight:700!important;font-size:16px!important}"
						+ "dialog#ke-quick-book .ke-qb-error{margin:10px 0 0!important;color:#ffb4b4!important;font-size:14px!important}"
						+ "dialog#ke-quick-book .ke-bookbox-book,dialog#ke-quick-book .ke-bookbox-cart{display:block!important;width:100%!important;min-height:50px!important;margin-top:14px!important;border-radius:12px!important;font-size:16px!important;font-weight:700!important;cursor:pointer!important}"
						+ "dialog#ke-quick-book .ke-bookbox-book{background:#F5C518!important;color:#122327!important;border:0!important}"
						+ "dialog#ke-quick-book .ke-bookbox-cart{background:transparent!important;color:#F5C518!important;border:1px solid #F5C518!important}"
						+ "@media (max-width:767px){dialog#ke-quick-book{width:min(420px,calc(100vw - 24px))!important;max-height:min(92vh,820px)!important;overflow:auto!important}dialog#ke-quick-book .ke-qb-cols{display:flex!important;flex-direction:column!important}dialog#ke-quick-book .ke-qb-sum{grid-template-columns:1fr!important}}";
					document.head.appendChild(style);
				}
				quickBook = document.createElement("dialog");
				quickBook.id = "ke-quick-book";
				quickBook.className = "ke-quick-book";
				quickBook.innerHTML = ''
					+ '<div class="ke-bookbox">'
					+ '<div class="ke-qb-top"><div><p class="ke-bookbox-from"></p><p class="ke-qb-from"></p><p>Choose a date and how many guests.</p></div>'
					+ '<button type="button" class="ke-qb-close" data-ke-qb-close aria-label="Close">×</button></div>'
					+ '<form class="ke-bookbox-form">'
					+ '<div class="ke-qb-cols">'
					+ '<div class="ke-qb-side">'
					+ '<label class="ke-bookbox-field ke-qb-pickup"><span>Select Pickup Location</span><select name="pickup"></select></label>'
					+ '<label class="ke-bookbox-field ke-qb-date"><span>Booking Date</span><input type="date" name="date" required></label>'
					+ '</div>'
					+ '<div class="ke-qb-guests"></div>'
					+ '</div>'
					+ '<div class="ke-qb-sum"><p>Per pax <strong class="ke-qb-perpax">—</strong></p><p>Total <strong class="ke-qb-total">—</strong></p></div>'
					+ '<p class="ke-qb-error" hidden></p>'
					+ '<button type="submit" class="ke-bookbox-book"></button>'
					+ '</form></div>';
				document.body.appendChild(quickBook);
				quickBook.addEventListener("click", function (ev) {
					var step = ev.target.closest("[data-qb-step]");
					if (step) {
						ev.preventDefault();
						var input = quickBook.querySelector('[name="' + step.getAttribute("data-qb-step") + '"]');
						if (!input) return;
						var v = parseInt(input.value, 10) || 0;
						v += step.getAttribute("data-dir") === "-" ? -1 : 1;
						if (v < 0) v = 0;
						if (v > 30) v = 30;
						input.value = String(v);
						refreshQuickQuote();
						return;
					}
					if (ev.target === quickBook || ev.target.closest("[data-ke-qb-close]")) {
						ev.preventDefault();
						quickBook.close();
					}
				});
				quickBook.querySelector("form").addEventListener("submit", function (ev) {
					ev.preventDefault();
					var meta = quickBook._meta || {};
					var err = quickBook.querySelector(".ke-qb-error");
					var date = (quickBook.querySelector('[name="date"]') || {}).value || "";
					var counts = {
						foreign_adult: quickQty("foreign_adult"),
						local_adult: quickQty("local_adult"),
						foreign_child: quickQty("foreign_child"),
						local_child: quickQty("local_child")
					};
					var pax = counts.foreign_adult + counts.local_adult + counts.foreign_child + counts.local_child;
					if (!date) {
						if (err) { err.hidden = false; err.textContent = "Choose a booking date."; }
						var dateInput = quickBook.querySelector('[name="date"]');
						if (dateInput) dateInput.focus();
						return;
					}
					if (pax < 1) {
						if (err) { err.hidden = false; err.textContent = "Select at least one guest."; }
						return;
					}
					if (err) err.hidden = true;
					var pickupEl = quickBook.querySelector('[name="pickup"]');
					postCart({
						csrf: data.csrf,
						product_id: meta.product || "tour-cebu",
						package_slug: meta.slug || "",
						date: date,
						arrive: date,
						pickup: pickupEl ? pickupEl.value : "",
						foreign_adult: counts.foreign_adult,
						local_adult: counts.local_adult,
						foreign_child: counts.foreign_child,
						local_child: counts.local_child,
						guests: pax,
						notes: meta.notes || ""
					}, meta.next || "/shop/cart.php");
				});
				return quickBook;
			}
			function openQuickBook(btn, card) {
				var book = {};
				try { book = JSON.parse(card.getAttribute("data-ke-book") || "{}"); } catch (err) { book = {}; }
				var dlg = ensureQuickBook();
				var checkout = !!btn.getAttribute("data-ke-next");
				dlg._meta = {
					product: btn.getAttribute("data-ke-product") || "tour-cebu",
					slug: book.slug || "",
					notes: btn.getAttribute("data-ke-notes") || "",
					next: btn.getAttribute("data-ke-next") || "/shop/cart.php"
				};
				dlg._book = book;
				var title = dlg.querySelector(".ke-bookbox-from");
				if (title) title.textContent = btn.getAttribute("data-ke-notes") || "Book this tour";
				var go = dlg.querySelector(".ke-bookbox-book, .ke-bookbox-cart");
				if (go) {
					go.textContent = checkout ? "Book now" : "Add to cart";
					go.className = checkout ? "ke-bookbox-book" : "ke-bookbox-cart";
				}
				var err = dlg.querySelector(".ke-qb-error");
				if (err) { err.hidden = true; err.textContent = ""; }
				var dateInput = dlg.querySelector('[name="date"]');
				if (dateInput) {
					dateInput.min = todayIso();
					dateInput.value = "";
				}
				var pickupWrap = dlg.querySelector(".ke-qb-pickup");
				var pickup = dlg.querySelector('[name="pickup"]');
				var stops = Array.isArray(book.pickups) ? book.pickups : [];
				if (pickup) pickup.innerHTML = "";
				if (pickupWrap) pickupWrap.hidden = stops.length < 1;
				stops.forEach(function (stop) {
					var opt = document.createElement("option");
					opt.value = stop;
					opt.textContent = stop;
					pickup.appendChild(opt);
				});
				var guests = dlg.querySelector(".ke-qb-guests");
				var rows = book.split === false ? [
					["foreign_adult", "Adult", book.ageAdult || "5 years old & above"],
					["foreign_child", "Child", book.ageChild || "Below 5 years old"]
				] : [
					["foreign_adult", "Foreign Adult", book.ageAdult || "5 years old & above"],
					["local_adult", "Local Adult", book.ageAdult || "5 years old & above"],
					["foreign_child", "Foreign Child", book.ageChild || "Below 5 years old"],
					["local_child", "Local Child", book.ageChild || "Below 5 years old"]
				];
				guests.innerHTML = "";
				rows.forEach(function (row) {
					var line = document.createElement("div");
					line.className = "ke-bookbox-guest";
					var copy = document.createElement("div");
					var strong = document.createElement("strong");
					strong.textContent = row[1];
					var small = document.createElement("small");
					small.textContent = row[2];
					copy.appendChild(strong);
					copy.appendChild(small);
					var unit = document.createElement("em");
					unit.className = "ke-bookbox-unit";
					unit.setAttribute("data-qb-unit", row[0]);
					copy.appendChild(unit);
					var step = document.createElement("div");
					step.className = "ke-step";
					var minus = document.createElement("button");
					minus.type = "button";
					minus.setAttribute("data-qb-step", row[0]);
					minus.setAttribute("data-dir", "-");
					minus.textContent = "−";
					var input = document.createElement("input");
					input.type = "text";
					input.name = row[0];
					input.value = "0";
					input.readOnly = true;
					input.setAttribute("inputmode", "numeric");
					var plus = document.createElement("button");
					plus.type = "button";
					plus.setAttribute("data-qb-step", row[0]);
					plus.setAttribute("data-dir", "+");
					plus.textContent = "+";
					step.appendChild(minus);
					step.appendChild(input);
					step.appendChild(plus);
					line.appendChild(copy);
					line.appendChild(step);
					guests.appendChild(line);
				});
				refreshQuickQuote();
				if (dlg.showModal) dlg.showModal();
				if (dateInput) dateInput.focus();
			}

			document.addEventListener("click", function (e) {
				var btn = e.target.closest("[data-ke-cart]");
				if (!btn) return;
				e.preventDefault();
				var form = btn.closest("form") || document.getElementById("dreamit-form");
				var next = btn.getAttribute("data-ke-next") || "/shop/cart.php";
				var fields;
				var bookCard = btn.closest("[data-ke-book]");
				if (bookCard && !(form && form.classList.contains("ke-bookbox-form"))) {
					openQuickBook(btn, bookCard);
					return;
				}
				if (form && form.classList.contains("ke-bookbox-form")) {
					fields = { csrf: data.csrf };
					var fd = new FormData(form);
					fd.forEach(function (v, k) {
						if (k === "addons[]" || k === "addons") {
							if (!fields["addons[]"]) fields["addons[]"] = [];
							fields["addons[]"].push(v);
						} else {
							fields[k] = v;
						}
					});
					var pax = (parseInt(fields.foreign_adult, 10) || 0)
						+ (parseInt(fields.local_adult, 10) || 0)
						+ (parseInt(fields.foreign_child, 10) || 0)
						+ (parseInt(fields.local_child, 10) || 0);
					if (pax < 1) {
						window.alert("Select at least one guest.");
						return;
					}
					if (!fields.date && fields.arrive) {
						fields.date = fields.arrive;
					}
					if (!fields.date) {
						window.alert("Choose a booking date first.");
						var dateInput = form.querySelector('[name="arrive"]');
						if (dateInput) dateInput.focus();
						return;
					}
				} else {
					var dateEl = form ? form.querySelector('[name="arrive"]') : null;
					var groupEl = form ? form.querySelector('[name="group"]') : null;
					var guests = "2";
					if (groupEl && groupEl.value && groupEl.value !== "0") {
						guests = /^\d+$/.test(groupEl.value) ? groupEl.value : String(groupEl.value).split("-")[0];
					}
					fields = {
						csrf: data.csrf,
						product_id: btn.getAttribute("data-ke-product") || "tour-cebu",
						date: dateEl ? dateEl.value : "",
						guests: guests,
						vehicle: "",
						notes: btn.getAttribute("data-ke-notes") || ""
					};
				}
				postCart(fields, next);
			});
		})
		.catch(function () {});
})();

(function () {
	if (/^\/admin(\/|$)/i.test(location.pathname)) {
		return;
	}
	var s = document.createElement("script");
	s.src = "/assets/js/kuyaely-chat.js?v=7";
	s.defer = true;
	(document.head || document.documentElement).appendChild(s);
})();

(function () {
	function normPath(p) {
		p = String(p || "/").split("?")[0].split("#")[0];
		if (p.length > 1 && p.slice(-1) === "/") {
			p = p.slice(0, -1);
		}
		p = p.replace(/\.html$/i, "");
		if (p === "/index" || p === "") {
			p = "/";
		}
		return p || "/";
	}

	function mark() {
		var path = normPath(location.pathname);
		document.querySelectorAll(".header-menu > ul.nav_scroll > li > a").forEach(function (a) {
			var href = a.getAttribute("href") || "";
			var parent = a.parentElement;
			var on = false;
			if (parent && parent.classList.contains("nav-more-dropdown")) {
				on = path === "/galary" || path === "/permits";
			} else {
				var abs;
				try {
					abs = normPath(new URL(href, location.origin).pathname);
				} catch (e) {
					return;
				}
				if (abs === "/") {
					on = path === "/";
				} else if (abs === "/about") {
					on = path === "/about" || path === "/our-story";
				} else if (abs.indexOf("tours-and-packages") !== -1) {
					on = path.indexOf("/tours") === 0 || /\/(cebu|bohol|siquijor|dumaguete)-tour$/.test(path);
				} else if (abs === "/service") {
					on = path === "/service";
				} else if (abs === "/contact") {
					on = path === "/contact";
				} else {
					on = path === abs;
				}
			}
			if (on) {
				a.setAttribute("aria-current", "page");
				if (parent) {
					parent.classList.add("is-current");
				}
			}
		});
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", mark);
	} else {
		mark();
	}
})();

(function () {
	if (/^\/admin(\/|$)/i.test(location.pathname)) return;

	var earlyLang = "";
	var earlyMatch = document.cookie.match(/(?:^|; )googtrans=(?:\/en\/)([^;]+)/);
	if (earlyMatch && earlyMatch[1]) earlyLang = decodeURIComponent(earlyMatch[1]);
	if (!earlyLang) {
		try { earlyLang = localStorage.getItem("ke_lang") || "en"; } catch (e) { earlyLang = "en"; }
	}
	var lineStyle = document.createElement("style");
	lineStyle.textContent = ".ke-line{display:block}";
	document.head.appendChild(lineStyle);
	if (earlyLang && earlyLang !== "en") {
		document.documentElement.classList.add("ke-i18n");
		window.keI18n = true;
		var i18nStyle = document.createElement("style");
		i18nStyle.textContent = "html.ke-i18n .goog-text-highlight,html.ke-i18n font{background:transparent!important;box-shadow:none!important}html.ke-i18n .hero-section-1{height:auto!important;min-height:0!important}html.ke-i18n .hero-section-1 .hero_content h1{font-size:clamp(40px,7vw,72px)!important;line-height:1.2!important}html.ke-i18n body.ke-home .ke-mhero h1,html.ke-i18n body.ke-home .ke-mwhy h2,html.ke-i18n .section-title h1,html.ke-i18n h1.footer-sing-up-title,html.ke-i18n .hero-section-1 .hero-journey-box h2{line-height:1.25!important;text-transform:none}html.ke-i18n body.ke-home .ke-mhero h1{font-size:clamp(28px,8vw,40px)!important}html.ke-i18n body.ke-home .hero-wrapper{min-height:0!important}html.ke-i18n body.ke-home .hero-thumb{position:relative!important;top:auto!important;left:auto!important;transform:none!important;margin:8px auto 0!important}html.ke-i18n body.ke-home .ke-mhome{margin-top:0!important}html.ke-i18n body.ke-home .ke-mhero-script{position:static!important;transform:none!important;display:block;margin:8px 18px!important;text-align:left!important}html.ke-i18n .brand-name,html.ke-i18n .brand-sub,html.ke-i18n .ke-book-btn,html.ke-i18n .header-btn a,html.ke-i18n .hero-btn a,html.ke-i18n .defult-btn a,html.ke-i18n .ke-mfind-btn,html.ke-i18n .form-input-bx button,html.ke-i18n .ke-dot-copy{white-space:normal!important}html.ke-i18n .form-input-bx button{position:static!important;width:100%;height:auto!important;min-height:48px;margin-top:8px;border-radius:50px!important;padding:12px 16px!important}html.ke-i18n .ke-dot-banner,html.ke-i18n .ke-dot-banner--mob{height:auto!important;border-radius:16px!important}html.ke-i18n .split-line,html.ke-i18n .text-anime-3 div,html.ke-i18n .text-effect div{display:inline!important;position:static!important;transform:none!important;opacity:1!important;overflow:visible!important}html.ke-i18n .ke-line{display:block}";
		document.head.appendChild(i18nStyle);
	}

	var CURRENCIES = [
		{ code: "PHP", name: "Philippine peso", php: 1, symbol: "₱", digits: 0 },
		{ code: "USD", name: "US dollar", php: 58, symbol: "$", digits: 2 },
		{ code: "EUR", name: "Euro", php: 63, symbol: "€", digits: 2 },
		{ code: "GBP", name: "British pound", php: 75, symbol: "£", digits: 2 },
		{ code: "AUD", name: "Australian dollar", php: 38, symbol: "A$", digits: 2 },
		{ code: "SGD", name: "Singapore dollar", php: 44, symbol: "S$", digits: 2 },
		{ code: "CNY", name: "Chinese yuan", php: 8.1, symbol: "CN¥", digits: 0 },
		{ code: "TWD", name: "Taiwan dollar", php: 1.85, symbol: "NT$", digits: 0 },
		{ code: "JPY", name: "Japanese yen", php: 0.39, symbol: "¥", digits: 0 },
		{ code: "KRW", name: "Korean won", php: 0.042, symbol: "₩", digits: 0 }
	];
	var LANGS = [
		{ code: "en", short: "EN", name: "English" },
		{ code: "ko", short: "KO", name: "한국어" },
		{ code: "zh-CN", short: "ZH", name: "中文" },
		{ code: "ja", short: "JA", name: "日本語" },
		{ code: "tl", short: "FIL", name: "Filipino" }
	];
	var priceRe = /₱\s?(\d{1,3}(?:,\d{3})+|\d+)(?:\.\d{1,2})?/g;
	var applying = false;

	function stored(key, fallback) {
		try { return localStorage.getItem(key) || fallback; } catch (e) { return fallback; }
	}
	function save(key, value) {
		try { localStorage.setItem(key, value); } catch (e) {}
	}
	function currency() {
		var code = stored("ke_cur", "PHP");
		for (var i = 0; i < CURRENCIES.length; i++) {
			if (CURRENCIES[i].code === code) return CURRENCIES[i];
		}
		return CURRENCIES[0];
	}
	function language() {
		var match = document.cookie.match(/(?:^|; )googtrans=(?:\/en\/)([^;]+)/);
		if (match && match[1] && match[1] !== "en") return decodeURIComponent(match[1]);
		return stored("ke_lang", "en");
	}
	function langMeta(code) {
		for (var i = 0; i < LANGS.length; i++) {
			if (LANGS[i].code === code) return LANGS[i];
		}
		return LANGS[0];
	}
	function formatMoney(pesos, cur) {
		var n = pesos / cur.php;
		var text = n.toLocaleString("en-US", {
			minimumFractionDigits: cur.digits,
			maximumFractionDigits: cur.digits
		});
		return cur.symbol + text;
	}
	function renderMoney() {
		var cur = currency();
		applying = true;
		document.querySelectorAll("[data-ke-php]").forEach(function (el) {
			var pesos = parseFloat(el.getAttribute("data-ke-php")) || 0;
			el.textContent = formatMoney(pesos, cur);
		});
		var note = document.getElementById("ke-cur-note");
		if (cur.code === "PHP") {
			if (note) note.remove();
		} else if (!note) {
			note = document.createElement("p");
			note.id = "ke-cur-note";
			note.textContent = "Prices in " + cur.code + " are approximate. You pay in Philippine pesos.";
			var main = document.querySelector("main") || document.body;
			main.insertBefore(note, main.firstChild);
		} else {
			note.textContent = "Prices in " + cur.code + " are approximate. You pay in Philippine pesos.";
		}
		applying = false;
	}
	function scan(root) {
		if (!root || applying) return;
		applying = true;
		var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
			acceptNode: function (node) {
				if (!node.nodeValue || node.nodeValue.indexOf("₱") === -1) return NodeFilter.FILTER_REJECT;
				var parent = node.parentElement;
				if (!parent || parent.closest("script, style, textarea, [data-ke-php], [data-ke-picker]")) return NodeFilter.FILTER_REJECT;
				return NodeFilter.FILTER_ACCEPT;
			}
		});
		var nodes = [];
		while (walker.nextNode()) nodes.push(walker.currentNode);
		nodes.forEach(function (node) {
			var text = node.nodeValue || "";
			var parent = node.parentNode;
			if (!parent) return;
			var frag = document.createDocumentFragment();
			var last = 0;
			var match;
			priceRe.lastIndex = 0;
			while ((match = priceRe.exec(text))) {
				if (match.index > last) frag.appendChild(document.createTextNode(text.slice(last, match.index)));
				var span = document.createElement("span");
				span.setAttribute("data-ke-php", String(parseFloat(match[1].replace(/,/g, "")) || 0));
				span.textContent = match[0];
				frag.appendChild(span);
				last = match.index + match[0].length;
			}
			if (last === 0) return;
			if (last < text.length) frag.appendChild(document.createTextNode(text.slice(last)));
			parent.replaceChild(frag, node);
		});
		applying = false;
		renderMoney();
	}
	function fillMenu(details, items, current, onPick) {
		var box = details.querySelector("div");
		var summary = details.querySelector("summary");
		if (!box || !summary) return;
		details.classList.add("notranslate");
		details.setAttribute("translate", "no");
		box.innerHTML = "";
		items.forEach(function (item) {
			var btn = document.createElement("button");
			btn.type = "button";
			btn.textContent = item.label;
			if (item.code === current) btn.setAttribute("aria-current", "true");
			btn.addEventListener("click", function (e) {
				e.preventDefault();
				onPick(item);
				details.removeAttribute("open");
			});
			box.appendChild(btn);
		});
		summary.textContent = items.filter(function (item) { return item.code === current; })[0]
			? items.filter(function (item) { return item.code === current; })[0].short
			: current;
	}
	function setLanguage(code) {
		var host = location.hostname.replace(/^www\./, "");
		function write(value, age) {
			var base = "googtrans=" + value + "; Path=/; Max-Age=" + age + "; SameSite=Lax";
			document.cookie = base;
			if (host.indexOf(".") > 0) document.cookie = base + "; Domain=." + host;
		}
		if (code === "en") {
			write("", 0);
		} else {
			write("/en/" + code, 31536000);
		}
		save("ke_lang", code);
		location.reload();
	}
	function loadTranslate(code) {
		if (code === "en" || window.google && window.google.translate) return;
		var box = document.createElement("div");
		box.id = "google_translate_element";
		box.hidden = true;
		document.body.appendChild(box);
		window.googleTranslateElementInit = function () {
			new window.google.translate.TranslateElement({
				pageLanguage: "en",
				includedLanguages: "en,ko,zh-CN,ja,tl",
				autoDisplay: false
			}, "google_translate_element");
		};
		var script = document.createElement("script");
		script.src = "https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit";
		document.body.appendChild(script);
	}
	function boot() {
		var style = document.createElement("style");
		style.textContent = ".ke-mini-dd{position:relative;display:inline-block;vertical-align:middle}.ke-mini-dd>summary{list-style:none;cursor:pointer;color:#fff;font:700 12px/1 Inter,Segoe UI,Arial,sans-serif;letter-spacing:.04em}.ke-mini-dd>summary::-webkit-details-marker{display:none}.ke-mini-dd>summary::after{content:\"\\25BE\";margin-left:4px;font-size:9px;opacity:.8}.ke-mini-dd>div{display:none;position:absolute;right:0;top:calc(100% + 6px);min-width:168px;padding:6px;background:#1C2D31;color:#fff;border:1px solid rgba(245,197,24,.28);border-radius:8px;box-shadow:0 10px 24px rgba(0,0,0,.35);z-index:40}.ke-mini-dd[open]>div{display:block}.ke-mini-dd>div button{display:block;width:100%;text-align:left;background:transparent;border:0;color:#fff;font:600 13px/1.3 Inter,Segoe UI,Arial,sans-serif;padding:7px 8px;border-radius:6px;cursor:pointer}.ke-mini-dd>div button[aria-current=true],.ke-mini-dd>div button:hover{background:rgba(245,197,24,.16);color:#F5C518}.ke-cur-hint{margin:4px 8px 6px;color:rgba(255,255,255,.72);font:500 11px/1.3 Inter,Segoe UI,Arial,sans-serif}#ke-cur-note{margin:0;padding:8px 16px;background:#10262c;color:#F5C518;font:600 13px/1.4 Inter,Segoe UI,Arial,sans-serif;text-align:center}.goog-te-banner-frame,.skiptranslate,iframe.skiptranslate,#goog-gt-tt,.goog-te-balloon-frame{display:none!important}body{top:0!important}";
		document.head.appendChild(style);

		var moneyHost = document.querySelector("[data-ke-picker='money']");
		var langHost = document.querySelector("[data-ke-picker='lang']");
		document.querySelectorAll(".ke-mini-dd").forEach(function (el) {
			var summary = el.querySelector("summary");
			var label = summary ? summary.textContent.replace(/\s+/g, "") : "";
			if (!moneyHost && label === "PHP") moneyHost = el;
			if (!langHost && label === "EN") langHost = el;
		});
		var slot = document.querySelector(".ke-topbar-right, .ke-gnav-actions, .ke-shop-icons");
		if (slot && !moneyHost) {
			slot.insertAdjacentHTML("beforeend", '<details class="ke-mini-dd" data-ke-picker="money"><summary>PHP</summary><div></div></details>');
			moneyHost = slot.querySelector("[data-ke-picker='money']");
		}
		if (slot && !langHost) {
			slot.insertAdjacentHTML("beforeend", '<details class="ke-mini-dd" data-ke-picker="lang"><summary>EN</summary><div></div></details>');
			langHost = slot.querySelector("[data-ke-picker='lang']");
		}
		function payPage() {
			return /\/shop\/(checkout|pay|confirm|pay-return)(?:\.php)?(?:\/|$)/i.test(location.pathname);
		}
		function moneyItems() {
			return CURRENCIES.map(function (item) {
				return { code: item.code, short: item.code, label: item.code + " · " + item.name };
			});
		}
		function mountMoney(host) {
			fillMenu(host, moneyItems(), currency().code, function (item) {
				save("ke_cur", item.code);
				mountMoney(host);
				if (payPage()) {
					var existing = document.getElementById("ke-cur-note");
					if (item.code === "PHP") {
						if (existing) existing.remove();
					} else if (!existing) {
						var note = document.createElement("p");
						note.id = "ke-cur-note";
						note.textContent = "This page stays in Philippine pesos, which is the amount you pay.";
						var main = document.querySelector("main") || document.body;
						main.insertBefore(note, main.firstChild);
					}
				} else {
					renderMoney();
				}
			});
			var box = host.querySelector("div");
			if (box) {
				var hint = document.createElement("p");
				hint.className = "ke-cur-hint";
				hint.textContent = "Approximate. You pay in PHP.";
				box.appendChild(hint);
			}
		}
		if (moneyHost) {
			moneyHost.setAttribute("data-ke-picker", "money");
			mountMoney(moneyHost);
		}
		var lang = language();
		if (langHost) {
			langHost.setAttribute("data-ke-picker", "lang");
			fillMenu(langHost, LANGS.map(function (item) {
				return { code: item.code, short: item.short, label: item.name };
			}), lang, function (item) {
				setLanguage(item.code);
			});
		}
		if (!payPage()) {
			scan(document.body);
			var timer = 0;
			var observer = new MutationObserver(function (records) {
				if (applying) return;
				var nodes = [];
				records.forEach(function (rec) {
					rec.addedNodes.forEach(function (node) {
						if (node.nodeType !== 1) return;
						if (node.closest && node.closest("font, .skiptranslate, #google_translate_element")) return;
						nodes.push(node);
					});
				});
				if (!nodes.length) return;
				clearTimeout(timer);
				timer = setTimeout(function () {
					nodes.forEach(function (node) { if (node.isConnected) scan(node); });
				}, 180);
			});
			observer.observe(document.body, { childList: true, subtree: true });
		} else if (currency().code !== "PHP") {
			var payNote = document.createElement("p");
			payNote.id = "ke-cur-note";
			payNote.textContent = "This page stays in Philippine pesos, which is the amount you pay.";
			var payMain = document.querySelector("main") || document.body;
			payMain.insertBefore(payNote, payMain.firstChild);
		}
		loadTranslate(lang);
	}
	if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
	else boot();
})();
