<?php
/**
 * AJAX handler-ji za bulk upload funkcionalnost
 * 
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX: Skeniraj Media Library
 * 
 * Vrne seznam vseh datotek, število datotek, skupno velikost
 */
add_action( 'wp_ajax_arnes_s3_scan_library', 'arnes_s3_ajax_scan_library' );

function arnes_s3_ajax_scan_library() {
	
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_bulk_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja za to akcijo.' ] );
	}
	
	// Pridobi filtre iz POST data
	$filters = [
		'date_from'    => isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '',
		'date_to'      => isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '',
		'mime_type'    => isset( $_POST['mime_type'] ) ? sanitize_text_field( $_POST['mime_type'] ) : 'all',
		'min_size'     => isset( $_POST['min_size'] ) ? floatval( $_POST['min_size'] ) : 0,
		'max_size'     => isset( $_POST['max_size'] ) ? floatval( $_POST['max_size'] ) : 0,
		'only_missing' => isset( $_POST['only_missing'] ) && $_POST['only_missing'] === 'true',
	];
	
	// Skeniraj Media Library
	$result = arnes_s3_scan_media_library( $filters );
	
	// Pripravi response
	wp_send_json_success( [
		'total_files'   => $result['total_files'],
		'total_size'    => $result['total_size'],
		'total_size_formatted' => arnes_s3_format_bytes( $result['total_size'] ),
		'files'         => $result['files'],
	] );
}

/**
 * AJAX: Bulk upload batch (10 datotek naenkrat)
 * 
 * Procesira batch datotek in vrne progress
 */
add_action( 'wp_ajax_arnes_s3_bulk_upload_batch', 'arnes_s3_ajax_bulk_upload_batch' );

function arnes_s3_ajax_bulk_upload_batch() {
	
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_bulk_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja za to akcijo.' ] );
	}
	
	// Pridobi file IDs iz POST data
	$file_ids = isset( $_POST['file_ids'] ) ? array_map( 'absint', (array) $_POST['file_ids'] ) : [];
	$is_dry_run = isset( $_POST['dry_run'] ) && $_POST['dry_run'] === 'true';
	
	if ( empty( $file_ids ) ) {
		wp_send_json_error( [ 'message' => 'Ni datotek za procesiranje.' ] );
	}
	
	$results = [];
	$success_count = 0;
	$error_count = 0;
	
	foreach ( $file_ids as $file_id ) {
		
		// Če je dry-run, samo simuliraj
		if ( $is_dry_run ) {
			$results[] = [
				'id'      => $file_id,
				'success' => true,
				'message' => 'DRY RUN - datoteka ne bo naložena',
			];
			$success_count++;
			continue;
		}
		
		// Uploadi datoteko
		$upload_result = arnes_s3_upload_single_file( $file_id );
		
		$results[] = [
			'id'      => $file_id,
			'success' => $upload_result['success'],
			'message' => $upload_result['message'],
		];
		
		if ( $upload_result['success'] ) {
			$success_count++;
		} else {
			$error_count++;
		}
	}
	
	// Vrni rezultat
	wp_send_json_success( [
		'results'       => $results,
		'success_count' => $success_count,
		'error_count'   => $error_count,
	] );
}

/**
 * AJAX: Shrani stanje uploada (za resume funkcionalnost)
 */
add_action( 'wp_ajax_arnes_s3_save_state', 'arnes_s3_ajax_save_state' );

function arnes_s3_ajax_save_state() {
	
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_bulk_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja za to akcijo.' ] );
	}
	
	// Pridobi state iz POST data
	$state = isset( $_POST['state'] ) ? json_decode( stripslashes( $_POST['state'] ), true ) : [];
	
	if ( empty( $state ) ) {
		wp_send_json_error( [ 'message' => 'Neveljaven state.' ] );
	}
	
	// Shrani state v transient
	arnes_s3_save_bulk_state( $state );
	
	wp_send_json_success( [ 'message' => 'Stanje shranjeno.' ] );
}

/**
 * AJAX: Pridobi shranjeno stanje uploada
 */
add_action( 'wp_ajax_arnes_s3_get_state', 'arnes_s3_ajax_get_state' );

function arnes_s3_ajax_get_state() {
	
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_bulk_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja za to akcijo.' ] );
	}
	
	// Pridobi state iz transienta
	$state = arnes_s3_get_bulk_state();
	
	if ( ! $state ) {
		wp_send_json_error( [ 'message' => 'Shranjeno stanje ne obstaja.' ] );
	}
	
	wp_send_json_success( [ 'state' => $state ] );
}

/**
 * AJAX: Izbriši shranjeno stanje
 */
add_action( 'wp_ajax_arnes_s3_delete_state', 'arnes_s3_ajax_delete_state' );

function arnes_s3_ajax_delete_state() {
	
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_bulk_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja za to akcijo.' ] );
	}
	
	// Izbriši state
	arnes_s3_delete_bulk_state();
	
	wp_send_json_success( [ 'message' => 'Stanje izbrisano.' ] );
}

/**
 * AJAX: Shrani rezultate zadnjega bulk uploada
 */
add_action( 'wp_ajax_arnes_s3_save_bulk_result', 'arnes_s3_ajax_save_bulk_result' );

function arnes_s3_ajax_save_bulk_result() {
	
	// Preveri nonce
	check_ajax_referer( 'arnes_s3_bulk_nonce', 'nonce' );
	
	// Preveri permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => 'Nimate dovoljenja za to akcijo.' ] );
	}
	
	// Pridobi results iz POST data
	$results = [
		'total_files'   => isset( $_POST['total_files'] ) ? absint( $_POST['total_files'] ) : 0,
		'success_count' => isset( $_POST['success_count'] ) ? absint( $_POST['success_count'] ) : 0,
		'error_count'   => isset( $_POST['error_count'] ) ? absint( $_POST['error_count'] ) : 0,
		'duration'      => isset( $_POST['duration'] ) ? absint( $_POST['duration'] ) : 0,
	];
	
	// Shrani rezultate
	arnes_s3_save_last_bulk_result( $results );
	
	wp_send_json_success( [ 'message' => 'Rezultati shranjeni.' ] );
}
