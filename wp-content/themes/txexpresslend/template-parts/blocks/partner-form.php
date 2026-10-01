<?php
/**
 * Block "Partner form (full screen)": an empty column for an embeddable partner form
 * (on the right on desktop, at the top on mobile) and benefits next to it (apply.html in the design).
 *
 * "Widget code" goes into the column as is; "Code before </body>" goes right before the
 * closing </body> tag — embeddable forms usually come as these two parts. Only
 * administrators edit these fields, so it's safe to put HTML/JS there.
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;

$txl_title       = $args['title'] ?: get_the_title();
$txl_footer_code = (string) $args['footer_code'];

if ( $txl_footer_code ) {
	// Priority 100: after everything else in the footer, right before </body>.
	add_action(
		'wp_footer',
		static function () use ( $txl_footer_code ) {
			echo $txl_footer_code; // phpcs:ignore WordPress.Security.EscapeOutput -- code from an administrator.
		},
		100
	);
}
?>
<div class="relative">
	<div class="absolute inset-0 max-lg:hidden grid grid-cols-2">
		<div class=""></div>
		<div class="bg-white-bg"></div>
	</div>
	<div class="ui-container relative z-10 min-h-screen lg:grid lg:grid-cols-2">
		<?php // lg:order-1 moves this column to the right on desktop; on mobile it comes first. ?>
		<div
			class="max-lg:bg-white-bg max-lg:-mx-4 max-lg:px-4 max-lg:min-h-screen flex flex-col justify-end py-33 lg:pt-10 lg:pb-30 lg:justify-center lg:order-1">
			<?php echo $args['widget']; // phpcs:ignore WordPress.Security.EscapeOutput -- widget code from an administrator. ?>
		</div>

		<?php get_template_part( 'template-parts/benefits', null, array( 'title' => $txl_title ) ); ?>
	</div>
</div>
