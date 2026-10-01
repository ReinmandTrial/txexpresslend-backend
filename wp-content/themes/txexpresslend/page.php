<?php
/**
 * Every page, the home page included: the blocks from the page builder (see inc/blocks.php).
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
	<main class="flex-auto">
		<?php txl_render_blocks(); ?>
	</main>
<?php
get_footer();
