<?php
/**
 * Core plugin class.
 *
 * @package NPBN_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main NPBN_Cookie_Consent class.
 */
final class NPBN_Cookie_Consent {

	/**
	 * Singleton instance.
	 *
	 * @var NPBN_Cookie_Consent|null
	 */
	private static $instance = null;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Get singleton instance.
	 *
	 * @return NPBN_Cookie_Consent
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings = get_option( 'npbn_cookie_consent_settings', array() );
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once NPBN_COOKIE_CONSENT_DIR . 'includes/class-npbn-cookie-blocker.php';
		new NPBN_Cookie_Blocker();

		require_once NPBN_COOKIE_CONSENT_DIR . 'includes/class-npbn-cookie-logger.php';
		new NPBN_Cookie_Logger();

		require_once NPBN_COOKIE_CONSENT_DIR . 'includes/class-npbn-cookie-scanner.php';
		new NPBN_Cookie_Scanner();

		if ( is_admin() ) {
			require_once NPBN_COOKIE_CONSENT_DIR . 'includes/class-npbn-cookie-admin.php';
			new NPBN_Cookie_Admin();

			require_once NPBN_COOKIE_CONSENT_DIR . 'includes/class-npbn-cookie-updater.php';
			new NPBN_Cookie_Updater();
		}
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		if ( ! is_admin() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_head', array( $this, 'inline_styles' ), 99 );
			add_action( 'wp_footer', array( $this, 'render_banner' ), 99 );
			add_filter( 'script_loader_tag', array( $this, 'exclude_from_optimizers' ), 10, 2 );
		}

		add_shortcode( 'npbn_cookie_settings', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Add attributes to prevent caching/optimization plugins from deferring or delaying our script.
	 *
	 * Compatible with: FlyingPress, WP Rocket, LiteSpeed Cache, Autoptimize, SG Optimizer, etc.
	 *
	 * @param string $tag    Script HTML tag.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function exclude_from_optimizers( $tag, $handle ) {
		if ( 'npbn-cookie-banner' !== $handle ) {
			return $tag;
		}

		// Attributes recognized by popular optimization plugins.
		$attrs = array(
			'data-no-optimize="1"',   // FlyingPress, Autoptimize, LiteSpeed Cache.
			'data-no-defer="1"',      // FlyingPress, WP Rocket.
			'data-no-minify="1"',     // WP Rocket, SG Optimizer.
			'data-cfasync="false"',   // Cloudflare Rocket Loader.
		);

		return str_replace( '<script ', '<script ' . implode( ' ', $attrs ) . ' ', $tag );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'npbn-cookie-banner',
			NPBN_COOKIE_CONSENT_URL . 'assets/css/cookie-banner.css',
			array(),
			NPBN_COOKIE_CONSENT_VERSION
		);

		wp_enqueue_script(
			'npbn-cookie-banner',
			NPBN_COOKIE_CONSENT_URL . 'assets/js/cookie-banner.js',
			array(),
			NPBN_COOKIE_CONSENT_VERSION,
			false // Load in <head> so MutationObserver starts early.
		);

		wp_localize_script(
			'npbn-cookie-banner',
			'npbnCookieConsent',
			array(
				'expiryDays'       => intval( $this->get_setting( 'cookie_expiry' ) ),
				'blockedDomains'   => NPBN_Cookie_Blocker::get_blocked_domains(),
				'domainCategories' => NPBN_Cookie_Blocker::get_domain_categories(),
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'npbn_cookie_consent_nonce' ),
				'showSettingsBtn'  => $this->get_setting( 'show_settings_btn' ) !== '0',
				'categories'       => array(
					'necessary'  => array(
						'label'       => 'en' === $this->get_setting( 'plugin_language' ) ? 'Strictly Necessary' : 'คุกกี้ที่จำเป็นอย่างยิ่ง',
						'description' => $this->get_setting( 'category_desc_necessary' ),
						'required'    => true,
					),
					'functional' => array(
						'label'       => 'en' === $this->get_setting( 'plugin_language' ) ? 'Functional' : 'คุกกี้เพื่อการตั้งค่า',
						'description' => $this->get_setting( 'category_desc_functional' ),
						'required'    => false,
					),
					'analytics'  => array(
						'label'       => 'en' === $this->get_setting( 'plugin_language' ) ? 'Analytics' : 'คุกกี้เพื่อการวิเคราะห์และวัดผล',
						'description' => $this->get_setting( 'category_desc_analytics' ),
						'required'    => false,
					),
					'marketing'  => array(
						'label'       => 'en' === $this->get_setting( 'plugin_language' ) ? 'Marketing' : 'คุกกี้เพื่อการตลาด',
						'description' => $this->get_setting( 'category_desc_marketing' ),
						'required'    => false,
					),
				),
				'i18n'             => array(
					'modalTitle'      => $this->get_setting( 'settings_modal_title' ),
					'savePreferences' => $this->get_setting( 'save_preferences_text' ),
					'acceptAll'       => $this->get_setting( 'accept_text' ),
					'rejectAll'       => $this->get_setting( 'reject_all_text' ),
					'alwaysOn'        => 'en' === $this->get_setting( 'plugin_language' ) ? 'Always On' : 'เปิดใช้งานเสมอ',
				),
			)
		);
	}

	/**
	 * Output inline CSS custom properties from settings.
	 */
	public function inline_styles() {
		$bg       = $this->get_setting( 'bg_color', '#ffffff' );
		$text     = $this->get_setting( 'text_color', '#333333' );
		$btn_bg   = $this->get_setting( 'btn_accept_bg', '#16a34a' );
		$btn_text = $this->get_setting( 'btn_accept_text', '#ffffff' );
		$blur     = $this->get_setting( 'backdrop_blur' );

		$blur_val = ( '1' === $blur ) ? 'blur(8px)' : 'none';

		printf(
			'<style id="npbn-cookie-consent-vars">#npbn-cookie-banner,#npbn-cookie-modal{--npbn-bg:%s;--npbn-text:%s;--npbn-btn-accept-bg:%s;--npbn-btn-accept-text:%s;--npbn-backdrop-blur:%s}</style>',
			esc_attr( $bg ),
			esc_attr( $text ),
			esc_attr( $btn_bg ),
			esc_attr( $btn_text ),
			esc_attr( $blur_val )
		);
	}

	/**
	 * Render the consent banner in the footer.
	 */
	public function render_banner() {
		$position          = $this->get_setting( 'position' );
		$banner_heading    = $this->get_setting( 'banner_heading' );
		$banner_text       = $this->get_setting( 'banner_text' );
		$accept_text       = $this->get_setting( 'accept_text' );
		$reject_text       = $this->get_setting( 'reject_text' );
		$reject_all_text   = $this->get_setting( 'reject_all_text' );
		$privacy_link_text = $this->get_setting( 'privacy_link_text' );
		$privacy_url       = $this->get_setting( 'privacy_url' );
		$modal_title       = $this->get_setting( 'settings_modal_title' );
		$save_text         = $this->get_setting( 'save_preferences_text' );
		$full_width        = $this->get_setting( 'banner_full_width' );

		// Fall back to WordPress privacy policy page.
		if ( empty( $privacy_url ) ) {
			$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
			if ( $privacy_page_id ) {
				$privacy_url = get_permalink( $privacy_page_id );
			}
		}

		// Validate position.
		$valid_positions = array( 'bottom', 'top', 'modal' );
		if ( ! in_array( $position, $valid_positions, true ) ) {
			$position = 'bottom';
		}

		// Banner (hidden by default via CSS opacity/visibility, JS adds --visible class to fade in).
		?>
		<?php
		$banner_classes = 'npbn-cookie-banner npbn-cookie-banner--' . esc_attr( $position );
		if ( '1' === $full_width ) {
			$banner_classes .= ' npbn-cookie-banner--full-width';
		}
		?>
		<div id="npbn-cookie-banner"
			 class="<?php echo esc_attr( $banner_classes ); ?>"
			 role="dialog"
			 aria-modal="false"
			 aria-label="<?php esc_attr_e( 'Cookie consent', 'npbn-cookie-consent' ); ?>"
			 aria-describedby="npbn-cookie-message"
			 tabindex="-1">
			<div class="npbn-cookie-banner__inner">
				<div class="npbn-cookie-banner__content">
					<?php if ( $banner_heading ) : ?>
						<p class="npbn-cookie-banner__heading"><?php echo esc_html( $banner_heading ); ?></p>
					<?php endif; ?>
					<p id="npbn-cookie-message" class="npbn-cookie-banner__text">
						<?php echo wp_kses_post( $banner_text ); ?>
						<?php if ( $privacy_url ) : ?>
							<a href="<?php echo esc_url( $privacy_url ); ?>"
							   class="npbn-cookie-banner__link"
							   target="_blank"
							   rel="noopener noreferrer">
								<?php echo esc_html( $privacy_link_text ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
				<div class="npbn-cookie-banner__actions">
					<?php if ( $this->get_setting( 'show_reject_all_banner' ) !== '0' ) : ?>
						<button type="button"
								id="npbn-cookie-reject-all"
								class="npbn-cookie-banner__btn npbn-cookie-banner__btn--reject">
							<?php echo esc_html( $reject_all_text ); ?>
						</button>
					<?php endif; ?>
					<button type="button"
							id="npbn-cookie-settings"
							class="npbn-cookie-banner__btn npbn-cookie-banner__btn--settings">
						<?php echo esc_html( $reject_text ); ?>
					</button>
					<button type="button"
							id="npbn-cookie-accept"
							class="npbn-cookie-banner__btn npbn-cookie-banner__btn--accept">
						<?php echo esc_html( $accept_text ); ?>
					</button>
				</div>
			</div>
		</div>

		<?php // Granular settings modal (hidden by default, JS shows on demand). ?>
		<div id="npbn-cookie-modal"
			 class="npbn-cookie-modal"
			 role="dialog"
			 aria-modal="true"
			 aria-label="<?php echo esc_attr( $modal_title ); ?>"
			 tabindex="-1">
			<div class="npbn-cookie-modal__overlay"></div>
			<div class="npbn-cookie-modal__dialog">
				<div class="npbn-cookie-modal__header">
					<h2 class="npbn-cookie-modal__title"><?php echo esc_html( $modal_title ); ?></h2>
					<button type="button"
							class="npbn-cookie-modal__close"
							aria-label="<?php esc_attr_e( 'Close', 'npbn-cookie-consent' ); ?>">&times;</button>
				</div>
				<div class="npbn-cookie-modal__body" id="npbn-cookie-modal-body">
					<?php // Category toggles rendered by JS from localized data. ?>
				</div>
				<div class="npbn-cookie-modal__footer">
					<button type="button" id="npbn-modal-reject-all"
							class="npbn-cookie-banner__btn npbn-cookie-banner__btn--reject">
						<?php echo esc_html( $reject_all_text ); ?>
					</button>
					<button type="button" id="npbn-modal-save"
							class="npbn-cookie-banner__btn npbn-cookie-banner__btn--settings">
						<?php echo esc_html( $save_text ); ?>
					</button>
					<button type="button" id="npbn-modal-accept-all"
							class="npbn-cookie-banner__btn npbn-cookie-banner__btn--accept">
						<?php echo esc_html( $accept_text ); ?>
					</button>
				</div>
			</div>
		</div>

		<?php if ( $this->get_setting( 'show_settings_btn', '1' ) !== '0' ) : ?>
			<button type="button"
					id="npbn-cookie-settings-btn"
					class="npbn-cookie-settings-btn"
					aria-label="<?php esc_attr_e( 'Cookie settings', 'npbn-cookie-consent' ); ?>">
				<?php echo 'en' === $this->get_setting( 'plugin_language' ) ? esc_html( 'Manage Cookie Preferences' ) : esc_html( 'เปลี่ยนการตั้งค่าคุกกี้' ); ?>
			</button>
		<?php endif; ?>
		<?php
	}

	/**
	 * Shortcode [npbn_cookie_settings] — renders cookie category toggles inline on a page.
	 *
	 * @return string
	 */
	public function render_shortcode() {
		$accept_text     = $this->get_setting( 'accept_text' );
		$reject_all_text = $this->get_setting( 'reject_all_text' );
		$save_text       = $this->get_setting( 'save_preferences_text' );

		ob_start();
		?>
		<div id="npbn-cookie-shortcode" class="npbn-cookie-shortcode">
			<div class="npbn-cookie-shortcode__body" id="npbn-cookie-shortcode-body">
				<?php // Category toggles rendered by JS from localized data. ?>
			</div>
			<div class="npbn-cookie-shortcode__actions">
				<button type="button" id="npbn-shortcode-reject-all"
						class="npbn-cookie-banner__btn npbn-cookie-banner__btn--reject">
					<?php echo esc_html( $reject_all_text ); ?>
				</button>
				<button type="button" id="npbn-shortcode-save"
						class="npbn-cookie-banner__btn npbn-cookie-banner__btn--settings">
					<?php echo esc_html( $save_text ); ?>
				</button>
				<button type="button" id="npbn-shortcode-accept-all"
						class="npbn-cookie-banner__btn npbn-cookie-banner__btn--accept">
					<?php echo esc_html( $accept_text ); ?>
				</button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Default values for all settings.
	 *
	 * @return array
	 */
	public static function get_defaults( $lang = null ) {
		if ( null === $lang ) {
			$settings = get_option( 'npbn_cookie_consent_settings', array() );
			$lang     = $settings['plugin_language'] ?? 'th';
		}

		$shared = array(
			'plugin_language'        => 'th',
			'privacy_url'            => '',
			'position'               => 'bottom',
			'bg_color'               => '#ffffff',
			'text_color'             => '#333333',
			'btn_accept_bg'          => '#16a34a',
			'btn_accept_text'        => '#ffffff',
			'cookie_expiry'          => 365,
			'show_settings_btn'      => '1',
			'show_reject_all_banner' => '1',
			'backdrop_blur'          => '0',
			'banner_full_width'      => '0',
		);

		$text = array(
			'th' => array(
				'banner_heading'           => 'เราใช้คุกกี้',
				'banner_text'              => 'เว็บไซต์นี้ใช้คุกกี้เพื่อให้เว็บไซต์ทำงานได้อย่างถูกต้อง จดจำการตั้งค่าของคุณ วิเคราะห์การใช้งานเว็บไซต์ และสนับสนุนการนำเสนอเนื้อหาและโฆษณาที่เหมาะสมกับความสนใจของคุณ คุณสามารถเลือกยอมรับทั้งหมด ปฏิเสธคุกกี้ที่ไม่จำเป็น หรือปรับแต่งการตั้งค่าได้ตามต้องการ',
				'accept_text'              => 'ยอมรับทั้งหมด',
				'reject_text'              => 'ตั้งค่าคุกกี้',
				'reject_all_text'          => 'ปฏิเสธคุกกี้ที่ไม่จำเป็น',
				'settings_modal_title'     => 'ตั้งค่าคุกกี้',
				'save_preferences_text'    => 'บันทึกการตั้งค่า',
				'privacy_link_text'        => 'นโยบายคุกกี้',
				'category_desc_necessary'  => 'คุกกี้ประเภทนี้จำเป็นต่อการทำงานพื้นฐานของเว็บไซต์ ทำให้คุณสามารถใช้งานฟังก์ชันหลักต่าง ๆ ได้ เช่น การเข้าสู่ระบบ การรักษาความปลอดภัยของเว็บไซต์ การจดจำการตั้งค่าความเป็นส่วนตัว หรือการส่งแบบฟอร์ม หากไม่มีคุกกี้ประเภทนี้ เว็บไซต์อาจไม่สามารถทำงานได้อย่างถูกต้อง',
				'category_desc_functional' => 'คุกกี้ประเภทนี้ช่วยให้เว็บไซต์จดจำข้อมูลที่คุณเลือกไว้ เพื่อให้การใช้งานสะดวกและเหมาะสมกับคุณมากขึ้น เช่น ภาษา พื้นที่ให้บริการ หรือการตั้งค่าบางอย่างบนเว็บไซต์',
				'category_desc_analytics'  => 'คุกกี้ประเภทนี้ช่วยให้เราเข้าใจวิธีที่ผู้ใช้งานเข้ามาใช้งานเว็บไซต์ เช่น หน้าที่มีผู้เข้าชมมาก ระยะเวลาในการเข้าชม แหล่งที่มาของผู้เข้าชม หรือพฤติกรรมการใช้งานโดยรวม ข้อมูลดังกล่าวจะช่วยให้เราปรับปรุงเว็บไซต์ เนื้อหา และประสิทธิภาพการใช้งานให้ดียิ่งขึ้น',
				'category_desc_marketing'  => 'คุกกี้ประเภทนี้ใช้เพื่อบันทึกพฤติกรรมการเข้าชมเว็บไซต์ของคุณ เพื่อนำไปใช้ในการนำเสนอเนื้อหา โปรโมชั่น หรือโฆษณาที่สอดคล้องกับความสนใจของคุณมากขึ้น รวมถึงใช้วัดผลประสิทธิภาพของแคมเปญโฆษณาบนแพลตฟอร์มต่าง ๆ',
			),
			'en' => array(
				'banner_heading'           => 'We use cookies',
				'banner_text'              => 'This website uses cookies to ensure proper functionality, remember your preferences, analyze site usage, and support relevant content and advertising. You can accept all, reject non-essential cookies, or customize your preferences.',
				'accept_text'              => 'Accept All',
				'reject_text'              => 'Cookie Settings',
				'reject_all_text'          => 'Reject Non-Essential',
				'settings_modal_title'     => 'Cookie Settings',
				'save_preferences_text'    => 'Save Preferences',
				'privacy_link_text'        => 'Cookie Policy',
				'category_desc_necessary'  => 'These cookies are essential for the website to function properly. They enable core features such as login, security, privacy settings, and form submissions. Without these cookies, the website may not work correctly.',
				'category_desc_functional' => 'These cookies help the website remember your choices to provide a more convenient and personalized experience, such as language, region, or other site preferences.',
				'category_desc_analytics'  => 'These cookies help us understand how visitors use the website, such as which pages are most popular, visit duration, traffic sources, and overall usage patterns. This data helps us improve the website, content, and user experience.',
				'category_desc_marketing'  => 'These cookies track your browsing behavior to deliver content, promotions, or ads relevant to your interests, and to measure the effectiveness of advertising campaigns across various platforms.',
			),
		);

		$lang_text = $text[ $lang ] ?? $text['th'];

		return array_merge( $shared, $lang_text );
	}

	/**
	 * Get a setting value. Falls back to centralized defaults when empty.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Optional explicit default (overrides centralized defaults).
	 * @return mixed
	 */
	public function get_setting( $key, $default = null ) {
		if ( isset( $this->settings[ $key ] ) && '' !== $this->settings[ $key ] ) {
			return $this->settings[ $key ];
		}
		if ( null !== $default ) {
			return $default;
		}
		$defaults = self::get_defaults();
		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
	}
}
