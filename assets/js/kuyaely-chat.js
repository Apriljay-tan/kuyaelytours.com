(function () {
	if (window.__keChat) {
		return;
	}
	window.__keChat = true;
	if (/^\/admin(\/|$)/i.test(location.pathname)) {
		return;
	}

	var WA = "https://wa.me/639209851802";
	var CSS = "/assets/css/kuyaely-chat.css?v=6";
	var KEY = "keChatBox";
	var WELCOME = "Hi! Tell us your name and how we can help with a tour or van.";

	function ready(fn) {
		if (document.readyState === "loading") {
			document.addEventListener("DOMContentLoaded", fn);
		} else {
			fn();
		}
	}

	function esc(value) {
		return String(value == null ? "" : value).replace(/[&<>"']/g, function (ch) {
			return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
		});
	}

	function injectCss() {
		if (document.getElementById("ke-chat-css")) {
			return;
		}
		var link = document.createElement("link");
		link.id = "ke-chat-css";
		link.rel = "stylesheet";
		link.href = CSS;
		document.head.appendChild(link);
	}

	function loadState() {
		var blank = { name: "", email: "", phone: "", messages: [], ack: false };
		try {
			var raw = localStorage.getItem(KEY);
			if (!raw) {
				return blank;
			}
			var data = JSON.parse(raw);
			if (!data || typeof data !== "object") {
				return blank;
			}
			data.name = String(data.name || "");
			data.email = String(data.email || "");
			data.phone = String(data.phone || "");
			data.messages = Array.isArray(data.messages) ? data.messages : [];
			data.ack = !!data.ack;
			return data;
		} catch (e) {
			return blank;
		}
	}

	function saveState(state) {
		try {
			localStorage.setItem(KEY, JSON.stringify({
				name: state.name || "",
				email: state.email || "",
				phone: state.phone || "",
				messages: state.messages || [],
				ack: !!state.ack
			}));
		} catch (e) {}
	}

	function clock(ts) {
		if (ts == null || ts === "") {
			return "";
		}
		var d;
		if (typeof ts === "number") {
			d = new Date(ts);
		} else {
			var raw = String(ts).replace(" ", "T");
			d = new Date(raw);
			if (isNaN(d.getTime())) {
				d = new Date(raw + "Z");
			}
		}
		if (isNaN(d.getTime())) {
			return String(ts).slice(11, 16);
		}
		return d.toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
	}

	function icon(name) {
		if (name === "wa") {
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.08-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.87 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.05 21.78h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26C2.16 6.33 6.6 1.9 12.05 1.9a9.82 9.82 0 0 1 6.99 2.9 9.83 9.83 0 0 1 2.89 6.99c0 5.45-4.43 9.88-9.88 9.88zm8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.33.16 11.89c0 2.1.55 4.14 1.59 5.94L0 24l6.3-1.65a11.88 11.88 0 0 0 5.69 1.45h.01c6.55 0 11.89-5.34 11.89-11.9 0-3.18-1.24-6.16-3.48-8.41z"/></svg>';
		}
		if (name === "wc") {
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9.5 3.2C5.2 3.2 1.7 6.3 1.7 10.1c0 2.15 1.15 4.08 2.95 5.4l-.55 2.05c-.1.35.25.64.55.48l2.55-1.45c.7.18 1.45.27 2.22.27.22 0 .43 0 .64-.03A5.6 5.6 0 0 1 9.2 14.4c-2.95 0-5.35-2.25-5.35-5.02S6.25 4.36 9.2 4.36c2.7 0 4.95 1.9 5.4 4.4.72-.1 1.46-.1 2.18.03C16.3 5.5 13.2 3.2 9.5 3.2zm5.55 6.55c-2.72 0-4.95 1.9-4.95 4.28 0 2.37 2.23 4.27 4.95 4.27.55 0 1.08-.07 1.58-.2l2.12 1.2c.22.12.5-.06.4-.3l-.42-1.72c1.48-.95 2.42-2.45 2.42-4.15 0-2.38-2.23-4.28-5.1-4.28zM7.85 8.2a.95.95 0 1 1 0 1.9.95.95 0 0 1 0-1.9zm3.55 0a.95.95 0 1 1 0 1.9.95.95 0 0 1 0-1.9zm3.65 3.85a.8.8 0 1 1 0 1.6.8.8 0 0 1 0-1.6zm3.5 0a.8.8 0 1 1 0 1.6.8.8 0 0 1 0-1.6z"/></svg>';
		}
		if (name === "vb") {
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2c5.1 0 9.2 3.5 9.2 8.4 0 4.2-3.1 7.7-7.4 8.3l.2 2.8c.05.7-.7 1.15-1.25.75l-3.7-2.55C5.3 18.9 2.8 15.4 2.8 10.4 2.8 5.5 6.9 2 12 2zm-2.2 5.3c-.4 0-.7.3-.7.7 0 4.1 2.6 6.8 6.6 6.8.4 0 .7-.3.7-.7s-.3-.7-.7-.7c-3.2 0-5.2-2-5.2-5.4 0-.4-.3-.7-.7-.7zm-.1 2.5c-.4 0-.7.3-.7.7 0 2.5 1.5 4.1 4 4.1.4 0 .7-.3.7-.7s-.3-.7-.7-.7c-1.7 0-2.6-1-2.6-2.7 0-.4-.3-.7-.7-.7z"/></svg>';
		}
		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4.2 5.4A2.4 2.4 0 0 1 6.6 3h10.8A2.4 2.4 0 0 1 19.8 5.4v8.2a2.4 2.4 0 0 1-2.4 2.4h-5.1L8 18.8c-.5.38-1.2.02-1.2-.6v-2.2H6.6a2.4 2.4 0 0 1-2.4-2.4V5.4z"/></svg>';
	}

	function channelRow(key, ico, label, sub) {
		return (
			'<button class="ke-chat-channel" type="button" data-channel="' + key + '">' +
				'<span class="ke-chat-ico ke-chat-ico-' + ico + '">' + icon(ico) + "</span>" +
				"<span><b>" + label + '</b><span data-channel-sub>' + sub + "</span></span>" +
			"</button>"
		);
	}

	function channelPane(key, title, soon) {
		return (
			'<div class="ke-chat-pane" data-pane="' + key + '">' +
				'<button class="ke-chat-back" type="button" data-pane="home">&larr; All options</button>' +
				'<div class="ke-chat-card" data-channel-card="' + key + '">' +
					'<div class="ke-chat-qr" data-qr>QR soon</div>' +
					"<p><strong>" + title + "</strong></p>" +
					'<p data-detail-copy>' + soon + "</p>" +
					'<a class="ke-chat-open" data-open-app hidden target="_blank" rel="noopener">Open ' + title + "</a>" +
				"</div>" +
			"</div>"
		);
	}

	function markup() {
		return (
			'<div id="ke-chat" class="ke-chat">' +
				'<div class="ke-chat-panel" role="dialog" aria-label="Chat with Kuya Ely Tours" hidden>' +
					'<div class="ke-chat-head">' +
						'<img src="/assets/downloaded/kuyaely_logo_web.png" alt="">' +
						'<div><strong>Kuya Ely Tours</strong><small>Cebu · Bohol · Siquijor · Dumaguete</small></div>' +
						'<button class="ke-chat-close" type="button" aria-label="Close chat">&times;</button>' +
					'</div>' +
					'<div class="ke-chat-body">' +
						'<div class="ke-chat-pane is-on" data-pane="home">' +
							'<p class="ke-chat-lead">Message us on an app you already use, or start a chat on this site.</p>' +
							'<div class="ke-chat-channels">' +
								channelRow("whatsapp", "wa", "WhatsApp", "+63 920 985 1802") +
								channelRow("wechat", "wc", "WeChat", "QR code coming soon") +
								channelRow("viber", "vb", "Viber", "Number and QR coming soon") +
								'<button class="ke-chat-channel" type="button" data-pane="inbox">' +
									'<span class="ke-chat-ico ke-chat-ico-in">' + icon("in") + '</span>' +
									'<span><b>Message us</b><span>Chat on this website</span></span>' +
								'</button>' +
							'</div>' +
						'</div>' +
						channelPane("whatsapp", "WhatsApp", "We will add our WhatsApp number and QR code here.") +
						channelPane("wechat", "WeChat", "We will add our WeChat ID and QR code here.") +
						channelPane("viber", "Viber", "We will add the Viber number and QR code here.") +
						'<div class="ke-chat-pane ke-chat-inbox" data-pane="inbox">' +
							'<button class="ke-chat-back" type="button" data-pane="home">&larr; All options</button>' +
							'<div class="ke-chat-id" data-id-form>' +
								'<label><span>Your name</span><input type="text" name="ke-chat-name" autocomplete="name" maxlength="80" placeholder="Name"></label>' +
								'<label><span>Email or WhatsApp</span><input type="text" name="ke-chat-contact" autocomplete="email" maxlength="120" placeholder="so we can reach you"></label>' +
							'</div>' +
							'<p class="ke-chat-as" data-id-summary hidden></p>' +
							'<div class="ke-chat-thread" data-thread role="log" aria-live="polite"></div>' +
							'<form class="ke-chat-composer" data-composer>' +
								'<input type="text" name="ke-chat-text" maxlength="1000" autocomplete="off" placeholder="Write a message">' +
								'<button type="submit">Send</button>' +
							'</form>' +
						'</div>' +
					'</div>' +
				'</div>' +
				'<button class="ke-chat-launch" type="button" aria-label="Open chat" aria-expanded="false">' +
					'<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 6.2A2.2 2.2 0 0 1 7.2 4h9.6A2.2 2.2 0 0 1 19 6.2v7.1A2.2 2.2 0 0 1 16.8 15.5H12L8.2 18.4c-.5.4-1.2 0-1.2-.6v-2.3H7.2A2.2 2.2 0 0 1 5 13.3V6.2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>' +
				'</button>' +
			'</div>'
		);
	}

	function looksEmail(value) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
	}

	function looksPhone(value) {
		return /^[+\d][\d\s().-]{6,}$/.test(value);
	}

	function splitContact(value) {
		var v = String(value || "").trim();
		if (looksEmail(v)) {
			return { email: v, phone: "" };
		}
		if (looksPhone(v)) {
			return { email: "", phone: v };
		}
		return { email: "", phone: v };
	}

	function readyToChat(state) {
		return String(state.name || "").trim().length >= 2 && (looksEmail(state.email) || looksPhone(state.phone) || String(state.email || state.phone || "").trim().length >= 6);
	}

	ready(function () {
		injectCss();
		if (document.getElementById("ke-chat")) {
			return;
		}
		document.body.insertAdjacentHTML("beforeend", markup());
		var root = document.getElementById("ke-chat");
		if (!root) {
			return;
		}

		var state = loadState();
		var lastPane = state.messages && state.messages.length ? "inbox" : "home";
		var accountLocked = false;
		var threadEl = root.querySelector("[data-thread]");
		var formEl = root.querySelector("[data-id-form]");
		var summaryEl = root.querySelector("[data-id-summary]");
		var composer = root.querySelector("[data-composer]");
		var nameInput = root.querySelector('[name="ke-chat-name"]');
		var contactInput = root.querySelector('[name="ke-chat-contact"]');
		var textInput = root.querySelector('[name="ke-chat-text"]');
		var channelKey = "";
		var channelState = {
			whatsapp: { phone: "+63 920 985 1802", handle: "", qr: "", href: WA, show: true, ready: true },
			wechat: { phone: "", handle: "", qr: "", href: "", show: true, ready: false },
			viber: { phone: "", handle: "", qr: "", href: "", show: true, ready: false }
		};

		function openApp(href) {
			if (!href) {
				return;
			}
			if (/^viber:/i.test(href)) {
				window.location.href = href;
				return;
			}
			window.open(href, "_blank", "noopener");
		}

		function paintChannels(channels) {
			if (!channels || typeof channels !== "object") {
				return;
			}
			var next = JSON.stringify(channels);
			if (next === channelKey) {
				return;
			}
			channelKey = next;
			["whatsapp", "wechat", "viber"].forEach(function (key) {
				var row = channels[key] || {};
				channelState[key] = {
					phone: String(row.phone || ""),
					handle: String(row.handle || ""),
					qr: String(row.qr || ""),
					href: String(row.href || (key === "whatsapp" ? WA : "")),
					show: row.show !== false,
					ready: !!row.ready
				};
				var btn = root.querySelector('[data-channel="' + key + '"]');
				if (btn) {
					btn.hidden = !channelState[key].show;
					var sub = btn.querySelector("[data-channel-sub]");
					if (sub) {
						if (key === "whatsapp") {
							sub.textContent = channelState[key].phone || "WhatsApp";
						} else if (key === "wechat") {
							sub.textContent = channelState[key].handle || (channelState[key].qr ? "Scan QR to add us" : "QR code coming soon");
						} else {
							sub.textContent = channelState[key].phone || (channelState[key].qr ? "Scan QR to add us" : "Number and QR coming soon");
						}
					}
				}
				var card = root.querySelector('[data-channel-card="' + key + '"]');
				if (!card) {
					return;
				}
				var qrEl = card.querySelector("[data-qr]");
				if (qrEl) {
					if (channelState[key].qr) {
						qrEl.classList.add("has-img");
						qrEl.innerHTML = '<img src="' + esc(channelState[key].qr) + '" alt="' + esc(key) + ' QR">';
					} else {
						qrEl.classList.remove("has-img");
						qrEl.textContent = "QR soon";
					}
				}
				var copy = card.querySelector("[data-detail-copy]");
				if (copy) {
					var bits = [];
					if (channelState[key].handle) {
						bits.push(key === "wechat" ? "ID: " + channelState[key].handle : channelState[key].handle);
					}
					if (channelState[key].phone) {
						bits.push(channelState[key].phone);
					}
					if (bits.length) {
						copy.textContent = bits.join(" · ");
					} else if (channelState[key].qr) {
						copy.textContent = "Scan the QR code in " + (key === "whatsapp" ? "WhatsApp" : key === "wechat" ? "WeChat" : "Viber") + " to message us.";
					} else if (key === "whatsapp") {
						copy.textContent = "We will add our WhatsApp number and QR code here.";
					} else if (key === "wechat") {
						copy.textContent = "We will add our WeChat ID and QR code here.";
					} else {
						copy.textContent = "We will add the Viber number and QR code here.";
					}
				}
				var openBtn = card.querySelector("[data-open-app]");
				if (openBtn) {
					if (channelState[key].href) {
						openBtn.hidden = false;
						openBtn.setAttribute("href", channelState[key].href);
						if (/^viber:/i.test(channelState[key].href)) {
							openBtn.removeAttribute("target");
						} else {
							openBtn.setAttribute("target", "_blank");
						}
					} else {
						openBtn.hidden = true;
						openBtn.removeAttribute("href");
					}
				}
			});
		}

		function showPane(name) {
			lastPane = name || "home";
			root.querySelectorAll(".ke-chat-pane").forEach(function (pane) {
				pane.classList.toggle("is-on", pane.getAttribute("data-pane") === lastPane);
			});
			if (lastPane === "inbox" && textInput) {
				window.setTimeout(function () { textInput.focus(); }, 50);
			}
		}

		function setOpen(open) {
			root.classList.toggle("is-open", open);
			var panel = root.querySelector(".ke-chat-panel");
			var launch = root.querySelector(".ke-chat-launch");
			if (panel) {
				panel.hidden = !open;
			}
			if (launch) {
				launch.setAttribute("aria-expanded", open ? "true" : "false");
				launch.setAttribute("aria-label", open ? "Close chat" : "Open chat");
			}
			if (open) {
				root.classList.remove("has-unread");
				showPane(lastPane);
				pollChat();
			}
		}

		function renderThread() {
			if (!threadEl) {
				return;
			}
			if (!state.messages.length) {
				state.messages.push({ from: "desk", text: WELCOME, at: Date.now() });
			}
			threadEl.innerHTML = state.messages.map(function (msg) {
				var mine = msg.from === "guest";
				var who = mine ? "You" : "Kuya Ely";
				var side = mine ? "is-me" : "is-desk";
				var stamp = clock(msg.at);
				return '<div class="ke-chat-msg ' + side + '"><p>' + esc(msg.text) + '</p><time>' + esc(who) + (stamp ? " · " + esc(stamp) : "") + "</time></div>";
			}).join("");
			threadEl.scrollTop = threadEl.scrollHeight;
		}

		function renderIdentity() {
			var readyNow = readyToChat(state);
			if (formEl) {
				formEl.hidden = readyNow && !formEl.classList.contains("is-editing");
			}
			if (nameInput && !formEl.classList.contains("is-editing")) {
				nameInput.value = state.name;
			}
			if (contactInput && !formEl.classList.contains("is-editing")) {
				contactInput.value = state.email || state.phone;
			}
			if (summaryEl) {
				if (readyNow && !(formEl && formEl.classList.contains("is-editing"))) {
					summaryEl.hidden = false;
					var bit = state.email || state.phone;
					summaryEl.innerHTML = "Chatting as <strong>" + esc(state.name) + "</strong>" + (bit ? " · " + esc(bit) : "") +
						(accountLocked ? "" : ' <button type="button" class="ke-chat-edit" data-edit-id>Change</button>');
				} else {
					summaryEl.hidden = true;
				}
			}
			if (textInput) {
				textInput.disabled = !readyNow;
				textInput.placeholder = readyNow ? "Write a message" : "Add your name first";
			}
			if (composer) {
				var sendBtn = composer.querySelector("button");
				if (sendBtn) {
					sendBtn.disabled = !readyNow;
				}
			}
		}

		function applyAccount(data) {
			if (!data || !(data.logged_in || data.name)) {
				return;
			}
			accountLocked = true;
			if (data.name) {
				state.name = String(data.name);
			}
			if (data.email) {
				state.email = String(data.email);
			}
			if (data.phone) {
				state.phone = String(data.phone);
			}
			saveState(state);
			renderIdentity();
		}

		function captureIdentity() {
			state.name = String(nameInput && nameInput.value || state.name || "").trim();
			var parts = splitContact(contactInput && contactInput.value || state.email || state.phone || "");
			if (parts.email) {
				state.email = parts.email;
			}
			if (parts.phone) {
				state.phone = parts.phone;
			}
			if (!state.email && !state.phone && contactInput) {
				state.phone = String(contactInput.value || "").trim();
			}
			saveState(state);
			renderIdentity();
		}

		var csrf = "";

		function msgKey(list) {
			if (!list || !list.length) {
				return "";
			}
			var last = list[list.length - 1];
			return String(list.length) + ":" + String(last.id || "") + ":" + String(last.text || "") + ":" + String(last.at || "");
		}

		function applyServer(data, opts) {
			opts = opts || {};
			if (!data) {
				return;
			}
			if (data.csrf) {
				csrf = String(data.csrf);
			}
			if (data.channels) {
				paintChannels(data.channels);
			}
			if (data.name) {
				state.name = String(data.name);
			}
			if (data.email) {
				state.email = String(data.email);
			}
			if (data.phone) {
				state.phone = String(data.phone);
			}
			if (data.logged_in) {
				accountLocked = true;
			}
			if (Array.isArray(data.messages) && data.messages.length) {
				var next = data.messages.map(function (msg) {
					return {
						id: String(msg.id || ""),
						from: msg.from === "desk" ? "desk" : "guest",
						text: String(msg.text || ""),
						at: msg.at || Date.now()
					};
				});
				var before = msgKey(state.messages);
				var after = msgKey(next);
				var last = next[next.length - 1];
				state.messages = next;
				state.ack = true;
				if (opts.switchPane !== false) {
					lastPane = "inbox";
				}
				if (before !== after) {
					if (last && last.from === "desk" && !root.classList.contains("is-open")) {
						root.classList.add("has-unread");
					}
					saveState(state);
					renderThread();
					renderIdentity();
				} else {
					saveState(state);
					renderIdentity();
				}
				return;
			}
			saveState(state);
			renderIdentity();
		}

		function pollChat() {
			if (document.hidden) {
				return;
			}
			fetch("/shop/chat.php", { credentials: "same-origin" })
				.then(function (r) { return r.json(); })
				.then(function (data) { applyServer(data, { switchPane: false }); })
				.catch(function () {});
		}

		function sendGuest(text) {
			text = String(text || "").trim();
			if (!text || !readyToChat(state)) {
				if (formEl) {
					formEl.classList.add("is-editing");
					formEl.hidden = false;
				}
				renderIdentity();
				if (nameInput && !state.name) {
					nameInput.focus();
				} else if (contactInput) {
					contactInput.focus();
				}
				return;
			}
			var sendBtn = composer ? composer.querySelector("button") : null;
			if (sendBtn) {
				sendBtn.disabled = true;
			}
			state.messages.push({ from: "guest", text: text, at: Date.now(), pending: true });
			state.ack = true;
			saveState(state);
			renderThread();
			if (textInput) {
				textInput.value = "";
			}
			var body = new URLSearchParams();
			body.set("csrf", csrf);
			body.set("name", state.name);
			body.set("email", state.email || "");
			body.set("phone", state.phone || "");
			body.set("text", text.slice(0, 1000));
			fetch("/shop/chat.php", {
				method: "POST",
				credentials: "same-origin",
				headers: { "Content-Type": "application/x-www-form-urlencoded" },
				body: body.toString()
			})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (!data || !data.ok) {
						if (state.messages.length && state.messages[state.messages.length - 1].pending) {
							state.messages.pop();
							renderThread();
						}
						window.alert((data && data.error) || "Could not send. Try again.");
						return;
					}
					applyServer(data);
					if (textInput) {
						textInput.focus();
					}
					window.setTimeout(pollChat, 600);
				})
				.catch(function () {
					if (state.messages.length && state.messages[state.messages.length - 1].pending) {
						state.messages.pop();
						renderThread();
					}
					window.alert("Could not send. Check your connection and try again.");
				})
				.then(function () {
					if (sendBtn) {
						sendBtn.disabled = !readyToChat(state);
					}
				});
		}

		root.querySelector(".ke-chat-launch").addEventListener("click", function () {
			setOpen(!root.classList.contains("is-open"));
		});
		root.querySelector(".ke-chat-close").addEventListener("click", function () {
			setOpen(false);
		});
		root.querySelectorAll("[data-pane]").forEach(function (btn) {
			if (btn.tagName !== "BUTTON") {
				return;
			}
			btn.addEventListener("click", function () {
				if (formEl) {
					formEl.classList.remove("is-editing");
				}
				showPane(btn.getAttribute("data-pane") || "home");
			});
		});
		root.querySelectorAll("[data-channel]").forEach(function (btn) {
			btn.addEventListener("click", function () {
				var key = btn.getAttribute("data-channel") || "";
				var row = channelState[key] || {};
				if (!row.show) {
					return;
				}
				if (row.href && !row.qr) {
					openApp(row.href);
					return;
				}
				showPane(key);
			});
		});
		root.querySelectorAll("[data-open-app]").forEach(function (btn) {
			btn.addEventListener("click", function (e) {
				var href = btn.getAttribute("href") || "";
				if (/^viber:/i.test(href)) {
					e.preventDefault();
					openApp(href);
				}
			});
		});
		root.addEventListener("click", function (e) {
			if (e.target.closest("[data-edit-id]")) {
				if (formEl) {
					formEl.classList.add("is-editing");
					formEl.hidden = false;
				}
				if (summaryEl) {
					summaryEl.hidden = true;
				}
				if (nameInput) {
					nameInput.focus();
				}
			}
		});
		if (nameInput) {
			nameInput.addEventListener("blur", captureIdentity);
			nameInput.addEventListener("change", captureIdentity);
		}
		if (contactInput) {
			contactInput.addEventListener("blur", captureIdentity);
			contactInput.addEventListener("change", captureIdentity);
		}
		if (composer) {
			composer.addEventListener("submit", function (e) {
				e.preventDefault();
				captureIdentity();
				sendGuest(textInput ? textInput.value : "");
			});
		}
		document.addEventListener("keydown", function (e) {
			if (e.key === "Escape") {
				setOpen(false);
			}
		});

		renderThread();
		renderIdentity();

		fetch("/shop/chat.php", { credentials: "same-origin" })
			.then(function (r) { return r.json(); })
			.then(applyServer)
			.catch(function () {})
			.then(function () {
				return fetch("/shop/nav-data.php", { credentials: "same-origin" });
			})
			.then(function (r) { return r ? r.json() : null; })
			.then(applyAccount)
			.catch(function () {});

		setInterval(pollChat, 1500);
		document.addEventListener("visibilitychange", function () {
			if (!document.hidden) {
				pollChat();
			}
		});
	});
})();
