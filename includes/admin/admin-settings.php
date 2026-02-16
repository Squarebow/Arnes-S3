<?php
/**
 * Registracija nastavitev za Arnes S3
 * 
 * Registrira posamezne WordPress options, settings sections in fields.
 * Vključuje callback funkcije za izris posameznih polj (text, password, checkbox).
 *
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registracija nastavitev, sekcij in polj
 */
function arnes_s3_register_settings() {

	/**
	 * Registracija posameznih nastavitev
	 */
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_endpoint' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_bucket' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_prefix' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_org_id' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_access_key' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_secret_key' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_keep_local' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_cdn_domain' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_serve_mode' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_auto_upload' );
	
	// Image Quality Settings (Phase 4.1)
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_jpeg_quality', [
		'type'              => 'integer',
		'sanitize_callback' => 'arnes_s3_sanitize_quality',
		'default'           => 82,
	] );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_webp_quality', [
		'type'              => 'integer',
		'sanitize_callback' => 'arnes_s3_sanitize_quality',
		'default'           => 82,
	] );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_avif_quality', [
		'type'              => 'integer',
		'sanitize_callback' => 'arnes_s3_sanitize_quality',
		'default'           => 82,
	] );
	
	// Image Format Priority (Phase 4.5)
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_format_priority', [
		'type'              => 'string',
		'sanitize_callback' => 'arnes_s3_sanitize_format_priority',
		'default'           => 'webp_first',
	] );

	/**
	 * Sekcija: Povezava
	 */
	add_settings_section(
		'arnes_s3_connection_section',
		'Povezava do Arnes Shrambe',
		'arnes_s3_connection_section_cb',
		'arnes-s3'
	);

	add_settings_field(
		'arnes_s3_endpoint',
		'S3 končna točka (endpoint)',
		'arnes_s3_text_field_cb',
		'arnes-s3',
		'arnes_s3_connection_section',
		array(
			'label_for' => 'arnes_s3_endpoint',
			'default'   => 'https://shramba.arnes.si',
		)
	);

	add_settings_field(
		'arnes_s3_bucket',
		'Bucket',
		'arnes_s3_text_field_cb',
		'arnes-s3',
		'arnes_s3_connection_section',
		array(
			'label_for' => 'arnes_s3_bucket',
			'default'   => 'arnes-shramba',
		)
	);

	add_settings_field(
		'arnes_s3_prefix',
		'Mapa/pot',
		'arnes_s3_text_field_cb',
		'arnes-s3',
		'arnes_s3_connection_section',
		array(
			'label_for' => 'arnes_s3_prefix',
			'default'   => 'arnes-s3',
		)
	);

	add_settings_field(
		'arnes_s3_access_key',
		'Access Key',
		'arnes_s3_password_field_cb',
		'arnes-s3',
		'arnes_s3_connection_section',
		array(
			'label_for' => 'arnes_s3_access_key',
		)
	);

	add_settings_field(
		'arnes_s3_secret_key',
		'Secret Key',
		'arnes_s3_password_field_cb',
		'arnes-s3',
		'arnes_s3_connection_section',
		array(
			'label_for' => 'arnes_s3_secret_key',
		)
	);

	/**
	 * Sekcija: Obnašanje
	 */
	add_settings_section(
		'arnes_s3_behavior_section',
		'Napredne nastavitve',
		'arnes_s3_behavior_section_cb',
		'arnes-s3'
	);

	add_settings_field(
		'arnes_s3_keep_local',
		'Ohrani lokalne datoteke',
		'arnes_s3_checkbox_field_cb',
		'arnes-s3',
		'arnes_s3_behavior_section',
		array(
			'label_for' => 'arnes_s3_keep_local',
		)
	);

	add_settings_field(
		'arnes_s3_cdn_domain',
		'CDN domena',
		'arnes_s3_text_field_cb',
		'arnes-s3',
		'arnes_s3_behavior_section',
		array(
			'label_for' => 'arnes_s3_cdn_domain',
			'default'   => '',
		)
	);
}
add_action( 'admin_init', 'arnes_s3_register_settings' );

/**
 * Opis sekcije: povezava
 */
function arnes_s3_connection_section_cb() {
	echo '<p>Nastavitve za povezavo z Arnes Shrambo S3.</p>';
}

/**
 * Opis sekcije: obnašanje
 */
function arnes_s3_behavior_section_cb() {
	echo '<p>Nastavitve delovanja vtičnika.</p>';
}

/**
 * Text input field
 */
function arnes_s3_text_field_cb( $args ) {
	$option  = get_option( $args['label_for'], $args['default'] ?? '' );
	?>
	<input
		type="text"
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>"
		value="<?php echo esc_attr( $option ); ?>"
		class="regular-text"
	/>
	<?php
}

/**
 * Password input field
 */
function arnes_s3_password_field_cb( $args ) {
	$option = get_option( $args['label_for'], '' );
	?>
	<input
		type="password"
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>"
		value="<?php echo esc_attr( $option ); ?>"
		class="regular-text"
		autocomplete="new-password"
	/>
	<?php
}

/**
 * Checkbox field
 */
function arnes_s3_checkbox_field_cb( $args ) {
	$option = get_option( $args['label_for'], 0 );
	?>
	<label>
		<input
			type="checkbox"
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="<?php echo esc_attr( $args['label_for'] ); ?>"
			value="1"
			<?php checked( 1, $option ); ?>
		/>
		Da
	</label>
	<?php
}

/**
 * Number input field (za quality sliders)
 */
function arnes_s3_number_field_cb( $args ) {
	$option = get_option( $args['label_for'], $args['default'] ?? 82 );
	$min    = $args['min'] ?? 1;
	$max    = $args['max'] ?? 100;
	?>
	<input
		type="number"
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>"
		value="<?php echo esc_attr( $option ); ?>"
		class="small-text"
		min="<?php echo esc_attr( $min ); ?>"
		max="<?php echo esc_attr( $max ); ?>"
		step="1"
	/>
	<span class="description"><?php echo esc_html( $args['description'] ?? '' ); ?></span>
	<?php
}

/**
 * Sanitize funkcija za image quality (vrednosti med 1-100)
 */
function arnes_s3_sanitize_quality( $value ) {
	$value = absint( $value );
	
	if ( $value < 1 ) {
		$value = 1;
	}
	
	if ( $value > 100 ) {
		$value = 100;
	}
	
	return $value;
}

/**
 * Sanitize funkcija za image format priority
 */
function arnes_s3_sanitize_format_priority( $value ) {
	$allowed_values = [ 'webp_first', 'avif_first' ];
	
	if ( ! in_array( $value, $allowed_values, true ) ) {
		return 'webp_first'; // Default
	}
	
	return $value;
}
