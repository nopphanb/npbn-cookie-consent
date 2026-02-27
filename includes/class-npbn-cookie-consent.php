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
						'label'       => __( 'คุกกี้ที่จำเป็น', 'npbn-cookie-consent' ),
						'description' => $this->get_setting( 'category_desc_necessary' ),
						'required'    => true,
					),
					'functional' => array(
						'label'       => __( 'คุกกี้เพื่อการใช้งาน', 'npbn-cookie-consent' ),
						'description' => $this->get_setting( 'category_desc_functional' ),
						'required'    => false,
					),
					'analytics'  => array(
						'label'       => __( 'คุกกี้เพื่อการวิเคราะห์', 'npbn-cookie-consent' ),
						'description' => $this->get_setting( 'category_desc_analytics' ),
						'required'    => false,
					),
					'marketing'  => array(
						'label'       => __( 'คุกกี้เพื่อการตลาด', 'npbn-cookie-consent' ),
						'description' => $this->get_setting( 'category_desc_marketing' ),
						'required'    => false,
					),
				),
				'i18n'             => array(
					'modalTitle'      => $this->get_setting( 'settings_modal_title' ),
					'savePreferences' => $this->get_setting( 'save_preferences_text' ),
					'acceptAll'       => $this->get_setting( 'accept_text' ),
					'rejectAll'       => $this->get_setting( 'reject_all_text' ),
					'alwaysOn'        => __( 'เปิดใช้งานเสมอ', 'npbn-cookie-consent' ),
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

		printf(
			'<style id="npbn-cookie-consent-vars">#npbn-cookie-banner,#npbn-cookie-modal{--npbn-bg:%s;--npbn-text:%s;--npbn-btn-accept-bg:%s;--npbn-btn-accept-text:%s}</style>',
			esc_attr( $bg ),
			esc_attr( $text ),
			esc_attr( $btn_bg ),
			esc_attr( $btn_text )
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
		<div id="npbn-cookie-banner"
			 class="npbn-cookie-banner npbn-cookie-banner--<?php echo esc_attr( $position ); ?>"
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
				<?php echo esc_html__( 'ตั้งค่าคุกกี้', 'npbn-cookie-consent' ); ?>
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
	public static function get_defaults() {
		return array(
			'banner_heading'           => __( 'เว็บไซต์นี้ใช้คุกกี้', 'npbn-cookie-consent' ),
			'banner_text'              => __( 'เว็บไซต์นี้ใช้คุกกี้เพื่อปรับปรุงประสบการณ์การใช้งานของคุณ กรุณายอมรับหรือปฏิเสธคุกกี้ที่ไม่จำเป็น', 'npbn-cookie-consent' ),
			'accept_text'              => __( 'ยอมรับทั้งหมด', 'npbn-cookie-consent' ),
			'reject_text'              => __( 'ตั้งค่าคุกกี้', 'npbn-cookie-consent' ),
			'reject_all_text'          => __( 'ปฏิเสธ', 'npbn-cookie-consent' ),
			'settings_modal_title'     => __( 'ตั้งค่าคุกกี้', 'npbn-cookie-consent' ),
			'save_preferences_text'    => __( 'บันทึกการตั้งค่า', 'npbn-cookie-consent' ),
			'privacy_link_text'        => __( 'นโยบายความเป็นส่วนตัว', 'npbn-cookie-consent' ),
			'privacy_url'              => '',
			'position'                 => 'bottom',
			'bg_color'                 => '#ffffff',
			'text_color'               => '#333333',
			'btn_accept_bg'            => '#16a34a',
			'btn_accept_text'          => '#ffffff',
			'cookie_expiry'            => 365,
			'show_settings_btn'        => '1',
			'show_reject_all_banner'   => '1',
			'category_desc_necessary'  => __( 'คุกกี้เหล่านี้จำเป็นสำหรับการทำงานของเว็บไซต์ ไม่สามารถปิดได้', 'npbn-cookie-consent' ),
			'category_desc_functional' => __( 'คุกกี้เหล่านี้ช่วยให้เว็บไซต์จดจำการตั้งค่าของคุณ เช่น ภาษาและภูมิภาค', 'npbn-cookie-consent' ),
			'category_desc_analytics'  => __( 'คุกกี้เหล่านี้ช่วยให้เราเข้าใจวิธีการใช้งานเว็บไซต์ เพื่อปรับปรุงประสิทธิภาพ', 'npbn-cookie-consent' ),
			'category_desc_marketing'  => __( 'คุกกี้เหล่านี้ใช้เพื่อแสดงโฆษณาที่เกี่ยวข้องกับคุณ', 'npbn-cookie-consent' ),
		);
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
