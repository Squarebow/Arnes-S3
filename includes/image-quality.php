<?php
/**
 * Image Quality Management
 * 
 * Upravlja kvaliteto kompresije slik za JPEG, WebP in AVIF formate.
 * Uporablja WordPress `wp_editor_set_quality` filter.
 * 
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nastavi kvaliteto kompresije glede na format slike
 * 
 * WordPress uporablja ta filter za določanje kvalitete:
 * - image/jpeg
 * - image/webp
 * - image/avif
 * 
 * @param int    $quality     Privzeta kvaliteta (82 za WP 6.5+)
 * @param string $mime_type   MIME tip slike
 * @return int                Prilagojena kvaliteta
 */
function arnes_s3_set_image_quality( $quality, $mime_type ) {
	$settings = arnes_s3_settings();
	
	switch ( $mime_type ) {
		case 'image/jpeg':
			return $settings['jpeg_quality'];
		
		case 'image/webp':
			return $settings['webp_quality'];
		
		case 'image/avif':
			return $settings['avif_quality'];
		
		default:
			// Za druge formate (PNG, GIF) vrnemo privzeto vrednost
			return $quality;
	}
}
add_filter( 'wp_editor_set_quality', 'arnes_s3_set_image_quality', 10, 2 );

/**
 * Nastavi kvaliteto za JPEG editor (fallback)
 * 
 * Nekateri vtičniki uporabljajo starejši filter samo za JPEG.
 * 
 * @param int $quality Privzeta kvaliteta
 * @return int         JPEG kvaliteta iz nastavitev
 */
function arnes_s3_set_jpeg_quality( $quality ) {
	$settings = arnes_s3_settings();
	return $settings['jpeg_quality'];
}
add_filter( 'jpeg_quality', 'arnes_s3_set_jpeg_quality', 10, 1 );

/**
 * Nastavi kvaliteto za WebP editor (fallback)
 * 
 * @param int $quality Privzeta kvaliteta
 * @return int         WebP kvaliteta iz nastavitev
 */
function arnes_s3_set_webp_quality( $quality ) {
	$settings = arnes_s3_settings();
	return $settings['webp_quality'];
}
add_filter( 'webp_quality', 'arnes_s3_set_webp_quality', 10, 1 );

/**
 * ============================================================================
 * IMAGE FORMAT PRIORITY (Phase 4.5)
 * ============================================================================
 */

/**
 * Spremeni prioriteto formatov slik v srcset
 * 
 * WordPress privzeto generira srcset v vrstnem redu: WebP, AVIF, Original
 * Ta filter omogoča spremembo vrstnega reda na: AVIF, WebP, Original
 * 
 * Browser izbere PRVI format ki ga podpira iz srcset seznama.
 * 
 * @param array  $sources  Array srcset virov
 * @param array  $size_array  Array velikosti slike
 * @param string $image_src  URL izvorne slike
 * @param array  $image_meta  Metadata slike
 * @param int    $attachment_id  ID attachment-a
 * @return array  Spremenjeni srcset viri
 */
function arnes_s3_reorder_image_formats( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	$settings = arnes_s3_settings();
	
	// Če je nastavitev 'webp_first', ne spreminjamo ničesar (WordPress default)
	if ( $settings['format_priority'] === 'webp_first' ) {
		return $sources;
	}
	
	// Če je nastavitev 'avif_first', spremenimo vrstni red
	if ( $settings['format_priority'] === 'avif_first' ) {
		// Ločimo vire glede na format
		$avif_sources = [];
		$webp_sources = [];
		$other_sources = [];
		
		foreach ( $sources as $width => $source ) {
			// Preverimo končnico URL-ja
			if ( isset( $source['url'] ) ) {
				$url = $source['url'];
				
				if ( strpos( $url, '.avif' ) !== false ) {
					$avif_sources[ $width ] = $source;
				} elseif ( strpos( $url, '.webp' ) !== false ) {
					$webp_sources[ $width ] = $source;
				} else {
					$other_sources[ $width ] = $source;
				}
			}
		}
		
		// Združimo v novem vrstnem redu: AVIF first, potem WebP, potem ostalo
		$sources = array_merge( $avif_sources, $webp_sources, $other_sources );
	}
	
	return $sources;
}
add_filter( 'wp_calculate_image_srcset', 'arnes_s3_reorder_image_formats', 10, 5 );
