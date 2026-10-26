<?php
declare(strict_types=1);
require dirname(__DIR__) . '/store/bootstrap.php';
require __DIR__ . '/chrome.php';
partner_bundle();
partner_layout_start('Support', 'support.php', 'Partner', false);
?>
<div class="ke-portal">
	<section class="ke-panel">
		<h2>Support</h2>
		<p class="ke-muted">The Kuya Ely office answers partnership questions at this address.</p>
		<div class="ke-profile-row"><i class="bx bx-envelope"></i><div><span>Email</span><strong><a href="mailto:support@kuyaelytours.com">support@kuyaelytours.com</a></strong></div></div>
		<div class="ke-profile-row"><i class="bx bx-globe"></i><div><span>Website</span><strong><a href="/contact">Contact page</a></strong></div></div>
		<div class="ke-quick" style="margin-top:16px;max-width:280px">
			<a href="mailto:support@kuyaelytours.com">Email the office <i class="bx bx-envelope"></i></a>
		</div>
	</section>
</div>
<?php partner_layout_end(); ?>
