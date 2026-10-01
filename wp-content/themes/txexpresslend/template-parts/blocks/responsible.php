<?php
/**
 * Block "Callout": a heading and a highlighted note with an info icon
 * (in the design — "Responsible Borrowing").
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="">
	<div class="ui-container">
		<?php if ( $args['overline'] ) : ?>
			<div class="ui-overline mb-4">
				<?php echo esc_html( $args['overline'] ); ?>
			</div>
		<?php endif; ?>
		<?php if ( $args['title'] ) : ?>
			<h2 class="max-w-200 mx-auto t-display-m lg:t-display text-dark text-center">
				<?php echo esc_html( $args['title'] ); ?>
			</h2>
		<?php endif; ?>
		<?php if ( $args['subtitle'] ) : ?>
			<p class="t-body-small lg:t-body-large text-blue-body text-center max-w-170 mx-auto mt-4">
				<?php txl_text( $args['subtitle'] ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $args['note'] ) : ?>
			<div
				class="max-w-241.5 mx-auto mt-6 flex items-start gap-3 rounded-xl bg-white-bg px-4 py-6 lg:p-7">
				<?php txl_icon( 'info', 'size-4.5 flex-none text-blue-lux' ); ?>
				<div class="t-body-small text-blue-lux [&_strong]:t-body-semibold">
					<?php txl_rich( $args['note'] ); ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
