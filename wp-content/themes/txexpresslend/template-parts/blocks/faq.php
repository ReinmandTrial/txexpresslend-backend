<?php
/**
 * Block "FAQ": questions and answers; the first one is open.
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

		<div
			class="max-w-200 mx-auto mt-6 lg:mt-10 divide-y divide-border-neutral border-b border-border-neutral">
			<?php foreach ( txl_rows( $args['items'] ) as $txl_i => $txl_faq ) : ?>
				<details class="group py-8"<?php echo 0 === $txl_i ? ' open' : ''; ?>>
					<summary
						class="flex items-start justify-between gap-4 cursor-pointer list-none [&::-webkit-details-marker]:hidden">
						<h3 class="t-h4-m lg:t-h4 text-dark"><?php echo esc_html( $txl_faq['question'] ); ?></h3>
						<span
							class="size-8 flex-none flex items-center justify-center rounded-full bg-gray text-blue transition-transform duration-300 group-open:rotate-180">
							<?php txl_icon( 'chevron-down', 'size-4' ); ?>
						</span>
					</summary>
					<div class="t-body-small lg:t-body text-blue-body mt-4">
						<?php txl_rich( $txl_faq['answer'] ); ?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
