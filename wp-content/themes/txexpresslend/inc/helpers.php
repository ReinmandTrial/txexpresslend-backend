<?php
/**
 * Small helpers for templates.
 */

defined( 'ABSPATH' ) || exit;

/*
 * Without the SCF plugin there is no get_field() and the site would crash with a fatal error.
 * Provide a stub instead: pages render without content and the admin shows a hint.
 */
if ( ! function_exists( 'get_field' ) ) {
	function get_field( $selector, $post_id = false, $format_value = true ) {
		return null;
	}

	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>The TX Express Lend theme requires the <strong>Secure Custom Fields</strong> plugin. Install and activate it under Plugins.</p></div>';
		}
	);
}

/**
 * Value from the Site Settings page.
 */
function txl_option( string $name ) {
	return get_field( $name, 'option' );
}

/**
 * Repeater rows. An empty SCF repeater returns false, not [].
 */
function txl_rows( $value ): array {
	return is_array( $value ) ? $value : array();
}

/**
 * Icon from the sprite: txl_icon( 'mail', 'size-4' ).
 * Color comes from a text-* class: the icons are drawn with currentColor.
 */
function txl_icon( string $name, string $class = '' ): void {
	printf(
		'<svg%s aria-hidden="true"><use href="%s#%s" /></svg>',
		$class ? ' class="' . esc_attr( $class ) . '"' : '',
		esc_url( (string) txl_vite_url( 'icons.svg' ) ),
		esc_attr( $name )
	);
}

/**
 * Media Library image by ID (SCF image fields return an ID).
 * WP adds srcset, width/height and lazy loading on its own.
 */
function txl_image( $id, string $class = '', array $attr = array() ): void {
	$id = (int) $id;

	if ( ! $id ) {
		return;
	}

	$attr = array( 'class' => $class ) + $attr;
	$html = wp_get_attachment_image( $id, 'full', false, $attr );

	// WP doesn't treat SVG as an image and won't build an <img> for it, so we do it ourselves.
	if ( ! $html ) {
		$src = wp_get_attachment_url( $id );

		if ( ! $src ) {
			return;
		}

		$attr += array(
			'src'     => $src,
			'alt'     => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'loading' => 'lazy',
		);

		$html = '<img';
		foreach ( $attr as $name => $value ) {
			$html .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
		}
		$html .= ' />';
	}

	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
}

/**
 * Plain text from a textarea: HTML is escaped, line breaks become <br />.
 */
function txl_text( ?string $text ): void {
	echo nl2br( esc_html( trim( (string) $text ) ) );
}

/**
 * Text from the visual editor (bold, italic, links).
 */
function txl_rich( ?string $html ): void {
	echo wp_kses_post( (string) $html );
}

/**
 * Textarea lines as an array, without empty ones.
 */
function txl_lines( ?string $text ): array {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $text ) ) ) );
}

/**
 * Amount in the design's format: 1500 → $1,500.
 */
function txl_money( int $amount ): string {
	return '$' . number_format( $amount );
}

/**
 * Loan amount range from Site Settings → Loan amounts.
 *
 * @return array{min: int, max: int, step: int, default: int, values: int[]}
 */
function txl_amounts(): array {
	$min     = (int) ( txl_option( 'amount_min' ) ?: 100 );
	$max     = max( $min, (int) ( txl_option( 'amount_max' ) ?: 1500 ) );
	$step    = max( 1, (int) ( txl_option( 'amount_step' ) ?: 100 ) );
	$default = (int) ( txl_option( 'amount_default' ) ?: $min );

	return array(
		'min'     => $min,
		'max'     => $max,
		'step'    => $step,
		'default' => min( max( $default, $min ), $max ),
		'values'  => range( $min, $max, $step ),
	);
}

/**
 * Loan form URL: every "Get started" button and the Quick Loan form lead here.
 */
function txl_loan_form_url(): string {
	$id = (int) txl_option( 'loan_form_page' ) ?: txl_page_with_block( 'loan_form' );

	return $id ? (string) get_permalink( $id ) : home_url( '/' );
}

/**
 * Top-level menu items for a location registered in register_nav_menus().
 *
 * wp_nav_menu() outputs its own <ul><li> markup, while in the design menus are
 * plain links with Tailwind classes. So we take the items and render them ourselves.
 *
 * @return WP_Post[] Items have ->url, ->title, ->target, ->current.
 */
function txl_menu_items( string $location ): array {
	$locations = get_nav_menu_locations();

	if ( empty( $locations[ $location ] ) ) {
		return array();
	}

	$items = wp_get_nav_menu_items( $locations[ $location ] );

	if ( ! $items ) {
		return array();
	}

	// Sets ->current so the active item can be marked with aria-current.
	_wp_menu_item_classes_by_context( $items );

	return array_values( array_filter( $items, static fn( $item ) => ! $item->menu_item_parent ) );
}

/**
 * Menu item link attributes: href, target, aria-current.
 */
function txl_menu_link_attrs( WP_Post $item ): void {
	printf( 'href="%s"', esc_url( $item->url ) );

	if ( $item->target ) {
		printf( ' target="%s" rel="noopener"', esc_attr( $item->target ) );
	}

	if ( $item->current ) {
		echo ' aria-current="page"';
	}
}
