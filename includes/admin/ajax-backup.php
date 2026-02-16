<?php
/**
 * AJAX handlers za Backup & Restore operacije
 * 
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX: Skeniraj za backup
 */
add_action( 'wp_ajax_arnes_s3_backup_scan', 'arnes_s3_ajax_backup_scan' );

function arnes_s3_ajax_backup_scan() {
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$source = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : 'local';
	$include_optimized = isset( $_POST['include_optimized'] ) ? (bool) $_POST['include_optimized'] : true;
	$file_types = isset( $_POST['file_types'] ) ? array_map( 'sanitize_text_field', $_POST['file_types'] ) : [ 'image', 'application', 'font', 'video', 'other' ];
	
	$results = arnes_s3_scan_for_backup( [
		'source'            => $source,
		'include_optimized' => $include_optimized,
		'file_types'        => $file_types,
	] );
	
	wp_send_json_success( $results );
}

/**
 * AJAX: Ustvari backup ZIP
 */
add_action( 'wp_ajax_arnes_s3_backup_create', 'arnes_s3_ajax_backup_create' );

function arnes_s3_ajax_backup_create() {
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	// Pridobi opcije
	$source = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : 'local';
	$include_optimized = isset( $_POST['include_optimized'] ) ? (bool) $_POST['include_optimized'] : true;
	$file_types = isset( $_POST['file_types'] ) ? array_map( 'sanitize_text_field', $_POST['file_types'] ) : [ 'image', 'application', 'font', 'video', 'other' ];
	
	// Skeniraj datoteke
	$scan_results = arnes_s3_scan_for_backup( [
		'source'            => $source,
		'include_optimized' => $include_optimized,
		'file_types'        => $file_types,
	] );
	
	if ( empty( $scan_results['files'] ) ) {
		wp_send_json_error( [ 'message' => 'Ni datotek za backup.' ] );
	}
	
	// Ustvari ZIP
	$result = arnes_s3_create_backup_zip( $scan_results['files'] );
	
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( [ 'message' => $result->get_error_message() ] );
	}
	
	wp_send_json_success( $result );
}

/**
 * AJAX: Pridobi seznam obstoječih backupov
 */
add_action( 'wp_ajax_arnes_s3_backup_list', 'arnes_s3_ajax_backup_list' );

function arnes_s3_ajax_backup_list() {
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$backups = arnes_s3_get_existing_backups();
	
	wp_send_json_success( [ 'backups' => $backups ] );
}

/**
 * AJAX: Izbriši backup
 */
add_action( 'wp_ajax_arnes_s3_backup_delete', 'arnes_s3_ajax_backup_delete' );

function arnes_s3_ajax_backup_delete() {
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$filename = isset( $_POST['filename'] ) ? sanitize_file_name( $_POST['filename'] ) : '';
	
	if ( empty( $filename ) ) {
		wp_send_json_error( [ 'message' => 'Manjka ime datoteke.' ] );
	}
	
	$deleted = arnes_s3_delete_backup( $filename );
	
	if ( $deleted ) {
		wp_send_json_success( [ 'message' => 'Backup izbrisan.' ] );
	} else {
		wp_send_json_error( [ 'message' => 'Ni bilo mogoče izbrisati backup-a.' ] );
	}
}

/**
 * AJAX: Skeniraj S3 za restore
 */
add_action( 'wp_ajax_arnes_s3_restore_scan', 'arnes_s3_ajax_restore_scan' );

function arnes_s3_ajax_restore_scan() {
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'missing';
	$file_types = isset( $_POST['file_types'] ) && is_array( $_POST['file_types'] ) ? array_map( 'sanitize_text_field', $_POST['file_types'] ) : [ 'image', 'application', 'font', 'video', 'other' ];
	
	// Če je file_types prazen array, uporabi vse tipe
	if ( empty( $file_types ) ) {
		$file_types = [ 'image', 'application', 'font', 'video', 'other' ];
	}
	
	$results = arnes_s3_scan_for_restore( [
		'mode'       => $mode,
		'file_types' => $file_types,
	] );
	
	if ( is_wp_error( $results ) ) {
		wp_send_json_error( [ 'message' => $results->get_error_message() ] );
	}
	
	wp_send_json_success( $results );
}

/**
 * AJAX: Restore datoteke iz S3 (batch processing)
 */
add_action( 'wp_ajax_arnes_s3_restore_process', 'arnes_s3_ajax_restore_process' );

function arnes_s3_ajax_restore_process() {
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	// Pridobi batch datotek za restore
	$files = isset( $_POST['files'] ) ? json_decode( stripslashes( $_POST['files'] ), true ) : [];
	
	if ( empty( $files ) ) {
		wp_send_json_error( [ 'message' => 'Ni datotek za restore.' ] );
	}
	
	$processed = 0;
	$errors = [];
	
	foreach ( $files as $file ) {
		$result = arnes_s3_restore_file( $file );
		
		if ( is_wp_error( $result ) ) {
			$errors[] = basename( $file['local_path'] ) . ': ' . $result->get_error_message();
		} else {
			$processed++;
		}
	}
	
	wp_send_json_success( [
		'processed' => $processed,
		'errors'    => $errors,
	] );
}

/**
 * ============================================================================
 * SYNC & MAINTENANCE AJAX HANDLERS
 * ============================================================================
 */

/**
 * AJAX: Skeniraj za manjkajoči S3 metadata
 */
add_action( 'wp_ajax_arnes_s3_sync_scan', 'arnes_s3_ajax_sync_scan' );

function arnes_s3_ajax_sync_scan() {
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$results = arnes_s3_scan_for_metadata_sync();
	
	if ( is_wp_error( $results ) ) {
		wp_send_json_error( [ 'message' => $results->get_error_message() ] );
	}
	
	wp_send_json_success( $results );
}

/**
 * AJAX: Popravi manjkajoči S3 metadata
 */
add_action( 'wp_ajax_arnes_s3_sync_fix', 'arnes_s3_ajax_sync_fix' );

function arnes_s3_ajax_sync_fix() {
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$items = isset( $_POST['items'] ) ? json_decode( stripslashes( $_POST['items'] ), true ) : [];
	
	if ( empty( $items ) ) {
		wp_send_json_error( [ 'message' => 'Ni podatkov za popravilo.' ] );
	}
	
	$results = arnes_s3_fix_metadata( $items );
	
	wp_send_json_success( $results );
}

/**
 * AJAX: Skeniraj za bulk delete lokalnih kopij
 */
add_action( 'wp_ajax_arnes_s3_local_delete_scan', 'arnes_s3_ajax_local_delete_scan' );

function arnes_s3_ajax_local_delete_scan() {
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$results = arnes_s3_scan_for_local_delete();
	
	if ( is_wp_error( $results ) ) {
		wp_send_json_error( [ 'message' => $results->get_error_message() ] );
	}
	
	wp_send_json_success( $results );
}

/**
 * AJAX: Izbriši lokalne kopije datotek
 */
add_action( 'wp_ajax_arnes_s3_local_delete_process', 'arnes_s3_ajax_local_delete_process' );

function arnes_s3_ajax_local_delete_process() {
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$files = isset( $_POST['files'] ) ? json_decode( stripslashes( $_POST['files'] ), true ) : [];
	
	if ( empty( $files ) ) {
		wp_send_json_error( [ 'message' => 'Ni datotek za brisanje.' ] );
	}
	
	$results = arnes_s3_delete_local_files( $files );
	
	wp_send_json_success( $results );
}

/**
 * AJAX: Preveri integriteteto
 */
add_action( 'wp_ajax_arnes_s3_integrity_check', 'arnes_s3_ajax_integrity_check' );

function arnes_s3_ajax_integrity_check() {
	check_ajax_referer( 'arnes_s3_backup_nonce', 'nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja.' ] );
	}
	
	$results = arnes_s3_check_integrity();
	
	if ( is_wp_error( $results ) ) {
		wp_send_json_error( [ 'message' => $results->get_error_message() ] );
	}
	
	wp_send_json_success( $results );
}
