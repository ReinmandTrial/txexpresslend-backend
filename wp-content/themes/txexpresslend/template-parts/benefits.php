<?php
/**
 * Column with the heading and the list of benefits from Site Settings → Get Started block.
 * Shared by the full-screen "Loan form" and "Partner form" blocks.
 *
 * @var array $args title — the heading (shown on desktop only).
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="pt-15 pb-24 lg:pt-10 lg:pb-30 flex flex-col justify-center lg:pr-10">
	<h2 class="mb-10 t-h1 max-lg:hidden text-dark">
		<?php echo esc_html( $args['title'] ); ?>
	</h2>
	<ul class="space-y-10">
		<?php foreach ( txl_rows( txl_option( 'benefits' ) ) as $txl_benefit ) : ?>
			<li class="flex gap-8">
				<div class="w-23 flex-none">
					<?php txl_image( $txl_benefit['image'], 'h-auto', array( 'alt' => '' ) ); ?>
				</div>
				<div class="max-w-100">
					<h4 class="t-h3-m mb-2 text-blue-lux">
						<?php txl_text( $txl_benefit['title'] ); ?>
					</h4>
					<p class="t-body-small">
						<?php txl_text( $txl_benefit['text'] ); ?>
					</p>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
