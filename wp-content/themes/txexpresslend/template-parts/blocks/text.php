<?php
/**
 * Block "Text": a heading and an article from the visual editor (text-page.html in the design).
 *
 * The editor can't put classes on headings and paragraphs, so their styles hang off
 * the .ui-content container (src/assets/css/main.css).
 *
 * @var array $args Block fields plus h1 and uid (see txl_render_blocks()).
 */

defined( 'ABSPATH' ) || exit;

$txl_tag   = $args['h1'] ? 'h1' : 'h2';
$txl_title = $args['title'] ?: get_the_title();
?>
<div class="ui-container">
	<article class="max-w-230 mx-auto">
		<<?php echo $txl_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- only h1 or h2. ?> class="t-h2-m lg:t-display-m text-dark mb-6">
			<?php echo esc_html( $txl_title ); ?>
		</<?php echo $txl_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>

		<div class="ui-content">
			<?php echo $args['content']; // phpcs:ignore WordPress.Security.EscapeOutput -- formatted editor content, like the_content(). ?>
		</div>
	</article>
</div>
