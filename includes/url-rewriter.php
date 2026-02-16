<?php
/**
 * URL Rewriter za Arnes S3
 * 
 * Prepiše WordPress media URL-je da kaže na Arnes S3 ali CDN.
 * Omogoča serving datotek direktno iz S3/CDN namesto iz lokalnega strežnika.
 * 
 * Funkcionalnosti:
 * - Rewriting attachment URLs (wp_get_attachment_url)
 * - Rewriting image src URLs (wp_get_attachment_image_src)
 * - Support za Arnes S3 direktno ali CDN
 * - Ohranitev originalnih URL-jev če datoteka ni v S3
 * 
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriraj URL rewriting filtere samo če je funkcionalnost omogočena
 */
function arnes_s3_init_url_rewriting() {
	$settings = arnes_s3_settings();
	
	// Preveri ali je URL rewriting omogočen (preverimo če imamo credentials)
	if ( empty( $settings['access_key'] ) || empty( $settings['secret_key'] ) ) {
		return;
	}
	
	// Registriraj filtere za URL rewriting
	add_filter( 'wp_get_attachment_url', 'arnes_s3_rewrite_attachment_url', 10, 2 );
	add_filter( 'wp_get_attachment_image_src', 'arnes_s3_rewrite_image_src', 10, 4 );
	add_filter( 'wp_calculate_image_srcset', 'arnes_s3_rewrite_srcset', 10, 5 );
}
add_action( 'init', 'arnes_s3_init_url_rewriting' );

/**
 * Rewrite attachment URL
 * 
 * Prepiše glavne attachment URL-je (slike, PDF, video, etc.)
 * 
 * @param string $url Original WordPress URL
 * @param int $attachment_id Attachment ID
 * @return string Rewritten URL (S3 ali CDN) ali original če datoteka ni v S3
 */
function arnes_s3_rewrite_attachment_url( $url, $attachment_id ) {
	
	// Pridobi S3 object key iz post meta
	$object_key = get_post_meta( $attachment_id, '_arnes_s3_object', true );
	
	// Če datoteka ni v S3, vrni original URL
	if ( empty( $object_key ) ) {
		return $url;
	}
	
	// Sestavi nov URL glede na nastavitve
	return arnes_s3_build_url( $object_key );
}

/**
 * Rewrite image src URLs
 * 
 * Prepiše image src array za wp_get_attachment_image_src()
 * 
 * @param array|false $image Array s podatki o sliki ali false
 * @param int $attachment_id Attachment ID
 * @param string|int[] $size Image size
 * @param bool $icon Whether to use icon
 * @return array|false Modified image array
 */
function arnes_s3_rewrite_image_src( $image, $attachment_id, $size, $icon ) {
	
	// Če ni image array, vrni original
	if ( ! is_array( $image ) ) {
		return $image;
	}
	
	// Pridobi S3 object key
	$object_key = get_post_meta( $attachment_id, '_arnes_s3_object', true );
	
	// Če datoteka ni v S3, vrni original
	if ( empty( $object_key ) ) {
		return $image;
	}
	
	// Rewrite samo URL (index 0), ostalo pusti nespremenjeno (width, height, is_intermediate)
	$image[0] = arnes_s3_build_url( $object_key );
	
	return $image;
}

/**
 * Rewrite responsive image srcset
 * 
 * Prepiše srcset URLs za responsive images
 * 
 * @param array $sources Srcset sources
 * @param array $size_array Image size array
 * @param string $image_src Original image URL
 * @param array $image_meta Image metadata
 * @param int $attachment_id Attachment ID
 * @return array Modified srcset sources
 */
function arnes_s3_rewrite_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	
	// Pridobi base S3 object key
	$base_object_key = get_post_meta( $attachment_id, '_arnes_s3_object', true );
	
	// Če datoteka ni v S3, vrni original
	if ( empty( $base_object_key ) ) {
		return $sources;
	}
	
	// Loop skozi vse srcset source-e in rewrite njihove URL-je
	foreach ( $sources as &$source ) {
		
		// Ekstrahiraj filename iz originala (zadnji del URL-ja)
		$original_filename = basename( $source['url'] );
		
		// Sestavi nov S3 object key (replace filename v base object key)
		$new_object_key = dirname( $base_object_key ) . '/' . $original_filename;
		
		// Sestavi nov URL
		$source['url'] = arnes_s3_build_url( $new_object_key );
	}
	
	return $sources;
}

/**
 * Build S3 ali CDN URL iz object key
 * 
 * Glede na nastavitve sestavi URL ki kaže bodisi direktno na Arnes S3
 * bodisi na CDN (npr. Cloudflare).
 * 
 * POMEMBNO: Arnes Shramba uporablja poseben URL format:
 * https://shramba.arnes.si/[ORG_ID]:[BUCKET]/[OBJECT_KEY]
 * 
 * @param string $object_key S3 object key (prefix/blog_id/year/month/file.ext)
 * @return string Popoln URL do datoteke
 */
function arnes_s3_build_url( $object_key ) {
	
	$settings = arnes_s3_settings();
	
	// Določi serve mode (arnes ali cdn)
	$serve_mode = get_option( 'arnes_s3_serve_mode', 'arnes' );
	
	if ( $serve_mode === 'cdn' && ! empty( $settings['cdn_domain'] ) ) {
		
		// CDN mode: uporabi CDN domeno
		$cdn_domain = rtrim( $settings['cdn_domain'], '/' );
		
		// Če imamo org_id, dodaj ga v URL
		if ( ! empty( $settings['org_id'] ) ) {
			return $cdn_domain . '/' . $settings['org_id'] . ':' . $settings['bucket'] . '/' . $object_key;
		} else {
			return $cdn_domain . '/' . $settings['bucket'] . '/' . $object_key;
		}
		
	} else {
		
		// Arnes S3 mode: uporabi direkten S3 URL z organization ID
		$endpoint = $settings['endpoint'];
		
		// Normaliziraj endpoint (odstrani https:// če je prisoten)
		$endpoint = preg_replace( '/^https?:\/\//', '', $endpoint );
		
		// Če imamo organization ID, uporabi Arnes format: ORG_ID:BUCKET
		if ( ! empty( $settings['org_id'] ) ) {
			return 'https://' . $endpoint . '/' . $settings['org_id'] . ':' . $settings['bucket'] . '/' . $object_key;
		} else {
			// Fallback na stari format (brez org_id)
			return 'https://' . $endpoint . '/' . $settings['bucket'] . '/' . $object_key;
		}
	}
}

/**
 * Preveri ali datoteka obstaja v S3
 * 
 * Izvede HEAD request na S3 da preveri obstoj datoteke.
 * Uporablja se za Media Library status column.
 * 
 * @param string $object_key S3 object key
 * @return bool True če datoteka obstaja v S3, false sicer
 */
function arnes_s3_file_exists_in_s3( $object_key ) {
	
	$settings = arnes_s3_settings();
	
	// Preveri credentials
	if ( empty( $settings['access_key'] ) || empty( $settings['secret_key'] ) ) {
		return false;
	}
	
	// Pridobi S3 client
	$s3 = arnes_s3_client();
	if ( ! $s3 ) {
		return false;
	}
	
	try {
		
		// HEAD request na S3 (ne downloadaj datoteke)
		$s3->headObject( [
			'Bucket' => $settings['bucket'],
			'Key'    => $object_key,
		] );
		
		return true;
		
	} catch ( Exception $e ) {
		return false;
	}
}
