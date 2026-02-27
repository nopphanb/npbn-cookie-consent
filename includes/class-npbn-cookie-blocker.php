<?php
/**
 * Server-side script blocking via output buffer (category-aware).
 *
 * @package NPBN_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * NPBN_Cookie_Blocker class.
 *
 * Intercepts the HTML output and rewrites third-party tracking script tags
 * to type="text/plain" so they don't execute until consent is given.
 * Supports per-category blocking (analytics, marketing, functional).
 */
class NPBN_Cookie_Blocker {

	/**
	 * Domain-to-category mapping.
	 *
	 * @var array
	 */
	private static $domain_categories = array(
		// Analytics.
		'google-analytics.com'         => 'analytics',
		'googletagmanager.com'         => 'analytics',
		'gtag/js'                      => 'analytics',
		'analytics.google.com'         => 'analytics',
		'static.hotjar.com'            => 'analytics',
		'plausible.io'                 => 'analytics',
		'clarity.ms'                   => 'analytics',
		'cdn.segment.com'              => 'analytics',

		// Marketing.
		'connect.facebook.net'         => 'marketing',
		'www.facebook.com/tr'          => 'marketing',
		'snap.licdn.com'               => 'marketing',
		'bat.bing.com'                 => 'marketing',
		'www.googleadservices.com'     => 'marketing',
		'pagead2.googlesyndication.com' => 'marketing',
		'tiktok.com/i18n/pixel'        => 'marketing',
		'analytics.tiktok.com'         => 'marketing',
		'line.me/tag'                  => 'marketing',
	);

	/**
	 * Categories blocked for the current request.
	 *
	 * @var array
	 */
	private $blocked_categories = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! is_admin() && ! wp_doing_ajax() && ! defined( 'REST_REQUEST' ) ) {
			add_action( 'template_redirect', array( $this, 'start_buffer' ) );
		}
	}

	/**
	 * Get the blocked domains list (flat array for backward compatibility).
	 *
	 * @return array
	 */
	public static function get_blocked_domains() {
		return array_keys( self::$domain_categories );
	}

	/**
	 * Get the domain-to-category mapping.
	 *
	 * @return array
	 */
	public static function get_domain_categories() {
		return self::$domain_categories;
	}

	/**
	 * Start output buffering based on consent state.
	 */
	public function start_buffer() {
		$consent = isset( $_COOKIE['npbn_cookie_consent'] ) ? $_COOKIE['npbn_cookie_consent'] : '';

		if ( empty( $consent ) ) {
			// No consent — block all non-necessary categories.
			$this->blocked_categories = array( 'functional', 'analytics', 'marketing' );
			ob_start( array( $this, 'process_buffer' ) );
			return;
		}

		// Backward compatibility: old string format.
		if ( 'accepted' === $consent ) {
			return; // All accepted, no blocking needed.
		}

		if ( 'rejected' === $consent ) {
			$this->blocked_categories = array( 'functional', 'analytics', 'marketing' );
			ob_start( array( $this, 'process_buffer' ) );
			return;
		}

		// New format: URL-encoded JSON.
		$decoded = json_decode( urldecode( $consent ), true );
		if ( ! is_array( $decoded ) ) {
			// Malformed cookie — block everything for safety.
			$this->blocked_categories = array( 'functional', 'analytics', 'marketing' );
			ob_start( array( $this, 'process_buffer' ) );
			return;
		}

		// Build list of blocked categories.
		$this->blocked_categories = array();
		$toggleable               = array( 'functional', 'analytics', 'marketing' );
		foreach ( $toggleable as $cat ) {
			if ( empty( $decoded[ $cat ] ) ) {
				$this->blocked_categories[] = $cat;
			}
		}

		if ( empty( $this->blocked_categories ) ) {
			return; // All categories accepted.
		}

		ob_start( array( $this, 'process_buffer' ) );
	}

	/**
	 * Process the output buffer — find and block matching script tags.
	 *
	 * @param string $html The full page HTML.
	 * @return string Modified HTML.
	 */
	public function process_buffer( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}

		$html = preg_replace_callback(
			'/<script\b([^>]*)>(.*?)<\/script>/is',
			array( $this, 'filter_script_tag' ),
			$html
		);

		return $html;
	}

	/**
	 * Check a single script tag and block it if its category is not accepted.
	 *
	 * @param array $matches Regex matches.
	 * @return string The original or modified script tag.
	 */
	private function filter_script_tag( $matches ) {
		$attributes = $matches[1];
		$content    = $matches[2];

		// Never block our own plugin scripts (the localized data contains domain names as data).
		if ( strpos( $attributes, 'npbn-cookie' ) !== false || strpos( $content, 'npbnCookieConsent' ) !== false ) {
			return $matches[0];
		}

		$category = $this->get_script_category( $attributes, $content );
		if ( ! $category || ! in_array( $category, $this->blocked_categories, true ) ) {
			return $matches[0];
		}

		// Replace existing type attribute or add one.
		if ( preg_match( '/type\s*=\s*["\'][^"\']*["\']/', $attributes ) ) {
			$attributes = preg_replace(
				'/type\s*=\s*["\'][^"\']*["\']/',
				'type="text/plain"',
				$attributes
			);
		} else {
			$attributes .= ' type="text/plain"';
		}

		$attributes .= ' data-cookieconsent="blocked"';
		$attributes .= ' data-cookieconsent-category="' . esc_attr( $category ) . '"';

		return '<script' . $attributes . '>' . $content . '</script>';
	}

	/**
	 * Get the category of a script based on its attributes and content.
	 *
	 * @param string $attributes Script tag attributes.
	 * @param string $content    Inline script content.
	 * @return string|null Category name or null if not a tracked script.
	 */
	private function get_script_category( $attributes, $content ) {
		$full_text = $attributes . ' ' . $content;

		foreach ( self::$domain_categories as $domain => $category ) {
			if ( stripos( $full_text, $domain ) !== false ) {
				return $category;
			}
		}

		return null;
	}
}
