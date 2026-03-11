/**
 * NPBN Cookie Consent — Admin settings page scripts.
 *
 * @package NPBN_Cookie_Consent
 */
(function ($) {
	$(document).ready(function () {
		// Color pickers.
		$('.npbn-color-picker').wpColorPicker({
			change: function () {
				setTimeout(updatePreview, 50);
			},
		});

		// Tab navigation.
		$('.npbn-admin-tabs .npbn-tab').on('click', function () {
			var target = $(this).data('tab');
			$('.npbn-admin-tabs .npbn-tab').removeClass('active');
			$(this).addClass('active');
			$('.npbn-tab-panel').removeClass('active');
			$('#npbn-panel-' + target).addClass('active');

			// Persist active tab.
			if (window.sessionStorage) {
				sessionStorage.setItem('npbn_active_tab', target);
			}
		});

		// Restore active tab.
		if (window.sessionStorage) {
			var saved = sessionStorage.getItem('npbn_active_tab');
			if (saved && $('.npbn-tab[data-tab="' + saved + '"]').length) {
				$('.npbn-tab[data-tab="' + saved + '"]').trigger('click');
			}
		}

		// Use Theme Colors toggle.
		$('#npbn-use_theme_colors').on('change', function () {
			var checked = $(this).is(':checked');
			$('#npbn-custom-colors').toggle(!checked);
			$('#npbn-theme-colors').toggle(checked);
		});

		// Live preview updates.
		$('#npbn-banner_heading, #npbn-banner_text').on('input', updatePreview);

		function updatePreview() {
			var bg = $('#npbn-bg_color').val() || '#ffffff';
			var text = $('#npbn-text_color').val() || '#333333';
			var btnBg = $('#npbn-btn_accept_bg').val() || '#16a34a';
			var btnText = $('#npbn-btn_accept_text').val() || '#ffffff';
			var heading = $('#npbn-banner_heading').val() || '';
			var bodyText = $('#npbn-banner_text').val() || '';

			// Try to read from color picker widget if available.
			var $bgPicker = $('#npbn-bg_color').closest('.wp-picker-container').find('.wp-color-result');
			if ($bgPicker.length) {
				bg = $bgPicker.css('background-color') || bg;
			}

			$('.npbn-preview-banner').css({ backgroundColor: bg, color: text });
			$('.npbn-preview-heading').text(heading);
			$('.npbn-preview-text').text(bodyText.substring(0, 100) + (bodyText.length > 100 ? '...' : ''));
			$('.npbn-preview-btn-accept').css({ backgroundColor: btnBg, color: btnText });
			$('.npbn-preview-btn-settings').css({ color: text });
		}

		// Initial preview.
		updatePreview();
	});
})(jQuery);
