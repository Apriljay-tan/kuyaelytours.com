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

	function goContact(params) {
		var q = new URLSearchParams();
		Object.keys(params).forEach(function (key) {
			if (params[key]) q.set(key, params[key]);
		});
		window.location.href = "/contact.html?" + q.toString() + "#booking";
	}

	document.addEventListener("submit", function (e) {
		var form = e.target;
		if (!form || form.id !== "dreamit-form") return;
		if (window.keShopReady) return;
		e.preventDefault();

		var active = form.querySelector(".add-bg.active") || form;
		var locationEl = active.querySelector('[name="location"], [name="children"], [name="pickup"]');
		var activityEl = active.querySelector('[name="activity"], [name="place"], [name="vehicle"]');
		var dateEl = active.querySelector('[name="arrive"]');
		var groupEl = active.querySelector('[name="group"]');
		var locVal = locationEl ? String(locationEl.value || "") : "";
		var actVal = activityEl ? String(activityEl.value || "") : "";
		var service = SERVICE_MAP[locVal] || SERVICE_MAP[actVal] || "";
		var tab = document.querySelector(".bokking-tabs .tab.active") || document.querySelector(".tab.active");
		var tabId = tab ? String(tab.getAttribute("data-tab") || "") : "";
		var tabLabel = tab ? String(tab.textContent || "").replace(/\s+/g, " ").trim() : "";
		var notes = [];

		if (tabId === "hotel" || /van/i.test(tabLabel) || form.querySelector('[name="vehicle"], [name="pickup"]')) {
			service = "Car Rental";
			notes.push("Request type: Van / car hire");
		} else if (tabId === "visa" || /private/i.test(tabLabel)) {
			notes.push("Request type: Private tour");
		} else if (tabId === "travel" || /tours/i.test(tabLabel)) {
			notes.push("Request type: Island tour");
		}

		if (locVal && locVal !== "0") notes.push("Island / pickup: " + fieldText(locationEl));
		if (actVal) notes.push("Tour / vehicle: " + fieldText(activityEl));
		if (groupEl && groupEl.value && groupEl.value !== "0") notes.push("Group: " + fieldText(groupEl));

		var guests = "";
		if (groupEl && groupEl.value && groupEl.value !== "0") {
			if (/^\d+$/.test(groupEl.value)) guests = groupEl.value;
			else if (groupEl.value.indexOf("+") !== -1) guests = "11";
			else guests = String(groupEl.value).split("-")[0];
		}

		goContact({
			Service: service,
			"Travel Date": dateEl ? dateEl.value : "",
			Guests: guests,
			Message: notes.join("\n")
		});
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
		return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M5 19.2c.8-3.2 3.4-5.2 7-5.2s6.2 2 7 5.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
	}
	function iconSvgCart() {
		return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h15l-1.4 8.2a2 2 0 0 1-2 1.8H9.2a2 2 0 0 1-2-1.7L5.2 4H3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="20" r="1.4" fill="currentColor"/><circle cx="18" cy="20" r="1.4" fill="currentColor"/></svg>';
	}
	function injectNav(data) {
		document.querySelectorAll(".ke-shop-link, .ke-header-icons").forEach(function (el) { el.remove(); });
		var accountHref = data.name ? "/account/bookings.php" : "/account/login.php";
		var accountLabel = data.name ? "Account" : "Sign in";
		var count = parseInt(data.count, 10) || 0;
		var badge = count > 0 ? '<span class="ke-cart-count">' + count + "</span>" : "";
		var icons = '<div class="ke-header-icons">'
			+ '<a class="ke-icon-btn" href="' + accountHref + '" aria-label="' + accountLabel + '" title="' + accountLabel + '">' + iconSvgUser() + "</a>"
			+ '<a class="ke-icon-btn" href="/shop/cart.php" aria-label="Cart" title="Cart">' + iconSvgCart() + badge + "</a>"
			+ "</div>";
		document.querySelectorAll(".header-right-wrapper").forEach(function (wrap) {
			var sidebar = wrap.querySelector(".header-sidebar");
			if (sidebar) sidebar.insertAdjacentHTML("beforebegin", icons);
			else wrap.insertAdjacentHTML("afterbegin", icons);
		});
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

			document.addEventListener("submit", function (e) {
				var form = e.target;
				if (!form || form.id !== "dreamit-form") return;
				e.preventDefault();
				var active = form.querySelector(".add-bg.active") || form;
				var locationEl = active.querySelector('[name="location"], [name="children"], [name="pickup"]');
				var activityEl = active.querySelector('[name="activity"], [name="place"], [name="vehicle"]');
				var dateEl = active.querySelector('[name="arrive"]');
				var groupEl = active.querySelector('[name="group"]');
				var locVal = locationEl ? String(locationEl.value || "") : "";
				var actVal = activityEl ? String(activityEl.value || "") : "";
				var tab = document.querySelector(".bokking-tabs .tab.active");
				var tabId = tab ? String(tab.getAttribute("data-tab") || "") : "";
				var productId = PRODUCT[locVal] || PRODUCT[actVal] || "tour-cebu";
				if (tabId === "hotel" || /van/i.test(tab ? tab.textContent : "")) {
					productId = PRODUCT[actVal] || PRODUCT.van;
				}
				if (actVal === "transfer" || locVal === "transfer") {
					productId = "transfer-airport";
				}
				var guests = "2";
				if (groupEl && groupEl.value && groupEl.value !== "0") {
					guests = /^\d+$/.test(groupEl.value) ? groupEl.value : String(groupEl.value).split("-")[0];
				}
				var vehicle = PRODUCT[actVal] && String(PRODUCT[actVal]).indexOf("van-") === 0 ? actVal : "";
				var goCheckout = form.getAttribute("data-ke-checkout") === "1";
				var notes = "";
				if (activityEl && activityEl.tagName === "SELECT") {
					var opt = activityEl.options[activityEl.selectedIndex];
					notes = opt && activityEl.value ? String(opt.text || "").trim() : "";
				}
				var fields = {
					csrf: data.csrf,
					product_id: productId,
					date: dateEl ? dateEl.value : "",
					guests: guests,
					vehicle: vehicle,
					notes: notes
				};
				var next = goCheckout ? "/shop/checkout.php" : "/shop/cart.php";
				if (!loggedIn()) {
					goLogin({ fields: fields, next: next });
					return;
				}
				postCart(fields, next);
			});

			function bindBookNow(wrap) {
				var book = wrap.querySelector(".ke-book-now");
				if (!book || book.dataset.keBound === "1") return;
				book.dataset.keBound = "1";
				book.addEventListener("click", function () {
					wrap.closest("form").setAttribute("data-ke-checkout", "1");
					if (wrap.querySelector("button[type='submit']")) {
						wrap.querySelector("button[type='submit']").click();
					}
				});
			}

			document.querySelectorAll("form#dreamit-form .booking-button").forEach(function (wrap) {
				if (!wrap.querySelector(".ke-book-now")) {
					var sub = wrap.querySelector("button[type='submit']");
					if (sub) sub.textContent = "Add to cart";
					var extra = document.createElement("div");
					extra.className = "ke-cart-extra";
					extra.innerHTML = '<button type="button" class="ke-book-now">Book now</button>';
					wrap.appendChild(extra);
				}
				bindBookNow(wrap);
			});

			document.addEventListener("click", function (e) {
				var btn = e.target.closest("[data-ke-cart]");
				if (!btn) return;
				e.preventDefault();
				var form = btn.closest("form") || document.getElementById("dreamit-form");
				var next = btn.getAttribute("data-ke-next") || "/shop/cart.php";
				var fields;
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
				if (!loggedIn()) {
					goLogin({ fields: fields, next: next });
					return;
				}
				postCart(fields, next);
			});
		})
		.catch(function () {});
})();
