<?php
/**
 * GitHub-based plugin updater.
 *
 * Checks the GitHub Releases API for new versions and hooks into the
 * WordPress plugin update system so the plugin can be updated from
 * the WordPress dashboard like any wp.org plugin.
 *
 * @package NPBN_Cookie_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * NPBN_Cookie_Updater class.
 */
class NPBN_Cookie_Updater {

	/**
	 * GitHub repository (owner/repo).
	 *
	 * @var string
	 */
	private $repo = 'nopphanb/npbn-cookie-consent';

	/**
	 * Plugin basename (e.g. npbn-cookie-consent/npbn-cookie-consent.php).
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Cached GitHub release data.
	 *
	 * @var object|null
	 */
	private $release = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->basename = NPBN_COOKIE_CONSENT_BASENAME;
		$this->version  = NPBN_COOKIE_CONSENT_VERSION;

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );

		// "Check for updates" link on the plugins page.
		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'add_check_update_link' ) );
		add_action( 'wp_ajax_npbn_check_update', array( $this, 'ajax_check_update' ) );
		add_action( 'admin_footer-plugins.php', array( $this, 'check_update_script' ) );
	}

	/**
	 * Fetch the latest release from GitHub.
	 *
	 * @return object|false Release object or false on failure.
	 */
	private function get_release() {
		if ( null !== $this->release ) {
			return $this->release;
		}

		$transient_key = 'npbn_cookie_github_release';
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			$this->release = $cached;
			return $this->release;
		}

		$url      = sprintf( 'https://api.github.com/repos/%s/releases/latest', $this->repo );
		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'NPBN-Cookie-Consent/' . $this->version,
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->release = false;
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body->tag_name ) ) {
			$this->release = false;
			return false;
		}

		// Cache for 6 hours.
		set_transient( $transient_key, $body, 6 * HOUR_IN_SECONDS );

		$this->release = $body;
		return $this->release;
	}

	/**
	 * Get the version number from the release tag (strips leading "v").
	 *
	 * @return string|false
	 */
	private function get_remote_version() {
		$release = $this->get_release();
		if ( ! $release ) {
			return false;
		}
		return ltrim( $release->tag_name, 'v' );
	}

	/**
	 * Get the zip download URL from the release.
	 *
	 * Prefers a .zip asset attached to the release; falls back to the
	 * auto-generated GitHub source zipball.
	 *
	 * @return string|false
	 */
	private function get_download_url() {
		$release = $this->get_release();
		if ( ! $release ) {
			return false;
		}

		// Check for an attached .zip asset first.
		if ( ! empty( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( preg_match( '/\.zip$/i', $asset->name ) ) {
					return $asset->browser_download_url;
				}
			}
		}

		// Fall back to GitHub's auto-generated zipball.
		return $release->zipball_url;
	}

	/**
	 * Hook into the update check transient.
	 *
	 * @param object $transient Update transient data.
	 * @return object
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_version = $this->get_remote_version();
		if ( ! $remote_version ) {
			return $transient;
		}

		if ( version_compare( $remote_version, $this->version, '>' ) ) {
			$transient->response[ $this->basename ] = (object) array(
				'slug'        => dirname( $this->basename ),
				'plugin'      => $this->basename,
				'new_version' => $remote_version,
				'url'         => sprintf( 'https://github.com/%s', $this->repo ),
				'package'     => $this->get_download_url(),
			);
		}

		return $transient;
	}

	/**
	 * Provide plugin info for the "View details" popup in the dashboard.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || dirname( $this->basename ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}

		$info                = new stdClass();
		$info->name          = 'NPBN Cookie Consent';
		$info->slug          = dirname( $this->basename );
		$info->version       = $this->get_remote_version();
		$info->author        = '<a href="https://npbn.me">Nopphan Bunnag</a>';
		$info->homepage      = sprintf( 'https://github.com/%s', $this->repo );
		$info->requires      = '5.8';
		$info->requires_php  = '7.4';
		$info->downloaded    = 0;
		$info->last_updated  = $release->published_at;
		$info->download_link = $this->get_download_url();

		$info->sections = array(
			'description' => 'PDPA-compliant cookie consent banner with granular per-category control.',
			'changelog'   => ! empty( $release->body ) ? wpautop( esc_html( $release->body ) ) : '',
		);

		return $info;
	}

	/**
	 * After install, ensure the plugin directory name is correct.
	 *
	 * GitHub zipballs extract to "owner-repo-hash/" — rename to match
	 * the expected plugin directory name.
	 *
	 * @param bool  $response   Installation response.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 * @param array $result     Installation result data.
	 * @return array
	 */
	public function post_install( $response, $hook_extra, $result ) {
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $result;
		}

		global $wp_filesystem;

		$proper_dir = WP_PLUGIN_DIR . '/' . dirname( $this->basename );
		$wp_filesystem->move( $result['destination'], $proper_dir );
		$result['destination'] = $proper_dir;

		// Re-activate the plugin.
		activate_plugin( $this->basename );

		return $result;
	}

	/**
	 * Add "Check for updates" action link on the plugins page.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function add_check_update_link( $links ) {
		$links['check_update'] = sprintf(
			'<a href="#" id="npbn-check-update" style="color:#2271b1;">%s</a>',
			esc_html__( 'Check for updates', 'npbn-cookie-consent' )
		);
		return $links;
	}

	/**
	 * AJAX handler — force-check GitHub for updates.
	 */
	public function ajax_check_update() {
		check_ajax_referer( 'npbn_check_update', 'nonce' );

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		// Clear cached release so we hit GitHub fresh.
		delete_transient( 'npbn_cookie_github_release' );
		$this->release = null;

		$remote_version = $this->get_remote_version();

		if ( ! $remote_version ) {
			wp_send_json_error( __( 'Could not reach GitHub.', 'npbn-cookie-consent' ) );
		}

		$has_update = version_compare( $remote_version, $this->version, '>' );

		// Also refresh the core update_plugins transient so WP shows the update row.
		if ( $has_update ) {
			$transient = get_site_transient( 'update_plugins' );
			if ( ! is_object( $transient ) ) {
				$transient = new stdClass();
			}
			if ( ! isset( $transient->checked ) ) {
				$transient->checked = array();
			}
			$transient->checked[ $this->basename ] = $this->version;
			$transient = $this->check_update( $transient );
			set_site_transient( 'update_plugins', $transient );
		}

		wp_send_json_success(
			array(
				'current'    => $this->version,
				'latest'     => $remote_version,
				'has_update' => $has_update,
			)
		);
	}

	/**
	 * Inline script for the "Check for updates" AJAX call on plugins.php.
	 */
	public function check_update_script() {
		$nonce = wp_create_nonce( 'npbn_check_update' );
		?>
		<script>
		(function(){
			var link = document.getElementById('npbn-check-update');
			if (!link) return;
			link.addEventListener('click', function(e){
				e.preventDefault();
				link.textContent = '<?php echo esc_js( __( 'Checking…', 'npbn-cookie-consent' ) ); ?>';
				link.style.pointerEvents = 'none';
				var xhr = new XMLHttpRequest();
				xhr.open('POST', ajaxurl);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onload = function(){
					try {
						var res = JSON.parse(xhr.responseText);
						if (res.success && res.data.has_update) {
							link.textContent = '<?php echo esc_js( __( 'Update available: v', 'npbn-cookie-consent' ) ); ?>' + res.data.latest;
							link.style.color = '#d63638';
							link.style.fontWeight = 'bold';
							link.style.pointerEvents = 'auto';
							link.addEventListener('click', function(){ location.reload(); });
						} else if (res.success) {
							link.textContent = '<?php echo esc_js( __( 'You have the latest version', 'npbn-cookie-consent' ) ); ?> (v' + res.data.current + ')';
							link.style.color = '#00a32a';
							setTimeout(function(){ link.textContent = '<?php echo esc_js( __( 'Check for updates', 'npbn-cookie-consent' ) ); ?>'; link.style.color = '#2271b1'; link.style.pointerEvents = 'auto'; }, 5000);
						} else {
							link.textContent = res.data || '<?php echo esc_js( __( 'Check failed', 'npbn-cookie-consent' ) ); ?>';
							link.style.color = '#d63638';
							setTimeout(function(){ link.textContent = '<?php echo esc_js( __( 'Check for updates', 'npbn-cookie-consent' ) ); ?>'; link.style.color = '#2271b1'; link.style.pointerEvents = 'auto'; }, 3000);
						}
					} catch(err) {
						link.textContent = '<?php echo esc_js( __( 'Check failed', 'npbn-cookie-consent' ) ); ?>';
						link.style.color = '#d63638';
					}
				};
				xhr.send('action=npbn_check_update&nonce=<?php echo esc_js( $nonce ); ?>');
			});
		})();
		</script>
		<?php
	}
}
