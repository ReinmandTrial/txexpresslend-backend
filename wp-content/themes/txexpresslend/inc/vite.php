<?php
/**
 * Enqueues the files built by Vite.
 *
 * Vite puts a hash in file names (main-B1LV4XlG.css) so browsers don't keep a stale
 * copy in cache. Which file is current is listed in dist/.vite/manifest.json:
 * the key is the source file, `file` is the built one.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contents of manifest.json. Empty array if the theme hasn't been built yet.
 */
function txl_vite_manifest(): array {
	static $manifest = null;

	if ( null === $manifest ) {
		$path     = get_theme_file_path( 'dist/.vite/manifest.json' );
		$manifest = is_readable( $path ) ? (array) json_decode( (string) file_get_contents( $path ), true ) : array();
	}

	return $manifest;
}

/**
 * URL of a built file by its source path: txl_vite_url( 'src/main.js' ).
 */
function txl_vite_url( string $source ): ?string {
	$file = txl_vite_manifest()[ $source ]['file'] ?? null;

	return $file ? get_theme_file_uri( 'dist/' . $file ) : null;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		$css = txl_vite_url( 'src/assets/css/main.css' );
		$js  = txl_vite_url( 'src/main.js' );

		if ( $css ) {
			// No version needed: the hash is already in the file name.
			wp_enqueue_style( 'txl-main', $css, array(), null );
		}

		if ( $js ) {
			// Printed as <script type="module">, same as in the design.
			wp_enqueue_script_module( 'txl-main', $js, array(), null );
		}
	}
);

// Without a build the site renders as bare HTML. Tell the admin what to do.
add_action(
	'admin_notices',
	static function () {
		if ( txl_vite_manifest() ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo 'The TX Express Lend theme is not built: <code>dist/.vite/manifest.json</code> is missing. ';
		echo 'Run <code>pnpm install &amp;&amp; pnpm build</code> in the theme folder.';
		echo '</p></div>';
	}
);
