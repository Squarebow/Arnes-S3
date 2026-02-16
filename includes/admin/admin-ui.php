<?php
/**
 * Admin UI za Arnes S3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registracija admin menija
 */
add_action( 'admin_menu', function () {
    add_media_page(
        'Arnes S3',
        'Arnes S3',
        'manage_options',
        'arnes-s3',
        'arnes_s3_render_admin_page'
    );
});

/**
 * Registracija nastavitev
 */
add_action( 'admin_init', function () {

    register_setting(
        'arnes_s3_settings_group',
        'arnes_s3_settings',
        [
            'sanitize_callback' => 'arnes_s3_sanitize_settings',
        ]
    );
});

/**
 * Sanitize nastavitev
 */
function arnes_s3_sanitize_settings( $input ) {

    return [
        'endpoint'    => sanitize_text_field( $input['endpoint'] ?? '' ),
        'bucket'      => sanitize_text_field( $input['bucket'] ?? '' ),
        'prefix'      => sanitize_text_field( $input['prefix'] ?? '' ),
        'access_key'  => sanitize_text_field( $input['access_key'] ?? '' ),
        'secret_key'  => sanitize_text_field( $input['secret_key'] ?? '' ),
        'keep_local'  => ! empty( $input['keep_local'] ) ? 1 : 0,
        'cdn_domain'  => sanitize_text_field( $input['cdn_domain'] ?? '' ),
    ];
}

/**
 * Izris admin strani
 */
function arnes_s3_render_admin_page() {

    $settings = get_option( 'arnes_s3_settings', [] );
    ?>

    <div class="wrap">
        <h1>Arnes S3</h1>

        <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Spremembe so bile uspešno shranjene.</strong></p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'arnes_s3_settings_group' );
            ?>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">S3 Endpoint</th>
                    <td>
                        <input type="text"
                               name="arnes_s3_settings[endpoint]"
                               value="<?php echo esc_attr( $settings['endpoint'] ?? 'shramba.arnes.si' ); ?>"
                               class="regular-text">
                        <p class="description">Primer: shramba.arnes.si</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Bucket</th>
                    <td>
                        <input type="text"
                               name="arnes_s3_settings[bucket]"
                               value="<?php echo esc_attr( $settings['bucket'] ?? 'arnes-shramba' ); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Mapa / Prefix</th>
                    <td>
                        <input type="text"
                               name="arnes_s3_settings[prefix]"
                               value="<?php echo esc_attr( $settings['prefix'] ?? '' ); ?>"
                               class="regular-text">
                        <p class="description">
                            Primer: upi/assets
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Access key</th>
                    <td>
                        <input type="text"
                               name="arnes_s3_settings[access_key]"
                               value="<?php echo esc_attr( $settings['access_key'] ?? '' ); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Secret key</th>
                    <td>
                        <input type="password"
                               name="arnes_s3_settings[secret_key]"
                               value="<?php echo esc_attr( $settings['secret_key'] ?? '' ); ?>"
                               class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Ohrani lokalne datoteke</th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="arnes_s3_settings[keep_local]"
                                   value="1"
                                <?php checked( ! empty( $settings['keep_local'] ) ); ?>>
                            Da
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">CDN domena (opcijsko)</th>
                    <td>
                        <input type="text"
                               name="arnes_s3_settings[cdn_domain]"
                               value="<?php echo esc_attr( $settings['cdn_domain'] ?? '' ); ?>"
                               class="regular-text">
                        <p class="description">
                            Primer: https://assets.example.si
                        </p>
                    </td>
                </tr>

            </table>

            <?php submit_button( 'Shrani spremembe' ); ?>
        </form>
    </div>
    <?php
}
