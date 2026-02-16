<?php
/**
 * Admin stran vtičnika Arnes S3
 * 
 * Funkcionalnosti:
 * - Registracija admin menija pod Media
 * - Enqueue JS in CSS asseta samo na plugin strani
 * - Rendering admin vmesnika (settings form, diagnostika)
 * 
 * POMEMBNO: Vsak form vsebuje hidden fields za ohranitev vrednosti iz drugih tab-ov!
 * To preprečuje brisanje nastavitev ko shranimo en tab.
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
	
	// Font Awesome 7 - CDN (deluje na vseh domenah)
	wp_enqueue_style(
		'font-awesome-7',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
		[],
		'6.5.1'
	);

	// S3 Test Connection JS
	wp_enqueue_script(
		'arnes-s3-admin',
		ARNES_S3_URL . 'assets/js/admin-s3-test.js',
		[],
		ARNES_S3_VERSION,
		true
	);

	wp_localize_script(
		'arnes-s3-admin',
		'arnesS3',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'arnes_s3_test_nonce' ),
		]
	);

	// Bulk Upload JS
	wp_enqueue_script(
		'arnes-s3-bulk-upload',
		ARNES_S3_URL . 'assets/js/admin-bulk-upload.js',
		[ 'jquery' ],
		ARNES_S3_VERSION,
		true
	);

	wp_localize_script(
		'arnes-s3-bulk-upload',
		'arnesS3Bulk',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'arnes_s3_bulk_nonce' ),
		]
	);

	// Backup & Restore JS
	wp_enqueue_script(
		'arnes-s3-backup',
		ARNES_S3_URL . 'assets/js/admin-backup.js',
		[ 'jquery' ],
		ARNES_S3_VERSION,
		true
	);

	wp_localize_script(
		'arnes-s3-backup',
		'arnesS3Backup',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'arnes_s3_backup_nonce' ),
		]
	);
}

/**
 * Add crossorigin attribute to Font Awesome script
 */
add_filter( 'script_loader_tag', 'arnes_s3_add_fontawesome_crossorigin', 10, 2 );

function arnes_s3_add_fontawesome_crossorigin( $tag, $handle ) {
	if ( 'font-awesome-7' === $handle ) {
		return str_replace( ' src', ' crossorigin="anonymous" src', $tag );
	}
	return $tag;
}

function arnes_s3_render_admin_page() {
	
	// Določi aktiven zavihek
	$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'povezava';
	
	?>
	<!-- Font Awesome Icon Styling -->
	<style>
		/* Uniform icon style for tabs and sections */
		.arnes-icon {
			margin-right: 8px;
			color: #2271b1; /* WordPress blue */
			font-size: 16px;
		}
		
		/* Tab navigation icons */
		.nav-tab .arnes-icon {
			margin-right: 6px;
			font-size: 15px;
		}
		
		/* Active tab icon */
		.nav-tab-active .arnes-icon {
			color: #135e96; /* Darker blue for active state */
		}
		
		/* Section header icons */
		h2 .arnes-icon,
		h3 .arnes-icon {
			margin-right: 10px;
			font-size: 18px;
		}
		
		/* Status icons - success/enabled */
		.arnes-icon-success {
			color: #00a32a;
			margin-right: 5px;
		}
		
		/* Status icons - error/disabled */
		.arnes-icon-error {
			color: #d63638;
			margin-right: 5px;
		}
		
		/* Status icons - warning */
		.arnes-icon-warning {
			color: #996800;
			margin-right: 5px;
		}
		
		/* Info icons */
		.arnes-icon-info {
			color: #2271b1;
			margin-right: 5px;
		}
		
		/* Small inline icons */
		.arnes-icon-sm {
			font-size: 14px;
			margin-right: 5px;
		}
	</style>
	
	<div class="wrap">
		<h1>Arnes S3</h1>

		<?php
		// Prikaz success sporočila po shranjevanju
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>Spremembe so bile uspešno shranjene.</p>
			</div>
			<?php
		}
		?>
		
		<!-- Navigacija med zavihki -->
		<h2 class="nav-tab-wrapper">
			<a href="?page=arnes-s3&tab=povezava" class="nav-tab <?php echo $active_tab === 'povezava' ? 'nav-tab-active' : ''; ?>">
				<i class="fa-solid fa-plug arnes-icon"></i>Povezava
			</a>
			<a href="?page=arnes-s3&tab=nastavitve" class="nav-tab <?php echo $active_tab === 'nastavitve' ? 'nav-tab-active' : ''; ?>">
				<i class="fa-solid fa-sliders arnes-icon"></i>Nastavitve
			</a>
			<a href="?page=arnes-s3&tab=mnozicno" class="nav-tab <?php echo $active_tab === 'mnozicno' ? 'nav-tab-active' : ''; ?>">
				<i class="fa-solid fa-cloud-arrow-up arnes-icon"></i>Nalaganje
			</a>
			<a href="?page=arnes-s3&tab=orodja" class="nav-tab <?php echo $active_tab === 'orodja' ? 'nav-tab-active' : ''; ?>">
				<i class="fa-solid fa-toolbox arnes-icon"></i>Orodja
			</a>
			<a href="?page=arnes-s3&tab=statistika" class="nav-tab <?php echo $active_tab === 'statistika' ? 'nav-tab-active' : ''; ?>">
				<i class="fa-solid fa-chart-line arnes-icon"></i>Statistika
			</a>			
		</h2>

		<!-- Vsebina zavihkov -->
		<div style="margin-top: 20px;">
			<?php
			switch ( $active_tab ) {
				case 'povezava':
					arnes_s3_render_tab_povezava();
					break;
				case 'nastavitve':
					arnes_s3_render_tab_nastavitve();
					break;
				case 'mnozicno':
					arnes_s3_render_tab_mnozicno();
					break;
				case 'orodja':
					arnes_s3_render_tab_orodja();
					break;
				case 'statistika':
					arnes_s3_render_tab_statistika();
					break;				
			}
			?>
		</div>
		
		<!-- Status vtičnika (diagnostika) na dnu -->
		<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dcdcde;">
			<?php arnes_s3_display_diagnostics(); ?>
		</div>
	</div>
	<?php
}

/**
 * Tab 1: Povezava
 */
function arnes_s3_render_tab_povezava() {
	$settings = arnes_s3_settings();
	?>
	<div style="display: flex; gap: 30px;">
		<!-- Leva stran: Settings (50%) -->
		<div style="flex: 0 0 48%;">
			<form method="post" action="options.php">
				<?php settings_fields( 'arnes_s3_settings_group' ); ?>
				
				<!-- HIDDEN FIELDS: Ohrani vrednosti iz Tab 2 (Nastavitve) -->
				<input type="hidden" name="arnes_s3_keep_local" value="<?php echo esc_attr( $settings['keep_local'] ); ?>" />
				<input type="hidden" name="arnes_s3_cdn_domain" value="<?php echo esc_attr( $settings['cdn_domain'] ); ?>" />
				<input type="hidden" name="arnes_s3_serve_mode" value="<?php echo esc_attr( get_option( 'arnes_s3_serve_mode', 'arnes' ) ); ?>" />
				<input type="hidden" name="arnes_s3_auto_upload" value="<?php echo esc_attr( $settings['auto_upload'] ); ?>" />
				<input type="hidden" name="arnes_s3_jpeg_quality" value="<?php echo esc_attr( $settings['jpeg_quality'] ); ?>" />
				<input type="hidden" name="arnes_s3_webp_quality" value="<?php echo esc_attr( $settings['webp_quality'] ); ?>" />
				<input type="hidden" name="arnes_s3_avif_quality" value="<?php echo esc_attr( $settings['avif_quality'] ); ?>" />
				
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="arnes_s3_endpoint">S3 končna točka (endpoint)</label>
						</th>
						<td>
							<input type="text" id="arnes_s3_endpoint" name="arnes_s3_endpoint" 
							       value="<?php echo esc_attr( $settings['endpoint'] ); ?>" 
							       class="regular-text" 
							       placeholder="https://shramba.arnes.si" />
							<p class="description">URL naslov Arnes Shrambe (ne spreminjaj!)</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="arnes_s3_bucket">Bucket</label>
						</th>
						<td>
							<input type="text" id="arnes_s3_bucket" name="arnes_s3_bucket" 
							       value="<?php echo esc_attr( $settings['bucket'] ); ?>" 
							       class="regular-text" />
							<p class="description">Ime bucketa v Arnes Shrambi. Privzeto: arnes-shramba</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="arnes_s3_prefix">Mapa/pot</label>
						</th>
						<td>
							<input type="text" id="arnes_s3_prefix" name="arnes_s3_prefix" 
							       value="<?php echo esc_attr( $settings['prefix'] ); ?>" 
							       class="regular-text" />
							<p class="description">Poljubna mapa v bucketu za organizacijo datotek, ki jo <br>ustvarite sami. Primer: moja-domena/slike ipd.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="arnes_s3_org_id">ID organizacije</label>
						</th>
						<td>
							<input type="text" id="arnes_s3_org_id" name="arnes_s3_org_id" 
							       value="<?php echo esc_attr( $settings['org_id'] ); ?>" 
							       class="regular-text" />
							<p class="description">Uporabniško ime vaše organizacije (številka) <br>najdete na Arnes Portalu članic.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="arnes_s3_access_key">Access key organizacije</label>
						</th>
						<td>
							<input type="password" id="arnes_s3_access_key" name="arnes_s3_access_key" 
							       value="<?php echo esc_attr( $settings['access_key'] ); ?>" 
							       class="regular-text" autocomplete="new-password" />
							<p class="description">Dostopni ključ za avtentikacijo</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="arnes_s3_secret_key">Secret key organizacije</label>
						</th>
						<td>
							<input type="password" id="arnes_s3_secret_key" name="arnes_s3_secret_key" 
							       value="<?php echo esc_attr( $settings['secret_key'] ); ?>" 
							       class="regular-text" autocomplete="new-password" />
							<p class="description">Skrivni ključ za avtentikacijo</p>
						</td>
					</tr>
				</table>
				
				<p class="submit" style="display: flex; gap: 12px; align-items: center;">
					<button type="button" id="arnes-s3-test" class="button button-secondary button-large">
						Preveri povezavo
					</button>
					<?php submit_button( 'Shrani spremembe', 'primary large', 'submit', false ); ?>
				</p>
			</form>
			
			<div id="arnes-s3-test-result" style="margin-top:15px;"></div>
		</div>
		
		<!-- Desna stran: Navodila (50%) -->
		<div style="flex: 0 0 48%; align-self: flex-start; background: #f9f9f9; padding: 20px; padding-bottom: 20px; border: 1px solid #dcdcde; border-radius: 4px;">
			<h3 style="margin-top: 0;">Navodila za povezavo</h3>
			
			<p><strong>Kje najdem podatke za povezavo:</strong></p>
			<ol>
				<li>Prijavite se v <a href="https://portal.arnes.si" target="_blank">Arnes portal članic</a>, kjer so vsi podatki v razdelku Arnes shramba.</li>
				<li>Uporabite obstoječ (arnes-shramba) ali ustvarite nov t.i. bucket (z orodjem Duplicati ali Min.io) oziroma.</li>
				<li>Na <a href="https://spletna.shramba.arnes.si/" target="_blank">portalu Arnes Shramba</a> (za prijavo uporabite access in secret key) ustvarite strukturo map in podmap, kamor želite shranjevati vsebino.</li>
				<li>V vsa polja na levi vpišite oziroma kopirajte podatke.</li>
				<li>Kliknite gumb <strong>Preveri povezavo</strong> in po potrditvi, da povezava deluje, še <strong>Shrani spremembe</strong>.
			</ol>
			
			<div class="notice notice-info inline" style="margin: 20px 0;">
				<p><strong>Opomba:</strong> S klikom na "Preveri povezavo" preverite, ali so vnešeni podatki pravilni, preden jih shranite.</p>
			</div>
			
			<p><strong>Priporočila za bucket:</strong></p>
			<ul>
				<li>Uporabite opisno ime (npr. <code>moja-domena-mediji</code>)</li>
				<li>Mape in podmape uporabite za ločevanje projektov (npr. <code>spletna-stran/slike</code>)</li>
				<li>Vedno preverite povezavo pred shranjevanjem nastavitev!</li>
			</ul>
			
			<p style="margin-top: 20px; margin-bottom: 0; padding-top: 15px; border-top: 1px solid #dcdcde; color: #646970; font-size: 13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo ARNES_S3_VERSION; ?>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Tab 2: Nastavitve
 */
function arnes_s3_render_tab_nastavitve() {
	$settings = arnes_s3_settings();
	$serve_mode = get_option( 'arnes_s3_serve_mode', 'arnes' );
	?>
	<div style="display: flex; gap: 30px;">
		<!-- Leva stran: Settings (48%) -->
		<div style="flex: 0 0 48%;">
			<form method="post" action="options.php">
				<?php settings_fields( 'arnes_s3_settings_group' ); ?>
				
				<!-- HIDDEN FIELDS: Ohrani vrednosti iz Tab 1 (Povezava) -->
				<input type="hidden" name="arnes_s3_endpoint" value="<?php echo esc_attr( $settings['endpoint'] ); ?>" />
				<input type="hidden" name="arnes_s3_bucket" value="<?php echo esc_attr( $settings['bucket'] ); ?>" />
				<input type="hidden" name="arnes_s3_prefix" value="<?php echo esc_attr( $settings['prefix'] ); ?>" />
				<input type="hidden" name="arnes_s3_org_id" value="<?php echo esc_attr( $settings['org_id'] ); ?>" />
				<input type="hidden" name="arnes_s3_access_key" value="<?php echo esc_attr( $settings['access_key'] ); ?>" />
				<input type="hidden" name="arnes_s3_secret_key" value="<?php echo esc_attr( $settings['secret_key'] ); ?>" />
				
				<table class="form-table" role="presentation">
					
					<!-- 1. AVTOMATSKO NALAGANJE -->
					<tr>
						<th scope="row" colspan="2" style="background: #f0f0f1; padding: 12px;">
							<strong style="font-size: 15px;">1. Samodejno nalaganje</strong>
						</th>
					</tr>
					<tr>
						<td colspan="2">
							<fieldset>
								<label>
									<input type="radio" name="arnes_s3_auto_upload" value="1" 
									       <?php checked( 1, (int) $settings['auto_upload'] ); ?> />
									<strong>Samodejno naloži nove datoteke v Arnes S3</strong>
								</label>
								<p class="description" style="margin: 5px 0 15px 25px;">
									Ob nalaganju medijskih datotek v WP knjižnico se bodo naložile tudi v Arnes spletno shrambo.
								</p>
								
								<label>
									<input type="radio" name="arnes_s3_auto_upload" value="0" 
									       <?php checked( 0, (int) $settings['auto_upload'] ); ?> />
									<strong>Ročno preko nalaganja</strong>
								</label>
								<p class="description" style="margin: 5px 0 0 25px;">
									Datoteke se hranijo samo lokalno, dokler jih ročno ne naložite v S3 v zavihku "Nalaganje".
								</p>
							</fieldset>
						</td>
					</tr>
					
					<!-- 2. OHRANI LOKALNE DATOTEKE -->
					<tr>
						<th scope="row" colspan="2" style="background: #f0f0f1; padding: 12px; padding-top: 30px;">
							<strong style="font-size: 15px;">2. Ohrani lokalne datoteke</strong>
						</th>
					</tr>
					<tr>
						<th scope="row" style="padding-left: 12px;">Ohrani lokalne datoteke</th>
						<td>
							<label>
								<input type="checkbox" name="arnes_s3_keep_local" value="1" 
								       <?php checked( $settings['keep_local'], true ); ?> />
								Ohrani kopije medijskih datotek na lokalnem strežniku po nalaganju
							</label>
							<p class="description">Datoteke bodo shranjene tako v Arnes spletni shrambi kot tudi lokalno na vašem strežniku.</p>
						</td>
					</tr>
					
					<!-- 3. NAČIN DOSTAVE DATOTEK -->
					<tr>
						<th scope="row" colspan="2" style="background: #f0f0f1; padding: 12px; padding-top: 30px;">
							<strong style="font-size: 15px;">3. Dostava datotek</strong>
						</th>
					</tr>
					<tr>
						<td colspan="2">
							<fieldset>
								<label>
									<input type="radio" name="arnes_s3_serve_mode" value="arnes" 
									       <?php checked( $serve_mode, 'arnes' ); ?> />
									<strong>Iz Arnes S3</strong>
								</label>
								<p class="description" style="margin: 5px 0 15px 25px;">
									Datoteke se dostavljajo direktno iz Arnes spletne shrambe
								</p>
								
								<label>
									<input type="radio" name="arnes_s3_serve_mode" value="cdn" 
									       <?php checked( $serve_mode, 'cdn' ); ?> />
									<strong>Uporabi CDN</strong>
								</label>
								<p class="description" style="margin: 5px 0 0 25px;">
									Hitrejša dostava prek CDN omrežja (npr. Cloudflare).
								</p>
							</fieldset>
						</td>
					</tr>
					<tr id="cdn-domain-row" style="<?php echo ( $serve_mode === 'cdn' ) ? '' : 'display: none;'; ?>">
						<th scope="row" style="padding-left: 12px;">
							<label for="arnes_s3_cdn_domain">CDN domena</label>
						</th>
						<td>
							<input type="text" id="arnes_s3_cdn_domain" name="arnes_s3_cdn_domain" 
							       value="<?php echo esc_attr( $settings['cdn_domain'] ); ?>" 
							       class="regular-text" 
							       placeholder="https://cdn.vasa-domena.si" />
							<p class="description">Vaša CDN domena (npr. https://cdn.vasa-domena.si)</p>
						</td>
					</tr>
					
					<!-- 4. NASTAVITVE KAKOVOSTI SLIK -->
					<tr>
						<th scope="row" colspan="2" style="background: #f0f0f1; padding: 12px; padding-top: 30px;">
							<strong style="font-size: 15px;">4. Kakovost slik</strong>
						</th>
					</tr>
					<tr>
						<th scope="row" style="padding-left: 20px;">
							<label for="arnes_s3_jpeg_quality">JPEG</label>
						</th>
						<td>
							<input type="range" 
							       id="arnes_s3_jpeg_quality_range" 
							       min="1" 
							       max="100" 
							       step="1" 
							       value="<?php echo esc_attr( $settings['jpeg_quality'] ); ?>"
							       style="width: 300px; vertical-align: middle;" />
							<input type="number" 
							       id="arnes_s3_jpeg_quality" 
							       name="arnes_s3_jpeg_quality" 
							       min="1" 
							       max="100" 
							       step="1" 
							       value="<?php echo esc_attr( $settings['jpeg_quality'] ); ?>"
							       class="small-text"
							       style="margin-left: 10px; width: 60px;" />
							<span style="margin-left: 5px;">%</span>
							<p class="description">Privzeto: 82. Višja vrednost = boljša kakovost = večja datoteka.</p>
						</td>
					</tr>
					<tr>
						<th scope="row" style="padding-left: 20px;">
							<label for="arnes_s3_webp_quality">WebP</label>
						</th>
						<td>
							<input type="range" 
							       id="arnes_s3_webp_quality_range" 
							       min="1" 
							       max="100" 
							       step="1" 
							       value="<?php echo esc_attr( $settings['webp_quality'] ); ?>"
							       style="width: 300px; vertical-align: middle;" />
							<input type="number" 
							       id="arnes_s3_webp_quality" 
							       name="arnes_s3_webp_quality" 
							       min="1" 
							       max="100" 
							       step="1" 
							       value="<?php echo esc_attr( $settings['webp_quality'] ); ?>"
							       class="small-text"
							       style="margin-left: 10px; width: 60px;" />
							<span style="margin-left: 5px;">%</span>
							<p class="description">Privzeto: 82. WebP dosega boljšo kompresijo kot JPEG pri enaki kakovosti.</p>
						</td>
					</tr>
					<tr>
						<th scope="row" style="padding-left: 20px;">
							<label for="arnes_s3_avif_quality">AVIF</label>
						</th>
						<td>
							<input type="range" 
							       id="arnes_s3_avif_quality_range" 
							       min="1" 
							       max="100" 
							       step="1" 
							       value="<?php echo esc_attr( $settings['avif_quality'] ); ?>"
							       style="width: 300px; vertical-align: middle;" />
							<input type="number" 
							       id="arnes_s3_avif_quality" 
							       name="arnes_s3_avif_quality" 
							       min="1" 
							       max="100" 
							       step="1" 
							       value="<?php echo esc_attr( $settings['avif_quality'] ); ?>"
							       class="small-text"
							       style="margin-left: 10px; width: 60px;" />
							<span style="margin-left: 5px;">%</span>
							<p class="description">Privzeto: 82. AVIF dosega najboljšo kompresijo pri enaki kakovosti.</p>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div class="notice notice-info inline" style="margin: 10px 0;">
								<p>
									<strong>💡 Priporočila</strong><br>
									<strong>Visoka kakovost:</strong> 90-100 (najmanjša kompresija, največje datoteke)<br>
									<strong>Optimalna kakovost:</strong> 82 (WordPress privzeto, odlično razmerje)<br>
									<strong>Nizka kakovost:</strong> 60-80 (večja kompresija, manjše datoteke, še vedno sprejemljiva kakovost)
								</p>
							</div>
						</td>
					</tr>
					
					<!-- 5. PRIORITETA FORMATOV SLIK -->
					<tr>
						<th scope="row" colspan="2" style="background: #f0f0f1; padding: 12px; padding-top: 30px;">
							<strong style="font-size: 15px;">5. Prioriteta formatov slik</strong>
						</th>
					</tr>
					<tr>
						<th scope="row" style="padding-left: 12px;">
							<label>Vrstni red formatov</label>
						</th>
						<td>
							<fieldset>
								<label style="display: block; margin-bottom: 10px;">
									<input type="radio" name="arnes_s3_format_priority" value="webp_first" 
									       <?php checked( $settings['format_priority'], 'webp_first' ); ?> />
									<strong>Najprej WebP</strong> (WordPress privzeto)
								</label>
								<p class="description" style="margin: 5px 0 15px 25px;">
									Brskalnik bo najprej izbral WebP, če ga strežnik podpira, nato AVIF. Najboljša kompatibilnost (~97% brskalnikov).
								</p>
								
								<label style="display: block; margin-bottom: 10px;">
									<input type="radio" name="arnes_s3_format_priority" value="avif_first" 
									       <?php checked( $settings['format_priority'], 'avif_first' ); ?> />
									<strong>Najprej AVIF</strong>
								</label>
								<p class="description" style="margin: 5px 0 0 25px;">
									Brskalnik bo najprej izbral AVIF, če ga strežnik podpira, nato WebP. Manjše datoteke, nižja kompatibilnost (~90% browserjev).
								</p>
							</fieldset>
						</td>
					</tr>
				</table>
									
				<?php submit_button( 'Shrani spremembe', 'primary large' ); ?>
			</form>
			
			<script>
			document.addEventListener('DOMContentLoaded', function() {
				// Serve mode radio buttons
				const radioButtons = document.querySelectorAll('input[name="arnes_s3_serve_mode"]');
				const cdnRow = document.getElementById('cdn-domain-row');
				
				radioButtons.forEach(function(radio) {
					radio.addEventListener('change', function() {
						if (this.value === 'cdn') {
							cdnRow.style.display = '';
						} else {
							cdnRow.style.display = 'none';
						}
					});
				});
				
				// Image quality sliders sync (range <-> number)
				function syncQualityInputs(type) {
					const rangeInput = document.getElementById('arnes_s3_' + type + '_quality_range');
					const numberInput = document.getElementById('arnes_s3_' + type + '_quality');
					
					if (rangeInput && numberInput) {
						// Range -> Number
						rangeInput.addEventListener('input', function() {
							numberInput.value = this.value;
						});
						
						// Number -> Range
						numberInput.addEventListener('input', function() {
							let val = parseInt(this.value);
							if (val < 1) val = 1;
							if (val > 100) val = 100;
							this.value = val;
							rangeInput.value = val;
						});
					}
				}
				
				syncQualityInputs('jpeg');
				syncQualityInputs('webp');
				syncQualityInputs('avif');
			});
			</script>
		</div>
		
		<!-- Desna stran: Navodila (52%) -->
		<div style="flex: 0 0 48%; align-self: flex-start; background: #f9f9f9; padding: 20px; padding-bottom: 20px; border: 1px solid #dcdcde; border-radius: 4px;">
			<h3 style="margin-top: 0;">Navodila za nastavitve</h3>
			
			<h4>1. Samodejno nalaganje</h4>
			<p>Izberite ali naj se nove datoteke, ki jih naložite v WP knjižnici, samodejno nalagajo tudi v Arnes S3 ali ne.</p>
			<ul>
				<li><strong>Samodejno:</strong> Vsaka nova datoteka naložena v Knjižnici se takoj naloži v oblak. <strong>Priporočeno za večino uporabnikov</strong>.</li>
				<li><strong>Ročno:</strong> Nove datoteke ostanejo samo lokalno. To je lahko uporabno za:
					<ul>
						<li> - t.i. batch-upload strategijo (naložiš več datotek lokalno, nato množično naložiš vse naenkrat)</li>
						<li> - Testiranje funkcionalnosti nalaganja</li>
						<li> - Kontrolirano nalaganje (sami izberete, kdaj se datoteke nalagajo v Arnes shrambo)</li>
					</ul>
				</li>
			</ul>
			
			<div class="notice notice-info inline" style="margin: 15px 0;">
				<p><strong>💡 Namig:</strong> Če želite testirati "Nalaganje" po tem, ko so vse datoteke že v S3, izključite avtomatsko nalaganje, naložite nekaj testnih datotek in izvedite nalaganje.</p>
			</div>
			
			<h4>2. Ohrani lokalne datoteke</h4>
			<p>Ko je ta možnost omogočena, so vse medijske datoteke shranjene <strong>tako v Arnes S3 kot lokalno</strong> na vašem WordPress strežniku.</p>
			<ul>
				<li><strong>Prednost:</strong> Varnostna kopija - če Arnes shramba ni dosegljiva, so datoteke še vedno na strežniku</li>
				<li><strong>Slabost:</strong> Nepotrebna poraba prostora na strežniku</li>
			</ul>
			
			<h4>3. Dostava datotek</h4>
			<p>Izberite kako želite dostavljati medijske datoteke obiskovalcem. Izbor vpliva na prikaz URLjev slik in drugih medijskih datotek na vaši spletni strani. Primer URLjev v brskalniku: Če izberete Arnes <code>https://shramba.arnes.si/.../ime-slike.jpg</code> oziroma če izberete CDN <code>https://cdn.moja-domena.si/.../ime-slike.jpg</code></p>
			<ul>
				<li><strong>Direktno iz Arnes S3:</strong> Datoteke se dostavljajo direktno iz Arnes Shramba strežnikov. Najboljša možnost za manjše strani.</li>
				<li><strong>Uporabi CDN:</strong> Hitrejša dostava prek CDN omrežja (npr. Cloudflare). Priporočeno za večje strani z mednarodnim občinstvom.</li>
			</ul>
			
			<p><strong>Primer nastavitev pri ponudniku Cloudflare:</strong></p>
			<ol>
				<li>V Cloudflare računu dodajte CNAME zapis: <code>cdn.vasa-domena.si</code> → <code>shramba.arnes.si</code></li> (namesto cdn lahko izberete poljubno poddomeno)
				<li>Omogočite "Proxy" (oranžen oblak)</li>
				<li>IZBIRNO - Ustvarite Cache Rule: <code>cdn.vasa-domena.si/*</code> → Eligible for Cache, Respect origin TTL</li>
				<li>Izberite "Uporabi CDN" zgoraj in vnesite: <code>https://cdn.vasa-domena.si</code></li>
			</ol>
			
			<h4>4. Kakovost slik</h4>
			<p>Nastavite kakovost kompresije za različne slikovne formate:</p>
			<ul>
				<li><strong>JPEG kakovost:</strong> Nastavitev kompresije za JPEG slike (1-100). Privzeto: 82</li>
				<li><strong>WebP kakovost:</strong> Nastavitev kompresije za WebP format. Privzeto: 82</li>
				<li><strong>AVIF kakovost:</strong> Nastavitev kompresije za AVIF format. Privzeto: 82</li>
			</ul>
			
			<h4>5. Prioriteta formatov slik</h4>
			<p>Določite vrstni red v katerem brskalnik izbere format slike iz <code>srcset</code> atributa:</p>
			<ul>
				<li><strong>Najprej WebP:</strong> WordPress privzeta vrednost. Najboljša kompatibilnost (~97% brskalnikov)</li>
				<li><strong>Najprej AVIF:</strong> Najboljša kompresija (30-50% manjše datoteke). Nekoliko nižja kompatibilnost (~90% brskalnikov)</li>
			</ul>
			
				<div class="notice notice-success inline" style="margin: 20px 0;">
				<p><strong>✅ Priporočilo:</strong> Za  brezhibno delovanje z Arnes S3 vtičnikom uporabite privzeto oziroma native WordPress optimizacijo slik (AVIF/WebP), torej brez dodatnih vtičnikov za optimizacijo slik.</p>
				<p>WordPress od različice 6.5 dalje podpira WebP in AVIF. Vse optimizirane verzije se avtomatsko naložijo v S3. To je najpreprostejši pristop.</p>
				<p><strong>Vtičniki za optimizacijo slik</strong> (ShortPixel, EWWW, Imagify, Smush, CompressX ipd.):</p>
				<p>Arnes S3 vtičnik je zasnovan tako, da optimizira slike pred nalaganjem in nato naloži optimizirane verzije v oblak. Deluje brezhibno z vsemi vtičniki.</p>
				<p><strong>Posebnosti:</strong></p>
				<p>Nekateri vtičniki optimizirane datoteke nalagajo v ločeno mapo, npr. <code>/wp-content/compressx-nextgen/</code>, ki je izven standardne WordPress uploads strukture. <strong>Arnes S3 vtičnik teh datoteke ne zaznava</strong>. Za brezhibno delovanje priporočamo, da druge vtičnike za optimizacijo slik deaktivirate.</p>
			</div>
			
			<p style="margin-top: 20px; margin-bottom: 0; padding-top: 15px; border-top: 1px solid #dcdcde; color: #646970; font-size: 13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo ARNES_S3_VERSION; ?>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Tab 3: Nalaganje
 */
function arnes_s3_render_tab_mnozicno() {
	// Podatki za zadnjo bulk upload operacijo
	$last_result = get_option( 'arnes_s3_last_bulk_result', null );
	?>
	<div style="display: flex; gap: 30px;">
		<!-- Leva stran: Bulk Upload UI (60%) -->
		<div style="flex: 0 0 58%;">
			
			<!-- KORAK 1: Scan Options -->
			<div class="postbox">
				<div class="inside" style="padding: 20px;">
					<h3 style="margin-top: 0;">1. Nastavitve pregleda</h3>
					
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">Datumsko območje</th>
							<td>
								<label style="display: inline-block; margin-right: 15px;">
									Od: <input type="date" id="arnes-s3-filter-date-from" class="regular-text" />
								</label>
								<label style="display: inline-block;">
									Do: <input type="date" id="arnes-s3-filter-date-to" class="regular-text" />
								</label>
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
								<label style="display: inline-block; margin-right: 15px;">
									Min: <input type="number" id="arnes-s3-filter-min-size" class="small-text" min="0" step="0.1" />
								</label>
								<label style="display: inline-block;">
									Max: <input type="number" id="arnes-s3-filter-max-size" class="small-text" min="0" step="0.1" />
								</label>
								<p class="description">Pustite prazno za neomejeno</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Možnosti</th>
							<td>
								<label style="display: block; margin-bottom: 8px;">
									<input type="checkbox" id="arnes-s3-only-missing" value="1" checked />
									Naloži samo datoteke, ki še niso v Arnes spletni shrambi
								</label>
								<label style="display: block;">
									<input type="checkbox" id="arnes-s3-dry-run" value="1" />
									Predogled (t.i. Dry-run mode)
								</label>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<button type="button" id="arnes-s3-scan-btn" class="button button-primary button-large">
							Preglej knjižnico
						</button>
					</p>
					
					<?php if ( $last_result ) : ?>
						<div class="notice notice-success inline" style="margin: 20px 0;">
							<p>
								<strong>Zadnje nalaganje:</strong><br>
								<span style="margin-left: 25px;">
									Naloženo: <strong><?php echo number_format( $last_result['success_count'] ); ?></strong> datotek
									<?php if ( $last_result['error_count'] > 0 ) : ?>
										| Napake: <strong><?php echo number_format( $last_result['error_count'] ); ?></strong>
									<?php endif; ?>
									<br>
									Datum: <?php echo date_i18n( 'd.m.Y H:i', strtotime( $last_result['date'] ) ); ?>
									| Trajanje: <?php echo gmdate( 'i:s', $last_result['duration'] ); ?> min
								</span>
							</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Status sporočilo (prikaže se med nalaganjem) -->
			<div id="arnes-s3-status-message" style="display: none;"></div>
			
			<!-- KORAK 2: Rezultati skeniranja -->
			<div id="arnes-s3-scan-results" style="margin-top: 20px;"></div>
			
			<!-- KORAK 3: Upload kontrole in progress -->
			<div id="arnes-s3-upload-controls" style="display: none; margin-top: 20px;">
				
				<!-- Progress Bar -->
				<div class="postbox">
					<div class="inside" style="padding: 20px;">
						<h3 style="margin-top: 0;">Potek nalaganja</h3>
						
						<div style="background: #f0f0f1; height: 30px; border-radius: 4px; overflow: hidden; margin-bottom: 15px;">
							<div id="arnes-s3-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
						</div>
						
						<table class="widefat" style="margin-top: 15px;">
							<tbody>
								<tr>
									<td style="width: 150px;"><strong>Napredek:</strong></td>
									<td>
										<span id="arnes-s3-progress-percentage">0%</span> 
										(<span id="arnes-s3-progress-files">0 / 0</span> datotek)
									</td>
								</tr>
								<tr>
									<td><strong>Trenutna datoteka:</strong></td>
									<td><span id="arnes-s3-current-file">-</span></td>
								</tr>
								<tr>
									<td><strong>Uspešno:</strong></td>
									<td><span id="arnes-s3-success-count" style="color: #46b450; font-weight: bold;">0</span></td>
								</tr>
								<tr>
									<td><strong>Napake:</strong></td>
									<td><span id="arnes-s3-error-count" style="color: #dc3232; font-weight: bold;">0</span></td>
								</tr>
								<tr>
									<td><strong>Pretečen čas:</strong></td>
									<td><span id="arnes-s3-elapsed-time">0:00</span></td>
								</tr>
								<tr>
									<td><strong>Preostali čas:</strong></td>
									<td><span id="arnes-s3-estimated-time">-</span></td>
								</tr>
							</tbody>
						</table>
						
						<p class="submit" style="margin-top: 20px;">
							<button type="button" id="arnes-s3-pause-btn" class="button button-secondary button-large">
								Premor
							</button>
							<button type="button" id="arnes-s3-resume-btn" class="button button-secondary button-large" style="display: none;">
								Nadaljuj
							</button>
							<button type="button" id="arnes-s3-cancel-btn" class="button button-large" style="margin-left: 10px;">
								Prekliči
							</button>
						</p>
					</div>
				</div>
			</div>
			
			<!-- Start Upload Button (prikazan po skeniranju) -->
			<p class="submit" style="margin-top: 20px;">
				<button type="button" id="arnes-s3-start-upload-btn" class="button button-primary button-large" disabled>
					Začni nalaganje
				</button>
			</p>
		</div>
		
		<!-- Desna stran: Navodila (40%) -->
		<div style="flex: 0 0 38%; align-self: flex-start; background: #f9f9f9; padding: 20px; padding-bottom: 20px; border: 1px solid #dcdcde; border-radius: 4px;">
			<h3 style="margin-top: 0;">Navodila</h3>
			
			<h4>Kako deluje nalaganje?</h4>
			<ol>
				<li><strong>Preglej medijsko knjižnico:</strong> Vtičnik bo pregledal vse medijske datoteke glede na izbrane filtre.</li>
				<li><strong>Preglej rezultate:</strong> Videli boste, koliko datotek bo naloženih in njihovo skupno velikost.</li>
				<li><strong>Začni nalaganje:</strong> Kliknite "Začni nalaganje" za začetek.</li>
				<li><strong>Spremljajte napredek:</strong> Trak prikazuje status nalaganja v realnem času.</li>
			</ol>
			
			<h4>Pomembne opombe:</h4>
			<ul>
				<li><strong>Nadaljuj:</strong> Če nalaganje prekinete ali zaprjete okno, lahko nadaljujete kasneje.</li>
				<li><strong>Predogled:</strong> Uporabite to možnost za predogled datotek brez dejanskega nalaganja.</li>
				<li><strong>Samo manjkajoče datoteke:</strong> Privzeto se naložijo samo datoteke, ki še niso v Arnes spletni shrambi (prepreči podvajanje).</li>
				<li><strong>Procesiranje serij:</strong> Datoteke se nalagajo v serijah po deset za optimalno hitrost in stabilnost.</li>
			</ul>
			
			<div class="notice notice-warning inline" style="margin: 20px 0;">
				<p><strong>Pomembno:</strong> Med nalaganjem ne zaprite te strani. Proces teče v ozadju in ga lahko kadarkoli ustavite, prekinete ali nadaljujete.</p>
			</div>
			
			<p style="margin-top: 20px; margin-bottom: 0; padding-top: 15px; border-top: 1px solid #dcdcde; color: #646970; font-size: 13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo ARNES_S3_VERSION; ?>
			</p>
		</div>
	</div>
	
	<script>
	// Enqueue bulk upload JS (če še ni)
	// To je že dodano v arnes_s3_admin_assets() funkcijo
	</script>
	<?php
}

/**
 * Tab 4: Orodja
 */
function arnes_s3_render_tab_orodja() {
	// Pridobi obstoječe backupe
	$existing_backups = arnes_s3_get_existing_backups();
	?>
	<style>
		/* Odstrani ikone iz buttonov v Orodja tabu */
		#arnes-s3-backup-scan-btn::before,
		#arnes-s3-restore-scan-btn::before {
			content: none !important;
			display: none !important;
		}
	</style>
	<div style="display: flex; gap: 30px;">
		<!-- Leva stran: Backup UI (60%) -->
		<div style="flex: 0 0 58%;">
			
			<!-- SEKCIJA 1: Backup Media Library -->
			<div class="postbox">
				<div class="inside" style="padding: 20px;">
					<h3 style="margin-top: 0;">Arhiviranje medijske knjižnice</h3>
					<p>Ustvarite varnostno kopijo celotne medijske knjižnice v ZIP arhiv.</p>
					
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">Kaj želite arhivirati</th>
							<td>
								<fieldset>
									<label style="display: block; margin-bottom: 8px;">
										<input type="radio" name="backup_source" value="local" checked />
										<strong>Lokalne datoteke</strong> - Arhivira datoteke, shranjenih na WordPress strežniku
									</label>
									<label style="display: block;">
										<input type="radio" name="backup_source" value="s3" />
										<strong>Datoteke v Arnes oblaku</strong> - Arhivira datoteke, ki so v Arnes oblaku
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">Vrste datotek</th>
							<td>
								<fieldset>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" class="backup-file-type" value="image" checked />
										Slike (JPG, PNG, WebP, AVIF, GIF)
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" class="backup-file-type" value="application" checked />
										Dokumenti (PDF, Word, Excel)
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" class="backup-file-type" value="font" checked />
										Tipografije (WOFF, WOFF2, TTF, OTF)
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" class="backup-file-type" value="video" checked />
										Video (MP4, WebM)
									</label>
									<label style="display: block;">
										<input type="checkbox" class="backup-file-type" value="other" checked />
										Ostalo (vsi drugi tipi)
									</label>
								</fieldset>
								<p class="description">Izberite katere vrste datotek želite vključiti v arhiv.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Dodatno</th>
							<td>
								<label>
									<input type="checkbox" id="backup_include_optimized" value="1" checked />
									Vključi sličice za predogled (thumbnails) in optimizirane slike (WebP in AVIF)
								</label>
								<p class="description">Če je omogočeno, bodo v arhiv vključene vse različice slik.</p>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<button type="button" id="arnes-s3-backup-scan-btn" class="button button-secondary button-large">
							Preglej datoteke
						</button>
						<button type="button" id="arnes-s3-backup-create-btn" class="button button-primary button-large" style="margin-left: 10px;" disabled>
							Ustvari arhiv
						</button>
					</p>
					
					<!-- Scan rezultati -->
					<div id="arnes-s3-backup-scan-results" style="display: none; margin-top: 20px;"></div>
					
					<!-- Progress -->
					<div id="arnes-s3-backup-progress" style="display: none; margin-top: 20px;">
						<div style="background: #f0f0f1; height: 30px; border-radius: 4px; overflow: hidden; margin-bottom: 15px;">
							<div id="arnes-s3-backup-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
						</div>
						<p id="arnes-s3-backup-status">Ustvarjam arhiv ...</p>
					</div>
				</div>
			</div>
			
			<!-- SEKCIJA 2: Obstoječi backupi -->
			<?php if ( ! empty( $existing_backups ) ) : ?>
			<div class="postbox" style="margin-top: 20px;">
				<div class="inside" style="padding: 20px;">
					<h3 style="margin-top: 0;">Obstoječi arhivi</h3>
					
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Ime datoteke</th>
								<th>Velikost</th>
								<th>Datum</th>
								<th>Dejanja</th>
							</tr>
						</thead>
						<tbody id="arnes-s3-backup-list">
							<?php foreach ( $existing_backups as $backup ) : ?>
							<tr data-filename="<?php echo esc_attr( $backup['filename'] ); ?>">
								<td>
									<strong><?php echo esc_html( $backup['filename'] ); ?></strong>
								</td>
								<td><?php echo size_format( $backup['size'], 2 ); ?></td>
								<td><?php echo date_i18n( 'd.m.Y H:i', $backup['date'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( $backup['url'] ); ?>" class="button button-small" target="_blank">
										Prenesi
									</a>
									<button type="button" class="button button-small arnes-s3-backup-delete" data-filename="<?php echo esc_attr( $backup['filename'] ); ?>" style="margin-left: 5px;">
										Izbriši
									</button>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php endif; ?>
			
			<!-- SEKCIJA 3: Restore iz S3 -->
			<div class="postbox" style="margin-top: 20px;">
				<div class="inside" style="padding: 20px;">
					<h3 style="margin-top: 0;">Obnova arhiva iz Arnes oblaka</h3>
					<p>Prenesite datoteke iz Arnes oblaka nazaj na lokalni WordPress strežnik.</p>
					
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">Način obnove</th>
							<td>
								<fieldset>
									<label style="display: block; margin-bottom: 8px;">
										<input type="radio" name="restore_mode" value="missing" checked />
										<strong>Samo manjkajoče</strong> - Povrni samo datoteke, ki ne obstajajo lokalno
									</label>
									<label style="display: block;">
										<input type="radio" name="restore_mode" value="all" />
										<strong>Vse datoteke</strong> - Povrni vse datoteke iz Arnes oblaka (prepiši obstoječe)
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">Vrste datotek</th>
							<td>
								<fieldset>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" class="restore-file-type" value="image" checked />
										Slike
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" class="restore-file-type" value="application" checked />
										Dokumenti
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" class="restore-file-type" value="font" checked />
										Fonti
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" class="restore-file-type" value="video" checked />
										Video
									</label>
									<label style="display: block;">
										<input type="checkbox" class="restore-file-type" value="other" checked />
										Ostalo
									</label>
								</fieldset>
							</td>
						</tr>
					</table>
					
					<div class="notice notice-warning inline" style="margin: 20px 0;">
				<p><strong>Opozorilo:</strong> Pred obnovo datotek iz oblaka VEDNO ustvarite varnostno kopijo datotek in podatkovne baze z namenskim WP orodjem!.</p>
			</div>
					
					<p class="submit">
						<button type="button" id="arnes-s3-restore-scan-btn" class="button button-secondary button-large">
							Preglej datoteke v oblaku
						</button>
						<button type="button" id="arnes-s3-restore-start-btn" class="button button-primary button-large" style="margin-left: 10px;" disabled>
							Začni obnovo
						</button>
					</p>
					
					<!-- Scan rezultati -->
					<div id="arnes-s3-restore-scan-results" style="display: none; margin-top: 20px;"></div>
					
					<!-- Progress -->
					<div id="arnes-s3-restore-progress" style="display: none; margin-top: 20px;">
						<div style="background: #f0f0f1; height: 30px; border-radius: 4px; overflow: hidden; margin-bottom: 15px;">
							<div id="arnes-s3-restore-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
						</div>
						<p><strong>Napredek:</strong> <span id="arnes-s3-restore-progress-text">0 / 0</span></p>
						<p><strong>Trenutna datoteka:</strong> <span id="arnes-s3-restore-current-file">-</span></p>
					</div>
				</div>
			</div>
			
			<!-- SEKCIJA 4: Sync & Maintenance -->
			<div class="postbox" style="margin-top: 20px;">
				<div class="inside" style="padding: 20px;">
					<h3 style="margin-top: 0;">Sinhronizacija podatkov</h3>
					<p>Orodja za vzdrževanje in sinhronizacijo medijske knjižnice z Arnes shrambo.</p>
					
					<!-- Sub-sekcija 1: Re-sync S3 Metadata -->
					<div style="border-left: 3px solid #2271b1; padding-left: 15px; margin-bottom: 25px;">
						<h4 style="margin-top: 0;">Sinhroniziraj metapodatke</h4>
						<p>Poišči medijske datoteke v Arnes oblaku, ki nimajo <code>_arnes_s3_object</code> post meta atributa in popravi metapodatke.</p>
						
						<p class="submit" style="margin-top: 10px;">
							<button type="button" id="arnes-s3-sync-scan-btn" class="button button-secondary">
								Preveri metapodatke
							</button>
							<button type="button" id="arnes-s3-sync-fix-btn" class="button button-primary" style="margin-left: 10px;" disabled>
								Popravi metapodatke
							</button>
						</p>
						
						<div id="arnes-s3-sync-results" style="display: none; margin-top: 15px;"></div>
					</div>
					
					<!-- Sub-sekcija 2: Bulk Delete lokalnih kopij -->
					<div style="border-left: 3px solid #d63638; padding-left: 15px; margin-bottom: 25px;">
						<h4 style="margin-top: 0;">Brisanje lokalne kopije</h4>
						<p>Izbriši lokalne kopije vseh datotek, ki so že varno shranjene v Arnes S3 (prihrani prostor na disku).</p>
						
						<div class="notice notice-warning inline" style="margin: 20px 0;">
				<p><strong>Opozorilo:</strong> Pred brisanjem lokalnih kopij VEDNO ustvarite varnostno kopijo datotek in podatkovne baze z namenskim WP orodjem!.</p>
			</div>
						
						<p class="submit" style="margin-top: 10px;">
							<button type="button" id="arnes-s3-local-delete-scan-btn" class="button button-secondary">
								Preglej datoteke
							</button>
							<button type="button" id="arnes-s3-local-delete-btn" class="button button-primary" style="margin-left: 10px;" disabled>
								Izbriši lokalne kopije
							</button>
						</p>
						
						<div id="arnes-s3-local-delete-results" style="display: none; margin-top: 15px;"></div>
					</div>
					
					<!-- Sub-sekcija 3: Preverjanje integritete -->
					<div style="border-left: 3px solid #00a32a; padding-left: 15px;">
						<h4 style="margin-top: 0;">Preverjanje integritete</h4>
						<p>Preveri usklajenost med lokalnimi datotekami in datotekami v oblaku.</p>
						
						<p class="submit" style="margin-top: 10px;">
							<button type="button" id="arnes-s3-integrity-check-btn" class="button button-secondary">
								Preveri integriteteto
							</button>
						</p>
						
						<div id="arnes-s3-integrity-results" style="display: none; margin-top: 15px;"></div>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Desna stran: Navodila (40%) -->
		<div style="flex: 0 0 38%; align-self: flex-start; background: #f9f9f9; padding: 20px; padding-bottom: 20px; border: 1px solid #dcdcde; border-radius: 4px;">
			<h3 style="margin-top: 0;">Navodila</h3>
			
			<h4>Arhiviranje medijske knjižnice</h4>
			<p>Ustvarite ZIP arhiv celotne medijske knjižnice za varnostno kopijo.</p>
			<ol>
				<li>Izberite vir datotek (lokalne ali oblak)</li>
				<li>Izberite vrste datotek za vključitev</li>
				<li>Kliknite "Preglej datoteke"</li>
				<li>Preglejte rezultate in kliknite "Ustvari arhiv"</li>
				<li>Prenesite ZIP datoteko</li>
			</ol>
			
			<h4>Obnovitev iz oblaka</h4>
			<p>Prenesite datoteke iz Arnes oblaka nazaj na lokalni strežnik.</p>
			<ol>
				<li>Izberite način obnovitve (samo manjkajoče ali vse)</li>
				<li>Izberite vrste datotek</li>
				<li>Kliknite "Preglej datoteke"</li>
				<li>Preglejte rezultate in kliknite "Začni obnovo"</li>
				<li>Počakajte na konec</li>
			</ol>
			
			<h4>Zakaj WebP/AVIF datoteke niso vidne lokalno v medijski knjižnici?</h4>
			<p>Če imate izključeno možnost "Ohrani lokalne datoteke" (zavihek Nastavitve):</p>
			<ul>
				<li>- WordPress generira WebP/AVIF ob nalaganju slike</li>
				<li>- Vtičnik jih naloži v Arnes oblak</li>
				<li>- Vtičnik izbriše lokalne kopije (da prihrani prostor)</li>
				<li>- Slike se strežejo direktno iz Arnes shrambe/CDN</li>
			</ul>
			<p><strong>Datoteke so varne v S3, lokalni disk pa je prost</strong>.</p>
			
			<div class="notice notice-warning inline" style="margin: 20px 0;">
				<p><strong>Opozorilo:</strong> Varnostne kopije se shranjujejo na istem strežniku, kjer zavzemajo prostor na disku. Za popolno varnost jih prenesite na zunanje lokacije.</p>
			</div>
			
			<p style="margin-top: 20px; margin-bottom: 0; padding-top: 15px; border-top: 1px solid #dcdcde; color: #646970; font-size: 13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo ARNES_S3_VERSION; ?>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Tab 5: Statistika
 */
function arnes_s3_render_tab_statistika() {
	
	global $wpdb;
	
	// Pridobi nastavitve
	$settings = arnes_s3_settings();
	$serve_mode = get_option( 'arnes_s3_serve_mode', 'arnes' );
	
	// Preveri če soCredentialsi konfigurirani
	$credentials_configured = ! empty( $settings['access_key'] ) && ! empty( $settings['secret_key'] ) && ! empty( $settings['org_id'] );
	
	if ( ! $credentials_configured ) {
		?>
		<div class="notice notice-warning" style="margin: 20px 0; padding: 15px;">
			<h3 style="margin-top: 0;">
				<i class="fa-solid fa-circle-info arnes-icon"></i> Konfiguracija potrebna
			</h3>
			<p>Statistika bo na voljo, ko boste konfigurirali povezavo z Arnes S3.</p>
			<p>
				<a href="?page=arnes-s3&tab=povezava" class="button button-primary">
					<i class="fa-solid fa-plug arnes-icon-sm"></i> Pojdi na zavihek Povezava
				</a>
			</p>
		</div>
		<?php
		return; // Zaustavimo izvajanje funkcije
	}
	
	// ======================================
	// 1. SKUPNA STATISTIKA MEDIJSKE KNJIŽNICE
	// ======================================
	
	// Skupno število priponk
	$total_attachments = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'" );
	
	// Priponke v S3 (imajo _arnes_s3_object meta)
	$attachments_in_s3 = $wpdb->get_var(
		"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_arnes_s3_object'"
	);
	
	// Priponke samo lokalno (brez S3)
	$attachments_local_only = $total_attachments - $attachments_in_s3;
	
	// Odstotek v S3
	$percentage_in_s3 = $total_attachments > 0 ? round( ( $attachments_in_s3 / $total_attachments ) * 100, 1 ) : 0;
	
	// ======================================
	// 2. STATISTIKA PO TIPIH DATOTEK
	// ======================================
	
	$mime_stats = $wpdb->get_results(
		"SELECT 
			SUBSTRING_INDEX(post_mime_type, '/', 1) as type_group,
			COUNT(*) as count
		FROM {$wpdb->posts}
		WHERE post_type = 'attachment'
		GROUP BY type_group
		ORDER BY count DESC"
	);
	
	// Pripravi statistiko po tipih z S3 statusom
	$type_breakdown = [];
	foreach ( $mime_stats as $stat ) {
		$type = $stat->type_group;
		$total_count = $stat->count;
		
		// Koliko datotek tega tipa je v S3
		$in_s3_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE p.post_type = 'attachment'
			AND p.post_mime_type LIKE %s
			AND pm.meta_key = '_arnes_s3_object'",
			$type . '/%'
		) );
		
		$type_breakdown[] = [
			'type'        => $type,
			'total'       => $total_count,
			'in_s3'       => $in_s3_count,
			'local_only'  => $total_count - $in_s3_count,
		];
	}
	
	// ======================================
	// 3. SKUPNA VELIKOST DATOTEK
	// ======================================
	
	// Skupna velikost vseh priponk (samo tiste, ki obstajajo lokalno)
	$attachments = $wpdb->get_results(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment'"
	);
	
	$total_local_size = 0;
	$total_s3_size = 0;
	
	foreach ( $attachments as $attachment ) {
		$file_path = get_attached_file( $attachment->ID );
		
		if ( $file_path && file_exists( $file_path ) ) {
			$file_size = filesize( $file_path );
			$total_local_size += $file_size;
			
			// Če je datoteka v S3, prištej tudi k S3 velikosti
			$in_s3 = get_post_meta( $attachment->ID, '_arnes_s3_object', true );
			if ( $in_s3 ) {
				$total_s3_size += $file_size;
			}
		}
	}
	
	// ======================================
	// 4. ZADNJI BULK UPLOAD
	// ======================================
	
	$last_bulk_result = arnes_s3_get_last_bulk_result();
	
	?>
	<div style="display: flex; gap: 30px;">
		<!-- Leva stran: Statistika (60%) -->
		<div style="flex: 0 0 58%;">
			
			<!-- SEKCIJA 1: Pregled -->
			<div class="postbox">
				<div class="inside" style="padding: 20px;">
					<h2 style="margin-top: 0;">
						<i class="fa-solid fa-chart-pie arnes-icon"></i> Pregled medijske knjižnice
					</h2>
					
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row" style="width: 50%;">Skupaj priponk:</th>
							<td><strong style="font-size: 18px;"><?php echo number_format( $total_attachments, 0, ',', '.' ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row">Naloženo v Arnes S3:</th>
							<td>
								<strong style="font-size: 18px; color: #00a32a;"><?php echo number_format( $attachments_in_s3, 0, ',', '.' ); ?></strong>
								<span style="color: #646970; margin-left: 10px;">(<?php echo $percentage_in_s3; ?>%)</span>
							</td>
						</tr>
						<tr>
							<th scope="row">Samo lokalno:</th>
							<td>
								<strong style="font-size: 18px; color: #d63638;"><?php echo number_format( $attachments_local_only, 0, ',', '.' ); ?></strong>
							</td>
						</tr>
					</table>
					
					<!-- Progress bar -->
					<div style="margin-top: 20px;">
						<div style="background: #f0f0f1; height: 30px; border-radius: 4px; overflow: hidden;">
							<div style="background: linear-gradient(90deg, #00a32a 0%, #2271b1 100%); height: 100%; width: <?php echo $percentage_in_s3; ?>%; transition: width 0.5s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
								<?php if ( $percentage_in_s3 > 10 ) echo $percentage_in_s3 . '%'; ?>
							</div>
						</div>
						<p class="description" style="margin-top: 8px;">Delež datotek naloženih v Arnes S3</p>
					</div>
				</div>
			</div>
			
			<!-- SEKCIJA 2: Razčlenitev po tipih -->
			<div class="postbox" style="margin-top: 20px;">
				<div class="inside" style="padding: 20px;">
					<h3 style="margin-top: 0;">
						<i class="fa-solid fa-folder arnes-icon"></i> Razčlenitev po tipih datotek
					</h3>
					
					<table class="widefat striped">
						<thead>
							<tr>
								<th>Tip</th>
								<th style="text-align: center;">Skupaj</th>
								<th style="text-align: center;">V S3</th>
								<th style="text-align: center;">Samo lokalno</th>
								<th style="text-align: center;">Pokritost</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $type_breakdown as $type_data ) : 
								$coverage = $type_data['total'] > 0 ? round( ( $type_data['in_s3'] / $type_data['total'] ) * 100, 0 ) : 0;
								
								// Ikone za tipe
								$icon_map = [
									'image'       => '<i class="fa-solid fa-image arnes-icon-sm"></i>',
									'application' => '<i class="fa-solid fa-file-pdf arnes-icon-sm"></i>',
									'video'       => '<i class="fa-solid fa-video arnes-icon-sm"></i>',
									'audio'       => '<i class="fa-solid fa-music arnes-icon-sm"></i>',
									'text'        => '<i class="fa-solid fa-file-lines arnes-icon-sm"></i>',
									'font'        => '<i class="fa-solid fa-font arnes-icon-sm"></i>',
								];
								// Če tip ni v seznamu, uporabi sponko kot privzeto ikono
								$icon = isset( $icon_map[ $type_data['type'] ] ) ? $icon_map[ $type_data['type'] ] : '<i class="fa-solid fa-paperclip arnes-icon-sm"></i>';
								
								// Prevedba tipov
								$type_labels = [
									'image'       => 'Slike',
									'application' => 'Dokumenti',
									'video'       => 'Video',
									'audio'       => 'Zvok',
									'text'        => 'Besedilo',
									'font'        => 'Fonti',
								];
								// Če tip ni v seznamu, uporabi "Ostalo" kot privzeto oznako
								$type_label = isset( $type_labels[ $type_data['type'] ] ) ? $type_labels[ $type_data['type'] ] : 'Ostalo';
							?>
							<tr>
								<td><strong><?php echo $icon; ?> <?php echo esc_html( $type_label ); ?></strong></td>
								<td style="text-align: center;"><?php echo number_format( $type_data['total'], 0, ',', '.' ); ?></td>
								<td style="text-align: center; color: #00a32a;"><strong><?php echo number_format( $type_data['in_s3'], 0, ',', '.' ); ?></strong></td>
								<td style="text-align: center; color: #d63638;"><?php echo number_format( $type_data['local_only'], 0, ',', '.' ); ?></td>
								<td style="text-align: center;">
									<div style="display: inline-flex; align-items: center; gap: 8px;">
										<div style="background: #f0f0f1; width: 80px; height: 20px; border-radius: 3px; overflow: hidden;">
											<div style="background: <?php echo $coverage >= 80 ? '#00a32a' : ( $coverage >= 50 ? '#f0b849' : '#d63638' ); ?>; height: 100%; width: <?php echo $coverage; ?>%;"></div>
										</div>
										<span style="font-size: 13px; font-weight: 600;"><?php echo $coverage; ?>%</span>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			
			<!-- SEKCIJA 3: Velikost shranjenih datotek -->
			<div class="postbox" style="margin-top: 20px;">
				<div class="inside" style="padding: 20px;">
					<h3 style="margin-top: 0;">
						<i class="fa-solid fa-hard-drive arnes-icon"></i> Velikost shranjenih datotek
					</h3>
					
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row" style="width: 50%;">Skupna velikost lokalnih datotek:</th>
							<td><strong style="font-size: 16px;"><?php echo size_format( $total_local_size, 2 ); ?></strong></td>
						</tr>
						<tr>
							<th scope="row">Približna velikost v S3:</th>
							<td><strong style="font-size: 16px; color: #2271b1;"><?php echo size_format( $total_s3_size, 2 ); ?></strong></td>
						</tr>
						<?php if ( ! $settings['keep_local'] && $attachments_in_s3 > 0 ) : ?>
						<tr>
							<th scope="row">Potencialni prihranek prostora:</th>
							<td>
								<strong style="font-size: 16px; color: #00a32a;"><?php echo size_format( $total_s3_size, 2 ); ?></strong>
								<p class="description">Z izbrisom lokalnih kopij datotek, ki so že v S3, lahko prihranite ta prostor.</p>
							</td>
						</tr>
						<?php endif; ?>
					</table>
				</div>
			</div>
			
			<!-- SEKCIJA 4: Zadnje množično nalaganje -->
			<?php if ( $last_bulk_result ) : ?>
			<div class="postbox" style="margin-top: 20px;">
				<div class="inside" style="padding: 20px;">
					<h3 style="margin-top: 0;">
						<i class="fa-solid fa-clock arnes-icon"></i> Zadnje množično nalaganje
					</h3>
					
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row" style="width: 50%;">Datum:</th>
							<td><?php echo date_i18n( 'd.m.Y H:i', strtotime( $last_bulk_result['date'] ) ); ?></td>
						</tr>
						<tr>
							<th scope="row">Skupaj datotek:</th>
							<td><?php echo number_format( $last_bulk_result['total_files'], 0, ',', '.' ); ?></td>
						</tr>
						<tr>
							<th scope="row">Uspešno naloženih:</th>
							<td style="color: #00a32a;"><strong><?php echo number_format( $last_bulk_result['success_count'], 0, ',', '.' ); ?></strong></td>
						</tr>
						<?php if ( $last_bulk_result['error_count'] > 0 ) : ?>
						<tr>
							<th scope="row">Napake:</th>
							<td style="color: #d63638;"><strong><?php echo number_format( $last_bulk_result['error_count'], 0, ',', '.' ); ?></strong></td>
						</tr>
						<?php endif; ?>
						<tr>
							<th scope="row">Čas izvajanja:</th>
							<td><?php echo gmdate( 'H:i:s', $last_bulk_result['duration'] ); ?></td>
						</tr>
					</table>
				</div>
			</div>
			<?php endif; ?>
			
		</div>
		
		<!-- Desna stran: Trenutne nastavitve (40%) -->
		<div style="flex: 0 0 38%; align-self: flex-start; background: #f9f9f9; padding: 20px; padding-bottom: 20px; border: 1px solid #dcdcde; border-radius: 4px;">
			<h3 style="margin-top: 0;">
				<i class="fa-solid fa-gear arnes-icon"></i> Trenutne nastavitve
			</h3>
			
			<h4 style="margin-top: 20px;">Povezava S3</h4>
			<table class="form-table" role="presentation" style="margin-top: 0;">
				<tr>
					<th scope="row" style="padding-left: 0; width: 40%;">Endpoint:</th>
					<td style="padding-left: 0;"><code><?php echo esc_html( $settings['endpoint'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left: 0;">Bucket:</th>
					<td style="padding-left: 0;"><code><?php echo esc_html( $settings['bucket'] ); ?></code></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left: 0;">Mapa:</th>
					<td style="padding-left: 0;"><code><?php echo esc_html( $settings['prefix'] ); ?></code></td>
				</tr>
			</table>
			
			<h4 style="margin-top: 25px;">Način delovanja</h4>
			<ul style="list-style: none; padding: 0; margin: 10px 0;">
				<li style="padding: 8px; background: <?php echo $settings['auto_upload'] ? '#d7f2e2' : '#fff3cd'; ?>; margin-bottom: 8px; border-radius: 4px;">
					<strong>Samodejno nalaganje:</strong>
					<?php if ( $settings['auto_upload'] ) : ?>
						<span style="color: #00a32a;">
							<i class="fa-solid fa-circle-check arnes-icon-success"></i> Vključeno
						</span>
					<?php else : ?>
						<span style="color: #996800;">
							<i class="fa-solid fa-circle-xmark arnes-icon-warning"></i> Izključeno
						</span>
					<?php endif; ?>
				</li>
				<li style="padding: 8px; background: <?php echo $settings['keep_local'] ? '#d7f2e2' : '#ffe5e5'; ?>; margin-bottom: 8px; border-radius: 4px;">
					<strong>Ohrani lokalno:</strong>
					<?php if ( $settings['keep_local'] ) : ?>
						<span style="color: #00a32a;">
							<i class="fa-solid fa-circle-check arnes-icon-success"></i> Vključeno
						</span>
					<?php else : ?>
						<span style="color: #d63638;">
							<i class="fa-solid fa-circle-xmark arnes-icon-error"></i> Izključeno
						</span>
					<?php endif; ?>
				</li>
				<li style="padding: 8px; background: #e5f5fa; border-radius: 4px;">
					<strong>Dostava datotek:</strong>
					<?php if ( $serve_mode === 'cdn' ) : ?>
						<span style="color: #2271b1;">
							<i class="fa-solid fa-network-wired arnes-icon-sm"></i> CDN
						</span><br>
						<small style="color: #646970;"><?php echo esc_html( $settings['cdn_domain'] ); ?></small>
					<?php else : ?>
						<span style="color: #2271b1;">
							<i class="fa-solid fa-cloud arnes-icon-sm"></i> Arnes S3
						</span>
					<?php endif; ?>
				</li>
			</ul>
			
			<h4 style="margin-top: 25px;">Kakovost slik</h4>
			<table class="form-table" role="presentation" style="margin-top: 0;">
				<tr>
					<th scope="row" style="padding-left: 0; width: 40%;">JPEG:</th>
					<td style="padding-left: 0;"><strong><?php echo $settings['jpeg_quality']; ?>%</strong></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left: 0;">WebP:</th>
					<td style="padding-left: 0;"><strong><?php echo $settings['webp_quality']; ?>%</strong></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left: 0;">AVIF:</th>
					<td style="padding-left: 0;"><strong><?php echo $settings['avif_quality']; ?>%</strong></td>
				</tr>
				<tr>
					<th scope="row" style="padding-left: 0;">Prioriteta:</th>
					<td style="padding-left: 0;">
						<?php if ( $settings['format_priority'] === 'avif_first' ) : ?>
							<span style="color: #2271b1;">AVIF → WebP</span>
						<?php else : ?>
							<span style="color: #2271b1;">WebP → AVIF</span>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			
			<div class="notice notice-info inline" style="margin: 25px 0 0 0;">
				<p>
					<i class="fa-solid fa-lightbulb arnes-icon-sm"></i>
					<strong>Namig:</strong> Če želite povečati pokritost S3, uporabite zavihek "Množično nalaganje" za nalaganje obstoječih datotek.
				</p>
			</div>
			
			<?php if ( $attachments_local_only > 0 && $settings['auto_upload'] ) : ?>
			<div class="notice notice-warning inline" style="margin: 15px 0 0 0;">
				<p>
					<i class="fa-solid fa-triangle-exclamation arnes-icon-sm"></i>
					<strong>Pozor:</strong>Imate <?php echo $attachments_local_only; ?> datotek samo lokalno. Te datoteke so bile naložene pred vklopom avtomatskega nalaganja.
				</p>
			</div>
			<?php endif; ?>
			
			<p style="margin-top: 20px; margin-bottom: 0; padding-top: 15px; border-top: 1px solid #dcdcde; color: #646970; font-size: 13px;">
				<strong>Različica:</strong> Arnes S3 v<?php echo ARNES_S3_VERSION; ?>
			</p>
		</div>
	</div>
	<?php
}
