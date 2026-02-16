<?php
/**
 * Helper funkcija za branje nastavitev Arnes S3
 * 
 * Zagotovi, da so vedno prisotne privzete vrednosti tudi če
 * opcije še niso shranjene v bazo.
 *
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vrne nastavitve Arnes S3 iz WordPress options
 * 
 * @return array Asociativno polje z nastavitvami
 */
function arnes_s3_settings() {
	return [
		'endpoint'      => get_option( 'arnes_s3_endpoint', 'https://shramba.arnes.si' ),
		'bucket'        => get_option( 'arnes_s3_bucket', 'arnes-shramba' ),
		'prefix'        => trim( get_option( 'arnes_s3_prefix', 'arnes-s3' ), '/' ),
		'org_id'        => get_option( 'arnes_s3_org_id', '' ),
		'access_key'    => get_option( 'arnes_s3_access_key' ),
		'secret_key'    => get_option( 'arnes_s3_secret_key' ),
		'cdn_domain'    => get_option( 'arnes_s3_cdn_domain' ),
		'keep_local'    => (bool) get_option( 'arnes_s3_keep_local', 0 ),
		'auto_upload'   => (bool) get_option( 'arnes_s3_auto_upload', 1 ), // Default: ON
		// Image quality settings (Phase 4.1)
		'jpeg_quality'     => (int) get_option( 'arnes_s3_jpeg_quality', 82 ),  // WordPress default
		'webp_quality'     => (int) get_option( 'arnes_s3_webp_quality', 82 ),  // WordPress default
		'avif_quality'     => (int) get_option( 'arnes_s3_avif_quality', 82 ),  // WordPress default
		// Image format priority (Phase 4.5)
		'format_priority'  => get_option( 'arnes_s3_format_priority', 'webp_first' ), // webp_first or avif_first
	];
}
