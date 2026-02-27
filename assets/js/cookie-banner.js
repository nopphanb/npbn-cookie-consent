/**
 * NPBN Cookie Consent — Frontend banner + granular modal logic.
 *
 * Handles consent state (per-category JSON), banner/modal display,
 * script blocking via MutationObserver, and consent withdrawal.
 *
 * @package NPBN_Cookie_Consent
 */
(function () {
	'use strict';

	var COOKIE_NAME = 'npbn_cookie_consent';
	var settings = window.npbnCookieConsent || {};
	var expiryDays = settings.expiryDays || 365;
	var domainCategories = settings.domainCategories || {};
	var categories = settings.categories || {};
	var i18n = settings.i18n || {};
	var focusCleanup = null;
	var modalFocusCleanup = null;
	var activeObserver = null;

	/* ==========================================================
	   Consent cookie — JSON format
	   ========================================================== */

	/**
	 * Read the consent cookie value.
	 *
	 * @return {Object|null} Category object or null if no consent.
	 */
	function getConsent() {
		var match = document.cookie.match(new RegExp('(^| )' + COOKIE_NAME + '=([^;]+)'));
		if (!match) return null;

		var raw = decodeURIComponent(match[2]);

		// Backward compatibility: old string format.
		if (raw === 'accepted') {
			return { necessary: true, functional: true, analytics: true, marketing: true };
		}
		if (raw === 'rejected') {
			return { necessary: true, functional: false, analytics: false, marketing: false };
		}

		// New JSON format.
		try {
			var parsed = JSON.parse(raw);
			if (typeof parsed === 'object' && parsed !== null) {
				parsed.necessary = true;
				return parsed;
			}
		} catch (e) {}

		return null;
	}

	/**
	 * Set the consent cookie (JSON).
	 *
	 * @param {Object} cat Category consent object.
	 */
	function setConsent(cat) {
		cat.necessary = true;
		var value = encodeURIComponent(JSON.stringify(cat));
		var maxAge = expiryDays * 24 * 60 * 60;
		document.cookie = COOKIE_NAME + '=' + value +
			';max-age=' + maxAge +
			';path=/' +
			';SameSite=Lax';
	}

	/**
	 * Delete the consent cookie.
	 */
	function deleteConsent() {
		document.cookie = COOKIE_NAME + '=;max-age=0;path=/;SameSite=Lax';
	}

	/* ==========================================================
	   Category-aware script blocking
	   ========================================================== */

	/**
	 * Get the category of a script node.
	 *
	 * @param {HTMLScriptElement} node
	 * @return {string|null}
	 */
	function getScriptCategory(node) {
		var src = node.getAttribute('src') || '';
		var content = node.textContent || '';
		var testString = src + ' ' + content;

		for (var domain in domainCategories) {
			if (domainCategories.hasOwnProperty(domain)) {
				if (testString.indexOf(domain) !== -1) {
					return domainCategories[domain];
				}
			}
		}
		return null;
	}

	/**
	 * Check if a script node should be blocked based on current consent.
	 *
	 * @param {HTMLScriptElement} node
	 * @return {boolean}
	 */
	function isBlockedScript(node) {
		var category = getScriptCategory(node);
		if (!category) return false;

		var consent = getConsent();
		if (!consent) return true; // No consent = block all non-necessary.
		return !consent[category];
	}

	/**
	 * Start a MutationObserver to block dynamically injected scripts.
	 *
	 * @return {MutationObserver}
	 */
	function startObserver() {
		var observer = new MutationObserver(function (mutations) {
			for (var i = 0; i < mutations.length; i++) {
				var addedNodes = mutations[i].addedNodes;
				for (var j = 0; j < addedNodes.length; j++) {
					var node = addedNodes[j];
					if (node.nodeType === 1 && node.tagName === 'SCRIPT' && isBlockedScript(node)) {
						node.type = 'text/plain';
						node.setAttribute('data-cookieconsent', 'blocked');
					}
				}
			}
		});

		observer.observe(document.documentElement, {
			childList: true,
			subtree: true,
		});

		return observer;
	}

	/* ==========================================================
	   Banner show/hide
	   ========================================================== */

	function showBanner() {
		var banner = document.getElementById('npbn-cookie-banner');
		if (!banner) return;

		void banner.offsetHeight;
		banner.classList.add('npbn-cookie-banner--visible');
		banner.focus();
		focusCleanup = trapFocus(banner);
	}

	function hideBanner() {
		var banner = document.getElementById('npbn-cookie-banner');
		if (!banner) return;

		banner.classList.remove('npbn-cookie-banner--visible');
		if (focusCleanup) {
			focusCleanup();
			focusCleanup = null;
		}
	}

	function showSettingsBtn() {
		var btn = document.getElementById('npbn-cookie-settings-btn');
		if (!btn) return;

		void btn.offsetHeight;
		btn.classList.add('npbn-cookie-settings-btn--visible');
	}

	function hideSettingsBtn() {
		var btn = document.getElementById('npbn-cookie-settings-btn');
		if (!btn) return;

		btn.classList.remove('npbn-cookie-settings-btn--visible');
	}

	/* ==========================================================
	   Granular settings modal
	   ========================================================== */

	/**
	 * Build category toggle HTML from localized data.
	 *
	 * @param {string} containerId Target container element ID.
	 * @param {string} idPrefix    Prefix for checkbox IDs (to avoid duplicate IDs).
	 */
	function renderCategoryToggles(containerId, idPrefix) {
		var container = document.getElementById(containerId || 'npbn-cookie-modal-body');
		if (!container) return;

		var prefix = idPrefix || 'npbn-cat-';
		var consent = getConsent();
		var html = '';
		var order = ['necessary', 'functional', 'analytics', 'marketing'];

		for (var i = 0; i < order.length; i++) {
			var key = order[i];
			var cat = categories[key];
			if (!cat) continue;

			var isRequired = cat.required;
			var isChecked = consent ? !!consent[key] : isRequired;

			html += '<div class="npbn-cookie-modal__category">';
			html += '<div class="npbn-cookie-modal__category-header">';
			html += '<span class="npbn-cookie-modal__category-label">' + escHtml(cat.label) + '</span>';
			html += '<div class="npbn-cookie-modal__category-toggle">';

			if (isRequired) {
				html += '<span class="npbn-cookie-modal__always-on">' + escHtml(i18n.alwaysOn || '') + '</span>';
				html += '<input type="checkbox" checked disabled data-category="' + key + '">';
			} else {
				html += '<label class="npbn-cookie-toggle" for="' + prefix + key + '">';
				html += '<input type="checkbox" id="' + prefix + key + '"'
					+ (isChecked ? ' checked' : '')
					+ ' data-category="' + key + '">';
				html += '<span class="npbn-cookie-toggle__slider"></span>';
				html += '</label>';
			}

			html += '</div></div>';
			html += '<p class="npbn-cookie-modal__category-desc">' + escHtml(cat.description) + '</p>';
			html += '</div>';
		}

		container.innerHTML = html;
	}

	function showModal() {
		var modal = document.getElementById('npbn-cookie-modal');
		if (!modal) return;

		renderCategoryToggles('npbn-cookie-modal-body', 'npbn-cat-');
		void modal.offsetHeight;
		modal.classList.add('npbn-cookie-modal--visible');
		document.body.style.overflow = 'hidden';
		modal.focus();
		modalFocusCleanup = trapFocus(modal);
	}

	function hideModal() {
		var modal = document.getElementById('npbn-cookie-modal');
		if (!modal) return;

		modal.classList.remove('npbn-cookie-modal--visible');
		document.body.style.overflow = '';
		if (modalFocusCleanup) {
			modalFocusCleanup();
			modalFocusCleanup = null;
		}
	}

	/**
	 * Read toggle states from a container.
	 *
	 * @param {string} selector CSS selector for the container.
	 * @return {Object}
	 */
	function getSelections(selector) {
		var result = { necessary: true };
		var checkboxes = document.querySelectorAll(selector + ' input[data-category]');
		for (var i = 0; i < checkboxes.length; i++) {
			var cat = checkboxes[i].getAttribute('data-category');
			if (cat !== 'necessary') {
				result[cat] = checkboxes[i].checked;
			}
		}
		return result;
	}

	function getModalSelections() {
		return getSelections('#npbn-cookie-modal');
	}

	/* ==========================================================
	   Accessibility — focus trapping
	   ========================================================== */

	/**
	 * Trap keyboard focus within an element.
	 *
	 * @param {HTMLElement} element
	 * @return {Function} Cleanup function to remove the listener.
	 */
	function trapFocus(element) {
		var focusable = element.querySelectorAll(
			'button:not([disabled]), a[href], input:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);
		if (focusable.length === 0) return function () {};

		var first = focusable[0];
		var last = focusable[focusable.length - 1];

		function handler(e) {
			if (e.key !== 'Tab') return;

			if (e.shiftKey) {
				if (document.activeElement === first) {
					e.preventDefault();
					last.focus();
				}
			} else {
				if (document.activeElement === last) {
					e.preventDefault();
					first.focus();
				}
			}
		}

		element.addEventListener('keydown', handler);
		return function () {
			element.removeEventListener('keydown', handler);
		};
	}

	/* ==========================================================
	   Consent logging (fire-and-forget)
	   ========================================================== */

	/**
	 * Log consent to the server via AJAX.
	 *
	 * @param {string} status 'accepted', 'rejected', 'partial', or 'revoked'.
	 * @param {Object} cat    Category consent object.
	 */
	function logConsent(status, cat) {
		if (!settings.ajaxUrl || !settings.nonce) return;

		var data = new FormData();
		data.append('action', 'npbn_log_consent');
		data.append('nonce', settings.nonce);
		data.append('consent_status', status);
		data.append('consent_categories', JSON.stringify(cat || {}));
		data.append('page_url', window.location.href);

		try {
			navigator.sendBeacon(settings.ajaxUrl, data);
		} catch (e) {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', settings.ajaxUrl, true);
			xhr.send(data);
		}
	}

	/* ==========================================================
	   Dynamic script unblocking
	   ========================================================== */

	/**
	 * Unblock scripts whose categories are now accepted.
	 *
	 * Instead of reloading the page, we clone each blocked script
	 * into a new <script> element so the browser executes it.
	 *
	 * @param {Object} accepted Category consent object.
	 */
	function unblockScripts(accepted) {
		var blocked = document.querySelectorAll('script[data-cookieconsent="blocked"]');
		for (var i = 0; i < blocked.length; i++) {
			var node = blocked[i];
			var category = node.getAttribute('data-cookieconsent-category') || getScriptCategory(node);

			if (!category || accepted[category]) {
				var newScript = document.createElement('script');
				// Copy attributes, skipping the ones we added for blocking.
				for (var j = 0; j < node.attributes.length; j++) {
					var attr = node.attributes[j];
					if (attr.name === 'type' || attr.name === 'data-cookieconsent' || attr.name === 'data-cookieconsent-category') {
						continue;
					}
					newScript.setAttribute(attr.name, attr.value);
				}
				// Copy inline content if no external src.
				if (!node.getAttribute('src') && node.textContent) {
					newScript.textContent = node.textContent;
				}
				node.parentNode.replaceChild(newScript, node);
			}
		}

		// Stop the observer if all categories are now accepted.
		if (accepted.functional && accepted.analytics && accepted.marketing && activeObserver) {
			activeObserver.disconnect();
			activeObserver = null;
		}
	}

	/* ==========================================================
	   Action handlers
	   ========================================================== */

	function handleAcceptAll() {
		var all = { necessary: true, functional: true, analytics: true, marketing: true };
		setConsent(all);
		logConsent('accepted', all);
		unblockScripts(all);
		hideBanner();
		hideModal();
		showSettingsBtn();
	}

	function handleRejectAll() {
		var none = { necessary: true, functional: false, analytics: false, marketing: false };
		setConsent(none);
		logConsent('rejected', none);
		hideBanner();
		hideModal();
		showSettingsBtn();
	}

	function handleSavePreferences() {
		var previousConsent = getConsent();
		var selections = getModalSelections();
		setConsent(selections);

		var allAccepted = selections.functional && selections.analytics && selections.marketing;
		var allRejected = !selections.functional && !selections.analytics && !selections.marketing;
		var status = allAccepted ? 'accepted' : (allRejected ? 'rejected' : 'partial');
		logConsent(status, selections);

		// Reload only when revoking a previously accepted category (scripts already running).
		var needsReload = false;
		if (previousConsent) {
			var cats = ['functional', 'analytics', 'marketing'];
			for (var i = 0; i < cats.length; i++) {
				if (previousConsent[cats[i]] && !selections[cats[i]]) {
					needsReload = true;
					break;
				}
			}
		}

		if (needsReload) {
			window.location.reload();
		} else {
			unblockScripts(selections);
			hideModal();
			hideBanner();
			showSettingsBtn();
		}
	}

	function handleOpenSettings() {
		hideBanner();
		showModal();
	}

	function handleSettingsClick() {
		showModal();
	}

	/* ==========================================================
	   Shortcode handlers
	   ========================================================== */

	function handleShortcodeSave() {
		var previousConsent = getConsent();
		var selections = getSelections('#npbn-cookie-shortcode');
		setConsent(selections);

		var allAccepted = selections.functional && selections.analytics && selections.marketing;
		var allRejected = !selections.functional && !selections.analytics && !selections.marketing;
		var status = allAccepted ? 'accepted' : (allRejected ? 'rejected' : 'partial');
		logConsent(status, selections);

		var needsReload = false;
		if (previousConsent) {
			var cats = ['functional', 'analytics', 'marketing'];
			for (var i = 0; i < cats.length; i++) {
				if (previousConsent[cats[i]] && !selections[cats[i]]) {
					needsReload = true;
					break;
				}
			}
		}

		if (needsReload) {
			window.location.reload();
		} else {
			unblockScripts(selections);
			hideBanner();
			showSettingsBtn();
		}
	}

	function initShortcode() {
		var shortcode = document.getElementById('npbn-cookie-shortcode');
		if (!shortcode) return;

		renderCategoryToggles('npbn-cookie-shortcode-body', 'npbn-sc-cat-');

		var scSaveBtn = document.getElementById('npbn-shortcode-save');
		var scAcceptBtn = document.getElementById('npbn-shortcode-accept-all');
		var scRejectBtn = document.getElementById('npbn-shortcode-reject-all');

		if (scSaveBtn) scSaveBtn.addEventListener('click', handleShortcodeSave);
		if (scAcceptBtn) scAcceptBtn.addEventListener('click', handleAcceptAll);
		if (scRejectBtn) scRejectBtn.addEventListener('click', handleRejectAll);
	}

	/* ==========================================================
	   Helpers
	   ========================================================== */

	function escHtml(s) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(s || ''));
		return div.innerHTML;
	}

	/* ==========================================================
	   Init
	   ========================================================== */

	function init() {
		var consent = getConsent();

		if (consent) {
			var allAccepted = consent.functional && consent.analytics && consent.marketing;
			if (allAccepted) {
				showSettingsBtn();
				return;
			}

			// Partial or fully rejected — keep observer running.
			activeObserver = startObserver();
			showSettingsBtn();
		} else {
			// No consent — block all, show banner.
			activeObserver = startObserver();
			showBanner();
		}

		// Bind banner buttons.
		var acceptBtn = document.getElementById('npbn-cookie-accept');
		var rejectAllBtn = document.getElementById('npbn-cookie-reject-all');
		var settingsOpenBtn = document.getElementById('npbn-cookie-settings');
		var settingsFloatBtn = document.getElementById('npbn-cookie-settings-btn');

		// Bind modal buttons.
		var modalCloseBtn = document.querySelector('.npbn-cookie-modal__close');
		var modalOverlay = document.querySelector('.npbn-cookie-modal__overlay');
		var modalSaveBtn = document.getElementById('npbn-modal-save');
		var modalAcceptAllBtn = document.getElementById('npbn-modal-accept-all');
		var modalRejectAllBtn = document.getElementById('npbn-modal-reject-all');

		if (acceptBtn) acceptBtn.addEventListener('click', handleAcceptAll);
		if (rejectAllBtn) rejectAllBtn.addEventListener('click', handleRejectAll);
		if (settingsOpenBtn) settingsOpenBtn.addEventListener('click', handleOpenSettings);
		if (settingsFloatBtn) settingsFloatBtn.addEventListener('click', handleSettingsClick);

		if (modalCloseBtn) modalCloseBtn.addEventListener('click', hideModal);
		if (modalOverlay) modalOverlay.addEventListener('click', hideModal);
		if (modalSaveBtn) modalSaveBtn.addEventListener('click', handleSavePreferences);
		if (modalAcceptAllBtn) modalAcceptAllBtn.addEventListener('click', handleAcceptAll);
		if (modalRejectAllBtn) modalRejectAllBtn.addEventListener('click', handleRejectAll);

		// Escape key closes modal.
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				var modal = document.getElementById('npbn-cookie-modal');
				if (modal && modal.classList.contains('npbn-cookie-modal--visible')) {
					hideModal();
				}
			}
		});

		// Initialize shortcode toggles if present on page.
		initShortcode();
	}

	// Expose revoke function for developers.
	window.npbnCookieConsentRevoke = function () {
		logConsent('revoked', {});
		deleteConsent();
		window.location.reload();
	};

	// Run on DOMContentLoaded.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
