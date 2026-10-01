<?php
/**
 * Block "Steps": numbered steps with images, on a light background.
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="bg-white-bg py-10 lg:py-30">
	<div class="ui-container">
		<?php if ( $args['overline'] ) : ?>
			<div class="ui-overline mb-4">
				<?php echo esc_html( $args['overline'] ); ?>
			</div>
		<?php endif; ?>
		<?php if ( $args['title'] ) : ?>
			<h2 class="max-w-200 mx-auto mb-14 lg:mb-20 t-display-m lg:t-display text-dark text-center">
				<?php echo esc_html( $args['title'] ); ?>
			</h2>
		<?php endif; ?>
		<div class="grid grid-cols-1 md:grid-cols-3 gap-10">
			<?php foreach ( txl_rows( $args['items'] ) as $txl_i => $txl_step ) : ?>
				<div class="">
					<div class="size-50 lg:size-78 mx-auto mb-6">
						<?php txl_image( $txl_step['image'], 'w-full h-full object-contain' ); ?>
					</div>
					<div class="">
						<h3 class="mb2 lg:mb-3 flex gap-2 items-center t-h3-m lg:t-h3 text-blue-lux">
							<span class="text-orange"><?php echo esc_html( sprintf( '%02d', $txl_i + 1 ) ); ?></span>
							<?php echo esc_html( $txl_step['title'] ); ?>
						</h3>
						<p class="t-body-small lg:t-body text-blue-body">
							<?php txl_text( $txl_step['text'] ); ?>
						</p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
