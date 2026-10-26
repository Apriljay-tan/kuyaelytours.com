<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require __DIR__ . '/chrome.php';
$bundle = partner_bundle();
$bookings = $bundle['bookings'];
partner_layout_start('My Bookings', 'bookings.php', 'Partner', false);
?>
<div class="ke-portal">
	<section class="ke-panel">
		<div class="ke-panel-head"><h2>My Bookings</h2><a href="/tours-and-packages.php">Create booking</a></div>
		<table class="ke-table">
			<thead><tr><th>Booking</th><th>Guest</th><th>Package</th><th>Status</th><th>Amount</th><th>Date</th></tr></thead>
			<tbody>
			<?php if (!$bookings): ?><tr><td colspan="6" class="ke-empty">No bookings on this login yet.</td></tr><?php endif; ?>
			<?php foreach ($bookings as $booking): if (!is_array($booking)) { continue; } ?>
				<tr>
					<td><strong><?php echo store_h((string) ($booking['id'] ?? '')); ?></strong></td>
					<td><?php echo store_h((string) ($booking['guest_name'] ?? '')); ?></td>
					<td><?php echo store_h(partner_booking_label($booking)); ?></td>
					<td><?php echo partner_status_pill((string) ($booking['status'] ?? '')); ?></td>
					<td><?php echo store_h(store_money((int) ($booking['total'] ?? 0))); ?></td>
					<td><?php echo store_h((string) ($booking['created'] ?? '')); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</section>
</div>
<?php partner_layout_end(); ?>
