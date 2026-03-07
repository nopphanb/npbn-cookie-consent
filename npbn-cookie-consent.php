<?php
/**
 * Plugin Name: NPBN Cookie Consent
 * Plugin URI:  https://npbn.me
 * Description: PDPA-compliant cookie consent banner. Auto-blocks third-party tracking scripts until consent is given.
 * Version:     1.5.0
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

define( 'NPBN_COOKIE_CONSENT_VERSION', '1.5.0' );
define( 'NPBN_COOKIE_CONSENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'NPBN_COOKIE_CONSENT_URL', plugin_dir_url( __FILE__ ) );
define( 'NPBN_COOKIE_CONSENT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize the plugin.
 */
function npbn_cookie_consent_init() {
	load_plugin_textdomain( 'npbn-cookie-consent', false, dirname( NPBN_COOKIE_CONSENT_BASENAME ) . '/languages' );

	// Ensure DB table exists (handles upgrades without re-activation).
	if ( get_option( 'npbn_cookie_consent_db_version' ) !== '1.5.0' ) {
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
		'banner_heading'            => 'เราใช้คุกกี้',
		'banner_text'               => 'เว็บไซต์นี้ใช้คุกกี้เพื่อให้เว็บไซต์ทำงานได้อย่างถูกต้อง จดจำการตั้งค่าของคุณ วิเคราะห์การใช้งานเว็บไซต์ และสนับสนุนการนำเสนอเนื้อหาและโฆษณาที่เหมาะสมกับความสนใจของคุณ คุณสามารถเลือกยอมรับทั้งหมด ปฏิเสธคุกกี้ที่ไม่จำเป็น หรือปรับแต่งการตั้งค่าได้ตามต้องการ',
		'accept_text'               => 'ยอมรับทั้งหมด',
		'reject_text'               => 'ตั้งค่าคุกกี้',
		'reject_all_text'           => 'ปฏิเสธคุกกี้ที่ไม่จำเป็น',
		'settings_modal_title'      => 'ตั้งค่าคุกกี้',
		'save_preferences_text'     => 'บันทึกการตั้งค่า',
		'privacy_link_text'         => 'นโยบายคุกกี้',
		'privacy_url'               => '',
		'position'                  => 'bottom',
		'bg_color'                  => '#ffffff',
		'text_color'                => '#333333',
		'btn_accept_bg'             => '#16a34a',
		'btn_accept_text'           => '#ffffff',
		'cookie_expiry'             => 365,
		'show_settings_btn'         => '1',
		'category_desc_necessary'   => 'คุกกี้ประเภทนี้จำเป็นต่อการทำงานพื้นฐานของเว็บไซต์ ทำให้คุณสามารถใช้งานฟังก์ชันหลักต่าง ๆ ได้ เช่น การเข้าสู่ระบบ การรักษาความปลอดภัยของเว็บไซต์ การจดจำการตั้งค่าความเป็นส่วนตัว หรือการส่งแบบฟอร์ม หากไม่มีคุกกี้ประเภทนี้ เว็บไซต์อาจไม่สามารถทำงานได้อย่างถูกต้อง',
		'category_desc_functional'  => 'คุกกี้ประเภทนี้ช่วยให้เว็บไซต์จดจำข้อมูลที่คุณเลือกไว้ เพื่อให้การใช้งานสะดวกและเหมาะสมกับคุณมากขึ้น เช่น ภาษา พื้นที่ให้บริการ หรือการตั้งค่าบางอย่างบนเว็บไซต์',
		'category_desc_analytics'   => 'คุกกี้ประเภทนี้ช่วยให้เราเข้าใจวิธีที่ผู้ใช้งานเข้ามาใช้งานเว็บไซต์ เช่น หน้าที่มีผู้เข้าชมมาก ระยะเวลาในการเข้าชม แหล่งที่มาของผู้เข้าชม หรือพฤติกรรมการใช้งานโดยรวม ข้อมูลดังกล่าวจะช่วยให้เราปรับปรุงเว็บไซต์ เนื้อหา และประสิทธิภาพการใช้งานให้ดียิ่งขึ้น',
		'category_desc_marketing'   => 'คุกกี้ประเภทนี้ใช้เพื่อบันทึกพฤติกรรมการเข้าชมเว็บไซต์ของคุณ เพื่อนำไปใช้ในการนำเสนอเนื้อหา โปรโมชั่น หรือโฆษณาที่สอดคล้องกับความสนใจของคุณมากขึ้น รวมถึงใช้วัดผลประสิทธิภาพของแคมเปญโฆษณาบนแพลตฟอร์มต่าง ๆ',
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

	update_option( 'npbn_cookie_consent_db_version', '1.5.0' );
}
register_activation_hook( __FILE__, 'npbn_cookie_consent_activate' );

/**
 * Deactivation hook.
 */
function npbn_cookie_consent_deactivate() {
	// Settings preserved for re-activation.
}
register_deactivation_hook( __FILE__, 'npbn_cookie_consent_deactivate' );
