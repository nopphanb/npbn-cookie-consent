<?php
/**
 * Plugin Name: NPBN Cookie Consent
 * Plugin URI:  https://npbn.me
 * Description: PDPA-compliant cookie consent banner. Auto-blocks third-party tracking scripts until consent is given.
 * Version:     1.3.0
 * Author:      Nopphan Bunnag
 * Author URI:  https://npbn.me
 * Text Domain: npbn-cookie-consent
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package NPBN_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

define( 'NPBN_COOKIE_CONSENT_VERSION', '1.3.0' );
define( 'NPBN_COOKIE_CONSENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'NPBN_COOKIE_CONSENT_URL', plugin_dir_url( __FILE__ ) );
define( 'NPBN_COOKIE_CONSENT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize the plugin.
 */
function npbn_cookie_consent_init() {
	load_plugin_textdomain( 'npbn-cookie-consent', false, dirname( NPBN_COOKIE_CONSENT_BASENAME ) . '/languages' );

	// Ensure DB table exists (handles upgrades without re-activation).
	if ( get_option( 'npbn_cookie_consent_db_version' ) !== '1.3.0' ) {
		npbn_cookie_consent_activate();
	}

	require_once NPBN_COOKIE_CONSENT_DIR . 'includes/class-npbn-cookie-consent.php';
	NPBN_Cookie_Consent::instance();
}
add_action( 'plugins_loaded', 'npbn_cookie_consent_init' );

/**
 * Activation hook — set defaults.
 */
function npbn_cookie_consent_activate() {
	$defaults = array(
		'banner_heading'            => 'เว็บไซต์นี้ใช้คุกกี้',
		'banner_text'               => 'เว็บไซต์นี้ใช้คุกกี้เพื่อปรับปรุงประสบการณ์การใช้งานของคุณ กรุณายอมรับหรือปฏิเสธคุกกี้ที่ไม่จำเป็น',
		'accept_text'               => 'ยอมรับทั้งหมด',
		'reject_text'               => 'ตั้งค่าคุกกี้',
		'reject_all_text'           => 'ปฏิเสธ',
		'settings_modal_title'      => 'ตั้งค่าคุกกี้',
		'save_preferences_text'     => 'บันทึกการตั้งค่า',
		'privacy_link_text'         => 'นโยบายความเป็นส่วนตัว',
		'privacy_url'               => '',
		'position'                  => 'bottom',
		'bg_color'                  => '#ffffff',
		'text_color'                => '#333333',
		'btn_accept_bg'             => '#16a34a',
		'btn_accept_text'           => '#ffffff',
		'cookie_expiry'             => 365,
		'show_settings_btn'         => '1',
		'category_desc_necessary'   => 'คุกกี้เหล่านี้จำเป็นสำหรับการทำงานของเว็บไซต์ ไม่สามารถปิดได้',
		'category_desc_functional'  => 'คุกกี้เหล่านี้ช่วยให้เว็บไซต์จดจำการตั้งค่าของคุณ เช่น ภาษาและภูมิภาค',
		'category_desc_analytics'   => 'คุกกี้เหล่านี้ช่วยให้เราเข้าใจวิธีการใช้งานเว็บไซต์ เพื่อปรับปรุงประสิทธิภาพ',
		'category_desc_marketing'   => 'คุกกี้เหล่านี้ใช้เพื่อแสดงโฆษณาที่เกี่ยวข้องกับคุณ',
	);

	if ( ! get_option( 'npbn_cookie_consent_settings' ) ) {
		add_option( 'npbn_cookie_consent_settings', $defaults );
	}

	// Create consent log table.
	global $wpdb;
	$table_name      = $wpdb->prefix . 'npbn_consent_log';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		consent_status varchar(20) NOT NULL,
		consent_categories varchar(500) DEFAULT NULL,
		ip_address varchar(45) NOT NULL DEFAULT '',
		user_agent text NOT NULL,
		page_url text NOT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY consent_status (consent_status),
		KEY created_at (created_at)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	update_option( 'npbn_cookie_consent_db_version', '1.3.0' );
}
register_activation_hook( __FILE__, 'npbn_cookie_consent_activate' );

/**
 * Deactivation hook.
 */
function npbn_cookie_consent_deactivate() {
	// Settings preserved for re-activation.
}
register_deactivation_hook( __FILE__, 'npbn_cookie_consent_deactivate' );
