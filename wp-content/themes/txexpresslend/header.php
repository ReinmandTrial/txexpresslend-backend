<?php
/**
 * Start of every page: <head>, the header and the mobile menu.
 * Page templates include it via get_header() and then output their own <main>.
 */

defined( 'ABSPATH' ) || exit;

$txl_menu     = txl_menu_items( 'primary' );
$txl_cta_text = txl_option( 'header_cta_text' );
$txl_cta_url  = txl_loan_form_url();
$txl_logo_id  = txl_option( 'header_logo' );

// Logo from Site Settings, or the theme's default if none was uploaded.
$txl_logo = static function () use ( $txl_logo_id ) {
	if ( $txl_logo_id ) {
		txl_image( $txl_logo_id, 'w-full h-auto', array( 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager' ) );
		return;
	}

	printf(
		'<img src="%s" alt="%s" class="w-full h-auto" />',
		esc_url( get_theme_file_uri( 'assets/logo.svg' ) ),
		esc_attr( get_bloginfo( 'name' ) )
	);
};
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<?php wp_head(); ?>
</head>

<body <?php body_class( 'bg-white flex flex-col min-h-full' ); ?>>
	<?php wp_body_open(); ?>

	<header
		class="sticky top-0 z-200 bg-white shadow-[2px_3px_9px_0px_#2F485814] flex flex-col justify-center py-2 min-h-13 lg:min-h-19">
		<div class="ui-container flex items-center gap-3 justify-between">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="block w-48.5 flex-none">
				<?php $txl_logo(); ?>
			</a>

			<?php if ( $txl_menu ) : ?>
				<nav class="max-lg:hidden flex items-center gap-8" aria-label="Main">
					<?php foreach ( $txl_menu as $txl_item ) : ?>
						<a <?php txl_menu_link_attrs( $txl_item ); ?> class="t-label text-[#475569]"><?php echo esc_html( $txl_item->title ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php if ( $txl_cta_text ) : ?>
				<a href="<?php echo esc_url( $txl_cta_url ); ?>" class="max-lg:hidden btn-orange-base px-5 py-3 t-label"><?php echo esc_html( $txl_cta_text ); ?></a>
			<?php endif; ?>

			<button type="button" class="lg:hidden -mr-2 p-2" aria-label="Open menu" aria-expanded="false"
				aria-controls="mobile-menu" data-menu-open>
				<?php txl_icon( 'burger', 'size-6 text-blue-lux' ); ?>
			</button>
		</div>
	</header>

	<div id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu"
		class="lg:hidden fixed inset-0 z-300 flex flex-col bg-white invisible opacity-0 transition-opacity duration-300 data-open:visible data-open:opacity-100"
		data-menu>
		<div class="flex-none flex flex-col justify-center py-2 min-h-13">
			<div class="ui-container flex items-center gap-3 justify-between">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="block w-48.5 flex-none">
					<?php $txl_logo(); ?>
				</a>

				<button type="button" class="-mr-2 p-2" aria-label="Close menu" data-menu-close>
					<?php txl_icon( 'close', 'size-6 text-blue-lux' ); ?>
				</button>
			</div>
		</div>

		<nav class="ui-container px-10 flex-auto overflow-y-auto py-6" aria-label="Mobile">
			<?php foreach ( $txl_menu as $txl_item ) : ?>
				<a <?php txl_menu_link_attrs( $txl_item ); ?> class="block py-4 t-body-semibold text-[#475569]"><?php echo esc_html( $txl_item->title ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( $txl_cta_text ) : ?>
			<div class="ui-container px-8 flex-none pt-4 pb-10">
				<a href="<?php echo esc_url( $txl_cta_url ); ?>" class="block btn-orange-base py-3 t-label w-full"><?php echo esc_html( $txl_cta_text ); ?></a>
			</div>
		<?php endif; ?>
	</div>
