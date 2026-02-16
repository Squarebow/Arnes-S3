<?php
/**
 * Diagnostika sistema za Arnes S3
 * 
 * Funkcionalnosti:
 * - Preveri podporo za moderne image formate (WebP, AVIF)
 * - Zazna image optimization vtičnike in preveri kompatibilnost
 * - Shrani rezultate diagnostike v WordPress options
 * - Prikaže formatiran pregled v admin vmesniku
 *
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prevedi angleški mesec v slovenski (rodilnik - "februarja")
 * 
 * @param string $date Datum v angleščini
 * @return string Datum v slovenščini
 */
function arnes_s3_translate_date( $date ) {
	$months_en = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
	$months_sl = array( 'januarja', 'februarja', 'marca', 'aprila', 'maja', 'junija', 'julija', 'avgusta', 'septembra', 'oktobra', 'novembra', 'decembra' );
	
	return str_replace( $months_en, $months_sl, $date );
}

/**
 * Izvede diagnostiko sistema ob aktivaciji vtičnika
 */
function arnes_s3_run_diagnostics() {
	// Preveri image support
	$image_support = arnes_s3_check_image_support();
	update_option( 'arnes_s3_image_support', $image_support );
	
	// Zazna image optimization vtičnike
	$detected_plugins = arnes_s3_detect_image_plugins();
	update_option( 'arnes_s3_detected_plugins', $detected_plugins );
	
	// Shrani timestamp diagnostike (uporabi current_time za DST support)
	update_option( 'arnes_s3_diagnostics_timestamp', current_time( 'timestamp' ) );
}

/**
 * Auto-refresh diagnostics ob shranjevanju katerihkoli Arnes S3 nastavitev
 * 
 * To zagotavlja da so diagnostika podatki vedno sveži in odražajo trenutno stanje.
 * Npr. če uporabnik deaktivira CompressX plugin, to bo takoj vidno v diagnostiki.
 */
add_action( 'update_option_arnes_s3_endpoint', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );
add_action( 'update_option_arnes_s3_bucket', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );
add_action( 'update_option_arnes_s3_prefix', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );
add_action( 'update_option_arnes_s3_org_id', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );
add_action( 'update_option_arnes_s3_access_key', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );
add_action( 'update_option_arnes_s3_secret_key', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );
add_action( 'update_option_arnes_s3_keep_local', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );
add_action( 'update_option_arnes_s3_cdn_domain', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );
add_action( 'update_option_arnes_s3_serve_mode', 'arnes_s3_refresh_diagnostics_on_save', 10, 0 );

function arnes_s3_refresh_diagnostics_on_save() {
	// Prepreči infinite loop (če diagnostika sama updatea options)
	static $running = false;
	
	if ( $running ) {
		return;
	}
	
	$running = true;
	arnes_s3_run_diagnostics();
	$running = false;
}

/**
 * Preveri podporo za image formate (WebP, AVIF)
 * 
 * @return array Informacije o podpori
 */
function arnes_s3_check_image_support() {
	$support = [
		'engine'         => 'none',
		'engine_version' => '',
		'webp'           => false,
		'avif'           => false,
		'formats'        => [],
		'php_version'    => PHP_VERSION,
		'wp_version'     => get_bloginfo( 'version' ),
	];
	
	// Preveri Imagick
	if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
		$support['engine'] = 'Imagick';
		
		try {
			$imagick = new Imagick();
			$version = $imagick->getVersion();
			if ( isset( $version['versionString'] ) ) {
				$support['engine_version'] = $version['versionString'];
			}
			
			$formats = $imagick->queryFormats();
			$support['formats'] = $formats;
			$support['webp'] = in_array( 'WEBP', $formats, true );
			$support['avif'] = in_array( 'AVIF', $formats, true );
		} catch ( Exception $e ) {
			$support['engine'] = 'Imagick (napaka pri inicializaciji)';
		}
	}
	// Preveri GD
	elseif ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) {
		$support['engine'] = 'GD';
		
		$gd_info = gd_info();
		if ( isset( $gd_info['GD Version'] ) ) {
			$support['engine_version'] = $gd_info['GD Version'];
		}
		
		$support['webp'] = function_exists( 'imagewebp' ) && ! empty( $gd_info['WebP Support'] );
		$support['avif'] = function_exists( 'imageavif' ); // PHP 8.1+
		
		$support['formats'] = array_keys( array_filter( [
			'JPEG' => ! empty( $gd_info['JPEG Support'] ),
			'PNG'  => ! empty( $gd_info['PNG Support'] ),
			'GIF'  => ! empty( $gd_info['GIF Read Support'] ),
			'WEBP' => $support['webp'],
			'AVIF' => $support['avif'],
		] ) );
	}
	
	return $support;
}

/**
 * Zazna image optimization vtičnike
 * 
 * @return array Seznam zaznanih vtičnikov
 */
function arnes_s3_detect_image_plugins() {
	$plugins = [];
	
	// ShortPixel
	if ( class_exists( 'ShortPixelPlugin' ) || defined( 'SHORTPIXEL_PLUGIN_FILE' ) ) {
		$plugins[] = [
			'name'       => 'ShortPixel Image Optimizer',
			'slug'       => 'shortpixel',
			'compatible' => 'full',
			'note'       => 'Deluje brezhibno z Arnes S3. Optimizirane slike bodo avtomatsko naložene v S3.',
		];
	}
	
	// Imagify
	if ( class_exists( 'Imagify' ) || defined( 'IMAGIFY_VERSION' ) ) {
		$plugins[] = [
			'name'       => 'Imagify',
			'slug'       => 'imagify',
			'compatible' => 'full',
			'note'       => 'Deluje brezhibno z Arnes S3. Optimizirane slike bodo avtomatsko naložene v S3.',
		];
	}
	
	// CompressX
	if ( defined( 'COMPRESSX_VERSION' ) || class_exists( 'CompressX' ) ) {
		$plugins[] = [
			'name'       => 'CompressX',
			'slug'       => 'compressx',
			'compatible' => 'partial',
			'note'       => 'Optimizirane slike se shranjujejo v ločeno mapo <code>/wp-content/compressx-nextgen/</code>, ki je izven standardne WordPress uploads strukture. Arnes S3 te datoteke ne zaznava. Za nastavitve glej zavihek <strong>Nastavitve</strong> → sekcija "Scenariji optimizacije slik". Priporočamo prehod na native WordPress optimizacijo.',
		];
	}
	
	// EWWW Image Optimizer
	if ( class_exists( 'EWWW_Image_Optimizer' ) || defined( 'EWWW_IMAGE_OPTIMIZER_VERSION' ) ) {
		$plugins[] = [
			'name'       => 'EWWW Image Optimizer',
			'slug'       => 'ewww-image-optimizer',
			'compatible' => 'full',
			'note'       => 'Deluje brezhibno z Arnes S3. Optimizirane slike bodo avtomatsko naložene v S3.',
		];
	}
	
	// Smush
	if ( class_exists( 'WP_Smush' ) || defined( 'WP_SMUSH_VERSION' ) ) {
		$plugins[] = [
			'name'       => 'Smush',
			'slug'       => 'smush',
			'compatible' => 'full',
			'note'       => 'Deluje brezhibno z Arnes S3. Optimizirane slike bodo avtomatsko naložene v S3.',
		];
	}
	
	// Optimole
	if ( class_exists( 'Optimole' ) || defined( 'OPTIMOLE_VERSION' ) ) {
		$plugins[] = [
			'name'       => 'Optimole',
			'slug'       => 'optimole-wp',
			'compatible' => 'full',
			'note'       => 'Deluje brezhibno z Arnes S3.',
		];
	}
	
	return $plugins;
}

/**
 * Pridobi shranjene rezultate diagnostike
 * 
 * @return array|false Rezultati ali false če diagnostika še ni bila izvedena
 */
function arnes_s3_get_diagnostics() {
	$image_support = get_option( 'arnes_s3_image_support', false );
	$detected_plugins = get_option( 'arnes_s3_detected_plugins', [] );
	$timestamp = get_option( 'arnes_s3_diagnostics_timestamp', false );
	
	if ( ! $image_support ) {
		return false;
	}
	
	return [
		'image_support'    => $image_support,
		'detected_plugins' => $detected_plugins,
		'timestamp'        => $timestamp,
	];
}

/**
 * Formatiran prikaz diagnostike (za uporabo v admin)
 * Kompakten prikaz - vse v eni vrstici kjer je mogoče
 * 
 * @return string HTML output
 */
function arnes_s3_display_diagnostics() {
	$diagnostics = arnes_s3_get_diagnostics();
	
	if ( ! $diagnostics ) {
		echo '<div class="notice notice-warning"><p>Diagnostika še ni bila izvedena. Poskusite deaktivirati in ponovno aktivirati vtičnik.</p></div>';
		return;
	}
	
	$support = $diagnostics['image_support'];
	$plugins = $diagnostics['detected_plugins'];
	
	// Preveri connection status
	$connection_status = get_option( 'arnes_s3_connection_status', false );
	$connection_tested = get_option( 'arnes_s3_connection_tested', false );
	
	?>
	<h3 style="margin-bottom: 10px;">Status vtičnika</h3>
	
	<table class="form-table" role="presentation" style="margin-top: 0;">
		<tr style="vertical-align: top;">
			<th scope="row" style="width: 200px; padding-top: 5px; padding-bottom: 5px;">Povezava z Arnes:</th>
			<td style="padding-top: 5px; padding-bottom: 5px;">
				<?php if ( ! $connection_tested ) : ?>
					<span style="color: #d63638; font-weight: 600;">Povezava še ni bila preverjena</span>
					<span class="description"> - Uporabite gumb "Preveri povezavo" v zavihku Povezava</span>
				<?php elseif ( $connection_status && is_array( $connection_status ) ) : ?>
					<?php if ( $connection_status['success'] ) : ?>
						<span style="color: #00a32a; font-weight: 600;">Povezava deluje</span>
						<span class="description"> - Zadnja preverba: <?php echo esc_html( arnes_s3_translate_date( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $connection_status['timestamp'] ) ) ); ?></span>
					<?php else : ?>
						<span style="color: #d63638; font-weight: 600;">Povezava ne deluje</span>
						<span class="description"> - Napaka: <?php echo esc_html( $connection_status['message'] ); ?></span>
					<?php endif; ?>
				<?php else : ?>
					<span style="color: #d63638; font-weight: 600;">Status neznan</span>
				<?php endif; ?>
			</td>
		</tr>
		<tr style="vertical-align: top;">
			<th scope="row" style="padding-top: 5px; padding-bottom: 5px;">Strežnik:</th>
			<td style="padding-top: 5px; padding-bottom: 5px;">
				<strong><?php echo esc_html( $support['engine'] ); ?></strong>
				<?php if ( ! empty( $support['engine_version'] ) ) : ?>
					<span class="description">(<?php echo esc_html( $support['engine_version'] ); ?>)</span>
				<?php endif; ?>
				&nbsp;|&nbsp;
				WebP: 
				<?php if ( $support['webp'] ) : ?>
					<span style="color: #00a32a; font-weight: 600;">DA</span>
				<?php else : ?>
					<span style="color: #d63638; font-weight: 600;">NE</span>
				<?php endif; ?>
				&nbsp;|&nbsp;
				AVIF: 
				<?php if ( $support['avif'] ) : ?>
					<span style="color: #00a32a; font-weight: 600;">DA</span>
				<?php else : ?>
					<span style="color: #d63638; font-weight: 600;">NE</span>
				<?php endif; ?>
			</td>
		</tr>
		<tr style="vertical-align: top;">
			<th scope="row" style="padding-top: 5px; padding-bottom: 5px;">Vrstni red formatov:</th>
			<td style="padding-top: 5px; padding-bottom: 5px;">
				<?php if ( $support['avif'] && $support['webp'] ) : ?>
					AVIF → WebP → JPG/PNG (fallback)
				<?php elseif ( $support['webp'] ) : ?>
					WebP → JPG/PNG (fallback)
				<?php else : ?>
					Samo JPG/PNG (brez modernih formatov)
				<?php endif; ?>
			</td>
		</tr>
		
		<tr style="vertical-align: top;">
			<th scope="row" style="padding-top: 5px; padding-bottom: 5px;">Zaznani vtičniki:</th>
			<td style="padding-top: 5px; padding-bottom: 5px;">
				<?php if ( ! empty( $plugins ) ) : ?>
					<?php foreach ( $plugins as $plugin ) : ?>
						<div style="margin-bottom: 8px;">
							<strong><?php echo esc_html( $plugin['name'] ); ?></strong><br>
							<span class="description"><?php echo wp_kses_post( $plugin['note'] ); ?></span>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<span class="description">Ni zaznanih vtičnikov za optimizacijo slik</span>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	
	<?php if ( ! $support['webp'] && ! $support['avif'] ) : ?>
		<div class="notice notice-warning inline" style="margin-top: 10px;">
			<p><strong>Vaš strežnik ne podpira modernih image formatov.</strong> Za WebP in AVIF podporo potrebujete PHP 8.1+ z Imagick 7.0+ razširitvijo ali GD z WebP/AVIF podporo.</p>
		</div>
	<?php endif; ?>
	
	<p class="description" style="margin-top: 10px;">
		Diagnostika izvedena: <?php echo esc_html( arnes_s3_translate_date( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $diagnostics['timestamp'] ) ) ); ?>
	</p>
	<?php
}
