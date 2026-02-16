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
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_access_key' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_secret_key' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_keep_local' );
	register_setting( 'arnes_s3_settings_group', 'arnes_s3_cdn_domain' );

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
