<?php
/**
 * Admin fields (the Secure Custom Fields plugin, same API as ACF).
 *
 * The field groups live in acf-json/*.json. SCF loads them from there and writes
 * them back when a group is edited in the admin (SCF → Field Groups), so the fields
 * are versioned in git together with the templates.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'acf/settings/save_json', static fn() => get_theme_file_path( 'acf-json' ) );
add_filter( 'acf/settings/load_json', static fn() => array( get_theme_file_path( 'acf-json' ) ) );

// Site Settings page: content shared by all pages (header, hero, footer).
add_action(
	'acf/init',
	static function () {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		acf_add_options_page(
			array(
				'page_title' => 'Site Settings',
				'menu_title' => 'Site Settings',
				'menu_slug'  => 'txl-settings',
				'icon_url'   => 'dashicons-admin-customizer',
				'position'   => 3,
				'redirect'   => false,
			)
		);
	}
);

// Collapsed page builder blocks show their heading next to the type: "FAQ — Frequently Asked Questions".
add_filter(
	'acf/fields/flexible_content/layout_title/name=blocks',
	static function ( $title ) {
		$heading = trim( implode( ' ', array_filter( array( get_sub_field( 'title' ), get_sub_field( 'title_accent' ), get_sub_field( 'title_after' ) ) ) ) );

		if ( $heading ) {
			$title .= ' — ' . esc_html( wp_trim_words( $heading, 8 ) );
		}

		if ( get_sub_field( 'hidden' ) ) {
			$title .= ' <em>(hidden)</em>';
		}

		return $title;
	}
);

// Trimmed visual editor toolbar for short texts: bold, italic and links only.
add_filter(
	'acf/fields/wysiwyg/toolbars',
	static function ( $toolbars ) {
		$toolbars['Inline'] = array( 1 => array( 'bold', 'italic', 'link', 'unlink', 'undo', 'redo' ) );

		return $toolbars;
	}
);
