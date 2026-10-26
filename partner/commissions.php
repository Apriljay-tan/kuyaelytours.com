<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require __DIR__ . '/chrome.php';
$bundle = partner_bundle();
$desk = $bundle['desk'];
$kind = (string) ($desk['commission_type'] ?? 'fixed') === 'percent' ? 'Percent' : 'Fixed amount';
$value = (string) ($desk['commission_type'] ?? '') === 'percent'
	? (int) ($desk['commission_value'] ?? 0) . '%'
	: store_money((int) ($desk['commission_value'] ?? 0));
$terms = trim((string) ($desk['payment_terms'] ?? ''));
if ($terms === '') {
	$terms = 'N/A';
}
partner_layout_start('Commissions', 'commissions.php', 'Partner', false);
?>
<div class="ke-portal">
	<section class="ke-panel">
		<h2>Commissions</h2>
		<p class="ke-muted">These are the terms saved for your partnership. Payout totals appear here after the office records them.</p>
		<div class="ke-profile-row"><i class="bx bx-wallet"></i><div><span>Commission type</span><strong><?php echo store_h($kind); ?></strong></div></div>
		<div class="ke-profile-row"><i class="bx bx-purchase-tag"></i><div><span>Commission value</span><strong><?php echo store_h($value); ?></strong></div></div>
		<div class="ke-profile-row"><i class="bx bx-calendar"></i><div><span>Payment terms</span><strong><?php echo store_h($terms); ?></strong></div></div>
	</section>
</div>
<?php partner_layout_end(); ?>
