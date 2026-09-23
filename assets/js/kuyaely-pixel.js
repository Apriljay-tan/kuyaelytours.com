(function () {
	if (!/(?:^|; )ke_consent=1(?:;|$)/.test(document.cookie)) {
		return;
	}
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

	function track(event, params, eventId) {
		if (!event) {
			return "";
		}
		if (!eventId) {
			eventId = Math.random().toString(16).slice(2) + Date.now().toString(16);
		}
		if (typeof window.fbq === "function") {
			var opts = { eventID: eventId };
			if (standard[event]) {
				window.fbq("track", event, params || {}, opts);
			} else {
				window.fbq("trackCustom", event, params || {}, opts);
			}
		}
		return eventId;
	}

	function sendCapi(events) {
		if (!events || !events.length) {
			return;
		}
		fetch("/shop/capi.php", {
			method: "POST",
			credentials: "same-origin",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ events: events, url: location.href })
		}).catch(function () {});
	}

	function fireList(list) {
		var capi = [];
		(list || []).forEach(function (item) {
			if (!item || !item.event) {
				return;
			}
			var id = track(item.event, item.params || {}, item.event_id || "");
			if (standard[item.event]) {
				capi.push({ event: item.event, event_id: id, params: item.params || {} });
			}
		});
		sendCapi(capi);
	}

	fireList(window.kePixelEvents);

	var qs = new URLSearchParams(location.search);
	if (qs.get("ke_find")) {
		var term = clean(qs.get("ke_find"));
		var key = "ke_find:" + term + ":" + location.pathname;
		try {
			if (term && !sessionStorage.getItem(key)) {
				sessionStorage.setItem(key, "1");
				var searchId = track("Search", { search_string: term });
				sendCapi([{ event: "Search", event_id: searchId, params: { search_string: term } }]);
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
			var phoneId = track("Contact", { content_name: "Phone" });
			sendCapi([{ event: "Contact", event_id: phoneId, params: { content_name: "Phone" } }]);
		} else if (/^mailto:/i.test(href)) {
			var mailId = track("Contact", { content_name: "Email" });
			sendCapi([{ event: "Contact", event_id: mailId, params: { content_name: "Email" } }]);
		} else if (/wa\.me|whatsapp/i.test(href)) {
			var waId = track("Contact", { content_name: "WhatsApp" });
			sendCapi([{ event: "Contact", event_id: waId, params: { content_name: "WhatsApp" } }]);
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
		var payId = track("AddPaymentInfo", { value: value, currency: "PHP" });
		sendCapi([{ event: "AddPaymentInfo", event_id: payId, params: { value: value, currency: "PHP" } }]);
	}, true);

	document.addEventListener("change", function (e) {
		var el = e.target;
		if (!el || !el.name) {
			return;
		}
		if ((el.name === "date" || el.name === "arrive") && el.value) {
			var scheduleId = track("Schedule", {
				content_name: clean(document.title),
				content_category: location.pathname
			});
			sendCapi([{
				event: "Schedule",
				event_id: scheduleId,
				params: { content_name: clean(document.title), content_category: location.pathname }
			}]);
		}
		if (/^(guests|foreign_adult|local_adult|foreign_child|local_child|vehicle|group)$/.test(el.name)) {
			var customId = track("CustomizeProduct", {
				content_name: el.name,
				content_category: location.pathname
			});
			sendCapi([{
				event: "CustomizeProduct",
				event_id: customId,
				params: { content_name: el.name, content_category: location.pathname }
			}]);
		}
	}, true);
})();
