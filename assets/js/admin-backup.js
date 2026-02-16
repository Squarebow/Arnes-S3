/**
 * Arnes S3 - Backup & Restore JavaScript
 */

jQuery(document).ready(function($) {
	
	/**
	 * Skeniraj datoteke za backup
	 */
	$('#arnes-s3-backup-scan-btn').on('click', function() {
		const button = $(this);
		const resultsDiv = $('#arnes-s3-backup-scan-results');
		const createBtn = $('#arnes-s3-backup-create-btn');
		
		// Disable button
		button.prop('disabled', true);
		createBtn.prop('disabled', true);
		
		// Prikaži loading indicator
		let html = '<div class="notice notice-info inline" style="padding: 15px;">';
		html += '<p style="margin: 0;"><span class="spinner is-active" style="float: left; margin: 0 10px 0 0;"></span>';
		html += '<strong>Preverjam medijsko knjižnico ...</strong></p>';
		html += '</div>';
		resultsDiv.html(html).slideDown();
		
		// Pridobi opcije
		const source = $('input[name="backup_source"]:checked').val();
		const includeOptimized = $('#backup_include_optimized').is(':checked');
		const fileTypes = [];
		$('.backup-file-type:checked').each(function() {
			fileTypes.push($(this).val());
		});
		
		// AJAX request
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_backup_scan',
				nonce: arnesS3Backup.nonce,
				source: source,
				include_optimized: includeOptimized,
				file_types: fileTypes
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					
					// Prikaz rezultatov
					let html = '<div class="notice notice-success inline" style="padding: 15px;">';
					html += '<h4 style="margin-top: 0;">Rezultati preverjanja:</h4>';
					html += '<table class="widefat" style="background: white;">';
					html += '<tr><td><strong>Medijske datoteke:</strong></td><td>' + number_format(data.attachments) + '</td></tr>';
					html += '<tr><td><strong>Število datotek:</strong></td><td>' + number_format(data.count) + '</td></tr>';
					html += '<tr><td><strong>Skupna velikost:</strong></td><td>' + formatBytes(data.total_size) + '</td></tr>';
					html += '</table>';
					html += '</div>';
					
					resultsDiv.html(html).slideDown();
					
					// Omogoči gumb za create backup
					if (data.count > 0) {
						createBtn.prop('disabled', false);
					}
				} else {
					showError('Napaka pri skeniranju: ' + response.data.message);
				}
			},
			error: function() {
				showError('Napaka pri komunikaciji s strežnikom.');
			},
			complete: function() {
				button.prop('disabled', false);
			}
		});
	});
	
	/**
	 * Ustvari backup ZIP
	 */
	$('#arnes-s3-backup-create-btn').on('click', function() {
		const button = $(this);
		const progressDiv = $('#arnes-s3-backup-progress');
		const progressBar = $('#arnes-s3-backup-progress-bar');
		const statusText = $('#arnes-s3-backup-status');
		
		// Potrdi akcijo
		if (!confirm('Ali res želite ustvariti arhiv varnostne kopije? To lahko traja nekaj minut.')) {
			return;
		}
		
		// Disable button
		button.prop('disabled', true);
		$('#arnes-s3-backup-scan-btn').prop('disabled', true);
		
		// Prikaži progress
		progressDiv.slideDown();
		progressBar.css('width', '10%');
		statusText.text('Ustvarjam backup ZIP arhiv ...');
		
		// Pridobi opcije
		const source = $('input[name="backup_source"]:checked').val();
		const includeOptimized = $('#backup_include_optimized').is(':checked');
		const fileTypes = [];
		$('.backup-file-type:checked').each(function() {
			fileTypes.push($(this).val());
		});
		
		// AJAX request
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_backup_create',
				nonce: arnesS3Backup.nonce,
				source: source,
				include_optimized: includeOptimized,
				file_types: fileTypes
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					
					// Posodobi progress na 100%
					progressBar.css('width', '100%');
					
					// Prikaz rezultata
					let html = '<div class="notice notice-success inline" style="padding: 15px;">';
					html += '<h4 style="margin-top: 0;">Arhiv je bil uspešno ustvarjen</h4>';
					html += '<table class="widefat" style="background: white;">';
					html += '<tr><td><strong>Ime datoteke:</strong></td><td>' + data.filename + '</td></tr>';
					html += '<tr><td><strong>Velikost ZIP:</strong></td><td>' + formatBytes(data.size) + '</td></tr>';
					html += '<tr><td><strong>Dodanih datotek:</strong></td><td>' + number_format(data.added) + '</td></tr>';
					html += '</table>';
					html += '<p style="margin-top: 15px;"><a href="' + data.zip_url + '" class="button button-primary button-large" target="_blank">Prenesi arhiv</a></p>';
					
					if (data.errors && data.errors.length > 0) {
						html += '<p style="margin-top: 15px;"><strong>Opozorila:</strong></p><ul>';
						data.errors.forEach(function(error) {
							html += '<li>' + error + '</li>';
						});
						html += '</ul>';
					}
					
					html += '</div>';
					
					statusText.html(html);
					
					// Reload backup list
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					showError('Napaka pri ustvarjanju arhiva: ' + response.data.message);
					progressDiv.slideUp();
				}
			},
			error: function() {
				showError('Napaka pri komunikaciji s strežnikom.');
				progressDiv.slideUp();
			},
			complete: function() {
				button.prop('disabled', false);
				$('#arnes-s3-backup-scan-btn').prop('disabled', false);
			}
		});
	});
	
	/**
	 * Izbriši backup
	 */
	$(document).on('click', '.arnes-s3-backup-delete', function() {
		const button = $(this);
		const filename = button.data('filename');
		const row = button.closest('tr');
		
		// Potrdi brisanje
		if (!confirm('Ali res želite izbrisati arhiv "' + filename + '"?')) {
			return;
		}
		
		// Disable button
		button.prop('disabled', true).text('Brišem ...');
		
		// AJAX request
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_backup_delete',
				nonce: arnesS3Backup.nonce,
				filename: filename
			},
			success: function(response) {
				if (response.success) {
					// Odstrani vrstico iz tabele
					row.fadeOut(300, function() {
						$(this).remove();
						
						// Preveri če je tabela prazna
						if ($('#arnes-s3-backup-list tr').length === 0) {
							location.reload();
						}
					});
				} else {
					alert('Napaka: ' + response.data.message);
					button.prop('disabled', false).text('Izbriši');
				}
			},
			error: function() {
				alert('Napaka pri komunikaciji s strežnikom.');
				button.prop('disabled', false).text('Izbriši');
			}
		});
	});
	
	/**
	 * RESTORE FUNKCIONALNOST
	 */
	
	let restoreFiles = [];
	
	/**
	 * Skeniraj S3 za restore
	 */
	$('#arnes-s3-restore-scan-btn').on('click', function() {
		const button = $(this);
		const resultsDiv = $('#arnes-s3-restore-scan-results');
		const startBtn = $('#arnes-s3-restore-start-btn');
		
		// Disable button
		button.prop('disabled', true);
		resultsDiv.hide();
		startBtn.prop('disabled', true);
		
		// Prikaži loading indicator
		let html = '<div class="notice notice-info inline" style="padding: 15px;">';
		html += '<p style="margin: 0;"><span class="spinner is-active" style="float: left; margin: 0 10px 0 0;"></span>';
		html += '<strong>Preverjam Arnes S3 bucket ...</strong><br>';
		html += '<span style="margin-left: 32px; color: #646970;">To lahko traja več minut.</span></p>';
		html += '</div>';
		resultsDiv.html(html).slideDown();
		
		// Pridobi opcije
		const mode = $('input[name="restore_mode"]:checked').val();
		const fileTypes = [];
		$('.restore-file-type:checked').each(function() {
			fileTypes.push($(this).val());
		});
		
		// AJAX request
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_restore_scan',
				nonce: arnesS3Backup.nonce,
				mode: mode,
				file_types: fileTypes
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					restoreFiles = data.files;
					
					// Prikaz rezultatov
					let html = '<div class="notice notice-success inline" style="padding: 15px;">';
					html += '<h4 style="margin-top: 0;">Arnes S3 datoteke za obnovitev:</h4>';
					html += '<table class="widefat" style="background: white;">';
					html += '<tr><td><strong>Število datotek:</strong></td><td>' + number_format(data.count) + '</td></tr>';
					html += '<tr><td><strong>Skupna velikost:</strong></td><td>' + formatBytes(data.total_size) + '</td></tr>';
					html += '</table>';
					html += '</div>';
					
					resultsDiv.html(html).slideDown();
					
					// Omogoči gumb za restore
					if (data.count > 0) {
						startBtn.prop('disabled', false);
					} else {
						showRestoreError('Ni datotek za obnovitev. Vse datoteke že obstajajo lokalno ali pa v Arnes S3 oblaku ni ustreznih datotek.');
					}
				} else {
					showRestoreError('Napaka pri preverjanju: ' + response.data.message);
				}
			},
			error: function(xhr, status, error) {
				showRestoreError('Napaka pri komunikaciji s strežnikom: ' + error);
			},
			complete: function() {
				button.prop('disabled', false);
			}
		});
	});
	
	/**
	 * Začni restore proces
	 */
	$('#arnes-s3-restore-start-btn').on('click', function() {
		const button = $(this);
		const progressDiv = $('#arnes-s3-restore-progress');
		const progressBar = $('#arnes-s3-restore-progress-bar');
		const progressText = $('#arnes-s3-restore-progress-text');
		const currentFile = $('#arnes-s3-restore-current-file');
		
		// Potrdi akcijo
		if (!confirm('Ali res želite obnoviti datoteke iz Arnes S3 shrambe? To lahko traja nekaj minut.')) {
			return;
		}
		
		// Disable buttons
		button.prop('disabled', true);
		$('#arnes-s3-restore-scan-btn').prop('disabled', true);
		
		// Prikaži progress
		progressDiv.slideDown();
		progressBar.css('width', '0%');
		
		// Process files v batch-ih
		const batchSize = 5;
		let processed = 0;
		let totalFiles = restoreFiles.length;
		
		function processBatch(startIndex) {
			if (startIndex >= totalFiles) {
				// Končano
				progressBar.css('width', '100%');
				progressText.text(totalFiles + ' / ' + totalFiles);
				currentFile.text('Obnovitev je končana!');
				
				setTimeout(function() {
					alert('Obnovitev je bila uspešno končana! Obnovljenih datotek: ' + totalFiles);
					location.reload();
				}, 1000);
				return;
			}
			
			const batch = restoreFiles.slice(startIndex, startIndex + batchSize);
			
			// Update UI
			const progress = Math.round((startIndex / totalFiles) * 100);
			progressBar.css('width', progress + '%');
			progressText.text(startIndex + ' / ' + totalFiles);
			if (batch.length > 0) {
				currentFile.text(batch[0].local_path.split('/').pop());
			}
			
			// AJAX request za batch
			$.ajax({
				url: arnesS3Backup.ajaxUrl,
				type: 'POST',
				data: {
					action: 'arnes_s3_restore_process',
					nonce: arnesS3Backup.nonce,
					files: JSON.stringify(batch)
				},
				success: function(response) {
					if (response.success) {
						processed += response.data.processed;
						
						// Proces next batch
						processBatch(startIndex + batchSize);
					} else {
						alert('Napaka pri obnovitvi: ' + response.data.message);
						button.prop('disabled', false);
						$('#arnes-s3-restore-scan-btn').prop('disabled', false);
					}
				},
				error: function() {
					alert('Napaka pri komunikaciji s strežnikom.');
					button.prop('disabled', false);
					$('#arnes-s3-restore-scan-btn').prop('disabled', false);
				}
			});
		}
		
		// Začni s prvim batch-em
		processBatch(0);
	});
	
	function showRestoreError(message) {
		const html = '<div class="notice notice-error inline" style="padding: 15px;"><p>' + message + '</p></div>';
		$('#arnes-s3-restore-scan-results').html(html).slideDown();
	}
	
	/**
	 * ============================================================================
	 * SYNC & MAINTENANCE FUNKCIONALNOST
	 * ============================================================================
	 */
	
	let syncItems = [];
	let localDeleteFiles = [];
	
	/**
	 * Re-sync S3 Metadata - Scan
	 */
	$('#arnes-s3-sync-scan-btn').on('click', function() {
		const button = $(this);
		const resultsDiv = $('#arnes-s3-sync-results');
		const fixBtn = $('#arnes-s3-sync-fix-btn');
		
		button.prop('disabled', true);
		fixBtn.prop('disabled', true);
		
		// Loading indicator
		let html = '<div class="notice notice-info inline" style="padding: 15px;">';
		html += '<p style="margin: 0;"><span class="spinner is-active" style="float: left; margin: 0 10px 0 0;"></span>';
		html += '<strong>Preverjam in primerjam metapodatke ...</strong></p>';
		html += '</div>';
		resultsDiv.html(html).slideDown();
		
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_sync_scan',
				nonce: arnesS3Backup.nonce
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					syncItems = data.missing_meta;
					
					if (data.count > 0) {
						html = '<div class="notice notice-warning inline" style="padding: 15px;">';
						html += '<h4 style="margin-top: 0;">Najdenih medijskih datotek brez metapodatkov:</h4>';
						html += '<p><strong>' + number_format(data.count) + '</strong> medijskih datotek je v S3 oblaku, vendar nimajo <code>_arnes_s3_object</code> post meta atributa.</p>';
						html += '</div>';
						
						resultsDiv.html(html).slideDown();
						fixBtn.prop('disabled', false);
					} else {
						html = '<div class="notice notice-success inline" style="padding: 15px;">';
						html += '<p>Vse medijske datoteke imajo pravilne metapodatke. Popravilo ni potrebno.</p>';
						html += '</div>';
						resultsDiv.html(html).slideDown();
					}
				} else {
					showSyncError('Napaka pri preverjanju: ' + response.data.message);
				}
			},
			error: function() {
				showSyncError('Napaka pri komunikaciji s strežnikom.');
			},
			complete: function() {
				button.prop('disabled', false);
			}
		});
	});
	
	/**
	 * Re-sync S3 Metadata - Fix
	 */
	$('#arnes-s3-sync-fix-btn').on('click', function() {
		const button = $(this);
		const resultsDiv = $('#arnes-s3-sync-results');
		
		if (!confirm('Ali res želite popraviti metapodatke za ' + syncItems.length + ' medijskih datotek?')) {
			return;
		}
		
		button.prop('disabled', true);
		$('#arnes-s3-sync-scan-btn').prop('disabled', true);
		
		// Loading
		let html = '<div class="notice notice-info inline" style="padding: 15px;">';
		html += '<p style="margin: 0;"><span class="spinner is-active" style="float: left; margin: 0 10px 0 0;"></span>';
		html += '<strong>Popravljam metapodatke ...</strong></p>';
		html += '</div>';
		resultsDiv.html(html).slideDown();
		
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_sync_fix',
				nonce: arnesS3Backup.nonce,
				items: JSON.stringify(syncItems)
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					
					html = '<div class="notice notice-success inline" style="padding: 15px;">';
					html += '<h4 style="margin-top: 0;">Metadata uspešno popravljeni</h4>';
					html += '<p><strong>' + number_format(data.fixed) + '</strong> medijskih datotek ima zdaj pravilne metapodatke.</p>';
					html += '</div>';
					
					resultsDiv.html(html).slideDown();
					
					// Reset
					syncItems = [];
					button.prop('disabled', true);
				} else {
					showSyncError('Napaka pri popravilu: ' + response.data.message);
				}
			},
			error: function() {
				showSyncError('Napaka pri komunikaciji s strežnikom.');
			},
			complete: function() {
				$('#arnes-s3-sync-scan-btn').prop('disabled', false);
			}
		});
	});
	
	/**
	 * Bulk Delete lokalnih kopij - Scan
	 */
	$('#arnes-s3-local-delete-scan-btn').on('click', function() {
		const button = $(this);
		const resultsDiv = $('#arnes-s3-local-delete-results');
		const deleteBtn = $('#arnes-s3-local-delete-btn');
		
		button.prop('disabled', true);
		deleteBtn.prop('disabled', true);
		
		// Loading
		let html = '<div class="notice notice-info inline" style="padding: 15px;">';
		html += '<p style="margin: 0;"><span class="spinner is-active" style="float: left; margin: 0 10px 0 0;"></span>';
		html += '<strong>Preverjam lokalne datoteke in primerjam z S3 ...</strong><br>';
		html += '<span style="margin-left: 32px; color: #646970;">To lahko traja več minut.</span></p>';
		html += '</div>';
		resultsDiv.html(html).slideDown();
		
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_local_delete_scan',
				nonce: arnesS3Backup.nonce
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					localDeleteFiles = data.files;
					
					if (data.count > 0) {
						html = '<div class="notice notice-warning inline" style="padding: 15px;">';
						html += '<h4 style="margin-top: 0;">Datoteke, ki jih lahko izbrišete lokalno:</h4>';
						html += '<table class="widefat" style="background: white;">';
						html += '<tr><td><strong>Število datotek:</strong></td><td>' + number_format(data.count) + '</td></tr>';
						html += '<tr><td><strong>Prihranjen prostor:</strong></td><td>' + formatBytes(data.total_size) + '</td></tr>';
						html += '</table>';
						html += '<p style="margin-top: 10px;"><strong>Opozorilo:</strong> Te datoteke bodo izbrisane LOKALNO, torej z vašega strežnika, ostanejo pa v Arnes S3 oblaku. Priporočamo varnostno kopijo podatkovne baze in datotek pred nadaljevanjem.</p>';
						html += '</div>';
						
						resultsDiv.html(html).slideDown();
						deleteBtn.prop('disabled', false);
					} else {
						html = '<div class="notice notice-info inline" style="padding: 15px;">';
						html += '<p>Ni lokalnih datotek za brisanje. Vse datoteke so samo v Arnes S3 oblaku.</p>';
						html += '</div>';
						resultsDiv.html(html).slideDown();
					}
				} else {
					showLocalDeleteError('Napaka pri preverjanju: ' + response.data.message);
				}
			},
			error: function() {
				showLocalDeleteError('Napaka pri komunikaciji s strežnikom.');
			},
			complete: function() {
				button.prop('disabled', false);
			}
		});
	});
	
	/**
	 * Bulk Delete lokalnih kopij - Delete
	 */
	$('#arnes-s3-local-delete-btn').on('click', function() {
		const button = $(this);
		const resultsDiv = $('#arnes-s3-local-delete-results');
		
		if (!confirm('Ali res želite IZBRISATI ' + localDeleteFiles.length + ' lokalnih datotek? Datoteke bodo ostale v Arnes S3 oblaku.')) {
			return;
		}
		
		button.prop('disabled', true);
		$('#arnes-s3-local-delete-scan-btn').prop('disabled', true);
		
		// Loading
		let html = '<div class="notice notice-info inline" style="padding: 15px;">';
		html += '<p style="margin: 0;"><span class="spinner is-active" style="float: left; margin: 0 10px 0 0;"></span>';
		html += '<strong>Brišem lokalne datoteke ...</strong></p>';
		html += '</div>';
		resultsDiv.html(html).slideDown();
		
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_local_delete_process',
				nonce: arnesS3Backup.nonce,
				files: JSON.stringify(localDeleteFiles)
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					
					html = '<div class="notice notice-success inline" style="padding: 15px;">';
					html += '<h4 style="margin-top: 0;">Lokalne datoteke uspešno izbrisane</h4>';
					html += '<table class="widefat" style="background: white;">';
					html += '<tr><td><strong>Izbrisanih datotek:</strong></td><td>' + number_format(data.deleted) + '</td></tr>';
					html += '<tr><td><strong>Prihranjen prostor:</strong></td><td>' + formatBytes(data.freed_space) + '</td></tr>';
					html += '</table>';
					html += '</div>';
					
					resultsDiv.html(html).slideDown();
					
					// Reset
					localDeleteFiles = [];
					button.prop('disabled', true);
				} else {
					showLocalDeleteError('Napaka pri brisanju: ' + response.data.message);
				}
			},
			error: function() {
				showLocalDeleteError('Napaka pri komunikaciji s strežnikom.');
			},
			complete: function() {
				$('#arnes-s3-local-delete-scan-btn').prop('disabled', false);
			}
		});
	});
	
	/**
	 * Preverjanje integritete
	 */
	$('#arnes-s3-integrity-check-btn').on('click', function() {
		const button = $(this);
		const resultsDiv = $('#arnes-s3-integrity-results');
		
		button.prop('disabled', true);
		
		// Loading
		let html = '<div class="notice notice-info inline" style="padding: 15px;">';
		html += '<p style="margin: 0;"><span class="spinner is-active" style="float: left; margin: 0 10px 0 0;"></span>';
		html += '<strong>Preverjam integriteteto ...</strong><br>';
		html += '<span style="margin-left: 32px; color: #646970;">Primerjam lokalne datoteke z datotekami v Arnes S3 oblaku. To lahko traja več minut.</span></p>';
		html += '</div>';
		resultsDiv.html(html).slideDown();
		
		$.ajax({
			url: arnesS3Backup.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_integrity_check',
				nonce: arnesS3Backup.nonce
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					
					// Build report
					html = '<div class="notice notice-success inline" style="padding: 15px;">';
					html += '<h4 style="margin-top: 0;">Poročilo o integriteti</h4>';
					html += '<table class="widefat" style="background: white;">';
					html += '<tr><td><strong>Skupaj medijskih datotek:</strong></td><td>' + number_format(data.total) + '</td></tr>';
					html += '<tr style="color: #00a32a;"><td><strong>Ujemanje:</strong></td><td>' + number_format(data.ok) + '</td></tr>';
					html += '<tr><td><strong>Brez S3 meta:</strong></td><td>' + number_format(data.no_meta) + '</td></tr>';
					html += '<tr style="color: #d63638;"><td><strong>Manjka v S3:</strong></td><td>' + number_format(data.missing_s3.length) + '</td></tr>';
					html += '<tr style="color: #d63638;"><td><strong>Manjka lokalno:</strong></td><td>' + number_format(data.missing_local.length) + '</td></tr>';
					html += '<tr style="color: #dba617;"><td><strong>Neujemanje velikosti:</strong></td><td>' + number_format(data.size_mismatch.length) + '</td></tr>';
					html += '</table>';
					html += '</div>';
					
					resultsDiv.html(html).slideDown();
				} else {
					showIntegrityError('Napaka pri preverjanju: ' + response.data.message);
				}
			},
			error: function() {
				showIntegrityError('Napaka pri komunikaciji s strežnikom.');
			},
			complete: function() {
				button.prop('disabled', false);
			}
		});
	});
	
	function showSyncError(message) {
		const html = '<div class="notice notice-error inline" style="padding: 15px;"><p>' + message + '</p></div>';
		$('#arnes-s3-sync-results').html(html).slideDown();
	}
	
	function showLocalDeleteError(message) {
		const html = '<div class="notice notice-error inline" style="padding: 15px;"><p>' + message + '</p></div>';
		$('#arnes-s3-local-delete-results').html(html).slideDown();
	}
	
	function showIntegrityError(message) {
		const html = '<div class="notice notice-error inline" style="padding: 15px;"><p>' + message + '</p></div>';
		$('#arnes-s3-integrity-results').html(html).slideDown();
	}
	
	/**
	 * Helper functions
	 */
	
	function formatBytes(bytes, decimals = 2) {
		if (bytes === 0) return '0 Bytes';
		
		const k = 1024;
		const dm = decimals < 0 ? 0 : decimals;
		const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		
		return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
	}
	
	function number_format(number) {
		return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
	}
	
	function showError(message) {
		const html = '<div class="notice notice-error inline" style="padding: 15px;"><p>' + message + '</p></div>';
		$('#arnes-s3-backup-scan-results').html(html).slideDown();
	}
});
