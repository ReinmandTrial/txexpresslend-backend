<?php
/**
 * Block "Regulations": a large card on the left and smaller cards on the right.
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;

$txl_cards = txl_rows( $args['cards'] );

// The large card on the left spans as many grid rows as there are cards on the right.
// Classes are written out in full: Tailwind scans the file text and won't find concatenated ones.
$txl_span = array( 'lg:row-span-1', 'lg:row-span-1', 'lg:row-span-2', 'lg:row-span-3', 'lg:row-span-4' )[ min( 4, count( $txl_cards ) ) ];
?>
<section class="py-10 lg:py-30">
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
			<p class="t-body-small lg:t-body-large text-blue-body text-center max-w-191.5 mx-auto mt-4">
				<?php txl_text( $args['subtitle'] ); ?>
			</p>
		<?php endif; ?>

		<div class="mt-10 lg:mt-15 gap-x-8 gap-y-8 lg:gap-y-6 grid grid-cols-1 lg:grid-cols-[1fr_1.2fr]">
			<div
				class="<?php echo esc_attr( $txl_span ); ?> rounded-20 p-4 lg:p-8 bg-white shadow-[0px_4px_16px_0px_#0F172A06,0px_24px_48px_0px_#0F172A12]">
				<div
					class="aspect-330/230 lg:aspect-510/375 rounded-xl lg:rounded-20 overflow-hidden mb-4 lg:mb-6">
					<?php txl_image( $args['main_image'], 'w-full h-full object-cover' ); ?>
				</div>
				<p class="t-body-small lg:t-body text-blue-light mt-2">
					<?php txl_text( $args['main_text'] ); ?>
				</p>
			</div>
			<?php foreach ( $txl_cards as $txl_card ) : ?>
				<div class="rounded-20 p-4 lg:p-6 bg-white shadow-[0px_2px_10px_0px_#0000000D]">
					<div class="aspect-330/140 lg:aspect-630/140 rounded-xl overflow-hidden mb-4">
						<?php txl_image( $txl_card['image'], 'w-full h-full object-cover' ); ?>
					</div>
					<h4 class="t-h4-m lg:t-h4 text-dark">
						<?php echo esc_html( $txl_card['title'] ); ?>
					</h4>
					<p class="t-body-small lg:t-label text-blue-light mt-2">
						<?php txl_text( $txl_card['text'] ); ?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
