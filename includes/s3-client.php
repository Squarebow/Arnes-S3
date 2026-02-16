<?php
/**
 * Inicializacija S3 odjemalca za Arnes Shramba
 *
 * @package Arnes_S3
 */

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ustvari S3 klient z normaliziranim endpointom
 */
function arnes_s3_get_client( $settings ) {

	// Normalizacija endpointa (vedno https)
	$endpoint = 'https://' . preg_replace( '#^https?://#', '', trim( $settings['endpoint'] ) );

	return new S3Client( [
		'version'                 => 'latest',
		'region'                  => 'us-east-1',
		'endpoint'                => $endpoint,
		'use_path_style_endpoint' => true,
		'credentials'             => [
			'key'    => $settings['access_key'],
			'secret' => $settings['secret_key'],
		],
	] );
}

/**
 * Test povezave do Arnes S3
 */
function arnes_s3_test_connection( $settings ) {

	try {
		$client = arnes_s3_get_client( $settings );

		// 1. Preveri dostop do bucket-a
		$client->headBucket( [
			'Bucket' => $settings['bucket'],
		] );

		// 2. Test pisanja v prefix
		$test_key = rtrim( $settings['prefix'], '/' ) . '/_arnes_s3_test_' . time() . '.txt';

		$client->putObject( [
			'Bucket' => $settings['bucket'],
			'Key'    => $test_key,
			'Body'   => 'Arnes S3 connection test',
		] );

		// 3. Pobriši testni objekt
		$client->deleteObject( [
			'Bucket' => $settings['bucket'],
			'Key'    => $test_key,
		] );

		return [
			'success' => true,
			'message' => 'Povezava je uspela. Dostop do Arnes shrambe deluje.',
		];

	} catch ( AwsException $e ) {

		return [
			'success' => false,
			'message' => 'Napaka pri povezavi: ' . $e->getAwsErrorMessage(),
		];

	} catch ( Exception $e ) {

		return [
			'success' => false,
			'message' => 'Splošna napaka: ' . $e->getMessage(),
		];
	}
}

/**
 * Wrapper funkcija za enostaven dostop do S3 klienta
 * 
 * Uporabi obstoječe nastavitve iz WordPress options in ustvari S3Client.
 * Če credentials niso prisotni, vrne null (ne crasha).
 * 
 * @return S3Client|null Vrne S3 klient ali null če nastavitve niso prisotne
 */
function arnes_s3_client() {
	$settings = arnes_s3_settings();
	
	// Zaščita: če nimamo credentials, ne ustvari klienta
	if ( empty( $settings['access_key'] ) || empty( $settings['secret_key'] ) ) {
		return null;
	}
	
	return arnes_s3_get_client( $settings );
}