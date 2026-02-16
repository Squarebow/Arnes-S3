<?php
/**
 * Plugin Name: Arnes S3
 * Description: Samodejno nalaganje WordPress medijskih datotek v Arnes Shrambo (S3-compatible storage).
 * Version: 1.0.8
 * Author: SquareBow
 * License: GPL-2.0-or-later
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Tested up to: 6.9
 * Text Domain: arnes-s3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Osnovne konstante vtičnika
 */
define( 'ARNES_S3_PATH', plugin_dir_path( __FILE__ ) );
define( 'ARNES_S3_URL', plugin_dir_url( __FILE__ ) );
define( 'ARNES_S3_VERSION', '1.0.8' );

/**
 * Vključi AWS SDK in core komponente
 */
require_once ARNES_S3_PATH . 'vendor/autoload.php';

/**
 * Vključi helper funkcije
 */
require_once ARNES_S3_PATH . 'includes/settings.php';      // Helper za branje nastavitev
require_once ARNES_S3_PATH . 'includes/s3-client.php';     // S3 client initialization
require_once ARNES_S3_PATH . 'includes/uploader.php';      // Media upload handler
require_once ARNES_S3_PATH . 'includes/diagnostics.php';   // System diagnostics
require_once ARNES_S3_PATH . 'includes/url-rewriter.php';  // URL rewriting za S3/CDN
require_once ARNES_S3_PATH . 'includes/media-library-column.php'; // Media Library custom column
require_once ARNES_S3_PATH . 'includes/bulk-upload.php';   // Bulk upload helper functions
require_once ARNES_S3_PATH . 'includes/image-quality.php'; // Image quality management (Phase 4.1)
require_once ARNES_S3_PATH . 'includes/backup-restore.php'; // Backup & restore functions (Phase 4.2)

/**
 * Vključi admin komponente
 */
require_once ARNES_S3_PATH . 'includes/admin/admin-page.php';          // Admin page rendering
require_once ARNES_S3_PATH . 'includes/admin/admin-settings.php';      // Settings registration
require_once ARNES_S3_PATH . 'includes/admin/ajax-test-connection.php'; // AJAX connection test
require_once ARNES_S3_PATH . 'includes/admin/ajax-bulk-upload.php';    // AJAX bulk upload handlers
require_once ARNES_S3_PATH . 'includes/admin/ajax-backup.php';         // AJAX backup handlers (Phase 4.2)

/**
 * Aktivacijski hook - nastavi privzete vrednosti in izvedi diagnostiko
 */
register_activation_hook( __FILE__, 'arnes_s3_activation' );

function arnes_s3_activation() {
	// Nastavi privzeto vrednost za "Ohrani lokalne datoteke" na DA (1)
	if ( false === get_option( 'arnes_s3_keep_local' ) ) {
		add_option( 'arnes_s3_keep_local', 1 );
	}
	
	// Nastavi privzeto vrednost za "Serve mode" na "arnes" (direktno iz S3)
	if ( false === get_option( 'arnes_s3_serve_mode' ) ) {
		add_option( 'arnes_s3_serve_mode', 'arnes' );
	}
	
	// Nastavi privzeto vrednost za "Auto upload" na DA (1)
	if ( false === get_option( 'arnes_s3_auto_upload' ) ) {
		add_option( 'arnes_s3_auto_upload', 1 );
	}
	
	// Izvedi diagnostiko sistema (image support, zaznaj optimization vtičnike)
	arnes_s3_run_diagnostics();
}
