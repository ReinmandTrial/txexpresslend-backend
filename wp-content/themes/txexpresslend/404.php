<?php
/**
 * Page not found.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
	<main class="flex-auto">
		<div class="ui-container pt-15 pb-30 lg:py-30 text-center">
			<div class="ui-overline mb-4">Error 404</div>
			<h1 class="max-w-200 mx-auto t-display-m lg:t-display text-dark">Page not found</h1>
			<p class="t-body-small lg:t-body-large text-blue-body max-w-170 mx-auto mt-4">
				The page you are looking for doesn&rsquo;t exist or has been moved.
			</p>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-block btn-orange-base mt-8">Go to homepage</a>
		</div>
	</main>
<?php
get_footer();
