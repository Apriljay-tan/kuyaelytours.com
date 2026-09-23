(function () {
	var standard = {
		ViewContent: 1,
		Search: 1,
		AddToCart: 1,
		InitiateCheckout: 1,
		AddPaymentInfo: 1,
		Purchase: 1,
		Lead: 1,
		CompleteRegistration: 1,
		Contact: 1,
		Schedule: 1,
		Subscribe: 1,
		CustomizeProduct: 1
	};

	function clean(text) {
		return String(text || "")
			.replace(/\s+/g, " ")
			.replace(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/ig, "email")
			.replace(/\+?\d[\d\s().-]{6,}\d/g, "phone")
			.trim()
			.slice(0, 80);
	}

	function track(event, params) {
		if (typeof window.fbq !== "function" || !event) {
			return;
		}
		if (standard[event]) {
			window.fbq("track", event, params || {});
		} else {
			window.fbq("trackCustom", event, params || {});
		}
	}

	function fireList(list) {
		(list || []).forEach(function (item) {
			if (item && item.event) {
				track(item.event, item.params || {});
			}
		});
	}

	fireList(window.kePixelEvents);

	var qs = new URLSearchParams(location.search);
	if (qs.get("ke_find")) {
		var term = clean(qs.get("ke_find"));
		var key = "ke_find:" + term + ":" + location.pathname;
		try {
			if (term && !sessionStorage.getItem(key)) {
				sessionStorage.setItem(key, "1");
				track("Search", { search_string: term });
			}
		} catch (err) {}
		if (window.history && history.replaceState) {
			qs.delete("ke_find");
			var next = location.pathname + (qs.toString() ? "?" + qs.toString() : "") + location.hash;
			history.replaceState(null, "", next);
		}
	}

	fetch("/shop/pixel.php", { credentials: "same-origin", cache: "no-store" })
		.then(function (response) { return response.json(); })
		.then(function (data) { fireList(data && data.events); })
		.catch(function () {});

	document.addEventListener("click", function (e) {
		var el = e.target && e.target.closest ? e.target.closest("a, button, input[type='submit'], [role='button']") : null;
		if (!el) {
			return;
		}
		var href = el.getAttribute("href") || "";
		var label = clean(el.getAttribute("aria-label") || el.value || el.textContent || href || "button");
		track("Click", {
			content_name: label || "button",
			content_category: location.pathname
		});
		if (/^tel:/i.test(href)) {
			track("Contact", { content_name: "Phone" });
		} else if (/^mailto:/i.test(href)) {
			track("Contact", { content_name: "Email" });
		} else if (/wa\.me|whatsapp/i.test(href)) {
			track("Contact", { content_name: "WhatsApp" });
		}
	}, true);

	document.addEventListener("submit", function (e) {
		var form = e.target;
		if (!form || !form.getAttribute || !form.hasAttribute("data-pay-full")) {
			return;
		}
		var picked = form.querySelector("input[name='plan']:checked");
		var half = picked && picked.value === "half";
		var value = Number(half ? form.getAttribute("data-pay-half") : form.getAttribute("data-pay-full")) || 0;
		track("AddPaymentInfo", { value: value, currency: "PHP" });
	}, true);

	document.addEventListener("change", function (e) {
		var el = e.target;
		if (!el || !el.name) {
			return;
		}
		if ((el.name === "date" || el.name === "arrive") && el.value) {
			track("Schedule", {
				content_name: clean(document.title),
				content_category: location.pathname
			});
		}
		if (/^(guests|foreign_adult|local_adult|foreign_child|local_child|vehicle|group)$/.test(el.name)) {
			track("CustomizeProduct", {
				content_name: el.name,
				content_category: location.pathname
			});
		}
	}, true);
})();
