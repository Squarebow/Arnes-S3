<?php
/**
 * AJAX handler za testiranje povezave do Arnes S3
 * 
 * Preveri:
 * - Dostop do bucket-a (headBucket)
 * - Pisanje v prefix (putObject)
 * - Brisanje testnega objekta (deleteObject)
 *
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_arnes_s3_test_connection', 'arnes_s3_ajax_test_connection' );

function arnes_s3_ajax_test_connection() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Nimate dovoljenja.' );
	}

	check_ajax_referer( 'arnes_s3_test_nonce', 'nonce' );

	$settings = [
		'endpoint'    => sanitize_text_field( $_POST['endpoint'] ?? '' ),
		'bucket'      => sanitize_text_field( $_POST['bucket'] ?? '' ),
		'prefix'      => sanitize_text_field( $_POST['prefix'] ?? '' ),
		'access_key'  => sanitize_text_field( $_POST['access_key'] ?? '' ),
		'secret_key'  => sanitize_text_field( $_POST['secret_key'] ?? '' ),
	];

	require_once ARNES_S3_PATH . 'includes/s3-client.php';

	$result = arnes_s3_test_connection( $settings );
	
	// Shrani connection status za prikaz v diagnostiki
	// Uporablja current_time() za pravilno DST (daylight saving time) podporo
	$status = [
		'success'   => $result['success'],
		'message'   => $result['message'],
		'timestamp' => current_time( 'timestamp' ),
	];
	update_option( 'arnes_s3_connection_status', $status );
	update_option( 'arnes_s3_connection_tested', true );

	if ( $result['success'] ) {
		wp_send_json_success( $result['message'] );
	} else {
		wp_send_json_error( $result['message'] );
	}
}
