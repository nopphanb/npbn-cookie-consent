<?php
/**
 * Cookie scanner — detects cookies set by the site.
 *
 * @package NPBN_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * NPBN_Cookie_Scanner class.
 */
class NPBN_Cookie_Scanner {

	/**
	 * Known cookies database with category and description.
	 *
	 * @var array
	 */
	private static $known_cookies = array(
		// WordPress core.
		'wordpress_logged_in_*'  => array(
			'category'    => 'necessary',
			'description' => 'WordPress login authentication cookie.',
			'duration'    => 'Session / 14 days',
		),
		'wordpress_sec_*'        => array(
			'category'    => 'necessary',
			'description' => 'WordPress secure authentication cookie.',
			'duration'    => 'Session / 14 days',
		),
		'wordpress_test_cookie'  => array(
			'category'    => 'necessary',
			'description' => 'WordPress test cookie to check if cookies are enabled.',
			'duration'    => 'Session',
		),
		'wp-settings-*'          => array(
			'category'    => 'necessary',
			'description' => 'WordPress user settings and preferences.',
			'duration'    => '1 year',
		),
		'wp-settings-time-*'     => array(
			'category'    => 'necessary',
			'description' => 'WordPress user settings timestamp.',
			'duration'    => '1 year',
		),
		'comment_author_*'       => array(
			'category'    => 'functional',
			'description' => 'Stores the comment author name for returning visitors.',
			'duration'    => '347 days',
		),
		'comment_author_email_*' => array(
			'category'    => 'functional',
			'description' => 'Stores the comment author email for returning visitors.',
			'duration'    => '347 days',
		),
		'comment_author_url_*'   => array(
			'category'    => 'functional',
			'description' => 'Stores the comment author URL for returning visitors.',
			'duration'    => '347 days',
		),

		// WooCommerce.
		'woocommerce_cart_hash'       => array(
			'category'    => 'necessary',
			'description' => 'WooCommerce cart hash for cart contents.',
			'duration'    => 'Session',
		),
		'woocommerce_items_in_cart'   => array(
			'category'    => 'necessary',
			'description' => 'WooCommerce flag for items in cart.',
			'duration'    => 'Session',
		),
		'wp_woocommerce_session_*'    => array(
			'category'    => 'necessary',
			'description' => 'WooCommerce session cookie.',
			'duration'    => '2 days',
		),
		'wc_cart_hash_*'              => array(
			'category'    => 'necessary',
			'description' => 'WooCommerce cart hash.',
			'duration'    => 'Session',
		),

		// Google Analytics.
		'_ga'     => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics: distinguishes unique users.',
			'duration'    => '2 years',
		),
		'_ga_*'   => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics: used to persist session state.',
			'duration'    => '2 years',
		),
		'_gid'    => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics: distinguishes users (24h).',
			'duration'    => '24 hours',
		),
		'_gat'    => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics: throttles request rate.',
			'duration'    => '1 minute',
		),
		'_gat_*'  => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics: throttles request rate for property.',
			'duration'    => '1 minute',
		),
		'__utma'  => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics (legacy): distinguishes users and sessions.',
			'duration'    => '2 years',
		),
		'__utmb'  => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics (legacy): determines new sessions/visits.',
			'duration'    => '30 minutes',
		),
		'__utmc'  => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics (legacy): used with __utmb to determine new session.',
			'duration'    => 'Session',
		),
		'__utmz'  => array(
			'category'    => 'analytics',
			'description' => 'Google Analytics (legacy): stores traffic source/campaign.',
			'duration'    => '6 months',
		),

		// Facebook.
		'_fbp'    => array(
			'category'    => 'marketing',
			'description' => 'Facebook Pixel: identifies browsers for ad delivery.',
			'duration'    => '3 months',
		),
		'_fbc'    => array(
			'category'    => 'marketing',
			'description' => 'Facebook: stores last click identifier.',
			'duration'    => '2 years',
		),
		'fr'      => array(
			'category'    => 'marketing',
			'description' => 'Facebook: ad delivery and measurement.',
			'duration'    => '3 months',
		),

		// Google Ads.
		'_gcl_au' => array(
			'category'    => 'marketing',
			'description' => 'Google Ads: stores conversion data.',
			'duration'    => '3 months',
		),
		'_gcl_aw' => array(
			'category'    => 'marketing',
			'description' => 'Google Ads: stores click information.',
			'duration'    => '3 months',
		),

		// Hotjar.
		'_hj*'    => array(
			'category'    => 'analytics',
			'description' => 'Hotjar: session recording and heatmap analytics.',
			'duration'    => '1 year',
		),

		// Microsoft Clarity.
		'_clck'   => array(
			'category'    => 'analytics',
			'description' => 'Microsoft Clarity: persists the Clarity user ID.',
			'duration'    => '1 year',
		),
		'_clsk'   => array(
			'category'    => 'analytics',
			'description' => 'Microsoft Clarity: connects page views into a session.',
			'duration'    => '1 day',
		),
		'CLID'    => array(
			'category'    => 'analytics',
			'description' => 'Microsoft Clarity: identifies first-time users.',
			'duration'    => '1 year',
		),

		// LINE Tag.
		'_lt_*'   => array(
			'category'    => 'marketing',
			'description' => 'LINE Tag: tracks conversions and audiences.',
			'duration'    => '2 years',
		),

		// TikTok.
		'_ttp'    => array(
			'category'    => 'marketing',
			'description' => 'TikTok Pixel: tracks visits for ad targeting.',
			'duration'    => '1 year',
		),
		'tt_*'    => array(
			'category'    => 'marketing',
			'description' => 'TikTok: advertising and tracking cookie.',
			'duration'    => '1 year',
		),

		// This plugin.
		'npbn_cookie_consent' => array(
			'category'    => 'necessary',
			'description' => 'NPBN Cookie Consent: stores your cookie consent preference.',
			'duration'    => 'Configurable (default 365 days)',
		),

		// PHPSESSID.
		'PHPSESSID' => array(
			'category'    => 'necessary',
			'description' => 'PHP session identifier.',
			'duration'    => 'Session',
		),
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_npbn_scan_cookies', array( $this, 'handle_scan' ) );
	}

	/**
	 * Handle the AJAX scan request.
	 */
	public function handle_scan() {
		check_ajax_referer( 'npbn_cookie_scanner_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.', 403 );
		}

		$url = isset( $_POST['scan_url'] ) ? esc_url_raw( wp_unslash( $_POST['scan_url'] ) ) : home_url( '/' );

		$results = $this->scan_url( $url );

		// Save results.
		update_option( 'npbn_cookie_scan_results', $results );
		update_option( 'npbn_cookie_scan_time', current_time( 'mysql' ) );

		wp_send_json_success( $results );
	}

	/**
	 * Scan a URL for cookies.
	 *
	 * @param string $url URL to scan.
	 * @return array Detected cookies.
	 */
	public function scan_url( $url ) {
		$cookies = array();

		// Make HTTP request and capture Set-Cookie headers.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 30,
				'redirection' => 3,
				'sslverify'   => false,
				'headers'     => array(
					'User-Agent' => 'Mozilla/5.0 (NPBN Cookie Scanner)',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'error'   => $response->get_error_message(),
				'cookies' => array(),
				'url'     => $url,
			);
		}

		// Parse Set-Cookie headers.
		$headers    = wp_remote_retrieve_headers( $response );
		$set_cookies = array();

		if ( $headers instanceof \WpOrg\Requests\Utility\CaseInsensitiveDictionary || $headers instanceof \Requests_Utility_CaseInsensitiveDictionary ) {
			$all = $headers->getAll();
			if ( isset( $all['set-cookie'] ) ) {
				$set_cookies = (array) $all['set-cookie'];
			}
		}

		foreach ( $set_cookies as $cookie_string ) {
			$parsed = $this->parse_set_cookie( $cookie_string );
			if ( $parsed ) {
				$cookies[] = $parsed;
			}
		}

		// Also scan the HTML body for known script patterns that set cookies.
		$body = wp_remote_retrieve_body( $response );
		$script_cookies = $this->detect_script_cookies( $body );

		foreach ( $script_cookies as $sc ) {
			// Avoid duplicates.
			$exists = false;
			foreach ( $cookies as $c ) {
				if ( $c['name'] === $sc['name'] ) {
					$exists = true;
					break;
				}
			}
			if ( ! $exists ) {
				$cookies[] = $sc;
			}
		}

		// Enrich with known cookie data.
		foreach ( $cookies as &$cookie ) {
			$known = $this->match_known_cookie( $cookie['name'] );
			if ( $known ) {
				$cookie['category']    = $known['category'];
				$cookie['description'] = $known['description'];
				if ( empty( $cookie['duration'] ) ) {
					$cookie['duration'] = $known['duration'];
				}
			} else {
				if ( empty( $cookie['category'] ) ) {
					$cookie['category'] = 'unknown';
				}
				if ( empty( $cookie['description'] ) ) {
					$cookie['description'] = '';
				}
			}
		}
		unset( $cookie );

		// Sort: necessary first, then functional, analytics, marketing, unknown.
		$order = array( 'necessary' => 0, 'functional' => 1, 'analytics' => 2, 'marketing' => 3, 'unknown' => 4 );
		usort( $cookies, function ( $a, $b ) use ( $order ) {
			$oa = $order[ $a['category'] ] ?? 5;
			$ob = $order[ $b['category'] ] ?? 5;
			return $oa - $ob;
		} );

		return array(
			'cookies' => $cookies,
			'url'     => $url,
			'count'   => count( $cookies ),
		);
	}

	/**
	 * Parse a Set-Cookie header string into cookie data.
	 *
	 * @param string $cookie_string Raw Set-Cookie header.
	 * @return array|null Parsed cookie or null.
	 */
	private function parse_set_cookie( $cookie_string ) {
		$parts = array_map( 'trim', explode( ';', $cookie_string ) );
		if ( empty( $parts[0] ) ) {
			return null;
		}

		$name_value = explode( '=', $parts[0], 2 );
		if ( count( $name_value ) < 2 ) {
			return null;
		}

		$cookie = array(
			'name'        => $name_value[0],
			'domain'      => '',
			'path'        => '/',
			'duration'    => 'Session',
			'secure'      => false,
			'httponly'     => false,
			'samesite'    => '',
			'source'      => 'http',
			'category'    => '',
			'description' => '',
		);

		foreach ( array_slice( $parts, 1 ) as $attr ) {
			$attr_parts = explode( '=', $attr, 2 );
			$key        = strtolower( trim( $attr_parts[0] ) );
			$value      = isset( $attr_parts[1] ) ? trim( $attr_parts[1] ) : '';

			switch ( $key ) {
				case 'domain':
					$cookie['domain'] = $value;
					break;
				case 'path':
					$cookie['path'] = $value;
					break;
				case 'max-age':
					$seconds = (int) $value;
					$cookie['duration'] = $this->seconds_to_human( $seconds );
					break;
				case 'expires':
					$timestamp = strtotime( $value );
					if ( $timestamp ) {
						$seconds = $timestamp - time();
						$cookie['duration'] = $seconds > 0 ? $this->seconds_to_human( $seconds ) : 'Expired';
					}
					break;
				case 'secure':
					$cookie['secure'] = true;
					break;
				case 'httponly':
					$cookie['httponly'] = true;
					break;
				case 'samesite':
					$cookie['samesite'] = $value;
					break;
			}
		}

		return $cookie;
	}

	/**
	 * Detect cookies likely set by known scripts in the HTML body.
	 *
	 * @param string $html Page HTML.
	 * @return array Detected script-based cookies.
	 */
	private function detect_script_cookies( $html ) {
		$cookies = array();

		$patterns = array(
			'google-analytics.com'   => array( '_ga', '_gid', '_gat' ),
			'googletagmanager.com'   => array( '_ga', '_gid' ),
			'gtag/js'                => array( '_ga', '_gid', '_gat' ),
			'connect.facebook.net'   => array( '_fbp' ),
			'facebook.com/tr'        => array( '_fbp' ),
			'hotjar.com'             => array( '_hjSessionUser', '_hjSession' ),
			'clarity.ms'             => array( '_clck', '_clsk' ),
			'tiktok.com'             => array( '_ttp' ),
			'line.me/tag'            => array( '_lt_cid' ),
		);

		foreach ( $patterns as $script_pattern => $cookie_names ) {
			if ( stripos( $html, $script_pattern ) !== false ) {
				foreach ( $cookie_names as $name ) {
					$cookies[] = array(
						'name'        => $name,
						'domain'      => '',
						'path'        => '/',
						'duration'    => '',
						'secure'      => false,
						'httponly'     => false,
						'samesite'    => '',
						'source'      => 'script',
						'category'    => '',
						'description' => '',
					);
				}
			}
		}

		return $cookies;
	}

	/**
	 * Match a cookie name against the known cookies database.
	 *
	 * @param string $name Cookie name.
	 * @return array|null Known cookie data or null.
	 */
	private function match_known_cookie( $name ) {
		// Exact match first.
		if ( isset( self::$known_cookies[ $name ] ) ) {
			return self::$known_cookies[ $name ];
		}

		// Wildcard match (patterns ending with *).
		foreach ( self::$known_cookies as $pattern => $data ) {
			if ( substr( $pattern, -1 ) === '*' ) {
				$prefix = substr( $pattern, 0, -1 );
				if ( strpos( $name, $prefix ) === 0 ) {
					return $data;
				}
			}
		}

		return null;
	}

	/**
	 * Convert seconds to a human-readable duration string.
	 *
	 * @param int $seconds Duration in seconds.
	 * @return string Human-readable duration.
	 */
	private function seconds_to_human( $seconds ) {
		if ( $seconds <= 0 ) {
			return 'Session';
		}
		if ( $seconds < 60 ) {
			return $seconds . ' seconds';
		}
		if ( $seconds < 3600 ) {
			return round( $seconds / 60 ) . ' minutes';
		}
		if ( $seconds < 86400 ) {
			return round( $seconds / 3600 ) . ' hours';
		}
		if ( $seconds < 2592000 ) {
			return round( $seconds / 86400 ) . ' days';
		}
		if ( $seconds < 31536000 ) {
			return round( $seconds / 2592000 ) . ' months';
		}
		return round( $seconds / 31536000, 1 ) . ' years';
	}

	/**
	 * Get the last scan results from the database.
	 *
	 * @return array|false Scan results or false.
	 */
	public static function get_last_scan() {
		$results = get_option( 'npbn_cookie_scan_results', false );
		$time    = get_option( 'npbn_cookie_scan_time', false );

		if ( ! $results ) {
			return false;
		}

		return array(
			'results' => $results,
			'time'    => $time,
		);
	}

	/**
	 * Get category label.
	 *
	 * @param string $category Category key.
	 * @return string Translated label.
	 */
	public static function get_category_label( $category ) {
		$labels = array(
			'necessary'  => __( 'Necessary', 'npbn-cookie-consent' ),
			'functional' => __( 'Functional', 'npbn-cookie-consent' ),
			'analytics'  => __( 'Analytics', 'npbn-cookie-consent' ),
			'marketing'  => __( 'Marketing', 'npbn-cookie-consent' ),
			'unknown'    => __( 'Unknown', 'npbn-cookie-consent' ),
		);
		return $labels[ $category ] ?? ucfirst( $category );
	}

	/**
	 * Get category color.
	 *
	 * @param string $category Category key.
	 * @return string Hex color.
	 */
	public static function get_category_color( $category ) {
		$colors = array(
			'necessary'  => '#2271b1',
			'functional' => '#0e9f6e',
			'analytics'  => '#9333ea',
			'marketing'  => '#dc2626',
			'unknown'    => '#6b7280',
		);
		return $colors[ $category ] ?? '#6b7280';
	}
}
