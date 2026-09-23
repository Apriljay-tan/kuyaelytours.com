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
			function ensureQuickBook() {
				if (quickBook) return quickBook;
				if (!document.getElementById("ke-quick-book-css")) {
					var style = document.createElement("style");
					style.id = "ke-quick-book-css";
					style.textContent = ""
						+ "dialog#ke-quick-book{width:min(420px,calc(100vw - 28px))!important;max-height:min(92vh,820px)!important;margin:auto!important;padding:0!important;border:0!important;border-radius:18px!important;background:#10262c!important;color:#fff!important;overflow:auto!important;box-shadow:0 24px 60px rgba(0,0,0,.45)!important}"
						+ "dialog#ke-quick-book::backdrop{background:rgba(6,16,18,.72)!important}"
						+ "dialog#ke-quick-book .ke-bookbox{margin:0!important;border:0!important;border-radius:18px!important;background:#10262c!important;color:#fff!important;overflow:hidden!important}"
						+ "dialog#ke-quick-book .ke-bookbox,dialog#ke-quick-book .ke-bookbox *{font-family:Inter,Segoe UI,Arial,sans-serif!important;letter-spacing:0!important;text-transform:none!important;box-sizing:border-box}"
						+ "dialog#ke-quick-book .ke-qb-top{display:flex!important;align-items:flex-start!important;justify-content:space-between!important;gap:12px!important;padding:18px 18px 6px!important}"
						+ "dialog#ke-quick-book .ke-qb-top p{margin:6px 0 0!important;color:rgba(255,255,255,.7)!important;font-size:13px!important;font-weight:500!important}"
						+ "dialog#ke-quick-book .ke-qb-top .ke-bookbox-from{margin:0!important;color:#F5C518!important;font-size:22px!important;font-weight:700!important;line-height:1.2!important}"
						+ "dialog#ke-quick-book .ke-qb-close{flex:0 0 auto!important;width:36px!important;height:36px!important;border-radius:50%!important;border:1px solid rgba(245,197,24,.5)!important;background:transparent!important;color:#F5C518!important;font-size:22px!important;line-height:1!important;cursor:pointer!important;padding:0!important}"
						+ "dialog#ke-quick-book .ke-bookbox-form{display:flex!important;flex-direction:column!important;gap:0!important;padding:8px 18px 18px!important;background:transparent!important}"
						+ "dialog#ke-quick-book .ke-bookbox-field{display:flex!important;flex-direction:column!important;gap:6px!important;margin:0 0 12px!important;width:100%!important}"
						+ "dialog#ke-quick-book .ke-bookbox-field span{display:block!important;color:rgba(255,255,255,.72)!important;font-size:13px!important}"
						+ "dialog#ke-quick-book .ke-bookbox-field select,dialog#ke-quick-book .ke-bookbox-field input[type=date]{display:block!important;width:100%!important;min-height:46px!important;height:46px!important;border-radius:10px!important;border:1px solid rgba(255,255,255,.16)!important;background:#0c1f24!important;color:#fff!important;padding:0 12px!important;color-scheme:dark}"
						+ "dialog#ke-quick-book .ke-bookbox-guest{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:12px!important;width:100%!important;padding:12px 0!important;border-top:1px solid rgba(255,255,255,.08)!important}"
						+ "dialog#ke-quick-book .ke-bookbox-guest strong{display:block!important;color:#fff!important;font-size:15px!important;font-weight:700!important}"
						+ "dialog#ke-quick-book .ke-bookbox-guest small{display:block!important;color:#F5C518!important;font-size:12px!important;margin-top:2px!important}"
						+ "dialog#ke-quick-book .ke-step{display:flex!important;align-items:center!important;gap:8px!important;flex:0 0 auto!important}"
						+ "dialog#ke-quick-book .ke-step button{width:34px!important;height:34px!important;min-height:0!important;border-radius:50%!important;border:1px solid #F5C518!important;background:transparent!important;color:#F5C518!important;font-size:20px!important;line-height:1!important;padding:0!important;cursor:pointer!important}"
						+ "dialog#ke-quick-book .ke-step input{width:36px!important;height:auto!important;min-height:0!important;border:0!important;background:transparent!important;color:#fff!important;text-align:center!important;font-size:16px!important;font-weight:700!important;padding:0!important}"
						+ "dialog#ke-quick-book .ke-qb-error{margin:8px 0 0!important;color:#ffb4b4!important;font-size:14px!important}"
						+ "dialog#ke-quick-book .ke-bookbox-book,dialog#ke-quick-book .ke-bookbox-cart{display:block!important;width:100%!important;min-height:48px!important;margin-top:14px!important;border-radius:10px!important;font-size:16px!important;font-weight:700!important;cursor:pointer!important}"
						+ "dialog#ke-quick-book .ke-bookbox-book{background:#F5C518!important;color:#122327!important;border:0!important}"
						+ "dialog#ke-quick-book .ke-bookbox-cart{background:transparent!important;color:#F5C518!important;border:1px solid #F5C518!important}";
					document.head.appendChild(style);
				}
				quickBook = document.createElement("dialog");
				quickBook.id = "ke-quick-book";
				quickBook.className = "ke-quick-book";
				quickBook.innerHTML = ''
					+ '<div class="ke-bookbox">'
					+ '<div class="ke-qb-top"><div><p class="ke-bookbox-from"></p><p>Choose a date and how many guests.</p></div>'
					+ '<button type="button" class="ke-qb-close" data-ke-qb-close aria-label="Close">×</button></div>'
					+ '<form class="ke-bookbox-form">'
					+ '<label class="ke-bookbox-field ke-qb-pickup"><span>Select Pickup Location</span><select name="pickup"></select></label>'
					+ '<label class="ke-bookbox-field"><span>Booking Date</span><input type="date" name="date" required></label>'
					+ '<div class="ke-qb-guests"></div>'
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
