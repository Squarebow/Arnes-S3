/**
 * Arnes S3 - Test Connection Button Handler
 * 
 * Handles AJAX test connection for S3 credentials validation.
 * Uses native Fetch API for WordPress AJAX calls.
 * After successful/failed test, reloads page to show updated connection status.
 * 
 * @package Arnes_S3
 */

document.addEventListener('DOMContentLoaded', function () {

	const button = document.getElementById('arnes-s3-test');
	const resultBox = document.getElementById('arnes-s3-test-result');

	if (!button) return;

	button.addEventListener('click', function () {

		resultBox.innerHTML = 'Preverjam povezavo ...';

		const data = new FormData();
		data.append('action', 'arnes_s3_test_connection');
		data.append('nonce', arnesS3.nonce);

		[
			'endpoint',
			'bucket',
			'prefix',
			'access_key',
			'secret_key'
		].forEach(function (id) {
			const el = document.getElementById('arnes_s3_' + id);
			if (el) data.append(id, el.value);
		});

		fetch(arnesS3.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		})
		.then(r => r.json())
		.then(response => {

			if (response.success) {
				resultBox.innerHTML =
					'<div class="notice notice-success"><p>' +
					response.data +
					'</p></div>';
			} else {
				resultBox.innerHTML =
					'<div class="notice notice-error"><p>' +
					response.data +
					'</p></div>';
			}
			
			// Reload page after 1.5 seconds to show updated connection status
			setTimeout(function() {
				location.reload();
			}, 1500);
		})
		.catch(() => {
			resultBox.innerHTML =
				'<div class="notice notice-error"><p>AJAX napaka.</p></div>';
			
			// Reload even on error to ensure status is updated
			setTimeout(function() {
				location.reload();
			}, 1500);
		});
	});
});
