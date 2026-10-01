<?php
/**
 * Block "Eligibility": a card with requirements and an image next to it.
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

		<div
			class="mt-10 lg:py-8 flex flex-col-reverse gap-6 lg:grid lg:grid-cols-[1fr_max-content] lg:gap-20 lg:items-center">
			<div
				class="bg-white border border-[#E5E7EB] shadow-[0px_2px_10px_0px_#0000000D] py-8 px-7 rounded-2xl">
				<?php if ( $args['card_title'] ) : ?>
					<h3 class="mb-6 t-h3 text-dark">
						<?php echo esc_html( $args['card_title'] ); ?>
					</h3>
				<?php endif; ?>
				<ul class="space-y-3">
					<?php foreach ( txl_rows( $args['items'] ) as $txl_item ) : ?>
						<li class="flex items-center gap-3">
							<div
								class="flex-none size-6.5 rounded-full bg-[#16A34A1F] flex items-center justify-center">
								<?php txl_icon( 'triangle-right', 'size-2.5 text-[#16A34A]' ); ?>
							</div>
							<span class="t-body text-blue-body">
								<?php echo esc_html( $txl_item['text'] ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $args['note'] ) : ?>
					<p class="t-label mt-12">
						<?php txl_text( $args['note'] ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="max-w-117.5 aspect-360/260 rounded-2xl overflow-hidden">
				<?php txl_image( $args['image'], 'w-full h-full object-cover' ); ?>
			</div>
		</div>
	</div>
</section>
