<?php
/**
 * Consent logger — AJAX endpoint and database operations.
 *
 * @package NPBN_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * NPBN_Cookie_Logger class.
 */
class NPBN_Cookie_Logger {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_npbn_log_consent', array( $this, 'handle_log' ) );
		add_action( 'wp_ajax_nopriv_npbn_log_consent', array( $this, 'handle_log' ) );
	}

	/**
	 * Handle the AJAX consent log request.
	 */
	public function handle_log() {
		check_ajax_referer( 'npbn_cookie_consent_nonce', 'nonce' );

		$status = isset( $_POST['consent_status'] ) ? sanitize_text_field( wp_unslash( $_POST['consent_status'] ) ) : '';

		if ( ! in_array( $status, array( 'accepted', 'rejected', 'revoked', 'partial' ), true ) ) {
			wp_send_json_error( 'Invalid consent status.', 400 );
		}

		$categories = isset( $_POST['consent_categories'] ) ? sanitize_text_field( wp_unslash( $_POST['consent_categories'] ) ) : '';

		$this->log_consent( $status, $categories );
		wp_send_json_success();
	}

	/**
	 * Insert a consent record into the database.
	 *
	 * @param string $status     Consent status: accepted, rejected, partial, or revoked.
	 * @param string $categories JSON string of per-category consent.
	 */
	public function log_consent( $status, $categories = '' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'npbn_consent_log';

		$ip = $this->get_client_ip();
		// Anonymize last octet for privacy.
		$ip = $this->anonymize_ip( $ip );

		$wpdb->insert(
			$table_name,
			array(
				'consent_status'     => $status,
				'consent_categories' => $categories,
				'ip_address'         => $ip,
				'user_agent'         => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'page_url'           => isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '',
				'created_at'         => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get the client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return $ip;
	}

	/**
	 * Anonymize an IP address by zeroing the last octet (IPv4) or last 80 bits (IPv6).
	 *
	 * @param string $ip IP address.
	 * @return string Anonymized IP.
	 */
	private function anonymize_ip( $ip ) {
		if ( strpos( $ip, ':' ) !== false ) {
			// IPv6: zero last 80 bits.
			return preg_replace( '/:[0-9a-fA-F]{0,4}(:[0-9a-fA-F]{0,4}){0,4}$/', ':0:0:0:0:0', $ip );
		}
		// IPv4: zero last octet.
		return preg_replace( '/\.\d+$/', '.0', $ip );
	}

	/**
	 * Get consent stats for the admin dashboard.
	 *
	 * @param int $days Number of days to look back. 0 = all time.
	 * @return array Stats array.
	 */
	public static function get_stats( $days = 30 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'npbn_consent_log';

		$where = '';
		if ( $days > 0 ) {
			$where = $wpdb->prepare( 'WHERE created_at >= %s', gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) ) );
		}

		$results = $wpdb->get_results(
			"SELECT consent_status, COUNT(*) as count FROM {$table_name} {$where} GROUP BY consent_status",
			ARRAY_A
		);

		$stats = array(
			'accepted' => 0,
			'rejected' => 0,
			'revoked'  => 0,
			'total'    => 0,
		);

		foreach ( $results as $row ) {
			$stats[ $row['consent_status'] ] = (int) $row['count'];
			$stats['total']                 += (int) $row['count'];
		}

		$stats['accept_rate'] = $stats['total'] > 0
			? round( ( $stats['accepted'] / $stats['total'] ) * 100, 1 )
			: 0;

		return $stats;
	}

	/**
	 * Get recent consent log entries.
	 *
	 * @param int $per_page Items per page.
	 * @param int $page     Current page.
	 * @return array { entries: array, total: int }
	 */
	public static function get_log( $per_page = 20, $page = 1 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'npbn_consent_log';

		$offset = ( $page - 1 ) * $per_page;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		$entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		return array(
			'entries' => $entries,
			'total'   => $total,
		);
	}
}
