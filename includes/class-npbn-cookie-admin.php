<?php
/**
 * Admin settings page.
 *
 * @package NPBN_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * NPBN_Cookie_Admin class.
 */
class NPBN_Cookie_Admin {

	/**
	 * Option key.
	 *
	 * @var string
	 */
	private $option_key = 'npbn_cookie_consent_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'handle_reset_consent' ) );
		add_action( 'admin_init', array( $this, 'handle_reset_defaults' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add menu pages.
	 */
	public function add_menu_pages() {
		add_menu_page(
			__( 'Cookie Consent', 'npbn-cookie-consent' ),
			__( 'Cookie Consent', 'npbn-cookie-consent' ),
			'manage_options',
			'npbn-cookie-consent',
			array( $this, 'render_settings_page' ),
			'dashicons-shield',
			81
		);

		add_submenu_page(
			'npbn-cookie-consent',
			__( 'Settings', 'npbn-cookie-consent' ),
			__( 'Settings', 'npbn-cookie-consent' ),
			'manage_options',
			'npbn-cookie-consent',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'npbn-cookie-consent',
			__( 'Consent Log', 'npbn-cookie-consent' ),
			__( 'Consent Log', 'npbn-cookie-consent' ),
			'manage_options',
			'npbn-cookie-consent-log',
			array( $this, 'render_log_page' )
		);

		add_submenu_page(
			'npbn-cookie-consent',
			__( 'Cookie Scanner', 'npbn-cookie-consent' ),
			__( 'Cookie Scanner', 'npbn-cookie-consent' ),
			'manage_options',
			'npbn-cookie-consent-scanner',
			array( $this, 'render_scanner_page' )
		);
	}

	/**
	 * Enqueue admin assets on our pages only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		$our_pages = array(
			'toplevel_page_npbn-cookie-consent',
			'cookie-consent_page_npbn-cookie-consent-log',
			'cookie-consent_page_npbn-cookie-consent-scanner',
		);
		if ( ! in_array( $hook, $our_pages, true ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'npbn-cookie-admin',
			NPBN_COOKIE_CONSENT_URL . 'assets/js/cookie-admin.js',
			array( 'wp-color-picker' ),
			NPBN_COOKIE_CONSENT_VERSION,
			true
		);
		wp_enqueue_style(
			'npbn-cookie-admin',
			NPBN_COOKIE_CONSENT_URL . 'assets/css/cookie-admin.css',
			array( 'wp-color-picker' ),
			NPBN_COOKIE_CONSENT_VERSION
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			$this->option_key,
			$this->option_key,
			array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) )
		);

		// --- Section: Banner Content ---
		add_settings_section(
			'npbn_banner_content',
			__( 'Banner Content', 'npbn-cookie-consent' ),
			'__return_empty_string',
			'npbn-cookie-consent'
		);

		add_settings_field(
			'banner_heading',
			__( 'Banner Heading', 'npbn-cookie-consent' ),
			array( $this, 'render_text_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'banner_heading' )
		);

		add_settings_field(
			'banner_text',
			__( 'Banner Text', 'npbn-cookie-consent' ),
			array( $this, 'render_textarea_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'banner_text' )
		);

		add_settings_field(
			'accept_text',
			__( 'Accept Button Text', 'npbn-cookie-consent' ),
			array( $this, 'render_text_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'accept_text' )
		);

		add_settings_field(
			'reject_text',
			__( 'Settings Button Text', 'npbn-cookie-consent' ),
			array( $this, 'render_text_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'reject_text' )
		);

		add_settings_field(
			'reject_all_text',
			__( 'Reject All Text', 'npbn-cookie-consent' ),
			array( $this, 'render_text_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'reject_all_text' )
		);

		add_settings_field(
			'settings_modal_title',
			__( 'Settings Modal Title', 'npbn-cookie-consent' ),
			array( $this, 'render_text_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'settings_modal_title' )
		);

		add_settings_field(
			'save_preferences_text',
			__( 'Save Preferences Text', 'npbn-cookie-consent' ),
			array( $this, 'render_text_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'save_preferences_text' )
		);

		add_settings_field(
			'privacy_link_text',
			__( 'Privacy Policy Link Text', 'npbn-cookie-consent' ),
			array( $this, 'render_text_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'privacy_link_text' )
		);

		add_settings_field(
			'privacy_url',
			__( 'Privacy Policy URL', 'npbn-cookie-consent' ),
			array( $this, 'render_url_field' ),
			'npbn-cookie-consent',
			'npbn_banner_content',
			array( 'key' => 'privacy_url' )
		);

		// --- Section: Appearance ---
		add_settings_section(
			'npbn_appearance',
			__( 'Appearance', 'npbn-cookie-consent' ),
			'__return_empty_string',
			'npbn-cookie-consent'
		);

		add_settings_field(
			'position',
			__( 'Banner Position', 'npbn-cookie-consent' ),
			array( $this, 'render_select_field' ),
			'npbn-cookie-consent',
			'npbn_appearance',
			array(
				'key'     => 'position',
				'options' => array(
					'bottom' => __( 'Bottom Bar', 'npbn-cookie-consent' ),
					'top'    => __( 'Top Bar', 'npbn-cookie-consent' ),
					'modal'  => __( 'Floating Modal', 'npbn-cookie-consent' ),
				),
			)
		);

		add_settings_field(
			'bg_color',
			__( 'Background Color', 'npbn-cookie-consent' ),
			array( $this, 'render_color_field' ),
			'npbn-cookie-consent',
			'npbn_appearance',
			array( 'key' => 'bg_color' )
		);

		add_settings_field(
			'text_color',
			__( 'Text Color', 'npbn-cookie-consent' ),
			array( $this, 'render_color_field' ),
			'npbn-cookie-consent',
			'npbn_appearance',
			array( 'key' => 'text_color' )
		);

		add_settings_field(
			'btn_accept_bg',
			__( 'Accept Button Color', 'npbn-cookie-consent' ),
			array( $this, 'render_color_field' ),
			'npbn-cookie-consent',
			'npbn_appearance',
			array( 'key' => 'btn_accept_bg' )
		);

		add_settings_field(
			'btn_accept_text',
			__( 'Accept Button Text Color', 'npbn-cookie-consent' ),
			array( $this, 'render_color_field' ),
			'npbn-cookie-consent',
			'npbn_appearance',
			array( 'key' => 'btn_accept_text' )
		);

		// --- Section: Settings ---
		add_settings_section(
			'npbn_general_settings',
			__( 'Settings', 'npbn-cookie-consent' ),
			'__return_empty_string',
			'npbn-cookie-consent'
		);

		add_settings_field(
			'plugin_language',
			__( 'Language', 'npbn-cookie-consent' ),
			array( $this, 'render_select_field' ),
			'npbn-cookie-consent',
			'npbn_general_settings',
			array(
				'key'     => 'plugin_language',
				'options' => array(
					'th' => 'ไทย (Thai)',
					'en' => 'English',
				),
			)
		);

		add_settings_field(
			'cookie_expiry',
			__( 'Cookie Expiry (days)', 'npbn-cookie-consent' ),
			array( $this, 'render_number_field' ),
			'npbn-cookie-consent',
			'npbn_general_settings',
			array(
				'key' => 'cookie_expiry',
				'min' => 1,
				'max' => 730,
			)
		);

		add_settings_field(
			'show_settings_btn',
			__( 'Show "Cookie Settings" Button', 'npbn-cookie-consent' ),
			array( $this, 'render_checkbox_field' ),
			'npbn-cookie-consent',
			'npbn_general_settings',
			array(
				'key'         => 'show_settings_btn',
				'description' => __( 'Show a floating button so visitors can change their consent choice (recommended for PDPA compliance).', 'npbn-cookie-consent' ),
			)
		);

		add_settings_field(
			'show_reject_all_banner',
			__( 'Show "Reject All" on Banner', 'npbn-cookie-consent' ),
			array( $this, 'render_checkbox_field' ),
			'npbn-cookie-consent',
			'npbn_general_settings',
			array(
				'key'         => 'show_reject_all_banner',
				'description' => __( 'Show the Reject All button directly on the banner popup. It always appears in the settings modal.', 'npbn-cookie-consent' ),
			)
		);

		add_settings_field(
			'banner_full_width',
			__( 'Full Width Banner', 'npbn-cookie-consent' ),
			array( $this, 'render_checkbox_field' ),
			'npbn-cookie-consent',
			'npbn_appearance',
			array(
				'key'         => 'banner_full_width',
				'description' => __( 'Display the banner as an edge-to-edge bar instead of a floating card.', 'npbn-cookie-consent' ),
			)
		);

		add_settings_field(
			'backdrop_blur',
			__( 'Backdrop Blur', 'npbn-cookie-consent' ),
			array( $this, 'render_checkbox_field' ),
			'npbn-cookie-consent',
			'npbn_appearance',
			array(
				'key'         => 'backdrop_blur',
				'description' => __( 'Apply a blur effect on the background when the settings modal is open.', 'npbn-cookie-consent' ),
			)
		);

		// --- Section: Cookie Category Descriptions ---
		add_settings_section(
			'npbn_category_descriptions',
			__( 'Cookie Category Descriptions', 'npbn-cookie-consent' ),
			function () {
				printf(
					'<p class="description">%s</p>',
					esc_html__( 'Customize the description text shown for each cookie category in the settings modal.', 'npbn-cookie-consent' )
				);
			},
			'npbn-cookie-consent'
		);

		add_settings_field(
			'category_desc_necessary',
			__( 'Necessary', 'npbn-cookie-consent' ),
			array( $this, 'render_textarea_field' ),
			'npbn-cookie-consent',
			'npbn_category_descriptions',
			array( 'key' => 'category_desc_necessary' )
		);

		add_settings_field(
			'category_desc_functional',
			__( 'Functional', 'npbn-cookie-consent' ),
			array( $this, 'render_textarea_field' ),
			'npbn-cookie-consent',
			'npbn_category_descriptions',
			array( 'key' => 'category_desc_functional' )
		);

		add_settings_field(
			'category_desc_analytics',
			__( 'Analytics', 'npbn-cookie-consent' ),
			array( $this, 'render_textarea_field' ),
			'npbn-cookie-consent',
			'npbn_category_descriptions',
			array( 'key' => 'category_desc_analytics' )
		);

		add_settings_field(
			'category_desc_marketing',
			__( 'Marketing', 'npbn-cookie-consent' ),
			array( $this, 'render_textarea_field' ),
			'npbn-cookie-consent',
			'npbn_category_descriptions',
			array( 'key' => 'category_desc_marketing' )
		);
	}

	/**
	 * Sanitize all settings on save.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized values.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$sanitized['banner_heading']    = sanitize_text_field( $input['banner_heading'] ?? '' );
		$sanitized['banner_text']       = wp_kses_post( $input['banner_text'] ?? '' );
		$sanitized['accept_text']       = sanitize_text_field( $input['accept_text'] ?? '' );
		$sanitized['reject_text']       = sanitize_text_field( $input['reject_text'] ?? '' );
		$sanitized['privacy_link_text'] = sanitize_text_field( $input['privacy_link_text'] ?? '' );
		$sanitized['privacy_url']       = esc_url_raw( $input['privacy_url'] ?? '' );

		$valid_positions       = array( 'bottom', 'top', 'modal' );
		$sanitized['position'] = in_array( $input['position'] ?? '', $valid_positions, true )
			? $input['position']
			: 'bottom';

		$color_fields = array( 'bg_color', 'text_color', 'btn_accept_bg', 'btn_accept_text' );
		foreach ( $color_fields as $field ) {
			$sanitized[ $field ] = sanitize_hex_color( $input[ $field ] ?? '' );
		}

		$valid_languages                 = array( 'th', 'en' );
		$sanitized['plugin_language']    = in_array( $input['plugin_language'] ?? '', $valid_languages, true )
			? $input['plugin_language']
			: 'th';
		$sanitized['cookie_expiry']      = min( 730, max( 1, absint( $input['cookie_expiry'] ?? 365 ) ) );
		$sanitized['show_settings_btn']      = ! empty( $input['show_settings_btn'] ) ? '1' : '0';
		$sanitized['show_reject_all_banner'] = ! empty( $input['show_reject_all_banner'] ) ? '1' : '0';
		$sanitized['banner_full_width']      = ! empty( $input['banner_full_width'] ) ? '1' : '0';
		$sanitized['backdrop_blur']          = ! empty( $input['backdrop_blur'] ) ? '1' : '0';

		$sanitized['reject_all_text']       = sanitize_text_field( $input['reject_all_text'] ?? '' );
		$sanitized['settings_modal_title']  = sanitize_text_field( $input['settings_modal_title'] ?? '' );
		$sanitized['save_preferences_text'] = sanitize_text_field( $input['save_preferences_text'] ?? '' );

		$category_desc_fields = array(
			'category_desc_necessary',
			'category_desc_functional',
			'category_desc_analytics',
			'category_desc_marketing',
		);
		foreach ( $category_desc_fields as $field ) {
			$sanitized[ $field ] = sanitize_textarea_field( $input[ $field ] ?? '' );
		}

		return $sanitized;
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php $this->maybe_show_privacy_notice(); ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( $this->option_key );
				do_settings_sections( 'npbn-cookie-consent' );
				submit_button();
				?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Reset Consent', 'npbn-cookie-consent' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Clear your own consent cookie so the banner appears again on the frontend. Useful for testing.', 'npbn-cookie-consent' ); ?></p>
			<form method="post" style="margin-top:10px;">
				<?php wp_nonce_field( 'npbn_reset_consent_action' ); ?>
				<button type="submit" name="npbn_reset_consent" value="1" class="button">
					<?php esc_html_e( 'Reset My Consent', 'npbn-cookie-consent' ); ?>
				</button>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Reset Text to Default', 'npbn-cookie-consent' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Reset all text fields (banner, buttons, modal, category descriptions) back to their default values. Colors and other settings are not affected.', 'npbn-cookie-consent' ); ?></p>
			<form method="post" style="margin-top:10px;">
				<?php wp_nonce_field( 'npbn_reset_defaults_action' ); ?>
				<button type="submit" name="npbn_reset_defaults" value="1" class="button">
					<?php esc_html_e( 'Reset Text to Default', 'npbn-cookie-consent' ); ?>
				</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the "Reset My Consent" admin action.
	 */
	public function handle_reset_consent() {
		if ( ! isset( $_POST['npbn_reset_consent'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'npbn_reset_consent_action' );

		// Delete the consent cookie by setting it in the past.
		setcookie( 'npbn_cookie_consent', '', time() - 3600, '/' );
		unset( $_COOKIE['npbn_cookie_consent'] );

		add_settings_error(
			$this->option_key,
			'consent_reset',
			__( 'Your consent cookie has been reset. Visit the frontend to see the banner again.', 'npbn-cookie-consent' ),
			'success'
		);
	}

	/**
	 * Handle the "Reset Text to Default" admin action.
	 */
	public function handle_reset_defaults() {
		if ( ! isset( $_POST['npbn_reset_defaults'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'npbn_reset_defaults_action' );

		$settings = get_option( $this->option_key, array() );
		$lang     = $settings['plugin_language'] ?? 'th';
		$defaults = NPBN_Cookie_Consent::get_defaults( $lang );

		$text_keys = array(
			'banner_heading',
			'banner_text',
			'accept_text',
			'reject_text',
			'reject_all_text',
			'settings_modal_title',
			'save_preferences_text',
			'privacy_link_text',
			'category_desc_necessary',
			'category_desc_functional',
			'category_desc_analytics',
			'category_desc_marketing',
		);

		foreach ( $text_keys as $key ) {
			$settings[ $key ] = $defaults[ $key ] ?? '';
		}

		update_option( $this->option_key, $settings );

		add_settings_error(
			$this->option_key,
			'defaults_reset',
			__( 'All text fields have been reset to their default values.', 'npbn-cookie-consent' ),
			'success'
		);
	}

	/**
	 * Show a notice if no privacy policy page is configured.
	 */
	private function maybe_show_privacy_notice() {
		$settings    = get_option( $this->option_key, array() );
		$privacy_url = $settings['privacy_url'] ?? '';
		$wp_privacy  = (int) get_option( 'wp_page_for_privacy_policy' );

		if ( empty( $privacy_url ) && ! $wp_privacy ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'No privacy policy page is configured. Please set a Privacy Policy URL below, or configure one under Settings > Privacy.', 'npbn-cookie-consent' )
			);
		}
	}

	/**
	 * Render the consent log page with analytics and log table.
	 */
	public function render_log_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page     = 20;

		$stats_30  = NPBN_Cookie_Logger::get_stats( 30 );
		$stats_all = NPBN_Cookie_Logger::get_stats( 0 );
		$log       = NPBN_Cookie_Logger::get_log( $per_page, $current_page );
		$total_pages = ceil( $log['total'] / $per_page );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Consent Log', 'npbn-cookie-consent' ); ?></h1>

			<div class="npbn-stats-cards" style="display:flex;gap:16px;margin:20px 0;flex-wrap:wrap;">
				<?php
				$cards = array(
					array(
						'label' => __( 'Total (30 days)', 'npbn-cookie-consent' ),
						'value' => number_format_i18n( $stats_30['total'] ),
						'color' => '#2271b1',
					),
					array(
						'label' => __( 'Accepted (30 days)', 'npbn-cookie-consent' ),
						'value' => number_format_i18n( $stats_30['accepted'] ),
						'color' => '#16a34a',
					),
					array(
						'label' => __( 'Rejected (30 days)', 'npbn-cookie-consent' ),
						'value' => number_format_i18n( $stats_30['rejected'] ),
						'color' => '#dc2626',
					),
					array(
						'label' => __( 'Accept Rate (30 days)', 'npbn-cookie-consent' ),
						'value' => $stats_30['accept_rate'] . '%',
						'color' => '#9333ea',
					),
				);

				foreach ( $cards as $card ) :
					?>
					<div style="background:#fff;border:1px solid #c3c4c7;border-left:4px solid <?php echo esc_attr( $card['color'] ); ?>;border-radius:4px;padding:16px 20px;min-width:160px;flex:1;">
						<div style="font-size:28px;font-weight:700;color:<?php echo esc_attr( $card['color'] ); ?>;line-height:1.2;">
							<?php echo esc_html( $card['value'] ); ?>
						</div>
						<div style="font-size:13px;color:#50575e;margin-top:4px;">
							<?php echo esc_html( $card['label'] ); ?>
						</div>
					</div>
					<?php
				endforeach;
				?>
			</div>

			<p class="description" style="margin-bottom:12px;">
				<?php
				printf(
					/* translators: %s: total record count */
					esc_html__( 'All time: %1$s total, %2$s accepted, %3$s rejected, %4$s revoked', 'npbn-cookie-consent' ),
					'<strong>' . number_format_i18n( $stats_all['total'] ) . '</strong>',
					'<strong>' . number_format_i18n( $stats_all['accepted'] ) . '</strong>',
					'<strong>' . number_format_i18n( $stats_all['rejected'] ) . '</strong>',
					'<strong>' . number_format_i18n( $stats_all['revoked'] ) . '</strong>'
				);
				?>
			</p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:50px;"><?php esc_html_e( 'ID', 'npbn-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Status', 'npbn-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Categories', 'npbn-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'npbn-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Page', 'npbn-cookie-consent' ); ?></th>
						<th><?php esc_html_e( 'Date', 'npbn-cookie-consent' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $log['entries'] ) ) : ?>
						<tr>
							<td colspan="6"><?php esc_html_e( 'No consent records yet.', 'npbn-cookie-consent' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $log['entries'] as $entry ) : ?>
							<tr>
								<td><?php echo esc_html( $entry['id'] ); ?></td>
								<td>
									<?php
									$status_colors = array(
										'accepted' => '#16a34a',
										'rejected' => '#dc2626',
										'revoked'  => '#ea580c',
										'partial'  => '#d97706',
									);
									$color = $status_colors[ $entry['consent_status'] ] ?? '#666';
									printf(
										'<span style="color:%s;font-weight:600;">%s</span>',
										esc_attr( $color ),
										esc_html( ucfirst( $entry['consent_status'] ) )
									);
									?>
								</td>
								<td>
									<?php
									$cat_colors = array(
										'necessary'  => '#2271b1',
										'functional' => '#0e9f6e',
										'analytics'  => '#9333ea',
										'marketing'  => '#dc2626',
									);
									$cats_raw = $entry['consent_categories'] ?? '';
									if ( $cats_raw ) {
										$cats = json_decode( $cats_raw, true );
										if ( is_array( $cats ) ) {
											foreach ( $cats as $cat_key => $cat_val ) {
												$badge_color = $cat_colors[ $cat_key ] ?? '#666';
												$badge_bg    = $cat_val ? $badge_color : '#999';
												$icon        = $cat_val ? '&#10003;' : '&#10005;';
												printf(
													'<span style="display:inline-block;background:%s;color:#fff;font-size:11px;padding:1px 7px;border-radius:10px;margin:1px 2px;white-space:nowrap;">%s %s</span>',
													esc_attr( $badge_bg ),
													$icon,
													esc_html( ucfirst( $cat_key ) )
												);
											}
										}
									} else {
										echo '<span style="color:#999;">—</span>';
									}
									?>
								</td>
								<td><code><?php echo esc_html( $entry['ip_address'] ); ?></code></td>
								<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
									title="<?php echo esc_attr( $entry['page_url'] ); ?>">
									<?php echo esc_html( $entry['page_url'] ); ?>
								</td>
								<td><?php echo esc_html( $entry['created_at'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						echo paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $current_page,
								'total'     => $total_pages,
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the cookie scanner page.
	 */
	public function render_scanner_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$last_scan = NPBN_Cookie_Scanner::get_last_scan();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cookie Scanner', 'npbn-cookie-consent' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Scan your website to detect cookies and third-party scripts. The scanner will make a request to your homepage and analyze the response.', 'npbn-cookie-consent' ); ?>
			</p>

			<div style="margin:20px 0;display:flex;align-items:center;gap:12px;">
				<input type="url"
					   id="npbn-scan-url"
					   value="<?php echo esc_attr( home_url( '/' ) ); ?>"
					   class="regular-text"
					   placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>">
				<button type="button"
						id="npbn-scan-btn"
						class="button button-primary">
					<?php esc_html_e( 'Scan Now', 'npbn-cookie-consent' ); ?>
				</button>
				<span id="npbn-scan-spinner" class="spinner" style="float:none;"></span>
			</div>

			<?php if ( $last_scan ) : ?>
				<p class="description" style="margin-bottom:16px;">
					<?php
					printf(
						/* translators: 1: cookie count, 2: scan time */
						esc_html__( 'Last scan: %1$s cookies found on %2$s', 'npbn-cookie-consent' ),
						'<strong>' . esc_html( $last_scan['results']['count'] ?? 0 ) . '</strong>',
						'<strong>' . esc_html( $last_scan['time'] ) . '</strong>'
					);
					?>
				</p>
			<?php endif; ?>

			<div id="npbn-scan-results">
				<?php
				if ( $last_scan && ! empty( $last_scan['results']['cookies'] ) ) {
					$this->render_scan_table( $last_scan['results']['cookies'] );
				}
				?>
			</div>
		</div>

		<script>
		(function() {
			var btn = document.getElementById('npbn-scan-btn');
			var spinner = document.getElementById('npbn-scan-spinner');
			var results = document.getElementById('npbn-scan-results');
			var urlInput = document.getElementById('npbn-scan-url');

			if (!btn) return;

			btn.addEventListener('click', function() {
				btn.disabled = true;
				spinner.classList.add('is-active');
				results.innerHTML = '<p><?php echo esc_js( __( 'Scanning...', 'npbn-cookie-consent' ) ); ?></p>';

				var data = new FormData();
				data.append('action', 'npbn_scan_cookies');
				data.append('nonce', '<?php echo esc_js( wp_create_nonce( 'npbn_cookie_scanner_nonce' ) ); ?>');
				data.append('scan_url', urlInput.value);

				fetch(ajaxurl, { method: 'POST', body: data })
					.then(function(r) { return r.json(); })
					.then(function(resp) {
						btn.disabled = false;
						spinner.classList.remove('is-active');

						if (!resp.success) {
							results.innerHTML = '<div class="notice notice-error"><p>' + (resp.data || 'Scan failed.') + '</p></div>';
							return;
						}

						var d = resp.data;
						if (d.error) {
							results.innerHTML = '<div class="notice notice-error"><p>' + d.error + '</p></div>';
							return;
						}

						var html = '<p class="description" style="margin-bottom:16px;"><strong>' + d.count + '</strong> <?php echo esc_js( __( 'cookies detected.', 'npbn-cookie-consent' ) ); ?></p>';
						html += '<table class="wp-list-table widefat fixed striped">';
						html += '<thead><tr>';
						html += '<th><?php echo esc_js( __( 'Cookie Name', 'npbn-cookie-consent' ) ); ?></th>';
						html += '<th><?php echo esc_js( __( 'Category', 'npbn-cookie-consent' ) ); ?></th>';
						html += '<th><?php echo esc_js( __( 'Duration', 'npbn-cookie-consent' ) ); ?></th>';
						html += '<th><?php echo esc_js( __( 'Source', 'npbn-cookie-consent' ) ); ?></th>';
						html += '<th><?php echo esc_js( __( 'Description', 'npbn-cookie-consent' ) ); ?></th>';
						html += '</tr></thead><tbody>';

						var colors = {
							necessary: '#2271b1',
							functional: '#0e9f6e',
							analytics: '#9333ea',
							marketing: '#dc2626',
							unknown: '#6b7280'
						};
						var labels = {
							necessary: '<?php echo esc_js( __( 'Necessary', 'npbn-cookie-consent' ) ); ?>',
							functional: '<?php echo esc_js( __( 'Functional', 'npbn-cookie-consent' ) ); ?>',
							analytics: '<?php echo esc_js( __( 'Analytics', 'npbn-cookie-consent' ) ); ?>',
							marketing: '<?php echo esc_js( __( 'Marketing', 'npbn-cookie-consent' ) ); ?>',
							unknown: '<?php echo esc_js( __( 'Unknown', 'npbn-cookie-consent' ) ); ?>'
						};

						if (d.cookies.length === 0) {
							html += '<tr><td colspan="5"><?php echo esc_js( __( 'No cookies detected.', 'npbn-cookie-consent' ) ); ?></td></tr>';
						}

						for (var i = 0; i < d.cookies.length; i++) {
							var c = d.cookies[i];
							var cat = c.category || 'unknown';
							var color = colors[cat] || '#6b7280';
							var label = labels[cat] || cat;
							html += '<tr>';
							html += '<td><code>' + escHtml(c.name) + '</code></td>';
							html += '<td><span style="color:' + color + ';font-weight:600;">' + escHtml(label) + '</span></td>';
							html += '<td>' + escHtml(c.duration || '-') + '</td>';
							html += '<td>' + escHtml(c.source === 'script' ? '<?php echo esc_js( __( 'Script (detected)', 'npbn-cookie-consent' ) ); ?>' : 'HTTP Header') + '</td>';
							html += '<td>' + escHtml(c.description || '-') + '</td>';
							html += '</tr>';
						}

						html += '</tbody></table>';
						results.innerHTML = html;

						// Reload to update "last scan" text.
					})
					.catch(function() {
						btn.disabled = false;
						spinner.classList.remove('is-active');
						results.innerHTML = '<div class="notice notice-error"><p><?php echo esc_js( __( 'Network error. Please try again.', 'npbn-cookie-consent' ) ); ?></p></div>';
					});
			});

			function escHtml(s) {
				var div = document.createElement('div');
				div.appendChild(document.createTextNode(s || ''));
				return div.innerHTML;
			}
		})();
		</script>
		<?php
	}

	/**
	 * Render the scan results table (for initial page load from saved results).
	 *
	 * @param array $cookies Cookie data.
	 */
	private function render_scan_table( $cookies ) {
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Cookie Name', 'npbn-cookie-consent' ); ?></th>
					<th><?php esc_html_e( 'Category', 'npbn-cookie-consent' ); ?></th>
					<th><?php esc_html_e( 'Duration', 'npbn-cookie-consent' ); ?></th>
					<th><?php esc_html_e( 'Source', 'npbn-cookie-consent' ); ?></th>
					<th><?php esc_html_e( 'Description', 'npbn-cookie-consent' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $cookies as $cookie ) : ?>
					<tr>
						<td><code><?php echo esc_html( $cookie['name'] ); ?></code></td>
						<td>
							<span style="color:<?php echo esc_attr( NPBN_Cookie_Scanner::get_category_color( $cookie['category'] ) ); ?>;font-weight:600;">
								<?php echo esc_html( NPBN_Cookie_Scanner::get_category_label( $cookie['category'] ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $cookie['duration'] ?: '-' ); ?></td>
						<td><?php echo 'script' === $cookie['source'] ? esc_html__( 'Script (detected)', 'npbn-cookie-consent' ) : 'HTTP Header'; ?></td>
						<td><?php echo esc_html( $cookie['description'] ?: '-' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	// ------------------------------------------------------------------
	// Field renderers.
	// ------------------------------------------------------------------

	/**
	 * Get a setting value.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	private function get_value( $key ) {
		$settings = get_option( $this->option_key, array() );
		$value    = $settings[ $key ] ?? '';
		if ( '' === $value ) {
			$defaults = NPBN_Cookie_Consent::get_defaults();
			return $defaults[ $key ] ?? '';
		}
		return $value;
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array $args Field args.
	 */
	public function render_textarea_field( $args ) {
		$key   = $args['key'];
		$value = $this->get_value( $key );
		printf(
			'<textarea name="%s[%s]" id="npbn-%s" rows="4" class="large-text">%s</textarea>',
			esc_attr( $this->option_key ),
			esc_attr( $key ),
			esc_attr( $key ),
			esc_textarea( $value )
		);
	}

	/**
	 * Render a text input field.
	 *
	 * @param array $args Field args.
	 */
	public function render_text_field( $args ) {
		$key   = $args['key'];
		$value = $this->get_value( $key );
		printf(
			'<input type="text" name="%s[%s]" id="npbn-%s" value="%s" class="regular-text">',
			esc_attr( $this->option_key ),
			esc_attr( $key ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}

	/**
	 * Render a URL input field.
	 *
	 * @param array $args Field args.
	 */
	public function render_url_field( $args ) {
		$key   = $args['key'];
		$value = $this->get_value( $key );
		printf(
			'<input type="url" name="%s[%s]" id="npbn-%s" value="%s" class="regular-text" placeholder="https://">',
			esc_attr( $this->option_key ),
			esc_attr( $key ),
			esc_attr( $key ),
			esc_attr( $value )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Leave empty to use the WordPress privacy policy page.', 'npbn-cookie-consent' )
		);
	}

	/**
	 * Render a select field.
	 *
	 * @param array $args Field args with 'options' array.
	 */
	public function render_select_field( $args ) {
		$key     = $args['key'];
		$value   = $this->get_value( $key );
		$options = $args['options'];

		printf(
			'<select name="%s[%s]" id="npbn-%s">',
			esc_attr( $this->option_key ),
			esc_attr( $key ),
			esc_attr( $key )
		);

		foreach ( $options as $opt_value => $opt_label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $opt_value ),
				selected( $value, $opt_value, false ),
				esc_html( $opt_label )
			);
		}

		echo '</select>';
	}

	/**
	 * Render a color picker field.
	 *
	 * @param array $args Field args.
	 */
	public function render_color_field( $args ) {
		$key   = $args['key'];
		$value = $this->get_value( $key );
		printf(
			'<input type="text" name="%s[%s]" id="npbn-%s" value="%s" class="npbn-color-picker">',
			esc_attr( $this->option_key ),
			esc_attr( $key ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}

	/**
	 * Render a number input field.
	 *
	 * @param array $args Field args with 'min' and 'max'.
	 */
	public function render_number_field( $args ) {
		$key   = $args['key'];
		$value = $this->get_value( $key );
		$min   = $args['min'] ?? 1;
		$max   = $args['max'] ?? 730;
		printf(
			'<input type="number" name="%s[%s]" id="npbn-%s" value="%s" min="%d" max="%d" class="small-text">',
			esc_attr( $this->option_key ),
			esc_attr( $key ),
			esc_attr( $key ),
			esc_attr( $value ),
			$min,
			$max
		);
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array $args Field args.
	 */
	public function render_checkbox_field( $args ) {
		$key         = $args['key'];
		$value       = $this->get_value( $key );
		$description = $args['description'] ?? '';

		$checked = ( '1' === $value ) ? 'checked' : '';

		printf(
			'<label><input type="checkbox" name="%s[%s]" id="npbn-%s" value="1" %s> %s</label>',
			esc_attr( $this->option_key ),
			esc_attr( $key ),
			esc_attr( $key ),
			$checked,
			esc_html( $description )
		);
	}
}
