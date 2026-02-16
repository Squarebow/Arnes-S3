<?php
/**
 * Media upload handler za Arnes S3
 * 
 * POMEMBNO: Uporablja wp_generate_attachment_metadata filter (NE add_attachment hook!)
 * 
 * Ta filter se izvede PO tem ko WordPress generira vse thumbnails, WebP/AVIF verzije.
 * To zagotavlja da so vse datoteke že na disku ko jih uploadamo v S3.
 * 
 * Uploada:
 * - Original file
 * - Vse thumbnail sizes (150x150, 300x300, 1024x683, itd.)
 * - WebP verzije (.jpg.webp)
 * - AVIF verzije (.jpg.avif)
 * - Scaled versions (-scaled.jpg)
 * 
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registriraj upload filter med plugins_loaded
 * 
 * To zagotavlja da se filter registrira v pravem trenutku WordPress lifecycle-a,
 * ko so vsi settings že dostopni.
 */
add_action( 'plugins_loaded', 'arnes_s3_register_upload_filter' );

function arnes_s3_register_upload_filter() {
	
	// Preveri credentials
	$settings = arnes_s3_settings();
	
	// Preveri ali je avtomatsko nalaganje omogočeno
	if ( empty( $settings['auto_upload'] ) ) {
		// Auto-upload je izklopljen - ne registriraj filtera
		return;
	}
	
	if ( ! empty( $settings['access_key'] ) && ! empty( $settings['secret_key'] ) ) {
		// Registriraj filter na wp_generate_attachment_metadata
		// Ta se izvede PO generiranju thumbnails
		add_filter( 'wp_generate_attachment_metadata', 'arnes_s3_handle_upload', 10, 2 );
	}
}

/**
 * Naloži medijsko datoteko v Arnes S3
 * 
 * Ta funkcija se kliče PO tem ko WordPress generira vse thumbnails in optimizirane verzije.
 * Uploada original file + vse WordPress-generirane versions.
 * 
 * @param array $metadata Attachment metadata (vsebuje vse generirane sizes)
 * @param int $attachment_id WordPress attachment ID
 * @return array Nespremenjeni metadata (mora biti returniran!)
 */
function arnes_s3_handle_upload( $metadata, $attachment_id ) {

	// POMEMBNO: Preveri post_type da izločimo plugin uploads
	$post = get_post( $attachment_id );
	
	// Samo attachments (ne custom post types)
	if ( ! $post || $post->post_type !== 'attachment' ) {
		return $metadata;
	}
	
	// Pridobi pot do original file
	$file = get_attached_file( $attachment_id );
	
	// Preveri če file obstaja
	if ( ! $file || ! file_exists( $file ) ) {
		return $metadata;
	}
	
	// Dodatna zaščita: Filtriranje plugin ZIP files
	// Plugin ZIPs so shranjeni direktno v /uploads/ root (ne v year/month strukturi)
	// User uploads so v /uploads/year/month/ strukturi
	$mime_type = get_post_mime_type( $attachment_id );
	if ( $mime_type === 'application/zip' && $file ) {
		// Preveri če je file v year/month directory strukturi
		// Regex pattern: /uploads/YYYY/MM/filename.zip
		if ( ! preg_match( '#/uploads/\d{4}/\d{2}/#', $file ) ) {
			// ZIP file NIJE v year/month strukturi → verjetno plugin ZIP
			error_log( '[Arnes S3] Skipping non-media ZIP file: ' . basename( $file ) );
			return $metadata; // Ignoriraj
		}
		// Če je ZIP v year/month strukturi → legit user upload, nadaljuj
	}

	$settings = arnes_s3_settings();
	
	// Double-check credentials (dodatna zaščita)
	if ( empty( $settings['access_key'] ) || empty( $settings['secret_key'] ) ) {
		return $metadata;
	}
	
	// Inicializiraj S3 client
	$s3 = arnes_s3_client();
	if ( ! $s3 ) {
		return $metadata;
	}

	// Izračunaj base paths
	$upload_dir = wp_upload_dir();
	$basedir = $upload_dir['basedir'];
	
	// Array za tracking uploaded files
	$uploaded_files = array();
	$failed_files = array();
	
	try {
		
		// ======================================
		// KORAK 1: Upload original file
		// ======================================
		
		$relative = ltrim( str_replace( $basedir, '', $file ), '/' );
		$object_key = $settings['prefix'] . '/' . get_current_blog_id() . '/' . $relative;
		
		try {
			$s3->putObject( [
				'Bucket'     => $settings['bucket'],
				'Key'        => $object_key,
				'SourceFile' => $file,
				'ACL'        => 'public-read',
			] );
			
			$uploaded_files[] = basename( $file );
			
			// Shrani S3 object key v post meta
			update_post_meta( $attachment_id, '_arnes_s3_object', $object_key );
			
		} catch ( Exception $e ) {
			$failed_files[] = basename( $file ) . ': ' . $e->getMessage();
		}
		
		
		// ======================================
		// KORAK 2: Upload vse WordPress sizes
		// ======================================
		
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			
			// Get directory kjer je original file
			$file_dir = dirname( $file );
			
			foreach ( $metadata['sizes'] as $size_name => $size_data ) {
				
				// Path do size file
				$size_file = $file_dir . '/' . $size_data['file'];
				
				if ( file_exists( $size_file ) ) {
					
					// Relativna pot za S3
					$size_relative = ltrim( str_replace( $basedir, '', $size_file ), '/' );
					$size_object_key = $settings['prefix'] . '/' . get_current_blog_id() . '/' . $size_relative;
					
					try {
						// Upload size file v S3
						$s3->putObject( [
							'Bucket'     => $settings['bucket'],
							'Key'        => $size_object_key,
							'SourceFile' => $size_file,
							'ACL'        => 'public-read',
						] );
						
						$uploaded_files[] = basename( $size_file );
						
					} catch ( Exception $e ) {
						$failed_files[] = basename( $size_file ) . ': ' . $e->getMessage();
					}
				}
			}
		}
		
		
		// ======================================
		// KORAK 3: Upload WebP/AVIF verzije
		// ======================================
		
		// WordPress lahko generira WebP/AVIF verzije ki NISO v metadata['sizes'] array
		// Te datoteke imajo isti filename + .webp ali .avif extension
		
		$file_dir = dirname( $file );
		$file_name = basename( $file );
		$file_base = pathinfo( $file_name, PATHINFO_FILENAME );
		$file_ext = pathinfo( $file_name, PATHINFO_EXTENSION );
		
		// Scan directory za vse WebP/AVIF verzije
		// Pattern 1: Original file formats (image.jpg.webp, image.jpg.avif)
		$pattern1 = $file_dir . '/' . $file_name . '.{webp,avif}';
		
		// Pattern 2: Size-specific formats (image-300x200.jpg.webp)
		$pattern2 = $file_dir . '/' . $file_base . '-*.' . $file_ext . '.{webp,avif}';
		
		// Pattern 3: Alternate naming (image-300x200-jpg.webp - brez pike pred jpg)
		$pattern3 = $file_dir . '/' . $file_base . '-*-' . $file_ext . '.{webp,avif}';
		
		$format_files = array_merge(
			glob( $pattern1, GLOB_BRACE ) ?: array(),
			glob( $pattern2, GLOB_BRACE ) ?: array(),
			glob( $pattern3, GLOB_BRACE ) ?: array()
		);
		
		// Remove duplicates
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
					$failed_files[] = basename( $format_file ) . ': ' . $e->getMessage();
				}
			}
		}
		
		
		// ======================================
		// KORAK 4: Izbriši lokalne datoteke (če setting je OFF)
		// ======================================
		
		if ( ! $settings['keep_local'] ) {
			// Izbriši original
			if ( file_exists( $file ) ) {
				@unlink( $file );
			}
			
			// Izbriši vse sizes
			if ( ! empty( $metadata['sizes'] ) ) {
				foreach ( $metadata['sizes'] as $size_data ) {
					$size_file = $file_dir . '/' . $size_data['file'];
					if ( file_exists( $size_file ) ) {
						@unlink( $size_file );
					}
				}
			}
			
			// Izbriši WebP/AVIF
			foreach ( $format_files as $format_file ) {
				if ( file_exists( $format_file ) ) {
					@unlink( $format_file );
				}
			}
		}
		
		
		// ======================================
		// Log rezultate
		// ======================================
		
		if ( ! empty( $uploaded_files ) ) {
			error_log( sprintf(
				'[Arnes S3] Successfully uploaded %d files for attachment ID %d: %s',
				count( $uploaded_files ),
				$attachment_id,
				implode( ', ', $uploaded_files )
			) );
		}
		
		if ( ! empty( $failed_files ) ) {
			error_log( sprintf(
				'[Arnes S3] Failed uploads for attachment ID %d: %s',
				$attachment_id,
				implode( '; ', $failed_files )
			) );
		}

	} catch ( Exception $e ) {
		// Catch-all za unexpected errors
		error_log( '[Arnes S3] Unexpected error: ' . $e->getMessage() );
	}
	
	// POMEMBNO: Vedno vrni metadata nespremenjene!
	// Filter mora returnati metadata, sicer WordPress ne shrani podatkov.
	return $metadata;
}