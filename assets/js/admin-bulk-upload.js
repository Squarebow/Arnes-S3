/**
 * Bulk Upload JavaScript za Arnes S3
 * 
 * Funkcionalnosti:
 * - Skeniranje Media Library
 * - AJAX batch processing (10 datotek na batch)
 * - Real-time progress bar
 * - Pause/Resume/Cancel funkcionalnost
 * - Dry-run mode
 * - Resume state management (WP transients)
 * 
 * @package Arnes_S3
 */

(function($) {
	'use strict';
	
	let uploadState = {
		files: [],
		currentIndex: 0,
		totalFiles: 0,
		successCount: 0,
		errorCount: 0,
		isRunning: false,
		isPaused: false,
		isDryRun: false,
		startTime: null,
	};
	
	const BATCH_SIZE = 10; // Število datotek na batch
	const THROTTLE_MS = 100; // Zakasnitev med batchi
	
	/**
	 * Inicializacija
	 */
	$(document).ready(function() {
		
		// Preveri če obstaja shranjeno stanje
		checkForSavedState();
		
		// Event handlers
		$('#arnes-s3-scan-btn').on('click', handleScan);
		$('#arnes-s3-start-upload-btn').on('click', handleStartUpload);
		$('#arnes-s3-pause-btn').on('click', handlePause);
		$('#arnes-s3-resume-btn').on('click', handleResume);
		$('#arnes-s3-cancel-btn').on('click', handleCancel);
		
		// Dynamic CDN domain visibility (že obstaja v admin-page.php)
	});
	
	/**
	 * Preveri če obstaja shranjeno stanje iz prejšnjega uploada
	 */
	function checkForSavedState() {
		$.ajax({
			url: arnesS3Bulk.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_get_state',
				nonce: arnesS3Bulk.nonce,
			},
			success: function(response) {
				if (response.success && response.data.state) {
					showResumeNotice(response.data.state);
				}
			}
		});
	}
	
	/**
	 * Prikaži resume notice
	 */
	function showResumeNotice(state) {
		const notice = `
			<div class="notice notice-info is-dismissible" id="arnes-s3-resume-notice">
				<p>
					<strong>Najdeno nedokončano nalaganje:</strong> 
					${state.successCount} od ${state.totalFiles} datotek naloženih. 
					<button type="button" class="button button-primary" id="arnes-s3-resume-previous">
						Nadaljuj
					</button>
					<button type="button" class="button" id="arnes-s3-clear-previous">
						Začni na novo
					</button>
				</p>
			</div>
		`;
		
		$('#arnes-s3-bulk-container').prepend(notice);
		
		$('#arnes-s3-resume-previous').on('click', function() {
			resumePreviousUpload(state);
		});
		
		$('#arnes-s3-clear-previous').on('click', function() {
			clearSavedState();
			$('#arnes-s3-resume-notice').remove();
		});
	}
	
	/**
	 * Nadaljuj prejšnji upload
	 */
	function resumePreviousUpload(state) {
		uploadState = state;
		uploadState.isRunning = true;
		uploadState.isPaused = false;
		
		$('#arnes-s3-resume-notice').remove();
		showUploadControls();
		updateProgress();
		
		processNextBatch();
	}
	
	/**
	 * Izbriši shranjeno stanje
	 */
	function clearSavedState() {
		$.ajax({
			url: arnesS3Bulk.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_delete_state',
				nonce: arnesS3Bulk.nonce,
			}
		});
	}
	
	/**
	 * Handler: Skeniraj Media Library
	 */
	function handleScan(e) {
		e.preventDefault();
		
		const $btn = $(this);
		const $results = $('#arnes-s3-scan-results');
		
		// Disable button
		$btn.prop('disabled', true).text('Preverjam ...');
		$results.html('<p>Preverjanje medijske knjižnice ...</p>');
		
		// Pridobi filtre
		const filters = {
			date_from: $('#arnes-s3-filter-date-from').val(),
			date_to: $('#arnes-s3-filter-date-to').val(),
			mime_type: $('#arnes-s3-filter-mime-type').val(),
			min_size: $('#arnes-s3-filter-min-size').val(),
			max_size: $('#arnes-s3-filter-max-size').val(),
			only_missing: $('#arnes-s3-only-missing').is(':checked'),
		};
		
		$.ajax({
			url: arnesS3Bulk.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_scan_library',
				nonce: arnesS3Bulk.nonce,
				...filters,
			},
			success: function(response) {
				$btn.prop('disabled', false).text('Preveri');
				
				if (response.success) {
					displayScanResults(response.data);
				} else {
					$results.html(`<div class="notice notice-error"><p>Napaka: ${response.data.message}</p></div>`);
				}
			},
			error: function() {
				$btn.prop('disabled', false).text('Preveri');
				$results.html('<div class="notice notice-error"><p>Prišlo je do napake pri preverjanju.</p></div>');
			}
		});
	}
	
	/**
	 * Prikaži rezultate skeniranja
	 */
	function displayScanResults(data) {
		const $results = $('#arnes-s3-scan-results');
		
		// Shrani files v uploadState
		uploadState.files = data.files;
		uploadState.totalFiles = data.total_files;
		uploadState.currentIndex = 0;
		uploadState.successCount = 0;
		uploadState.errorCount = 0;
		
		if (data.total_files === 0) {
			$results.html(`
				<div class="notice notice-warning">
					<p>Ni datotek za nalaganje glede na izbrane filtre.</p>
				</div>
			`);
			$('#arnes-s3-start-upload-btn').prop('disabled', true);
			return;
		}
		
		// Prikaži dry-run tabelo
		const isDryRun = $('#arnes-s3-dry-run').is(':checked');
		
		if (isDryRun) {
			displayDryRunTable(data.files);
		} else {
			$results.html(`
				<div class="notice notice-success">
					<p>
						<strong>Najdeno:</strong> ${data.total_files} datotek 
						(${data.total_size_formatted})
					</p>
				</div>
			`);
		}
		
		// Enable Start Upload button
		$('#arnes-s3-start-upload-btn').prop('disabled', false);
	}
	
	/**
	 * Prikaži dry-run tabelo z listo datotek
	 */
	function displayDryRunTable(files) {
		let tableHtml = `
			<div class="notice notice-info">
				<p><strong>DRY RUN MODE:</strong> Predogled datotek (ne bo naloženo)</p>
			</div>
			<table class="widefat" style="margin-top: 15px;">
				<thead>
					<tr>
						<th>Datoteka</th>
						<th>Vrsta</th>
						<th>Velikost</th>
						<th>Datum</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
		`;
		
		// Omeji prikaz na prvih 50 datotek (če jih je več, prikaži samo count)
		const displayFiles = files.slice(0, 50);
		
		displayFiles.forEach(file => {
			const statusIcon = file.in_s3 ? '🔄' : '📤';
			const statusText = file.in_s3 ? 'Posodobi' : 'Nov upload';
			
			tableHtml += `
				<tr>
					<td>${file.filename}</td>
					<td>${file.mime_type}</td>
					<td>${formatBytes(file.size)}</td>
					<td>${formatDate(file.date)}</td>
					<td>${statusIcon} ${statusText}</td>
				</tr>
			`;
		});
		
		tableHtml += '</tbody></table>';
		
		if (files.length > 50) {
			tableHtml += `<p style="margin-top: 10px;"><em>Prikazanih je prvih 50 od ${files.length} datotek.</em></p>`;
		}
		
		$('#arnes-s3-scan-results').html(tableHtml);
	}
	
	/**
	 * Handler: Začni upload
	 */
	function handleStartUpload(e) {
		e.preventDefault();
		
		if (uploadState.files.length === 0) {
			alert('Najprej skenirajte/preverite medijsko knjižnico.');
			return;
		}
		
		// Preveri ali je dry-run mode
		uploadState.isDryRun = $('#arnes-s3-dry-run').is(':checked');
		uploadState.isRunning = true;
		uploadState.isPaused = false;
		uploadState.startTime = Date.now();
		
		// Skrij scan results, prikaži progress
		$('#arnes-s3-scan-results').hide();
		showUploadControls();
		
		// Prikaži "Preparing..." status
		$('#arnes-s3-current-file').html('<em>Pripravljam nalaganje ...</em>');
		$('#arnes-s3-status-message').html('Začenjam nalaganje ...').show();
		
		// Dodaj pulse animacijo na progress bar (vizualna povratna informacija)
		$('#arnes-s3-progress-bar').addClass('arnes-s3-pulsing');
		
		// Začni processing po kratki zakasnitve (da se prikaže "Preparing" sporočilo)
		setTimeout(processNextBatch, 300);
	}
	
	/**
	 * Prikaži upload kontrole in progress bar
	 */
	function showUploadControls() {
		$('#arnes-s3-upload-controls').show();
		$('#arnes-s3-start-upload-btn').hide();
	}
	
	/**
	 * Procesiraj naslednji batch datotek
	 */
	function processNextBatch() {
		
		if (!uploadState.isRunning || uploadState.isPaused) {
			return;
		}
		
		// Preveri če smo končali
		if (uploadState.currentIndex >= uploadState.totalFiles) {
			handleComplete();
			return;
		}
		
		// Odstrani pulse animacijo (samo pri prvem batch-u)
		$('#arnes-s3-progress-bar').removeClass('arnes-s3-pulsing');
		
		// Pridobi naslednji batch
		const batchFiles = uploadState.files.slice(
			uploadState.currentIndex,
			uploadState.currentIndex + BATCH_SIZE
		);
		
		const fileIds = batchFiles.map(f => f.id);
		
		// Izračunaj batch številko
		const currentBatch = Math.floor(uploadState.currentIndex / BATCH_SIZE) + 1;
		const totalBatches = Math.ceil(uploadState.totalFiles / BATCH_SIZE);
		
		// Posodobi status sporočilo
		$('#arnes-s3-status-message').html(
			`Procesiram serijo <strong>${currentBatch} od ${totalBatches}</strong> (nalagam ${batchFiles.length} datotek)...`
		).show();
		
		// Posodobi current file
		$('#arnes-s3-current-file').text(batchFiles[0].filename);
		
		// AJAX request za batch
		$.ajax({
			url: arnesS3Bulk.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_bulk_upload_batch',
				nonce: arnesS3Bulk.nonce,
				file_ids: fileIds,
				dry_run: uploadState.isDryRun,
			},
			success: function(response) {
				if (response.success) {
					uploadState.successCount += response.data.success_count;
					uploadState.errorCount += response.data.error_count;
					uploadState.currentIndex += batchFiles.length;
					
					updateProgress();
					saveState();
					
					// Throttle: počakaj 100ms pred naslednjo batch
					setTimeout(processNextBatch, THROTTLE_MS);
				} else {
					handleError(response.data.message);
				}
			},
			error: function() {
				handleError('Prišlo je do napake pri nalaganju serije.');
			}
		});
	}
	
	/**
	 * Posodobi progress bar in statistiko
	 */
	function updateProgress() {
		const percentage = Math.round((uploadState.currentIndex / uploadState.totalFiles) * 100);
		
		$('#arnes-s3-progress-bar').css('width', percentage + '%');
		$('#arnes-s3-progress-percentage').text(percentage + '%');
		$('#arnes-s3-progress-files').text(`${uploadState.currentIndex} / ${uploadState.totalFiles}`);
		$('#arnes-s3-success-count').text(uploadState.successCount);
		$('#arnes-s3-error-count').text(uploadState.errorCount);
		
		// Izračunaj elapsed in estimated time
		if (uploadState.startTime) {
			const elapsedMs = Date.now() - uploadState.startTime;
			const elapsedSec = Math.floor(elapsedMs / 1000);
			
			$('#arnes-s3-elapsed-time').text(formatTime(elapsedSec));
			
			if (uploadState.currentIndex > 0) {
				const avgTimePerFile = elapsedMs / uploadState.currentIndex;
				const remainingFiles = uploadState.totalFiles - uploadState.currentIndex;
				const estimatedMs = avgTimePerFile * remainingFiles;
				const estimatedSec = Math.floor(estimatedMs / 1000);
				
				$('#arnes-s3-estimated-time').text(formatTime(estimatedSec));
			}
		}
	}
	
	/**
	 * Handler: Pause upload
	 */
	function handlePause(e) {
		e.preventDefault();
		uploadState.isPaused = true;
		
		$('#arnes-s3-pause-btn').hide();
		$('#arnes-s3-resume-btn').show();
		
		saveState();
	}
	
	/**
	 * Handler: Resume upload
	 */
	function handleResume(e) {
		e.preventDefault();
		uploadState.isPaused = false;
		
		$('#arnes-s3-resume-btn').hide();
		$('#arnes-s3-pause-btn').show();
		
		processNextBatch();
	}
	
	/**
	 * Handler: Cancel upload
	 */
	function handleCancel(e) {
		e.preventDefault();
		
		if (!confirm('Ste prepričani, da želite preklicati nalaganje?')) {
			return;
		}
		
		uploadState.isRunning = false;
		uploadState.isPaused = false;
		
		clearSavedState();
		resetUI();
	}
	
	/**
	 * Handel upload complete
	 */
	function handleComplete() {
		uploadState.isRunning = false;
		
		// Izračunaj trajanje v sekundah
		const duration = uploadState.startTime ? Math.floor((Date.now() - uploadState.startTime) / 1000) : 0;
		
		// Shrani rezultate zadnjega uploada (samo če ni dry-run)
		if (!uploadState.isDryRun) {
			saveLastBulkResult({
				total_files: uploadState.totalFiles,
				success_count: uploadState.successCount,
				error_count: uploadState.errorCount,
				duration: duration
			});
		}
		
		clearSavedState();
		
		const message = uploadState.isDryRun
			? `<strong>DRY RUN KONČAN:</strong> ${uploadState.totalFiles} datotek bi bilo naloženih.`
			: `<strong>NALAGANJE KONČANO!</strong><br>
			   Uspešno naloženih: <strong>${uploadState.successCount}</strong> datotek<br>
			   ${uploadState.errorCount > 0 ? `Napake: <strong>${uploadState.errorCount}</strong><br>` : ''}
			   Trajanje: <strong>${formatTime(duration)}</strong>`;
		
		$('#arnes-s3-upload-controls').hide();
		$('#arnes-s3-scan-results').html(`
			<div class="notice notice-success">
				<p>${message}</p>
			</div>
		`).show();
		
		// Skrij status sporočilo
		$('#arnes-s3-status-message').hide();
		
		// Reset UI
		$('#arnes-s3-start-upload-btn').show().prop('disabled', true);
		
		// Če ni dry-run, reload stran po 2 sekundah da prikaže novi last upload status
		if (!uploadState.isDryRun) {
			setTimeout(function() {
				location.reload();
			}, 2000);
		}
	}
	
	/**
	 * Handle error
	 */
	function handleError(message) {
		uploadState.isRunning = false;
		alert('Napaka: ' + message);
		resetUI();
	}
	
	/**
	 * Shrani trenutno stanje (za resume)
	 */
	function saveState() {
		$.ajax({
			url: arnesS3Bulk.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_save_state',
				nonce: arnesS3Bulk.nonce,
				state: JSON.stringify(uploadState),
			}
		});
	}
	
	/**
	 * Reset UI na začetno stanje
	 */
	function resetUI() {
		$('#arnes-s3-upload-controls').hide();
		$('#arnes-s3-scan-results').show();
		$('#arnes-s3-start-upload-btn').show();
		$('#arnes-s3-progress-bar').css('width', '0%');
		$('#arnes-s3-progress-percentage').text('0%');
	}
	
	/**
	 * Formatiraj byte v human-readable format
	 */
	function formatBytes(bytes) {
		const units = ['B', 'KB', 'MB', 'GB'];
		let i = 0;
		
		while (bytes > 1024 && i < units.length - 1) {
			bytes /= 1024;
			i++;
		}
		
		return bytes.toFixed(2) + ' ' + units[i];
	}
	
	/**
	 * Formatiraj datum
	 */
	function formatDate(dateString) {
		const date = new Date(dateString);
		return date.toLocaleDateString('sl-SI');
	}
	
	/**
	 * Formatiraj sekunde v MM:SS format
	 */
	function formatTime(seconds) {
		const minutes = Math.floor(seconds / 60);
		const secs = seconds % 60;
		return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
	}
	
	/**
	 * Shrani rezultate zadnjega bulk uploada
	 */
	function saveLastBulkResult(results) {
		$.ajax({
			url: arnesS3Bulk.ajaxUrl,
			type: 'POST',
			data: {
				action: 'arnes_s3_save_bulk_result',
				nonce: arnesS3Bulk.nonce,
				total_files: results.total_files,
				success_count: results.success_count,
				error_count: results.error_count,
				duration: results.duration
			}
		});
	}
	
	// CSS za pulsing animacijo (dodaj v DOM)
	$('<style>')
		.text(`
			@keyframes arnes-s3-pulse {
				0%, 100% { opacity: 0.6; }
				50% { opacity: 1; }
			}
			
			#arnes-s3-progress-bar.arnes-s3-pulsing {
				animation: arnes-s3-pulse 1.5s ease-in-out infinite;
				width: 5% !important;
				transition: none;
			}
			
			#arnes-s3-status-message {
				background: #f0f6fc;
				border-left: 4px solid #2271b1;
				padding: 12px 15px;
				margin: 15px 0;
				border-radius: 4px;
				display: none;
			}
		`)
		.appendTo('head');
	
})(jQuery);
