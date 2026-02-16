<?php
/**
 * Bulk Upload funkcionalnost za Arnes S3
 * 
 * Omogoča masovno nalaganje obstoječih medijskih datotek iz WordPress Media Library v S3.
 * 
 * Funkcionalnosti:
 * - Skeniranje Media Library
 * - Zaznavanje manjkajočih datotek v S3
 * - Batch processing (10 datotek na batch)
 * - Resume funkcionalnost (WP transients)
 * - Dry-run mode (preview brez uploada)
 * - Filtriranje po datumu, tipu, velikosti
 * 
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Skeniraj Media Library in vrni statistiko datotek
 * 
 * @param array $filters Opcijski filtri (date_from, date_to, mime_type, min_size, max_size)
 * @return array Array s statistiko: total_files, total_size, files_array
 */
function arnes_s3_scan_media_library( $filters = [] ) {
	
	global $wpdb;
	
	// Build SQL query
	$sql = "SELECT ID, post_mime_type, post_date FROM {$wpdb->posts} WHERE post_type = 'attachment'";
	
	// Filter po datumu (od)
	if ( ! empty( $filters['date_from'] ) ) {
		$date_from = sanitize_text_field( $filters['date_from'] );
		$sql .= $wpdb->prepare( " AND post_date >= %s", $date_from );
	}
	
	// Filter po datumu (do)
	if ( ! empty( $filters['date_to'] ) ) {
		$date_to = sanitize_text_field( $filters['date_to'] );
		$sql .= $wpdb->prepare( " AND post_date <= %s", $date_to . ' 23:59:59' );
	}
	
	// Filter po MIME tipu
	if ( ! empty( $filters['mime_type'] ) && $filters['mime_type'] !== 'all' ) {
		$mime_type = sanitize_text_field( $filters['mime_type'] );
		
		// Če je mime_type "image", išči vse image/* tipe
		if ( $mime_type === 'image' ) {
			$sql .= " AND post_mime_type LIKE 'image/%'";
		} else {
			$sql .= $wpdb->prepare( " AND post_mime_type = %s", $mime_type );
		}
	}
	
	$sql .= " ORDER BY post_date DESC";
	
	$attachments = $wpdb->get_results( $sql );
	
	$files = [];
	$total_size = 0;
	
	foreach ( $attachments as $attachment ) {
		
		$file_path = get_attached_file( $attachment->ID );
		
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			continue; // Skip neobstoječe datoteke
		}
		
		$file_size = filesize( $file_path );
		
		// Filter po velikosti (min)
		if ( ! empty( $filters['min_size'] ) && $file_size < ( $filters['min_size'] * 1024 * 1024 ) ) {
			continue;
		}
		
		// Filter po velikosti (max)
		if ( ! empty( $filters['max_size'] ) && $file_size > ( $filters['max_size'] * 1024 * 1024 ) ) {
			continue;
		}
		
		// Preveri če je datoteka že v S3
		$s3_object_key = get_post_meta( $attachment->ID, '_arnes_s3_object', true );
		$in_s3 = ! empty( $s3_object_key );
		
		// Če filter zahteva samo "missing" datoteke in je datoteka že v S3, skip
		if ( ! empty( $filters['only_missing'] ) && $in_s3 ) {
			continue;
		}
		
		$files[] = [
			'id'        => $attachment->ID,
			'filename'  => basename( $file_path ),
			'path'      => $file_path,
			'size'      => $file_size,
			'mime_type' => $attachment->post_mime_type,
			'date'      => $attachment->post_date,
			'in_s3'     => $in_s3,
		];
		
		$total_size += $file_size;
	}
	
	return [
		'total_files' => count( $files ),
		'total_size'  => $total_size,
		'files'       => $files,
	];
}

/**
 * Upload eno datoteko v S3 (uporablja obstoječo upload logiko)
 * 
 * @param int $attachment_id WordPress attachment ID
 * @return array Rezultat: success (bool), message (string), uploaded_count (int)
 */
function arnes_s3_upload_single_file( $attachment_id ) {
	
	$attachment_id = absint( $attachment_id );
	
	// Preveri če attachment obstaja
	$post = get_post( $attachment_id );
	if ( ! $post || $post->post_type !== 'attachment' ) {
		return [
			'success' => false,
			'message' => 'Attachment ne obstaja',
			'uploaded_count' => 0,
		];
	}
	
	// Pridobi pot do datoteke
	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! file_exists( $file ) ) {
		return [
			'success' => false,
			'message' => 'Datoteka ne obstaja na disku',
			'uploaded_count' => 0,
		];
	}
	
	// Inicializiraj S3 client
	$s3 = arnes_s3_client();
	if ( ! $s3 ) {
		return [
			'success' => false,
			'message' => 'S3 klient ni na voljo (preverite nastavitve)',
			'uploaded_count' => 0,
		];
	}
	
	$settings = arnes_s3_settings();
	$upload_dir = wp_upload_dir();
	$basedir = $upload_dir['basedir'];
	
	$uploaded_files = [];
	$failed_files = [];
	
	try {
		
		// ======================================
		// Upload original file
		// ======================================
		
		$relative = ltrim( str_replace( $basedir, '', $file ), '/' );
		$object_key = $settings['prefix'] . '/' . get_current_blog_id() . '/' . $relative;
		
		$s3->putObject( [
			'Bucket'     => $settings['bucket'],
			'Key'        => $object_key,
			'SourceFile' => $file,
			'ACL'        => 'public-read',
		] );
		
		$uploaded_files[] = basename( $file );
		
		// Shrani S3 object key v post meta
		update_post_meta( $attachment_id, '_arnes_s3_object', $object_key );
		
		// ======================================
		// Upload vse WordPress sizes
		// ======================================
		
		$metadata = wp_get_attachment_metadata( $attachment_id );
		
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			
			$file_dir = dirname( $file );
			
			foreach ( $metadata['sizes'] as $size_name => $size_data ) {
				
				$size_file = $file_dir . '/' . $size_data['file'];
				
				if ( file_exists( $size_file ) ) {
					
					$size_relative = ltrim( str_replace( $basedir, '', $size_file ), '/' );
					$size_object_key = $settings['prefix'] . '/' . get_current_blog_id() . '/' . $size_relative;
					
					try {
						$s3->putObject( [
							'Bucket'     => $settings['bucket'],
							'Key'        => $size_object_key,
							'SourceFile' => $size_file,
							'ACL'        => 'public-read',
						] );
						
						$uploaded_files[] = basename( $size_file );
						
					} catch ( Exception $e ) {
						$failed_files[] = basename( $size_file );
					}
				}
			}
		}
		
		// ======================================
		// Upload WebP/AVIF verzije
		// ======================================
		
		$file_dir = dirname( $file );
		$file_name = basename( $file );
		$file_base = pathinfo( $file_name, PATHINFO_FILENAME );
		$file_ext = pathinfo( $file_name, PATHINFO_EXTENSION );
		
		$pattern1 = $file_dir . '/' . $file_name . '.{webp,avif}';
		$pattern2 = $file_dir . '/' . $file_base . '-*.' . $file_ext . '.{webp,avif}';
		$pattern3 = $file_dir . '/' . $file_base . '-*-' . $file_ext . '.{webp,avif}';
		
		$format_files = array_merge(
			glob( $pattern1, GLOB_BRACE ) ?: [],
			glob( $pattern2, GLOB_BRACE ) ?: [],
			glob( $pattern3, GLOB_BRACE ) ?: []
		);
		
		$format_files = array_unique( $format_files );
		
		foreach ( $format_files as $format_file ) {
			
			if ( file_exists( $format_file ) ) {
				
				$format_relative = ltrim( str_replace( $basedir, '', $format_file ), '/' );
				$format_object_key = $settings['prefix'] . '/' . get_current_blog_id() . '/' . $format_relative;
				
				try {
					$s3->putObject( [
						'Bucket'     => $settings['bucket'],
						'Key'        => $format_object_key,
						'SourceFile' => $format_file,
						'ACL'        => 'public-read',
					] );
					
					$uploaded_files[] = basename( $format_file );
					
				} catch ( Exception $e ) {
					$failed_files[] = basename( $format_file );
				}
			}
		}
		
		// Vrni rezultat
		$message = sprintf(
			'Naloženo %d datotek',
			count( $uploaded_files )
		);
		
		if ( ! empty( $failed_files ) ) {
			$message .= sprintf( ' (%d napak)', count( $failed_files ) );
		}
		
		return [
			'success' => true,
			'message' => $message,
			'uploaded_count' => count( $uploaded_files ),
		];
		
	} catch ( Exception $e ) {
		
		return [
			'success' => false,
			'message' => 'Napaka: ' . $e->getMessage(),
			'uploaded_count' => 0,
		];
	}
}

/**
 * Shrani stanje bulk uploada v transient
 * 
 * @param array $state Stanje uploada
 */
function arnes_s3_save_bulk_state( $state ) {
	set_transient( 'arnes_s3_bulk_upload_state', $state, DAY_IN_SECONDS );
}

/**
 * Pridobi stanje bulk uploada iz transienta
 * 
 * @return array|false Stanje uploada ali false če ne obstaja
 */
function arnes_s3_get_bulk_state() {
	return get_transient( 'arnes_s3_bulk_upload_state' );
}

/**
 * Izbriši stanje bulk uploada (transient)
 */
function arnes_s3_delete_bulk_state() {
	delete_transient( 'arnes_s3_bulk_upload_state' );
}

/**
 * Formatiraj velikost datoteke v human-readable format
 * 
 * @param int $bytes Velikost v bytih
 * @return string Formatirana velikost (npr. "2.5 MB")
 */
function arnes_s3_format_bytes( $bytes ) {
	$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
	
	for ( $i = 0; $bytes > 1024 && $i < count( $units ) - 1; $i++ ) {
		$bytes /= 1024;
	}
	
	return round( $bytes, 2 ) . ' ' . $units[ $i ];
}

/**
 * Shrani rezultate zadnjega bulk uploada v WP option
 * 
 * @param array $results Rezultati uploada (total_files, success_count, error_count, duration)
 */
function arnes_s3_save_last_bulk_result( $results ) {
	$data = [
		'total_files'   => $results['total_files'],
		'success_count' => $results['success_count'],
		'error_count'   => $results['error_count'],
		'duration'      => $results['duration'], // v sekundah
		'date'          => current_time( 'mysql' ),
		'timestamp'     => time(),
	];
	
	update_option( 'arnes_s3_last_bulk_upload', $data );
}

/**
 * Pridobi rezultate zadnjega bulk uploada
 * 
 * @return array|false Rezultati uploada ali false če ne obstaja
 */
function arnes_s3_get_last_bulk_result() {
	return get_option( 'arnes_s3_last_bulk_upload', false );
}
