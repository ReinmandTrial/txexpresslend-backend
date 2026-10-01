<?php
/**
 * Block "How it works": an image with notes on the left, cost components on the right.
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
		<div class="mt-8 lg:mt-15 grid grid-cols-1 md:grid-cols-[1.45fr_1fr] gap-8 lg:gap-6">
			<div class="pt-6 px-4 pb-8 lg:p-6 rounded-xl bg-white-bg grid grid-cols-1 lg:grid-cols-2 gap-6">
				<div class="aspect-square lg:aspect-339/502 rounded-20 overflow-hidden">
					<?php txl_image( $args['image_mobile'], 'w-full h-full object-cover lg:hidden', array( 'alt' => '' ) ); ?>
					<?php txl_image( $args['image_desktop'], 'w-full h-full object-cover max-lg:hidden', array( 'alt' => '' ) ); ?>
				</div>
				<div class="">
					<?php foreach ( txl_rows( $args['notes'] ) as $txl_note ) : ?>
						<div
							class="not-last:mb-4 lg:not-last:mb-8 relative not-last:after:absolute not-last:after:-bottom-2 not-last:after:left-0 not-last:after:w-full not-last:after:h-px not-last:after:bg-border-neutral bg-white rounded-xl p-2 t-label text-blue-body [&_strong]:[font-weight:inherit] [&_strong]:text-dark">
							<?php txl_rich( $txl_note['text'] ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="">
				<?php if ( $args['components_title'] ) : ?>
					<h3 class="text-blue-lux t-h2-m lg:t-h2 mb-6">
						<?php echo esc_html( $args['components_title'] ); ?>
					</h3>
				<?php endif; ?>
				<ul class="space-y-6">
					<?php foreach ( txl_rows( $args['components'] ) as $txl_component ) : ?>
						<li
							class="p-6 border border-[#E5E7EB] bg-white rounded-xl shadow-[0px_2px_10px_0px_#0000000D] flex gap-5">
							<div
								class="size-14 flex items-center justify-center rounded-2xl bg-[#EAF1F8] border border-[#D6E4EF] flex-none">
								<?php txl_icon( $txl_component['icon'], 'size-6' ); ?>
							</div>
							<div class="t-body-small lg:t-body-large text-blue-light [&_strong]:[font-weight:inherit] [&_strong]:text-blue">
								<?php txl_rich( $txl_component['text'] ); ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>
