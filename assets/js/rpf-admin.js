/**
 * RePostFlow Admin JavaScript
 *
 * @package RePostFlow
 * @since   1.0.0
 */

(function ($) {
	'use strict';

	var RPF = {

		/**
		 * Initialize all event handlers.
		 */
		init: function () {
			this.bindPanelToggles();
			this.bindTestConnection();
			this.bindShareNow();
			this.bindClearQueue();
			this.bindDeleteLog();
			this.bindToggleEnabled();
		},

		/**
		 * Network settings panel accordion.
		 */
		bindPanelToggles: function () {
			$(document).on('click', '.rpf-network-panel-header', function () {
				var $header = $(this);
				var targetId = $header.data('toggle');
				var $body = $('#' + targetId);

				$header.toggleClass('active');
				$body.slideToggle(200);

				// Close other panels.
				$('.rpf-network-panel-header').not($header).removeClass('active');
				$('.rpf-network-panel-body').not($body).slideUp(200);
			});
		},

		/**
		 * Test network connection via AJAX.
		 */
		bindTestConnection: function () {
			$(document).on('click', '.rpf-test-connection', function () {
				var $btn = $(this);
				var network = $btn.data('network');
				var $result = $('#rpf-test-' + network);

				$btn.prop('disabled', true);
				$result
					.removeClass('rpf-test-success rpf-test-error')
					.addClass('rpf-test-loading')
					.text(rpfAdmin.strings.testing);

				$.post(rpfAdmin.ajaxUrl, {
					action: 'rpf_test_network',
					nonce: rpfAdmin.nonce,
					network: network
				}, function (response) {
					$btn.prop('disabled', false);
					$result.removeClass('rpf-test-loading');

					if (response.success && response.data.valid) {
						$result
							.addClass('rpf-test-success')
							.text('✓ ' + response.data.message);
					} else {
						var msg = response.data && response.data.message
							? response.data.message
							: rpfAdmin.strings.failed;
						$result
							.addClass('rpf-test-error')
							.text('✗ ' + msg);
					}
				}).fail(function () {
					$btn.prop('disabled', false);
					$result
						.removeClass('rpf-test-loading')
						.addClass('rpf-test-error')
						.text('✗ ' + rpfAdmin.strings.failed);
				});
			});
		},

		/**
		 * Share Now button.
		 */
		bindShareNow: function () {
			$(document).on('click', '#rpf-share-now', function () {
				var $btn = $(this);
				var $result = $('#rpf-share-result');

				$btn.prop('disabled', true).text(rpfAdmin.strings.sharing);
				$result.hide();

				$.post(rpfAdmin.ajaxUrl, {
					action: 'rpf_share_now',
					nonce: rpfAdmin.nonce
				}, function (response) {
					$btn.prop('disabled', false).html(
						'<span class="dashicons dashicons-share"></span> Share Now'
					);
					$result.show();

					if (response.success) {
						var html = '<strong>✓ ' + rpfAdmin.strings.shared + '</strong><br>';
						html += 'Post: "' + response.data.post_title + '"<br>';

						for (var net in response.data.results) {
							var r = response.data.results[net];
							html += net + ': ' + (r.success ? '✓' : '✗ ' + r.error) + '<br>';
						}

						$result
							.removeClass('rpf-result-error')
							.addClass('rpf-result-success')
							.html(html);
					} else {
						$result
							.removeClass('rpf-result-success')
							.addClass('rpf-result-error')
							.html('<strong>✗</strong> ' + response.data);
					}
				}).fail(function () {
					$btn.prop('disabled', false).html(
						'<span class="dashicons dashicons-share"></span> Share Now'
					);
					$result
						.show()
						.removeClass('rpf-result-success')
						.addClass('rpf-result-error')
						.text(rpfAdmin.strings.failed);
				});
			});
		},

		/**
		 * Clear queue button.
		 */
		bindClearQueue: function () {
			$(document).on('click', '#rpf-clear-queue', function () {
				if (!confirm(rpfAdmin.strings.confirm)) {
					return;
				}

				var $btn = $(this);
				$btn.prop('disabled', true);

				$.post(rpfAdmin.ajaxUrl, {
					action: 'rpf_clear_queue',
					nonce: rpfAdmin.nonce
				}, function (response) {
					if (response.success) {
						location.reload();
					} else {
						$btn.prop('disabled', false);
					}
				});
			});
		},

		/**
		 * Delete individual log entry.
		 */
		bindDeleteLog: function () {
			$(document).on('click', '.rpf-delete-log', function () {
				var $btn = $(this);
				var logId = $btn.data('id');

				$btn.prop('disabled', true);

				$.post(rpfAdmin.ajaxUrl, {
					action: 'rpf_delete_log',
					nonce: rpfAdmin.nonce,
					log_id: logId
				}, function (response) {
					if (response.success) {
						$('#queue-row-' + logId).fadeOut(300, function () {
							$(this).remove();
						});
					} else {
						$btn.prop('disabled', false);
					}
				});
			});
		},

		/**
		 * Toggle plugin enabled/disabled.
		 */
		bindToggleEnabled: function () {
			$(document).on('click', '.rpf-toggle-enabled', function () {
				var $btn = $(this);
				$btn.prop('disabled', true);

				$.post(rpfAdmin.ajaxUrl, {
					action: 'rpf_toggle_enabled',
					nonce: rpfAdmin.nonce
				}, function (response) {
					if (response.success) {
						location.reload();
					} else {
						$btn.prop('disabled', false);
					}
				});
			});
		}
	};

	$(document).ready(function () {
		RPF.init();
	});

})(jQuery);
