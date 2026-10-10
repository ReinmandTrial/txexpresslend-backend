<?php
/**
 * Block "Loan options": a heading and a row of cards; a card with a link is clickable as a whole.
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
			<h2 class="max-w-200 mx-auto mb-4 lg:mb-10 t-display-m lg:t-display text-dark text-center">
				<?php echo esc_html( $args['title'] ); ?>
			</h2>
		<?php endif; ?>
		<div class="grid grid-cols-2 md:grid-cols-4 gap-y-7 gap-x-2 lg:gap-x-5">
			<?php foreach ( txl_rows( $args['cards'] ) as $txl_card ) : ?>
				<?php
				// A card with a link is the link itself; without one it's a plain box.
				$txl_link = ! empty( $txl_card['link']['url'] ) ? $txl_card['link'] : null;
				$txl_tag  = $txl_link ? 'a' : 'div';
				?>
				<<?php echo $txl_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- only a or div. ?>
					<?php if ( $txl_link ) : ?>
						href="<?php echo esc_url( $txl_link['url'] ); ?>"
						<?php if ( $txl_link['target'] ) : ?>
							target="<?php echo esc_attr( $txl_link['target'] ); ?>" rel="noopener"
						<?php endif; ?>
					<?php endif; ?>
					class="flex flex-col gap-6 rounded-xl bg-white border border-border-neutral shadow-[0px_8px_24px_-4px_#12262614] hover:border-blue-light hover:shadow-[0px_4px_16px_0px_#0F172A06,0px_24px_48px_0px_#0F172A12] transition pt-6 lg:pt-14 px-3 lg:px-6 pb-5.5 lg:pb-14">
					<div class="w-25 lg:w-32 h-27.5 lg:h-35 mx-auto flex flex-col justify-end">
						<?php txl_image( $txl_card['image'], 'w-full h-auto object-contain' ); ?>
					</div>
					<div class="text-center">
						<h3 class="t-h3-m mb-2 text-blue-lux">
							<?php echo esc_html( $txl_card['title'] ); ?>
						</h3>
						<p class="t-body-small text-blue-body">
							<?php txl_text( $txl_card['text'] ); ?>
						</p>
					</div>
				</<?php echo $txl_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
