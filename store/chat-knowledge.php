<?php
declare(strict_types=1);

function store_chat_faqs(): array
{
	return [
		[
			'id' => 'where',
			'q' => 'Where do you tour?',
			'a' => 'We run private tours and van transfers in Cebu, Bohol, Siquijor, and Dumaguete.',
			'pattern' => '/\b(cebu|bohol|siquijor|dumaguete|mactan|oslob|moalboal|kawasan|destination|destinations|island)\b|where do you|which island/i',
		],
		[
			'id' => 'pickup',
			'q' => 'Do you pick up from the hotel?',
			'a' => 'Yes. We pick up from your hotel, and airport pickup is available by request. Send the hotel name, date, and how many guests.',
			'pattern' => '/pick[\s-]?up|\bhotel\b|\bairport\b/i',
		],
		[
			'id' => 'hours',
			'q' => 'What are your hours?',
			'a' => 'We are open daily from 6:00 AM to 10:00 PM. Airport pickup can be arranged by request.',
			'pattern' => '/\b(hours|open|closing|schedule)\b|what time/i',
		],
		[
			'id' => 'pay',
			'q' => 'How do I pay?',
			'a' => 'Pay on the site with QR Ph, PayPal, or bank transfer / GCash. You can pay in full, or pay 30% down and the rest before the trip.',
			'pattern' => '/\b(pay|payment|gcash|paypal|qr|deposit|bank)\b|down payment/i',
		],
		[
			'id' => 'price',
			'q' => 'How much does a tour cost?',
			'a' => 'Each tour page shows its price. The price can change until we confirm the trip. Entrance fees, meals, and boats are included only when the confirmation says so.',
			'pattern' => '/\b(price|prices|cost|rate|rates|fee|fees)\b|how much/i',
		],
		[
			'id' => 'private',
			'q' => 'Is the tour private?',
			'a' => 'Yes. The van is for your group only. It is not a shared join-in tour.',
			'pattern' => '/\bprivate\b|join[\s-]?in|\bshared\b|\bsharing\b/i',
		],
		[
			'id' => 'children',
			'q' => 'Can I bring children?',
			'a' => 'Yes. Child rates depend on the package and are shown on the tour page before you book.',
			'pattern' => '/\b(child|children|kid|kids|infant|toddler)\b/i',
		],
		[
			'id' => 'cancel',
			'q' => 'How do I change or cancel?',
			'a' => 'Message info@kuyaelytours.com or WhatsApp +63 920 985 1802. A refund, if any, depends on how much notice you give and whether a vehicle was already reserved.',
			'pattern' => '/cancel|refund|reschedul/i',
		],
		[
			'id' => 'book',
			'q' => 'How do I book?',
			'a' => 'Send your travel date, hotel or pickup point, destination, and number of guests. You can also book from the tour page.',
			'pattern' => '/\b(book|booking|reserve|availability|quote)\b/i',
		],
		[
			'id' => 'contact',
			'q' => 'How can I reach you?',
			'a' => 'Call or WhatsApp +63 920 985 1802, or email info@kuyaelytours.com. You can also use the chat apps on this button.',
			'pattern' => '/\b(phone|whatsapp|viber|email|contact|call|reach)\b/i',
		],
	];
}

function store_chat_reply(string $text): string
{
	$fallback = 'A staff member will contact you on the contact info you provided.';
	$byId = [];
	foreach (store_chat_faqs() as $row) {
		$byId[(string) ($row['id'] ?? '')] = $row;
	}
	foreach (['cancel', 'children', 'pay', 'price', 'hours', 'pickup', 'private', 'contact', 'book', 'where'] as $id) {
		$row = $byId[$id] ?? null;
		$pattern = (string) ($row['pattern'] ?? '');
		if ($row && $pattern !== '' && preg_match($pattern, $text)) {
			return (string) $row['a'];
		}
	}
	return $fallback;
}

function store_chat_faqs_public(): array
{
	$out = [];
	foreach (store_chat_faqs() as $row) {
		$out[] = [
			'q' => (string) $row['q'],
			'a' => (string) $row['a'],
		];
	}
	return $out;
}
