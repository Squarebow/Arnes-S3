<?php
/**
 * Backup & Restore funkcionalnost
 * 
 * Omogoča backup celotne Media Library v ZIP arhiv in restore iz S3.
 * 
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Skeniraj Media Library za backup
 * 
 * @param array $options Opcije skeniranja
 * @return array Rezultati skeniranja
 */
function arnes_s3_scan_for_backup( $options = [] ) {
	$defaults = [
		'source'            => 'local', // 'local' ali 's3'
		'include_optimized' => true,
		'file_types'        => [ 'image', 'application', 'font', 'video', 'other' ],
	];
	
	$options = wp_parse_args( $options, $defaults );
	
	// Pridobi vse attachment posts
	$attachments = get_posts( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );
	
	$files = [];
	$total_size = 0;
	$settings = arnes_s3_settings();
	
	foreach ( $attachments as $attachment ) {
		$attachment_id = $attachment->ID;
		$file_path = get_attached_file( $attachment_id );
		$mime_type = get_post_mime_type( $attachment_id );
		
		// Filtriraj glede na file type
		if ( ! arnes_s3_matches_file_types( $mime_type, $options['file_types'] ) ) {
			continue;
		}
		
		// Preveri če datoteka obstaja lokalno
		$local_exists = file_exists( $file_path );
		
		// Preveri če datoteka obstaja v S3
		$s3_key = get_post_meta( $attachment_id, '_arnes_s3_object', true );
		$s3_exists = ! empty( $s3_key );
		
		// Filtriraj glede na source
		if ( $options['source'] === 'local' && ! $local_exists ) {
			continue;
		}
		
		if ( $options['source'] === 's3' && ! $s3_exists ) {
			continue;
		}
		
		// Pridobi metadata
		$metadata = wp_get_attachment_metadata( $attachment_id );
		
		// Glavna datoteka
		if ( $local_exists ) {
			$files[] = [
				'attachment_id' => $attachment_id,
				'path'          => $file_path,
				'size'          => filesize( $file_path ),
				'type'          => 'original',
				's3_key'        => $s3_key,
			];
			
			$total_size += filesize( $file_path );
		}
		
		// Thumbnails in različne velikosti
		if ( ! empty( $metadata['sizes'] ) && $options['include_optimized'] ) {
			$upload_dir = wp_upload_dir();
			$base_dir = dirname( $file_path );
			
			foreach ( $metadata['sizes'] as $size_name => $size_data ) {
				$thumb_path = $base_dir . '/' . $size_data['file'];
				
				if ( file_exists( $thumb_path ) ) {
					$files[] = [
						'attachment_id' => $attachment_id,
						'path'          => $thumb_path,
						'size'          => filesize( $thumb_path ),
						'type'          => 'thumbnail',
						'size_name'     => $size_name,
					];
					
					$total_size += filesize( $thumb_path );
				}
			}
		}
	}
	
	return [
		'files'       => $files,
		'count'       => count( $files ),
		'total_size'  => $total_size,
		'attachments' => count( $attachments ),
	];
}

/**
 * Ustvari ZIP backup Media Library
 * 
 * @param array $files Seznam datotek za backup
 * @param array $options Opcije backupa
 * @return array|WP_Error Rezultat ali napaka
 */
function arnes_s3_create_backup_zip( $files, $options = [] ) {
	// Preveri če je ZipArchive na voljo
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 
			'zip_not_available', 
			'PHP ZipArchive razred ni na voljo. Preverite PHP konfiguraciio.' 
		);
	}
	
	$defaults = [
		'filename' => 'arnes-s3-backup-' . date( 'Y-m-d-His' ) . '.zip',
	];
	
	$options = wp_parse_args( $options, $defaults );
	
	// Ustvari začasno mapo za ZIP
	$upload_dir = wp_upload_dir();
	$backup_dir = $upload_dir['basedir'] . '/arnes-s3-backups';
	
	if ( ! file_exists( $backup_dir ) ) {
		wp_mkdir_p( $backup_dir );
	}
	
	$zip_path = $backup_dir . '/' . $options['filename'];
	
	// Ustvari ZIP arhiv
	$zip = new ZipArchive();
	
	if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
		return new WP_Error( 
			'zip_create_failed', 
			'Ni bilo mogoče ustvariti ZIP arhiva.' 
		);
	}
	
	$added = 0;
	$errors = [];
	
	foreach ( $files as $file ) {
		if ( ! file_exists( $file['path'] ) ) {
			$errors[] = 'Datoteka ne obstaja: ' . basename( $file['path'] );
			continue;
		}
		
		// Relativna pot v ZIP-u
		$relative_path = str_replace( $upload_dir['basedir'] . '/', '', $file['path'] );
		
		if ( $zip->addFile( $file['path'], $relative_path ) ) {
			$added++;
		} else {
			$errors[] = 'Ni bilo mogoče dodati: ' . basename( $file['path'] );
		}
	}
	
	$zip->close();
	
	// Preveri ali je ZIP uspešno ustvarjen
	if ( ! file_exists( $zip_path ) ) {
		return new WP_Error( 
			'zip_not_created', 
			'ZIP arhiv ni bil ustvarjen.' 
		);
	}
	
	return [
		'success'  => true,
		'zip_path' => $zip_path,
		'zip_url'  => $upload_dir['baseurl'] . '/arnes-s3-backups/' . $options['filename'],
		'filename' => $options['filename'],
		'size'     => filesize( $zip_path ),
		'added'    => $added,
		'errors'   => $errors,
	];
}

/**
 * Pridobi seznam obstoječih backupov
 * 
 * @return array Seznam backup datotek
 */
function arnes_s3_get_existing_backups() {
	$upload_dir = wp_upload_dir();
	$backup_dir = $upload_dir['basedir'] . '/arnes-s3-backups';
	
	if ( ! file_exists( $backup_dir ) ) {
		return [];
	}
	
	$files = glob( $backup_dir . '/*.zip' );
	$backups = [];
	
	foreach ( $files as $file ) {
		$backups[] = [
			'filename' => basename( $file ),
			'path'     => $file,
			'url'      => $upload_dir['baseurl'] . '/arnes-s3-backups/' . basename( $file ),
			'size'     => filesize( $file ),
			'date'     => filemtime( $file ),
		];
	}
	
	// Sortiraj po datumu (najnovejši najprej)
	usort( $backups, function( $a, $b ) {
		return $b['date'] - $a['date'];
	} );
	
	return $backups;
}

/**
 * Izbriši backup datoteko
 * 
 * @param string $filename Ime backup datoteke
 * @return bool Ali je brisanje uspelo
 */
function arnes_s3_delete_backup( $filename ) {
	$upload_dir = wp_upload_dir();
	$backup_path = $upload_dir['basedir'] . '/arnes-s3-backups/' . basename( $filename );
	
	if ( file_exists( $backup_path ) ) {
		return unlink( $backup_path );
	}
	
	return false;
}

/**
 * Skeniraj S3 bucket za restore
 * 
 * @param array $options Opcije skeniranja
 * @return array|WP_Error Rezultati skeniranja ali napaka
 */
function arnes_s3_scan_for_restore( $options = [] ) {
	$defaults = [
		'mode'       => 'missing', // 'missing' ali 'all'
		'file_types' => [ 'image', 'application', 'font', 'video', 'other' ],
	];
	
	$options = wp_parse_args( $options, $defaults );
	$settings = arnes_s3_settings();
	
	// Ustvari S3 client
	$s3 = arnes_s3_client();
	if ( ! $s3 ) {
		return new WP_Error( 's3_client_failed', 'Ni bilo mogoče ustvariti S3 client-a.' );
	}
	
	// Pridobi vse attachments ki imajo S3 object key
	$attachments = get_posts( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'meta_query'     => [
			[
				'key'     => '_arnes_s3_object',
				'compare' => 'EXISTS',
			],
		],
	] );
	
	$files_to_restore = [];
	$total_size = 0;
	
	foreach ( $attachments as $attachment ) {
		$attachment_id = $attachment->ID;
		$s3_key = get_post_meta( $attachment_id, '_arnes_s3_object', true );
		$local_path = get_attached_file( $attachment_id );
		$mime_type = get_post_mime_type( $attachment_id );
		
		// Filtriraj glede na file type
		if ( ! arnes_s3_matches_file_types( $mime_type, $options['file_types'] ) ) {
			continue;
		}
		
		// Preveri če je datoteka lokalno
		$local_exists = file_exists( $local_path );
		
		// Če je mode "missing" in datoteka obstaja lokalno, preskoči
		if ( $options['mode'] === 'missing' && $local_exists ) {
			continue;
		}
		
		// Preveri če datoteka obstaja v S3
		try {
			$result = $s3->headObject( [
				'Bucket' => $settings['bucket'],
				'Key'    => $s3_key,
			] );
			
			$files_to_restore[] = [
				'attachment_id' => $attachment_id,
				's3_key'        => $s3_key,
				'local_path'    => $local_path,
				'size'          => $result['ContentLength'],
				'mime_type'     => $mime_type,
				'local_exists'  => $local_exists,
			];
			
			$total_size += $result['ContentLength'];
			
		} catch ( Exception $e ) {
			// Datoteka ne obstaja v S3
			continue;
		}
		
		// Thumbnails
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $metadata['sizes'] ) ) {
			$base_dir = dirname( $local_path );
			
			foreach ( $metadata['sizes'] as $size_name => $size_data ) {
				$thumb_s3_key = dirname( $s3_key ) . '/' . $size_data['file'];
				$thumb_local_path = $base_dir . '/' . $size_data['file'];
				$thumb_local_exists = file_exists( $thumb_local_path );
				
				if ( $options['mode'] === 'missing' && $thumb_local_exists ) {
					continue;
				}
				
				try {
					$result = $s3->headObject( [
						'Bucket' => $settings['bucket'],
						'Key'    => $thumb_s3_key,
					] );
					
					$files_to_restore[] = [
						'attachment_id' => $attachment_id,
						's3_key'        => $thumb_s3_key,
						'local_path'    => $thumb_local_path,
						'size'          => $result['ContentLength'],
						'mime_type'     => $mime_type,
						'local_exists'  => $thumb_local_exists,
						'type'          => 'thumbnail',
					];
					
					$total_size += $result['ContentLength'];
					
				} catch ( Exception $e ) {
					continue;
				}
			}
		}
	}
	
	return [
		'files'      => $files_to_restore,
		'count'      => count( $files_to_restore ),
		'total_size' => $total_size,
	];
}

/**
 * Restore eno datoteko iz S3
 * 
 * @param array $file File data
 * @return bool|WP_Error Ali je restore uspel
 */
function arnes_s3_restore_file( $file ) {
	$settings = arnes_s3_settings();
	$s3 = arnes_s3_client();
	
	if ( ! $s3 ) {
		return new WP_Error( 's3_client_failed', 'Ni bilo mogoče ustvariti S3 client-a.' );
	}
	
	// Ustvari mapo če ne obstaja
	$dir = dirname( $file['local_path'] );
	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	
	// Prenesi datoteko iz S3
	try {
		$result = $s3->getObject( [
			'Bucket' => $settings['bucket'],
			'Key'    => $file['s3_key'],
		] );
		
		// Zapiši v lokalno datoteko
		file_put_contents( $file['local_path'], $result['Body'] );
		
		return true;
		
	} catch ( Exception $e ) {
		return new WP_Error( 
			'restore_failed', 
			'Napaka pri restore-u: ' . $e->getMessage() 
		);
	}
}

/**
 * Preveri ali mime type ustreza izbranim tipom
 * 
 * @param string $mime_type MIME tip datoteke
 * @param array  $file_types Izbrani tipi
 * @return bool Ali ustreza
 */
function arnes_s3_matches_file_types( $mime_type, $file_types ) {
	if ( empty( $file_types ) ) {
		return true;
	}
	
	$type_map = [
		'image'       => [ 'image/' ],
		'application' => [ 'application/pdf', 'application/msword', 'application/vnd.', 'text/' ],
		'font'        => [ 'font/', 'application/font', 'application/x-font' ],
		'video'       => [ 'video/' ],
		'other'       => [], // Vse ostalo
	];
	
	foreach ( $file_types as $type ) {
		if ( $type === 'other' ) {
			// Preveri če ne ustreza nobeni od drugih kategorij
			$matches_other_category = false;
			foreach ( [ 'image', 'application', 'font', 'video' ] as $check_type ) {
				foreach ( $type_map[ $check_type ] as $prefix ) {
					if ( strpos( $mime_type, $prefix ) === 0 ) {
						$matches_other_category = true;
						break 2;
					}
				}
			}
			if ( ! $matches_other_category ) {
				return true;
			}
		} else {
			foreach ( $type_map[ $type ] as $prefix ) {
				if ( strpos( $mime_type, $prefix ) === 0 ) {
					return true;
				}
			}
		}
	}
	
	return false;
}

/**
 * ============================================================================
 * SYNC & MAINTENANCE FUNKCIJE
 * ============================================================================
 */

/**
 * Skeniraj attachments za sync S3 metadata
 * Najde attachmente ki imajo datoteke v S3 ampak nimajo _arnes_s3_object meta
 * 
 * @return array Rezultati skeniranja
 */
function arnes_s3_scan_for_metadata_sync() {
	$settings = arnes_s3_settings();
	$s3 = arnes_s3_client();
	
	if ( ! $s3 ) {
		return new WP_Error( 's3_client_failed', 'Ni bilo mogoče ustvariti S3 client-a.' );
	}
	
	// Pridobi vse attachments ki NIMAJO S3 meta
	$attachments = get_posts( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'meta_query'     => [
			[
				'key'     => '_arnes_s3_object',
				'compare' => 'NOT EXISTS',
			],
		],
	] );
	
	$missing_meta = [];
	
	foreach ( $attachments as $attachment ) {
		$attachment_id = $attachment->ID;
		$local_path = get_attached_file( $attachment_id );
		
		// Konstruiraj pričakovani S3 key
		$upload_dir = wp_upload_dir();
		$relative_path = str_replace( $upload_dir['basedir'] . '/', '', $local_path );
		
		// Dodaj prefix in blog ID
		$blog_id = get_current_blog_id();
		$prefix = $settings['prefix'];
		$s3_key = $prefix . '/' . $blog_id . '/' . $relative_path;
		
		// Preveri če datoteka obstaja v S3
		try {
			$result = $s3->headObject( [
				'Bucket' => $settings['bucket'],
				'Key'    => $s3_key,
			] );
			
			// Datoteka obstaja v S3 ampak nima meta!
			$missing_meta[] = [
				'attachment_id' => $attachment_id,
				's3_key'        => $s3_key,
				'filename'      => basename( $local_path ),
				'size'          => $result['ContentLength'],
			];
			
		} catch ( Exception $e ) {
			// Datoteka ne obstaja v S3 - to je OK
			continue;
		}
	}
	
	return [
		'missing_meta' => $missing_meta,
		'count'        => count( $missing_meta ),
	];
}

/**
 * Popravi manjkajoči S3 metadata
 * 
 * @param array $items Attachments za popravilo
 * @return array Rezultati popravka
 */
function arnes_s3_fix_metadata( $items ) {
	$fixed = 0;
	$errors = [];
	
	foreach ( $items as $item ) {
		$result = update_post_meta( $item['attachment_id'], '_arnes_s3_object', $item['s3_key'] );
		
		if ( $result ) {
			$fixed++;
		} else {
			$errors[] = 'Ni bilo mogoče posodobiti meta za attachment ' . $item['attachment_id'];
		}
	}
	
	return [
		'fixed'  => $fixed,
		'errors' => $errors,
	];
}

/**
 * Skeniraj attachments za bulk delete lokalnih kopij
 * 
 * @return array Rezultati skeniranja
 */
function arnes_s3_scan_for_local_delete() {
	$settings = arnes_s3_settings();
	$s3 = arnes_s3_client();
	
	if ( ! $s3 ) {
		return new WP_Error( 's3_client_failed', 'Ni bilo mogoče ustvariti S3 client-a.' );
	}
	
	// Pridobi vse attachments ki imajo S3 meta
	$attachments = get_posts( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'meta_query'     => [
			[
				'key'     => '_arnes_s3_object',
				'compare' => 'EXISTS',
			],
		],
	] );
	
	$can_delete = [];
	$total_size = 0;
	
	foreach ( $attachments as $attachment ) {
		$attachment_id = $attachment->ID;
		$s3_key = get_post_meta( $attachment_id, '_arnes_s3_object', true );
		$local_path = get_attached_file( $attachment_id );
		
		// Preveri če datoteka obstaja lokalno
		if ( ! file_exists( $local_path ) ) {
			continue;
		}
		
		// Preveri če datoteka obstaja v S3
		try {
			$result = $s3->headObject( [
				'Bucket' => $settings['bucket'],
				'Key'    => $s3_key,
			] );
			
			// Datoteka obstaja v S3 in lokalno - lahko jo zbrišemo lokalno
			$local_size = filesize( $local_path );
			
			$can_delete[] = [
				'attachment_id' => $attachment_id,
				's3_key'        => $s3_key,
				'local_path'    => $local_path,
				'filename'      => basename( $local_path ),
				'size'          => $local_size,
			];
			
			$total_size += $local_size;
			
			// Dodaj tudi thumbnails
			$metadata = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $metadata['sizes'] ) ) {
				$base_dir = dirname( $local_path );
				
				foreach ( $metadata['sizes'] as $size_name => $size_data ) {
					$thumb_path = $base_dir . '/' . $size_data['file'];
					
					if ( file_exists( $thumb_path ) ) {
						$can_delete[] = [
							'attachment_id' => $attachment_id,
							's3_key'        => dirname( $s3_key ) . '/' . $size_data['file'],
							'local_path'    => $thumb_path,
							'filename'      => $size_data['file'],
							'size'          => filesize( $thumb_path ),
							'type'          => 'thumbnail',
						];
						
						$total_size += filesize( $thumb_path );
					}
				}
			}
			
		} catch ( Exception $e ) {
			// Datoteka ne obstaja v S3 - NE BRIŠI LOKALNO!
			continue;
		}
	}
	
	return [
		'files'      => $can_delete,
		'count'      => count( $can_delete ),
		'total_size' => $total_size,
	];
}

/**
 * Izbriši lokalne kopije datotek
 * 
 * @param array $files Datoteke za brisanje
 * @return array Rezultati brisanja
 */
function arnes_s3_delete_local_files( $files ) {
	$deleted = 0;
	$errors = [];
	$freed_space = 0;
	
	foreach ( $files as $file ) {
		if ( file_exists( $file['local_path'] ) ) {
			$size = filesize( $file['local_path'] );
			
			if ( unlink( $file['local_path'] ) ) {
				$deleted++;
				$freed_space += $size;
			} else {
				$errors[] = 'Ni bilo mogoče izbrisati: ' . basename( $file['local_path'] );
			}
		}
	}
	
	return [
		'deleted'     => $deleted,
		'freed_space' => $freed_space,
		'errors'      => $errors,
	];
}

/**
 * Preveri integriteteto - primerjaj lokalne datoteke vs S3
 * 
 * @return array Rezultati preverjanja
 */
function arnes_s3_check_integrity() {
	$settings = arnes_s3_settings();
	$s3 = arnes_s3_client();
	
	if ( ! $s3 ) {
		return new WP_Error( 's3_client_failed', 'Ni bilo mogoče ustvariti S3 client-a.' );
	}
	
	// Pridobi vse attachments
	$attachments = get_posts( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
	] );
	
	$results = [
		'total'           => count( $attachments ),
		'ok'              => 0,
		'missing_s3'      => [],
		'missing_local'   => [],
		'size_mismatch'   => [],
		'no_meta'         => 0,
	];
	
	foreach ( $attachments as $attachment ) {
		$attachment_id = $attachment->ID;
		$s3_key = get_post_meta( $attachment_id, '_arnes_s3_object', true );
		$local_path = get_attached_file( $attachment_id );
		$local_exists = file_exists( $local_path );
		
		// Če nima S3 meta, preskoči
		if ( empty( $s3_key ) ) {
			$results['no_meta']++;
			continue;
		}
		
		// Preveri S3
		try {
			$s3_result = $s3->headObject( [
				'Bucket' => $settings['bucket'],
				'Key'    => $s3_key,
			] );
			
			$s3_exists = true;
			$s3_size = $s3_result['ContentLength'];
			
		} catch ( Exception $e ) {
			$s3_exists = false;
			$s3_size = 0;
		}
		
		// Analiza
		if ( ! $s3_exists && $local_exists ) {
			// Datoteka obstaja lokalno ampak ne v S3
			$results['missing_s3'][] = [
				'attachment_id' => $attachment_id,
				'filename'      => basename( $local_path ),
				's3_key'        => $s3_key,
			];
		} elseif ( $s3_exists && ! $local_exists ) {
			// Datoteka obstaja v S3 ampak ne lokalno
			$results['missing_local'][] = [
				'attachment_id' => $attachment_id,
				'filename'      => basename( $local_path ),
				's3_key'        => $s3_key,
			];
		} elseif ( $s3_exists && $local_exists ) {
			// Obe obstajata - preveri velikost
			$local_size = filesize( $local_path );
			
			if ( $local_size !== $s3_size ) {
				$results['size_mismatch'][] = [
					'attachment_id' => $attachment_id,
					'filename'      => basename( $local_path ),
					'local_size'    => $local_size,
					's3_size'       => $s3_size,
				];
			} else {
				$results['ok']++;
			}
		}
	}
	
	return $results;
}
