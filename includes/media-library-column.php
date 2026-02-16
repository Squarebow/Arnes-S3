<?php
/**
 * Media Library Columns za Arnes S3
 * 
 * Doda custom column v WordPress Media Library list view,
 * ki prikazuje status datotek (Local only / Cloud only / Local + Cloud).
 * 
 * Uporablja WordPress native styling (badges) brez emojijev.
 * 
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue admin CSS za custom column styling
 */
add_action( 'admin_enqueue_scripts', 'arnes_s3_column_styles' );

function arnes_s3_column_styles( $hook ) {
	// Samo na Media Library page
	if ( $hook !== 'upload.php' ) {
		return;
	}
	
	// Inline CSS za S3 status badges (WordPress native colors)
	$css = '
		.arnes-s3-badge {
			display: inline-block;
			padding: 3px 8px;
			font-size: 12px;
			line-height: 1.4;
			border-radius: 3px;
			font-weight: 500;
			white-space: nowrap;
		}
		.arnes-s3-both {
			background: #d7f0ff;
			color: #135e96;
		}
		.arnes-s3-cloud {
			background: #ecf7ed;
			color: #1e4620;
		}
		.arnes-s3-local {
			background: #f0f0f1;
			color: #3c434a;
		}
		.arnes-s3-missing {
			background: #fcf0f1;
			color: #8c1c28;
		}
	';
	
	wp_add_inline_style( 'wp-admin', $css );
}

/**
 * Doda custom column v Media Library
 */
add_filter( 'manage_media_columns', 'arnes_s3_add_media_column' );

function arnes_s3_add_media_column( $columns ) {
	
	// Vstavi novo kolono pred "Date"
	$new_columns = [];
	
	foreach ( $columns as $key => $value ) {
		
		// Pred "Date" vstavi našo kolono
		if ( $key === 'date' ) {
			$new_columns['arnes_s3_status'] = 'S3 Status';
		}
		
		$new_columns[ $key ] = $value;
	}
	
	return $new_columns;
}

/**
 * Prikaži content custom kolone
 */
add_action( 'manage_media_custom_column', 'arnes_s3_display_media_column', 10, 2 );

function arnes_s3_display_media_column( $column_name, $attachment_id ) {
	
	if ( $column_name !== 'arnes_s3_status' ) {
		return;
	}
	
	// Preveri lokalno obstoj
	$file = get_attached_file( $attachment_id );
	$local_exists = file_exists( $file );
	
	// Preveri S3 obstoj
	$object_key = get_post_meta( $attachment_id, '_arnes_s3_object', true );
	$cloud_exists = ! empty( $object_key );
	
	// Določi status in prikaži badge (brez emojijev)
	if ( $local_exists && $cloud_exists ) {
		// Obe lokaciji
		echo '<span class="arnes-s3-badge arnes-s3-both">Lokalno + S3</span>';
		
	} elseif ( $cloud_exists && ! $local_exists ) {
		// Samo S3
		echo '<span class="arnes-s3-badge arnes-s3-cloud">Samo S3</span>';
		
	} elseif ( $local_exists && ! $cloud_exists ) {
		// Samo lokalno (še ni uploadano)
		echo '<span class="arnes-s3-badge arnes-s3-local">Samo lokalno</span>';
		
	} else {
		// Nobena lokacija (ne bi smelo biti mogoče)
		echo '<span class="arnes-s3-badge arnes-s3-missing">Manjka</span>';
	}
}

/**
 * Naredi custom column sortable (opcijsko - za prihodnje verzije)
 */
add_filter( 'manage_upload_sortable_columns', 'arnes_s3_sortable_media_column' );

function arnes_s3_sortable_media_column( $columns ) {
	$columns['arnes_s3_status'] = 'arnes_s3_status';
	return $columns;
}
