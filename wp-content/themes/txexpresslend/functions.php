<?php
/**
 * Theme entry point. WordPress loads this file on every request.
 * The code lives in inc/, this file only includes it.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/vite.php';
require_once __DIR__ . '/inc/fields.php';
require_once __DIR__ . '/inc/blocks.php';
require_once __DIR__ . '/inc/leads.php';
require_once __DIR__ . '/inc/forms.php';
