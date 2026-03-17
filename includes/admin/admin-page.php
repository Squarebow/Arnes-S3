<?php
/**
 * Admin stran vtičnika Arnes S3
 *
 * @package Arnes_S3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'arnes_s3_register_admin_menu' );
add_action( 'admin_enqueue_scripts', 'arnes_s3_admin_assets' );

function arnes_s3_register_admin_menu() {
	add_media_page(
		'Arnes S3',
		'Arnes S3',
		'manage_options',
		'arnes-s3',
		'arnes_s3_render_admin_page'
	);
}

function arnes_s3_admin_assets( $hook ) {

	if ( $hook !== 'media_page_arnes-s3' ) {
		return;
	}

	// Dashicons so del WordPress core — ni zunanjih virov
	wp_enqueue_style( 'dashicons' );

	wp_enqueue_script(
		'arnes-s3-admin',
		ARNES_S3_URL . 'assets/js/admin-s3-test.js',
		[],
		ARNES_S3_VERSION,
		true
	);
	wp_localize_script( 'arnes-s3-admin', 'arnesS3', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'arnes_s3_test_nonce' ),
	] );

	wp_enqueue_script(
		'arnes-s3-bulk-upload',
		ARNES_S3_URL . 'assets/js/admin-bulk-upload.js',
		[ 'jquery' ],
		ARNES_S3_VERSION,
		true
	);
	wp_localize_script( 'arnes-s3-bulk-upload', 'arnesS3Bulk', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'arnes_s3_bulk_nonce' ),
	] );

	wp_enqueue_script(
		'arnes-s3-backup',
		ARNES_S3_URL . 'assets/js/admin-backup.js',
		[ 'jquery' ],
		ARNES_S3_VERSION,
		true
	);
	wp_localize_script( 'arnes-s3-backup', 'arnesS3Backup', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'arnes_s3_backup_nonce' ),
	] );
}

function arnes_s3_render_admin_page() {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab je samo navigacijski parameter brez varnostnih posledic
	$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'povezava';
	?>
	<style>
		.arnes-icon        { margin-right: 6px; color: #2271b1; font-size: 18px; vertical-align: middle; }
		.nav-tab .arnes-icon      { margin-right: 5px; font-size: 16px; vertical-align: text-bottom; }
		.nav-tab-active .arnes-icon { color: #135e96; }
		h2 .arnes-icon, h3 .arnes-icon { margin-right: 8px; font-size: 20px; vertical-align: middle; }
		.arnes-icon-success { color: #00a32a; margin-right: 5px; font-size: 18px; vertical-align: middle; }
		.arnes-icon-error   { color: #d63638; margin-right: 5px; font-size: 18px; vertical-align: middle; }
		.arnes-icon-warning { color: #996800; margin-right: 5px; font-size: 18px; vertical-align: middle; }
		.arnes-icon-info    { color: #2271b1;  margin-right: 5px; font-size: 18px; vertical-align: middle; }
		.arnes-icon-sm      { font-size: 16px; margin-right: 4px; vertical-align: middle; }
	</style>

	<div class="wrap">
		<h1>Arnes S3</h1>

		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>Spremembe so bile uspešno shranjene!</p>
			</div>
			<?php
		}
		?>

		<h2 class="nav-tab-wrapper">
			<a href="?page=arnes-s3&tab=povezava" class="nav-tab <?php echo $active_tab === 'povezava' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-plugins arnes-icon" aria-hidden="true"></span>Povezava
			</a>
			<a href="?page=arnes-s3&tab=nastavitve" class="nav-tab <?php echo $active_tab === 'nastavitve' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-settings arnes-icon" aria-hidden="true"></span>Nastavitve
			</a>
			<a href="?page=arnes-s3&tab=mnozicno" class="nav-tab <?php echo $active_tab === 'mnozicno' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-cloud-upload arnes-icon" aria-hidden="true"></span>Nalaganje
			</a>
			<a href="?page=arnes-s3&tab=orodja" class="nav-tab <?php echo $active_tab === 'orodja' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-admin-tools arnes-icon" aria-hidden="true"></span>Orodja
			</a>
			<a href="?page=arnes-s3&tab=statistika" class="nav-tab <?php echo $active_tab === 'statistika' ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons dashicons-chart-bar arnes-icon" aria-hidden="true"></span>Statistika
			</a>
		</h2>

		<div style="margin-top: 20px;">
			<?php
			switch ( $active_tab ) {
				case 'povezava':   arnes_s3_render_tab_povezava();   break;
				case 'nastavitve': arnes_s3_render_tab_nastavitve();  break;
				case 'mnozicno':   arnes_s3_render_tab_mnozicno();    break;
				case 'orodja':     arnes_s3_render_tab_orodja();      break;
				case 'statistika': arnes_s3_render_tab_statistika();  break;
			}
			?>
		</div>

		<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dcdcde;">
			<?php arnes_s3_display_diagnostics(); ?>
		</div>
	</div>
	<?php
}

// ============================================================
// TAB 1: POVEZAVA
// ============================================================
function arnes_s3_render_tab_povezava() {
	$settings = arnes_s3_settings();
	?>
	<div style="display: flex; gap: 30px;">
		<div style="flex: 0 0 48%;">
			<form method="post" action="options.php">
				<?php settings_fields( 'arnes_s3_settings_group' ); ?>
				<input type="hidden" name="arnes_s3_keep_local"   value="<?php echo esc_attr( $settings['keep_local'] ); ?>" />
				<input type="hidden" name="arnes_s3_cdn_domain"   value="<?php echo esc_attr( $settings['cdn_domain'] ); ?>" />
				<input type="hidden" name="arnes_s3_serve_mode"   value="<?php echo esc_attr( get_option( 'arnes_s3_serve_mode', 'arnes' ) ); ?>" />
				<input type="hidden" name="arnes_s3_auto_upload"  value="<?php echo esc_attr( $settings['auto_upload'] ); ?>" />
				<input type="hidden" name="arnes_s3_jpeg_quality" value="<?php echo esc_attr( $settings['jpeg_quality'] ); ?>" />
				<input type="hidden" name="arnes_s3_webp_quality" value="<?php echo esc_attr( $settings['webp_quality'] ); ?>" />
				<input type="hidden" name="arnes_s3_avif_quality" value="<?php echo esc_attr( $settings['avif_quality'] ); ?>" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="arnes_s3_endpoint">S3 končna točka (endpoint)</label></th>
						<td>
							<input type="text" id="arnes_s3_endpoint" name="arnes_s3_endpoint"
							       value="<?php echo esc_attr( $settings['endpoint'] ); ?>"
							       class="regular-text" placeholder="https://shramba.arnes.si" />
							<p class="description">URL naslov Arnes Shrambe</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="arnes_s3_bucket">Bucket</label></th>
						<td>
							<input type="text" id="arnes_s3_bucket" name="arnes_s3_bucket"
							       value="<?php echo esc_attr( $settings['bucket'] ); ?>" class="regular-text" />
							<p class="description">Ime bucketa v Arnes Shrambi. Privzeto: arnes-shramba</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="arnes_s3_prefix">Mapa/pot</label></th>
						<td>
							<input type="text" id="arnes_s3_prefix" name="arnes_s3_prefix"
							       value="<?php echo esc_attr( $settings['prefix'] ); ?>" class="regular-text" />
							<p class="description">Poljubna mapa v bucketu za organizacijo datotek, ki jo<br>ustvarite sami. Primer: moja-domena/slike ipd.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="arnes_s3_org_id">ID organizacije</label></th>
						<td>
							<input type="text" id="arnes_s3_org_id" name="arnes_s3_org_id"
							       value="<?php echo esc_attr( $settings['org_id'] ); ?>" class="regular-text" />
							<p class="description">Uporabniško ime vaše organizacije (številka) najdete<br>na Arnes Portalu članic.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="arnes_s3_access_key">Access key organizacije</label></th>
						<td>
							<input type="password" id="arnes_s3_access_key" name="arnes_s3_access_key"
							       value="<?php echo esc_attr( $settings['access_key'] ); ?>"
							       class="regular-text" autocomplete="new-password" />
							<p class="description">Dostopni ključ za avtentikacijo</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="arnes_s3_secret_key">Secret key organizacije</label></th>
						<td>
							<input type="password" id="arnes_s3_secret_key" name="arnes_s3_secret_key"
							       value="<?php echo esc_attr( $settings['secret_key'] ); ?>"
							       class="regular-text" autocomplete="new-password" />
							<p class="description">Skrivni ključ za avtentikacijo</p>
						</td>
					</tr>
				</table>

				<p class="submit" style="display: flex; gap: 12px; align-items: center;">
					<button type="button" id="arnes-s3-test" class="button button-secondary button-large">Preveri povezavo</button>
					<?php submit_button( 'Shrani spremembe', 'primary large', 'submit', false ); ?>
				</p>
			</form>
			<div id="arnes-s3-test-result" style="margin-top:15px;"></div>
		</div>

		<div style="flex: 0 0 48%; align-self: flex-start; background: #f9f9f9; padding: 20px; border: 1px solid #dcdcde; border-radius: 4px;">
			<h3 style="margin-top: 0;">Navodila za povezavo</h3>
			<p><strong>Kje najdem podatke za povezavo:</strong></p>
			<ol>
				<li>Prijavite se v <a href="https://portal.arnes.si" target="_blank">Arnes portal članic</a>, kjer so vsi podatki v razdelku Arnes shramba. <strong>URL končne točke je že vnešen, ne spreminjajte ga!</strong></li>
				<li>Uporabite obstoječ t.i. bucket (arnes-shramba) ali ustvarite novega z orodjem Duplicati ali Min.io.</li>
				<li>Na <a href="https://spletna.shramba.arnes.si/" target="_blank">portalu Arnes Shramba</a> ustvarite strukturo map, kamor želite shranjevati vsebino, npr. <code>spletna-stran/slike</code>.</li>
				<li>V vsa prazna polja vpišite oziroma kopirajte podatke iz Arnes portala članic.</li>
				<li>Kliknite <strong>Preveri povezavo</strong> in po potrditvi še <strong>Shrani spremembe</strong>.</li>
			</ol>
			<div class="notice notice-info inline" style="margin: 20px 0;">
				<p><strong>Opomba:</strong> S klikom na gumb "Preveri povezavo" se prepričate, da so vnešeni podatki pravilni, preden jih shranite.</p>
			</div>
			<p><strong>Priporočila za nov bucket:</strong></p>
			<ul>
				<li>- Uporabite opisno ime, priporočamo ime domene (npr. <code>moja-domena</code>)</li>
				<li>- Mape in podmape uporabite za ločevanje projektov (npr. <code>spletna-stran/slike</code>)</li>
				<li>- Vedno preverite povezavo pred shranjevanjem nastavitev!</li>
			</ul>
			<p style="margin-top: 20px; margin-bottom: 0; padding-top: 15px; border-top: 1px solid #dcdcde; color: #646970; font-size: 13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo esc_html( ARNES_S3_VERSION ); ?>
			</p>
		</div>
	</div>
	<?php
}

// ============================================================
// TAB 2: NASTAVITVE
// ============================================================
function arnes_s3_render_tab_nastavitve() {
	$settings   = arnes_s3_settings();
	$serve_mode = get_option( 'arnes_s3_serve_mode', 'arnes' );
	?>
	<div style="display: flex; gap: 30px;">
		<div style="flex: 0 0 48%;">
			<form method="post" action="options.php">
				<?php settings_fields( 'arnes_s3_settings_group' ); ?>
				<input type="hidden" name="arnes_s3_endpoint"   value="<?php echo esc_attr( $settings['endpoint'] ); ?>" />
				<input type="hidden" name="arnes_s3_bucket"     value="<?php echo esc_attr( $settings['bucket'] ); ?>" />
				<input type="hidden" name="arnes_s3_prefix"     value="<?php echo esc_attr( $settings['prefix'] ); ?>" />
				<input type="hidden" name="arnes_s3_org_id"     value="<?php echo esc_attr( $settings['org_id'] ); ?>" />
				<input type="hidden" name="arnes_s3_access_key" value="<?php echo esc_attr( $settings['access_key'] ); ?>" />
				<input type="hidden" name="arnes_s3_secret_key" value="<?php echo esc_attr( $settings['secret_key'] ); ?>" />

				<table class="form-table" role="presentation">

					<tr><th scope="row" colspan="2" style="background:#f0f0f1;padding:12px;">
						<strong style="font-size:15px;">1. Samodejno nalaganje</strong>
					</th></tr>
					<tr><td colspan="2">
						<fieldset>
							<label>
								<input type="radio" name="arnes_s3_auto_upload" value="1" <?php checked( 1, (int) $settings['auto_upload'] ); ?> />
								<strong>Samodejno naloži nove datoteke tudi v Arnes S3 oblak</strong>
							</label>
							<p class="description" style="margin:5px 0 15px 25px;">Ob nalaganju medijskih datotek v WP knjižnico se bodo naložile tudi v Arnes spletno shrambo.</p>
							<label>
								<input type="radio" name="arnes_s3_auto_upload" value="0" <?php checked( 0, (int) $settings['auto_upload'] ); ?> />
								<strong>Samo ročno</strong>
							</label>
							<p class="description" style="margin:5px 0 0 25px;">Datoteke se hranijo samo lokalno, dokler jih ročno ne naložite v oblak v zavihku "Nalaganje".</p>
						</fieldset>
					</td></tr>

					<tr><th scope="row" colspan="2" style="background:#f0f0f1;padding:12px;padding-top:30px;">
						<strong style="font-size:15px;">2. Ohrani lokalne datoteke</strong>
					</th></tr>
					<tr>
						<th scope="row" style="padding-left:12px;">Ohrani lokalne datoteke</th>
						<td>
							<label>
								<input type="checkbox" name="arnes_s3_keep_local" value="1" <?php checked( $settings['keep_local'], true ); ?> />
								Ohrani kopije medijskih datotek na lokalnem strežniku po nalaganju
							</label>
							<p class="description">Datoteke bodo shranjene tako v Arnes spletni shrambi kot tudi lokalno na vašem strežniku.</p>
						</td>
					</tr>

					<tr><th scope="row" colspan="2" style="background:#f0f0f1;padding:12px;padding-top:30px;">
						<strong style="font-size:15px;">3. Dostava datotek</strong>
					</th></tr>
					<tr><td colspan="2">
						<fieldset>
							<label>
								<input type="radio" name="arnes_s3_serve_mode" value="arnes" <?php checked( $serve_mode, 'arnes' ); ?> />
								<strong>Iz Arnes S3</strong>
							</label>
							<p class="description" style="margin:5px 0 15px 25px;">Datoteke se dostavljajo direktno iz Arnes spletne shrambe</p>
							<label>
								<input type="radio" name="arnes_s3_serve_mode" value="cdn" <?php checked( $serve_mode, 'cdn' ); ?> />
								<strong>Uporabi CDN</strong>
							</label>
							<p class="description" style="margin:5px 0 0 25px;">Hitrejša dostava prek CDN omrežja (npr. Cloudflare).</p>
						</fieldset>
					</td></tr>
					<tr id="cdn-domain-row" style="<?php echo ( $serve_mode === 'cdn' ) ? '' : 'display:none;'; ?>">
						<th scope="row" style="padding-left:12px;"><label for="arnes_s3_cdn_domain">CDN domena</label></th>
						<td>
							<input type="text" id="arnes_s3_cdn_domain" name="arnes_s3_cdn_domain"
							       value="<?php echo esc_attr( $settings['cdn_domain'] ); ?>"
							       class="regular-text" placeholder="https://cdn.vasa-domena.si" />
							<p class="description">Vaša CDN domena (npr. https://cdn.vasa-domena.si)</p>
						</td>
					</tr>

					<tr><th scope="row" colspan="2" style="background:#f0f0f1;padding:12px;padding-top:30px;">
						<strong style="font-size:15px;">4. Kakovost slik</strong>
					</th></tr>
					<?php foreach ( [ 'jpeg' => 'JPEG', 'webp' => 'WebP', 'avif' => 'AVIF' ] as $fmt => $label ) : ?>
					<tr>
						<th scope="row" style="padding-left:20px;"><label for="arnes_s3_<?php echo esc_attr( $fmt ); ?>_quality"><?php echo esc_html( $label ); ?></label></th>
						<td>
							<input type="range" id="arnes_s3_<?php echo esc_attr( $fmt ); ?>_quality_range"
							       min="1" max="100" step="1"
							       value="<?php echo esc_attr( $settings[ $fmt . '_quality' ] ); ?>"
							       style="width:300px;vertical-align:middle;" />
							<input type="number" id="arnes_s3_<?php echo esc_attr( $fmt ); ?>_quality"
							       name="arnes_s3_<?php echo esc_attr( $fmt ); ?>_quality"
							       min="1" max="100" step="1"
							       value="<?php echo esc_attr( $settings[ $fmt . '_quality' ] ); ?>"
							       class="small-text" style="margin-left:10px;width:60px;" />
							<span style="margin-left:5px;">%</span>
						</td>
					</tr>
					<?php endforeach; ?>
					<tr><td colspan="2">
						<div class="notice notice-info inline" style="margin:10px 0;">
							<p>
								<strong>💡 Priporočila</strong><br>
								<strong>Visoka kakovost:</strong> 90-100 (najmanjša kompresija, največje datoteke)<br>
								<strong>Optimalna kakovost:</strong> 82 (WordPress privzeto, odlično razmerje)<br>
								<strong>Nizka kakovost:</strong> 60-80 (večja kompresija, manjše datoteke, še vedno sprejemljiva kakovost)
							</p>
						</div>
					</td></tr>

					<tr><th scope="row" colspan="2" style="background:#f0f0f1;padding:12px;padding-top:30px;">
						<strong style="font-size:15px;">5. Prioriteta formatov slik</strong>
					</th></tr>
					<tr>
						<th scope="row" style="padding-left:12px;"><label>Vrstni red formatov</label></th>
						<td>
							<fieldset>
								<label style="display:block;margin-bottom:10px;">
									<input type="radio" name="arnes_s3_format_priority" value="webp_first" <?php checked( $settings['format_priority'], 'webp_first' ); ?> />
									<strong>Najprej WebP</strong> (WordPress privzeto)
								</label>
								<p class="description" style="margin:5px 0 15px 25px;">Brskalnik bo najprej izbral WebP, nato AVIF. Najboljša kompatibilnost (~97% brskalnikov).</p>
								<label style="display:block;margin-bottom:10px;">
									<input type="radio" name="arnes_s3_format_priority" value="avif_first" <?php checked( $settings['format_priority'], 'avif_first' ); ?> />
									<strong>Najprej AVIF</strong>
								</label>
								<p class="description" style="margin:5px 0 0 25px;">Brskalnik bo najprej izbral AVIF, nato WebP. Manjše datoteke, nižja kompatibilnost (~90% browserjev).</p>
							</fieldset>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Shrani spremembe', 'primary large' ); ?>
			</form>

			<script>
			document.addEventListener('DOMContentLoaded', function() {
				const radioButtons = document.querySelectorAll('input[name="arnes_s3_serve_mode"]');
				const cdnRow = document.getElementById('cdn-domain-row');
				radioButtons.forEach(function(radio) {
					radio.addEventListener('change', function() {
						cdnRow.style.display = (this.value === 'cdn') ? '' : 'none';
					});
				});
				['jpeg','webp','avif'].forEach(function(type) {
					const range  = document.getElementById('arnes_s3_' + type + '_quality_range');
					const number = document.getElementById('arnes_s3_' + type + '_quality');
					if (range && number) {
						range.addEventListener('input',  function() { number.value = this.value; });
						number.addEventListener('input', function() {
							let v = parseInt(this.value);
							if (v < 1) v = 1; if (v > 100) v = 100;
							this.value = v; range.value = v;
						});
					}
				});
			});
			</script>
		</div>

		<div style="flex:0 0 48%;align-self:flex-start;background:#f9f9f9;padding:20px;border:1px solid #dcdcde;border-radius:4px;">
			<h3 style="margin-top:0;">Navodila za nastavitve</h3>
			<h4>1. Samodejno nalaganje</h4>
			<p>Izberite ali naj se nove datoteke samodejno naložijo tudi v Arnes S3 oblak.</p>
			<ul>
				<li><strong>Samodejno:</strong> Vsaka nova datoteka se takoj naloži tudi v oblak. <strong>Priporočeno za večino uporabnikov</strong>.</li>
				<li><strong>Ročno:</strong> Nove datoteke ostanejo samo lokalno.</li>
			</ul>
			<h4>2. Ohrani lokalne datoteke</h4>
			<p>Ko je ta možnost omogočena, so vse medijske datoteke shranjene <strong>tako v Arnes S3 kot lokalno</strong>.</p>
			<h4>3. Dostava datotek</h4>
			<p>Izberite kako želite dostavljati medijske datoteke obiskovalcem.</p>
			<ul>
				<li><strong>Direktno iz Arnes S3:</strong> Priporočeno za manjše strani.</li>
				<li><strong>Uporabi CDN:</strong> Priporočeno za večje strani z mednarodnim občinstvom.</li>
			</ul>
			<p><strong>Primer nastavitev pri Cloudflare:</strong></p>
			<ol>
				<li>Dodajte CNAME zapis: <code>cdn.vasa-domena.si</code> → <code>shramba.arnes.si</code></li>
				<li>Omogočite "Proxy" (oranžen oblak)</li>
				<li>Izberite "Uporabi CDN" in vnesite: <code>https://cdn.vasa-domena.si</code></li>
			</ol>
			<div class="notice notice-success inline" style="margin:20px 0;">
				<p><strong>✅ Priporočilo:</strong> Za brezhibno delovanje z Arnes S3 vtičnikom uporabite privzeto WordPress optimizacijo slik (AVIF/WebP), brez dodatnih vtičnikov za optimizacijo.</p>
			</div>
			<p style="margin-top:20px;margin-bottom:0;padding-top:15px;border-top:1px solid #dcdcde;color:#646970;font-size:13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo esc_html( ARNES_S3_VERSION ); ?>
			</p>
		</div>
	</div>
	<?php
}

// ============================================================
// TAB 3: NALAGANJE
// ============================================================
function arnes_s3_render_tab_mnozicno() {
	$last_result = get_option( 'arnes_s3_last_bulk_result', null );
	?>
	<div style="display: flex; gap: 30px;">
		<div style="flex: 0 0 58%;">
			<div class="postbox">
				<div class="inside" style="padding:20px;">
					<h3 style="margin-top:0;">1. Nastavitve pregleda medijske knjižnice</h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">Datumsko območje</th>
							<td>
								<label style="display:inline-block;margin-right:15px;">Od: <input type="date" id="arnes-s3-filter-date-from" class="regular-text" /></label>
								<label style="display:inline-block;">Do: <input type="date" id="arnes-s3-filter-date-to" class="regular-text" /></label>
								<p class="description">Pustite prazno za vse datume</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Vrste datotek</th>
							<td>
								<select id="arnes-s3-filter-mime-type" class="regular-text">
									<option value="all">Vse datoteke</option>
									<option value="image">Samo slike</option>
									<option value="application/pdf">Samo PDF</option>
									<option value="video/mp4">Samo video (MP4)</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">Velikost datotek (MB)</th>
							<td>
								<label style="display:inline-block;margin-right:15px;">Min: <input type="number" id="arnes-s3-filter-min-size" class="small-text" min="0" step="0.1" /></label>
								<label style="display:inline-block;">Max: <input type="number" id="arnes-s3-filter-max-size" class="small-text" min="0" step="0.1" /></label>
								<p class="description">Pustite prazno za neomejeno</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Možnosti</th>
							<td>
								<label style="display:block;margin-bottom:8px;"><input type="checkbox" id="arnes-s3-only-missing" value="1" checked /> Naloži samo datoteke, ki še niso v Arnes spletni shrambi</label>
								<label style="display:block;"><input type="checkbox" id="arnes-s3-dry-run" value="1" /> Predogled (ogled brez nalaganja)</label>
							</td>
						</tr>
					</table>
					<p class="submit">
						<button type="button" id="arnes-s3-scan-btn" class="button button-primary button-large">Preglej knjižnico</button>
					</p>
					<?php if ( $last_result ) : ?>
					<div class="notice notice-success inline" style="margin:20px 0;">
						<p>
							<strong>Zadnje nalaganje:</strong><br>
							<span style="margin-left:25px;">
								Naloženo: <strong><?php echo esc_html( number_format( $last_result['success_count'] ) ); ?></strong> datotek
								<?php if ( $last_result['error_count'] > 0 ) : ?>
									| Napake: <strong><?php echo esc_html( number_format( $last_result['error_count'] ) ); ?></strong>
								<?php endif; ?>
								<br>
								Datum: <?php echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $last_result['date'] ) ) ); ?>
								| Trajanje: <?php echo esc_html( gmdate( 'i:s', $last_result['duration'] ) ); ?> min
							</span>
						</p>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<div id="arnes-s3-status-message" style="display:none;"></div>
			<div id="arnes-s3-scan-results" style="margin-top:20px;"></div>

			<div id="arnes-s3-upload-controls" style="display:none;margin-top:20px;">
				<div class="postbox">
					<div class="inside" style="padding:20px;">
						<h3 style="margin-top:0;">Potek nalaganja</h3>
						<div style="background:#f0f0f1;height:30px;border-radius:4px;overflow:hidden;margin-bottom:15px;">
							<div id="arnes-s3-progress-bar" style="background:#2271b1;height:100%;width:0%;transition:width 0.3s;"></div>
						</div>
						<table class="widefat" style="margin-top:15px;">
							<tbody>
								<tr><td style="width:150px;"><strong>Napredek:</strong></td><td><span id="arnes-s3-progress-percentage">0%</span> (<span id="arnes-s3-progress-files">0 / 0</span> datotek)</td></tr>
								<tr><td><strong>Trenutna datoteka:</strong></td><td><span id="arnes-s3-current-file">-</span></td></tr>
								<tr><td><strong>Uspešno:</strong></td><td><span id="arnes-s3-success-count" style="color:#46b450;font-weight:bold;">0</span></td></tr>
								<tr><td><strong>Napake:</strong></td><td><span id="arnes-s3-error-count" style="color:#dc3232;font-weight:bold;">0</span></td></tr>
								<tr><td><strong>Pretečen čas:</strong></td><td><span id="arnes-s3-elapsed-time">0:00</span></td></tr>
								<tr><td><strong>Preostali čas:</strong></td><td><span id="arnes-s3-estimated-time">-</span></td></tr>
							</tbody>
						</table>
						<p class="submit" style="margin-top:20px;">
							<button type="button" id="arnes-s3-pause-btn" class="button button-secondary button-large">Premor</button>
							<button type="button" id="arnes-s3-resume-btn" class="button button-secondary button-large" style="display:none;">Nadaljuj</button>
							<button type="button" id="arnes-s3-cancel-btn" class="button button-large" style="margin-left:10px;">Prekliči</button>
						</p>
					</div>
				</div>
			</div>
			<p class="submit" style="margin-top:20px;">
				<button type="button" id="arnes-s3-start-upload-btn" class="button button-primary button-large" disabled>Začni nalaganje</button>
			</p>
		</div>

		<div style="flex:0 0 38%;align-self:flex-start;background:#f9f9f9;padding:20px;border:1px solid #dcdcde;border-radius:4px;">
			<h3 style="margin-top:0;">Navodila</h3>
			<h4>Kako deluje nalaganje datotek</h4>
			<ol>
				<li><strong>Preglej medijsko knjižnico:</strong> Vtičnik bo pregledal vse medijske datoteke glede na izbrane filtre.</li>
				<li><strong>Preglej rezultate:</strong> Videli boste, koliko datotek bo naloženih.</li>
				<li><strong>Začni nalaganje:</strong> Kliknite "Začni nalaganje".</li>
				<li><strong>Spremljajte napredek:</strong> Trak prikazuje status nalaganja v realnem času.</li>
			</ol>
			<h4>Pomembne opombe:</h4>
			<ul>
				<li><strong>Nadaljuj:</strong> Če nalaganje prekinete, lahko nadaljujete kasneje.</li>
				<li><strong>Predogled:</strong> Brez dejanskega nalaganja (t.i. dry-run).</li>
				<li><strong>Samo manjkajoče datoteke:</strong> Privzeto se naložijo samo datoteke, ki še niso v shrambi.</li>
				<li><strong>Procesiranje serij:</strong> Datoteke se nalagajo v serijah po deset hkrati.</li>
			</ul>
			<div class="notice notice-warning inline" style="margin:20px 0;">
				<p><strong>Pomembno:</strong> Med nalaganjem ne zaprite te strani. Proces teče v ozadju in ga lahko kadarkoli ustavite.</p>
			</div>
			<p style="margin-top:20px;margin-bottom:0;padding-top:15px;border-top:1px solid #dcdcde;color:#646970;font-size:13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo esc_html( ARNES_S3_VERSION ); ?>
			</p>
		</div>
	</div>
	<?php
}

// ============================================================
// TAB 4: ORODJA
// ============================================================
function arnes_s3_render_tab_orodja() {
	$existing_backups = arnes_s3_get_existing_backups();
	// Dovoljena HTML za ikone Dashicons v izpisu
	$allowed_icon_html = [ 'span' => [ 'class' => [], 'aria-hidden' => [] ] ];
	?>
	<div style="display: flex; gap: 30px;">
		<div style="flex: 0 0 58%;">

			<!-- SEKCIJA 1: Backup -->
			<div class="postbox">
				<div class="inside" style="padding:20px;">
					<h3 style="margin-top:0;">Arhiviranje medijske knjižnice</h3>
					<p>Ustvarite varnostno kopijo celotne medijske knjižnice v ZIP arhiv</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">Kaj želite arhivirati</th>
							<td>
								<fieldset>
									<label style="display:block;margin-bottom:8px;"><input type="radio" name="backup_source" value="local" checked /> <strong>Lokalne datoteke</strong></label>
									<label style="display:block;"><input type="radio" name="backup_source" value="s3" /> <strong>Datoteke v Arnes oblaku</strong></label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">Vrste datotek</th>
							<td>
								<fieldset>
									<?php foreach ( [ 'image' => 'Slike (JPG, PNG, WebP, AVIF, GIF)', 'application' => 'Dokumenti (PDF, Word, Excel)', 'font' => 'Tipografije (WOFF, WOFF2, TTF, OTF)', 'video' => 'Video (MP4, WebM)', 'other' => 'Ostalo (vsi drugi tipi)' ] as $val => $lbl ) : ?>
									<label style="display:block;margin-bottom:5px;">
										<input type="checkbox" class="backup-file-type" value="<?php echo esc_attr( $val ); ?>" checked /> <?php echo esc_html( $lbl ); ?>
									</label>
									<?php endforeach; ?>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">Dodatno</th>
							<td>
								<label><input type="checkbox" id="backup_include_optimized" value="1" checked /> Vključi sličice in optimizirane slike (WebP in AVIF)</label>
							</td>
						</tr>
					</table>
					<p class="submit">
						<button type="button" id="arnes-s3-backup-scan-btn" class="button button-secondary button-large">Preglej datoteke</button>
						<button type="button" id="arnes-s3-backup-create-btn" class="button button-primary button-large" style="margin-left:10px;" disabled>Ustvari arhiv</button>
					</p>
					<div id="arnes-s3-backup-scan-results" style="display:none;margin-top:20px;"></div>
					<div id="arnes-s3-backup-progress" style="display:none;margin-top:20px;">
						<div style="background:#f0f0f1;height:30px;border-radius:4px;overflow:hidden;margin-bottom:15px;">
							<div id="arnes-s3-backup-progress-bar" style="background:#2271b1;height:100%;width:0%;transition:width 0.3s;"></div>
						</div>
						<p id="arnes-s3-backup-status">Ustvarjam arhiv ...</p>
					</div>
				</div>
			</div>

			<!-- SEKCIJA 2: Obstoječi backupi -->
			<?php if ( ! empty( $existing_backups ) ) : ?>
			<div class="postbox" style="margin-top:20px;">
				<div class="inside" style="padding:20px;">
					<h3 style="margin-top:0;">Obstoječi arhivi</h3>
					<table class="widefat striped">
						<thead><tr><th>Ime datoteke</th><th>Velikost</th><th>Datum</th><th>Dejanja</th></tr></thead>
						<tbody id="arnes-s3-backup-list">
							<?php foreach ( $existing_backups as $backup ) : ?>
							<tr data-filename="<?php echo esc_attr( $backup['filename'] ); ?>">
								<td><strong><?php echo esc_html( $backup['filename'] ); ?></strong></td>
								<td><?php echo esc_html( size_format( $backup['size'], 2 ) ); ?></td>
								<td><?php echo esc_html( date_i18n( 'd.m.Y H:i', $backup['date'] ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( $backup['url'] ); ?>" class="button button-small" target="_blank">Prenesi</a>
									<button type="button" class="button button-small arnes-s3-backup-delete" data-filename="<?php echo esc_attr( $backup['filename'] ); ?>" style="margin-left:5px;">Izbriši</button>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>

			<!-- SEKCIJA 3: Restore -->
			<div class="postbox" style="margin-top:20px;">
				<div class="inside" style="padding:20px;">
					<h3 style="margin-top:0;">Obnova arhiva iz Arnes oblaka</h3>
					<p>Prenesite datoteke iz Arnes oblaka nazaj na lokalni WordPress strežnik</p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">Način obnove</th>
							<td>
								<fieldset>
									<label style="display:block;margin-bottom:8px;"><input type="radio" name="restore_mode" value="missing" checked /> <strong>Samo manjkajoče</strong></label>
									<label style="display:block;"><input type="radio" name="restore_mode" value="all" /> <strong>Vse datoteke</strong></label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">Vrste datotek</th>
							<td>
								<fieldset>
									<?php foreach ( [ 'image' => 'Slike', 'application' => 'Dokumenti', 'font' => 'Fonti', 'video' => 'Video', 'other' => 'Ostalo' ] as $val => $lbl ) : ?>
									<label style="display:block;margin-bottom:5px;">
										<input type="checkbox" class="restore-file-type" value="<?php echo esc_attr( $val ); ?>" checked /> <?php echo esc_html( $lbl ); ?>
									</label>
									<?php endforeach; ?>
								</fieldset>
							</td>
						</tr>
					</table>
					<div class="notice notice-warning inline" style="margin:20px 0;">
						<p><strong>Opozorilo:</strong> Pred obnovo datotek iz oblaka VEDNO ustvarite varnostno kopijo datotek in podatkovne baze!</p>
					</div>
					<p class="submit">
						<button type="button" id="arnes-s3-restore-scan-btn" class="button button-secondary button-large">Preglej datoteke v oblaku</button>
						<button type="button" id="arnes-s3-restore-start-btn" class="button button-primary button-large" style="margin-left:10px;" disabled>Začni obnovo</button>
					</p>
					<div id="arnes-s3-restore-scan-results" style="display:none;margin-top:20px;"></div>
					<div id="arnes-s3-restore-progress" style="display:none;margin-top:20px;">
						<div style="background:#f0f0f1;height:30px;border-radius:4px;overflow:hidden;margin-bottom:15px;">
							<div id="arnes-s3-restore-progress-bar" style="background:#2271b1;height:100%;width:0%;transition:width 0.3s;"></div>
						</div>
						<p><strong>Napredek:</strong> <span id="arnes-s3-restore-progress-text">0 / 0</span></p>
						<p><strong>Trenutna datoteka:</strong> <span id="arnes-s3-restore-current-file">-</span></p>
					</div>
				</div>
			</div>

			<!-- SEKCIJA 4: Sync & Maintenance -->
			<div class="postbox" style="margin-top:20px;">
				<div class="inside" style="padding:20px;">
					<h3 style="margin-top:0;">Sinhronizacija podatkov</h3>
					<p>Orodja za vzdrževanje in sinhronizacijo medijske knjižnice z Arnes shrambo.</p>

					<div style="border-left:3px solid #2271b1;padding-left:15px;margin-bottom:25px;">
						<h4 style="margin-top:0;">Sinhroniziraj metapodatke</h4>
						<p>Poišči medijske datoteke v Arnes oblaku, ki nimajo <code>_arnes_s3_object</code> post meta atributa, in popravi metapodatke.</p>
						<p class="submit" style="margin-top:10px;">
							<button type="button" id="arnes-s3-sync-scan-btn" class="button button-secondary">Preveri metapodatke</button>
							<button type="button" id="arnes-s3-sync-fix-btn" class="button button-primary" style="margin-left:10px;" disabled>Popravi metapodatke</button>
						</p>
						<div id="arnes-s3-sync-results" style="display:none;margin-top:15px;"></div>
					</div>

					<div style="border-left:3px solid #d63638;padding-left:15px;margin-bottom:25px;">
						<h4 style="margin-top:0;">Brisanje lokalne kopije</h4>
						<p>Izbriši lokalne kopije vseh datotek, ki so že varno shranjene v Arnes S3.</p>
						<div class="notice notice-warning inline" style="margin:20px 0;">
							<p><strong>Opozorilo:</strong> Pred brisanjem lokalnih kopij VEDNO ustvarite varnostno kopijo!</p>
						</div>
						<p class="submit" style="margin-top:10px;">
							<button type="button" id="arnes-s3-local-delete-scan-btn" class="button button-secondary">Preglej datoteke</button>
							<button type="button" id="arnes-s3-local-delete-btn" class="button button-primary" style="margin-left:10px;" disabled>Izbriši lokalne kopije</button>
						</p>
						<div id="arnes-s3-local-delete-results" style="display:none;margin-top:15px;"></div>
					</div>

					<div style="border-left:3px solid #00a32a;padding-left:15px;">
						<h4 style="margin-top:0;">Preverjanje integritete</h4>
						<p>Preveri usklajenost med lokalnimi datotekami in datotekami v oblaku.</p>
						<p class="submit" style="margin-top:10px;">
							<button type="button" id="arnes-s3-integrity-check-btn" class="button button-secondary">Preveri integriteteto</button>
						</p>
						<div id="arnes-s3-integrity-results" style="display:none;margin-top:15px;"></div>
					</div>
				</div>
			</div>
		</div>

		<div style="flex:0 0 38%;align-self:flex-start;background:#f9f9f9;padding:20px;border:1px solid #dcdcde;border-radius:4px;">
			<h3 style="margin-top:0;">Navodila</h3>
			<h4>1. Arhiviranje medijske knjižnice</h4>
			<ol>
				<li>Izberite vir datotek (lokalne ali oblak)</li>
				<li>Izberite vrste datotek za vključitev</li>
				<li>Kliknite "Preglej datoteke"</li>
				<li>Kliknite "Ustvari arhiv" in prenesite ZIP</li>
			</ol>
			<h4>2. Obnovitev iz oblaka</h4>
			<ol>
				<li>Izberite način obnovitve</li>
				<li>Kliknite "Preglej datoteke"</li>
				<li>Kliknite "Začni obnovo"</li>
			</ol>
			<div class="notice notice-warning inline" style="margin:20px 0;">
				<p><strong>Nasvet:</strong> Varnostne kopije se shranjujejo na istem strežniku. Za popolno varnost jih prenesite na zunanje lokacije.</p>
			</div>
			<p style="margin-top:20px;margin-bottom:0;padding-top:15px;border-top:1px solid #dcdcde;color:#646970;font-size:13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo esc_html( ARNES_S3_VERSION ); ?>
			</p>
		</div>
	</div>
	<?php
}

// ============================================================
// TAB 5: STATISTIKA
// ============================================================
function arnes_s3_render_tab_statistika() {

	global $wpdb;

	$settings   = arnes_s3_settings();
	$serve_mode = get_option( 'arnes_s3_serve_mode', 'arnes' );

	$credentials_configured = ! empty( $settings['access_key'] ) && ! empty( $settings['secret_key'] ) && ! empty( $settings['org_id'] );

	if ( ! $credentials_configured ) {
		?>
		<div class="notice notice-warning" style="margin:20px 0;padding:15px;">
			<h3 style="margin-top:0;">
				<span class="dashicons dashicons-info arnes-icon" aria-hidden="true"></span> Konfiguracija potrebna
			</h3>
			<p>Statistika bo na voljo, ko boste konfigurirali povezavo z Arnes S3.</p>
			<p><a href="?page=arnes-s3&tab=povezava" class="button button-primary">
				<span class="dashicons dashicons-admin-plugins arnes-icon-sm" aria-hidden="true"></span> Pojdi na zavihek Povezava
			</a></p>
		</div>
		<?php
		return;
	}

	// ---- Skupno število priponk ----
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$total_attachments = wp_cache_get( 'arnes_s3_total_attachments', 'arnes_s3' );
	if ( false === $total_attachments ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$total_attachments = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'" );
		wp_cache_set( 'arnes_s3_total_attachments', $total_attachments, 'arnes_s3', 300 );
	}

	// ---- Priponke v S3 ----
	$attachments_in_s3 = wp_cache_get( 'arnes_s3_attachments_in_s3', 'arnes_s3' );
	if ( false === $attachments_in_s3 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$attachments_in_s3 = $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_arnes_s3_object'"
		);
		wp_cache_set( 'arnes_s3_attachments_in_s3', $attachments_in_s3, 'arnes_s3', 300 );
	}

	$attachments_local_only = (int) $total_attachments - (int) $attachments_in_s3;
	$percentage_in_s3 = $total_attachments > 0 ? round( ( $attachments_in_s3 / $total_attachments ) * 100, 1 ) : 0;

	// ---- Statistika po tipih datotek ----
	$mime_stats = wp_cache_get( 'arnes_s3_mime_stats', 'arnes_s3' );
	if ( false === $mime_stats ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$mime_stats = $wpdb->get_results(
			"SELECT SUBSTRING_INDEX(post_mime_type, '/', 1) as type_group, COUNT(*) as count
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			GROUP BY type_group
			ORDER BY count DESC"
		);
		wp_cache_set( 'arnes_s3_mime_stats', $mime_stats, 'arnes_s3', 300 );
	}

	$type_breakdown = [];
	foreach ( $mime_stats as $stat ) {
		$type        = $stat->type_group;
		$total_count = $stat->count;
		$cache_key   = 'arnes_s3_in_s3_' . md5( $type );
		$in_s3_count = wp_cache_get( $cache_key, 'arnes_s3' );
		if ( false === $in_s3_count ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$in_s3_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'attachment'
				AND p.post_mime_type LIKE %s
				AND pm.meta_key = '_arnes_s3_object'",
				$type . '/%'
			) );
			wp_cache_set( $cache_key, $in_s3_count, 'arnes_s3', 300 );
		}
		$type_breakdown[] = [
			'type'       => $type,
			'total'      => $total_count,
			'in_s3'      => $in_s3_count,
			'local_only' => $total_count - $in_s3_count,
		];
	}

	// ---- Skupna velikost datotek ----
	$all_ids_cache_key = 'arnes_s3_all_attachment_ids';
	$attachment_ids    = wp_cache_get( $all_ids_cache_key, 'arnes_s3' );
	if ( false === $attachment_ids ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$attachment_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment'" );
		wp_cache_set( $all_ids_cache_key, $attachment_ids, 'arnes_s3', 300 );
	}

	$total_local_size = 0;
	$total_s3_size    = 0;
	foreach ( $attachment_ids as $id ) {
		$file_path = get_attached_file( $id );
		if ( $file_path && file_exists( $file_path ) ) {
			$file_size         = filesize( $file_path );
			$total_local_size += $file_size;
			if ( get_post_meta( $id, '_arnes_s3_object', true ) ) {
				$total_s3_size += $file_size;
			}
		}
	}

	$last_bulk_result = arnes_s3_get_last_bulk_result();

	// Dovoljena HTML za ikone Dashicons
	$allowed_icon_html = [ 'span' => [ 'class' => [], 'aria-hidden' => [] ] ];
	?>
	<div style="display: flex; gap: 30px;">
		<div style="flex: 0 0 58%;">

			<!-- SEKCIJA 1: Pregled -->
			<div class="postbox">
				<div class="inside" style="padding:20px;">
					<h2 style="margin-top:0;">
						<span class="dashicons dashicons-chart-pie arnes-icon" aria-hidden="true"></span> Pregled medijske knjižnice
					</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row" style="width:50%;">Skupaj priponk:</th>
							<td><strong style="font-size:18px;"><?php echo esc_html( number_format( $total_attachments, 0, ',', '.' ) ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row">Naloženo v Arnes S3:</th>
							<td>
								<strong style="font-size:18px;color:#00a32a;"><?php echo esc_html( number_format( $attachments_in_s3, 0, ',', '.' ) ); ?></strong>
								<span style="color:#646970;margin-left:10px;">(<?php echo esc_html( $percentage_in_s3 ); ?>%)</span>
							</td>
						</tr>
						<tr>
							<th scope="row">Samo lokalno:</th>
							<td><strong style="font-size:18px;color:#d63638;"><?php echo esc_html( number_format( $attachments_local_only, 0, ',', '.' ) ); ?></strong></td>
						</tr>
					</table>
					<div style="margin-top:20px;">
						<div style="background:#f0f0f1;height:30px;border-radius:4px;overflow:hidden;">
							<div style="background:linear-gradient(90deg,#00a32a 0%,#2271b1 100%);height:100%;width:<?php echo esc_attr( $percentage_in_s3 ); ?>%;transition:width 0.5s;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;">
								<?php if ( $percentage_in_s3 > 10 ) echo esc_html( $percentage_in_s3 ) . '%'; ?>
							</div>
						</div>
						<p class="description" style="margin-top:8px;">Delež datotek naloženih v Arnes S3</p>
					</div>
				</div>
			</div>

			<!-- SEKCIJA 2: Razčlenitev po tipih -->
			<div class="postbox" style="margin-top:20px;">
				<div class="inside" style="padding:20px;">
					<h3 style="margin-top:0;">
						<span class="dashicons dashicons-category arnes-icon" aria-hidden="true"></span> Razčlenitev po tipih datotek
					</h3>
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Tip</th>
								<th style="text-align:center;">Skupaj</th>
								<th style="text-align:center;">V S3</th>
								<th style="text-align:center;">Samo lokalno</th>
								<th style="text-align:center;">Pokritost</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $type_breakdown as $type_data ) :
								$coverage = $type_data['total'] > 0 ? round( ( $type_data['in_s3'] / $type_data['total'] ) * 100, 0 ) : 0;

								// Dashicons ikone namesto Font Awesome
								$icon_map = [
									'image'       => '<span class="dashicons dashicons-format-image arnes-icon-sm" aria-hidden="true"></span>',
									'application' => '<span class="dashicons dashicons-media-document arnes-icon-sm" aria-hidden="true"></span>',
									'video'       => '<span class="dashicons dashicons-video-alt3 arnes-icon-sm" aria-hidden="true"></span>',
									'audio'       => '<span class="dashicons dashicons-format-audio arnes-icon-sm" aria-hidden="true"></span>',
									'text'        => '<span class="dashicons dashicons-media-text arnes-icon-sm" aria-hidden="true"></span>',
									'font'        => '<span class="dashicons dashicons-editor-textcolor arnes-icon-sm" aria-hidden="true"></span>',
								];
								$icon = isset( $icon_map[ $type_data['type'] ] )
									? $icon_map[ $type_data['type'] ]
									: '<span class="dashicons dashicons-paperclip arnes-icon-sm" aria-hidden="true"></span>';

								$type_labels = [
									'image'       => 'Slike',
									'application' => 'Dokumenti',
									'video'       => 'Video',
									'audio'       => 'Zvok',
									'text'        => 'Besedilo',
									'font'        => 'Fonti',
								];
								$type_label = isset( $type_labels[ $type_data['type'] ] ) ? $type_labels[ $type_data['type'] ] : 'Ostalo';
							?>
							<tr>
								<td><strong><?php echo wp_kses( $icon, $allowed_icon_html ); ?> <?php echo esc_html( $type_label ); ?></strong></td>
								<td style="text-align:center;"><?php echo esc_html( number_format( $type_data['total'], 0, ',', '.' ) ); ?></td>
								<td style="text-align:center;color:#00a32a;"><strong><?php echo esc_html( number_format( $type_data['in_s3'], 0, ',', '.' ) ); ?></strong></td>
								<td style="text-align:center;color:#d63638;"><?php echo esc_html( number_format( $type_data['local_only'], 0, ',', '.' ) ); ?></td>
								<td style="text-align:center;">
									<div style="display:inline-flex;align-items:center;gap:8px;">
										<div style="background:#f0f0f1;width:80px;height:20px;border-radius:3px;overflow:hidden;">
											<div style="background:<?php echo esc_attr( $coverage >= 80 ? '#00a32a' : ( $coverage >= 50 ? '#f0b849' : '#d63638' ) ); ?>;height:100%;width:<?php echo esc_attr( $coverage ); ?>%;"></div>
										</div>
										<span style="font-size:13px;font-weight:600;"><?php echo esc_html( $coverage ); ?>%</span>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- SEKCIJA 3: Velikost datotek -->
			<div class="postbox" style="margin-top:20px;">
				<div class="inside" style="padding:20px;">
					<h3 style="margin-top:0;">
						<span class="dashicons dashicons-database arnes-icon" aria-hidden="true"></span> Velikost shranjenih datotek
					</h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row" style="width:50%;">Skupna velikost lokalnih datotek:</th>
							<td><strong style="font-size:16px;"><?php echo esc_html( size_format( $total_local_size, 2 ) ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row">Približna velikost v S3:</th>
							<td><strong style="font-size:16px;color:#2271b1;"><?php echo esc_html( size_format( $total_s3_size, 2 ) ); ?></strong></td>
						</tr>
						<?php if ( ! $settings['keep_local'] && $attachments_in_s3 > 0 ) : ?>
						<tr>
							<th scope="row">Potencialni prihranek prostora:</th>
							<td>
								<strong style="font-size:16px;color:#00a32a;"><?php echo esc_html( size_format( $total_s3_size, 2 ) ); ?></strong>
								<p class="description">Z izbrisom lokalnih kopij datotek, ki so že v S3, lahko prihranite ta prostor.</p>
							</td>
						</tr>
						<?php endif; ?>
					</table>
				</div>
			</div>

			<!-- SEKCIJA 4: Zadnje množično nalaganje -->
			<?php if ( $last_bulk_result ) : ?>
			<div class="postbox" style="margin-top:20px;">
				<div class="inside" style="padding:20px;">
					<h3 style="margin-top:0;">
						<span class="dashicons dashicons-clock arnes-icon" aria-hidden="true"></span> Zadnje množično nalaganje
					</h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row" style="width:50%;">Datum:</th>
							<td><?php echo esc_html( date_i18n( 'd.m.Y H:i', strtotime( $last_bulk_result['date'] ) ) ); ?></td>
						</tr>
						<tr>
							<th scope="row">Skupaj datotek:</th>
							<td><?php echo esc_html( number_format( $last_bulk_result['total_files'], 0, ',', '.' ) ); ?></td>
						</tr>
						<tr>
							<th scope="row">Uspešno naloženih:</th>
							<td style="color:#00a32a;"><strong><?php echo esc_html( number_format( $last_bulk_result['success_count'], 0, ',', '.' ) ); ?></strong></td>
						</tr>
						<?php if ( $last_bulk_result['error_count'] > 0 ) : ?>
						<tr>
							<th scope="row">Napake:</th>
							<td style="color:#d63638;"><strong><?php echo esc_html( number_format( $last_bulk_result['error_count'], 0, ',', '.' ) ); ?></strong></td>
						</tr>
						<?php endif; ?>
						<tr>
							<th scope="row">Čas izvajanja:</th>
							<td><?php echo esc_html( gmdate( 'H:i:s', $last_bulk_result['duration'] ) ); ?></td>
						</tr>
					</table>
				</div>
			</div>
			<?php endif; ?>

		</div>

		<!-- Desna stran: Trenutne nastavitve -->
		<div style="flex:0 0 38%;align-self:flex-start;background:#f9f9f9;padding:20px;border:1px solid #dcdcde;border-radius:4px;">
			<h3 style="margin-top:0;">
				<span class="dashicons dashicons-admin-generic arnes-icon" aria-hidden="true"></span> Trenutne nastavitve
			</h3>

			<h4 style="margin-top:20px;">Povezava S3</h4>
			<table class="form-table" role="presentation" style="margin-top:0;">
				<tr>
					<th scope="row" style="padding-left:0;width:40%;">Endpoint:</th>
					<td style="padding-left:0;"><code><?php echo esc_html( $settings['endpoint'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left:0;">Bucket:</th>
					<td style="padding-left:0;"><code><?php echo esc_html( $settings['bucket'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left:0;">Mapa:</th>
					<td style="padding-left:0;"><code><?php echo esc_html( $settings['prefix'] ); ?></code></td>
				</tr>
			</table>

			<h4 style="margin-top:25px;">Način delovanja</h4>
			<ul style="list-style:none;padding:0;margin:10px 0;">
				<li style="padding:8px;background:<?php echo $settings['auto_upload'] ? '#d7f2e2' : '#fff3cd'; ?>;margin-bottom:8px;border-radius:4px;">
					<strong>Samodejno nalaganje:</strong>
					<?php if ( $settings['auto_upload'] ) : ?>
						<span style="color:#00a32a;">
							<span class="dashicons dashicons-yes-alt arnes-icon-success" aria-hidden="true"></span> Vključeno
						</span>
					<?php else : ?>
						<span style="color:#996800;">
							<span class="dashicons dashicons-dismiss arnes-icon-warning" aria-hidden="true"></span> Izključeno
						</span>
					<?php endif; ?>
				</li>
				<li style="padding:8px;background:<?php echo $settings['keep_local'] ? '#d7f2e2' : '#ffe5e5'; ?>;margin-bottom:8px;border-radius:4px;">
					<strong>Ohrani lokalno:</strong>
					<?php if ( $settings['keep_local'] ) : ?>
						<span style="color:#00a32a;">
							<span class="dashicons dashicons-yes-alt arnes-icon-success" aria-hidden="true"></span> Vključeno
						</span>
					<?php else : ?>
						<span style="color:#d63638;">
							<span class="dashicons dashicons-dismiss arnes-icon-error" aria-hidden="true"></span> Izključeno
						</span>
					<?php endif; ?>
				</li>
				<li style="padding:8px;background:#e5f5fa;border-radius:4px;">
					<strong>Dostava datotek:</strong>
					<?php if ( $serve_mode === 'cdn' ) : ?>
						<span style="color:#2271b1;">
							<span class="dashicons dashicons-networking arnes-icon-sm" aria-hidden="true"></span> CDN
						</span><br>
						<small style="color:#646970;"><?php echo esc_html( $settings['cdn_domain'] ); ?></small>
					<?php else : ?>
						<span style="color:#2271b1;">
							<span class="dashicons dashicons-cloud arnes-icon-sm" aria-hidden="true"></span> Arnes S3
						</span>
					<?php endif; ?>
				</li>
			</ul>

			<h4 style="margin-top:25px;">Kakovost slik</h4>
			<table class="form-table" role="presentation" style="margin-top:0;">
				<tr>
					<th scope="row" style="padding-left:0;width:40%;">JPEG:</th>
					<td style="padding-left:0;"><strong><?php echo esc_html( $settings['jpeg_quality'] ); ?>%</strong></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left:0;">WebP:</th>
					<td style="padding-left:0;"><strong><?php echo esc_html( $settings['webp_quality'] ); ?>%</strong></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left:0;">AVIF:</th>
					<td style="padding-left:0;"><strong><?php echo esc_html( $settings['avif_quality'] ); ?>%</strong></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left:0;">Prioriteta:</th>
					<td style="padding-left:0;">
						<span style="color:#2271b1;">
							<?php echo $settings['format_priority'] === 'avif_first' ? 'AVIF → WebP' : 'WebP → AVIF'; ?>
						</span>
					</td>
				</tr>
			</table>

			<div class="notice notice-info inline" style="margin:25px 0 0 0;">
				<p>
					<span class="dashicons dashicons-lightbulb arnes-icon-sm" aria-hidden="true"></span>
					<strong>Namig:</strong> Če želite povečati pokritost S3, uporabite zavihek "Nalaganje" za nalaganje obstoječih datotek.
				</p>
			</div>

			<?php if ( $attachments_local_only > 0 && $settings['auto_upload'] ) : ?>
			<div class="notice notice-warning inline" style="margin:15px 0 0 0;">
				<p>
					<span class="dashicons dashicons-warning arnes-icon-sm" aria-hidden="true"></span>
					<strong>Pozor:</strong> Imate <?php echo esc_html( $attachments_local_only ); ?> datotek samo lokalno. Te datoteke so bile naložene pred vklopom avtomatskega nalaganja.
				</p>
			</div>
			<?php endif; ?>

			<p style="margin-top:20px;margin-bottom:0;padding-top:15px;border-top:1px solid #dcdcde;color:#646970;font-size:13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo esc_html( ARNES_S3_VERSION ); ?>
			</p>
		</div>
	</div>
	<?php
}
