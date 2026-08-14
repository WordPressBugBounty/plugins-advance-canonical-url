<?php
/**
 * Canonical URL settings and frontend output.
 *
 * @package advance-canonical-url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'advance_canonical_url' ) ) {
	class advance_canonical_url {

		const OPTION_NAME = 'acu_options';

		/**
		 * Registers plugin hooks.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'acu_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'acu_settings_page_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'acu_admin_assets' ) );
			add_action( 'wp_ajax_acu_save_settings', array( $this, 'acu_ajax_save_settings' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( ACU_PLUGIN_FILE ), array( $this, 'acu_action_links' ) );

			remove_action( 'wp_head', 'rel_canonical' );
			add_action( 'wp_head', array( $this, 'acu_render_canonical' ) );
		}

		/**
		 * Returns the default settings.
		 *
		 * @return array
		 */
		public static function acu_default_options() {
			return array(
				'canonical_method' => 'basic',
				'query_strings'    => 'yes',
			);
		}

		/**
		 * Adds missing defaults without overwriting existing settings.
		 */
		public static function acu_activation() {
			$current = get_option( self::OPTION_NAME, array() );
			$current = is_array( $current ) ? $current : array();

			update_option( self::OPTION_NAME, wp_parse_args( $current, self::acu_default_options() ) );
		}

		/**
		 * Gets normalized plugin options.
		 *
		 * @return array
		 */
		public static function acu_get_options() {
			$options = get_option( self::OPTION_NAME, array() );

			return wp_parse_args( is_array( $options ) ? $options : array(), self::acu_default_options() );
		}

		/**
		 * Registers the settings page.
		 */
		public function acu_plugin_page() {
			add_options_page(
				esc_html__( 'Advance Canonical URL', 'advance-canonical-url' ),
				esc_html__( 'Canonical URL', 'advance-canonical-url' ),
				'manage_options',
				'advance_canonical_settings',
				array( $this, 'acu_settings_form' )
			);
		}

		/**
		 * Loads styles only on this plugin's settings screen.
		 *
		 * @param string $hook_suffix Current admin page hook.
		 */
		public function acu_admin_assets( $hook_suffix ) {
			if ( 'settings_page_advance_canonical_settings' !== $hook_suffix ) {
				return;
			}

			$style_path    = ACU_PLUGIN_DIR . 'css/acu_admin.css';
			$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : ACU_VERSION;

			wp_enqueue_style( 'acu-admin-styles', ACU_PLUGIN_URL . 'css/acu_admin.css', array(), $style_version );

			$script_path    = ACU_PLUGIN_DIR . 'js/acu_admin.js';
			$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : ACU_VERSION;

			wp_enqueue_script( 'acu-admin-script', ACU_PLUGIN_URL . 'js/acu_admin.js', array(), $script_version, true );
			wp_localize_script(
				'acu-admin-script',
				'acuAdmin',
				array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'acu_save_settings' ),
					'savingLabel'   => __( 'Saving...', 'advance-canonical-url' ),
					'savedLabel'    => __( 'Saved', 'advance-canonical-url' ),
					'fallbackLabel' => __( 'Using standard save...', 'advance-canonical-url' ),
					'support'       => array(
						'checkoutUrl'       => 'https://checkout.freemius.com/js/v1/',
						'productId'         => ACU_FREEMIUS_PRODUCT_ID,
						'publicKey'         => ACU_FREEMIUS_PUBLIC_KEY,
						'productName'       => 'Advance Canonical URL',
						'loadingLabel'      => __( 'Opening secure checkout...', 'advance-canonical-url' ),
						'unavailableLabel'  => __( 'Support checkout is temporarily unavailable. Please try again later.', 'advance-canonical-url' ),
						'thankYouLabel'     => __( 'Thank you for supporting Advance Canonical URL.', 'advance-canonical-url' ),
					),
				)
			);
		}

		/**
		 * Returns the voluntary one-time support plans.
		 *
		 * @return array
		 */
		public static function acu_get_support_plans() {
			return array(
				array(
					'id'          => ACU_FREEMIUS_SUPPORTER_PLAN_ID,
					'title'       => __( 'Supporter', 'advance-canonical-url' ),
					'amount'      => '$5',
					'description' => __( 'A small thank-you that helps keep the plugin maintained.', 'advance-canonical-url' ),
					'featured'    => true,
				),
				array(
					'id'          => ACU_FREEMIUS_BACKER_PLAN_ID,
					'title'       => __( 'Backer', 'advance-canonical-url' ),
					'amount'      => '$15',
					'description' => __( 'Back ongoing compatibility work, fixes, and improvements.', 'advance-canonical-url' ),
				),
				array(
					'id'          => ACU_FREEMIUS_SPONSOR_PLAN_ID,
					'title'       => __( 'Sponsor', 'advance-canonical-url' ),
					'amount'      => '$49',
					'description' => __( 'Sponsor continued development and long-term plugin quality.', 'advance-canonical-url' ),
				),
				array(
					'id'          => ACU_FREEMIUS_AGENCY_SPONSOR_PLAN_ID,
					'title'       => __( 'Agency Sponsor', 'advance-canonical-url' ),
					'amount'      => '$99',
					'description' => __( 'Fund dependable maintenance for the WordPress community.', 'advance-canonical-url' ),
				),
			);
		}

		/**
		 * Adds a direct settings link on the Plugins screen.
		 *
		 * @param array $links Existing action links.
		 * @return array
		 */
		public function acu_action_links( $links ) {
			$settings_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'options-general.php?page=advance_canonical_settings' ) ),
				esc_html__( 'Settings', 'advance-canonical-url' )
			);

			array_unshift( $links, $settings_link );

			return $links;
		}

		/**
		 * Registers plugin settings.
		 */
		public function acu_settings_page_init() {
			register_setting(
				'acu_option_group',
				self::OPTION_NAME,
				array(
					'type'              => 'array',
					'sanitize_callback' => array( $this, 'acu_sanitize_and_validate' ),
					'default'           => self::acu_default_options(),
				)
			);
		}

		/**
		 * Renders the settings screen.
		 */
		public function acu_settings_form() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$options          = self::acu_get_options();
			$is_advanced      = 'advance' === $options['canonical_method'];
			$removes_queries  = 'yes' === $options['query_strings'];
			$method_label     = $is_advanced ? __( 'Per-content overrides', 'advance-canonical-url' ) : __( 'Automatic', 'advance-canonical-url' );
			$query_label      = $removes_queries ? __( 'Removed', 'advance-canonical-url' ) : __( 'Preserved', 'advance-canonical-url' );
			$active_tab       = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$active_tab       = in_array( $active_tab, array( 'settings', 'more_plugins' ), true ) ? $active_tab : 'settings';
			$page_description = 'more_plugins' === $active_tab
				? __( 'Discover more focused WordPress tools from the same developer.', 'advance-canonical-url' )
				: __( 'Choose how canonical URLs are generated across your site.', 'advance-canonical-url' );
			?>
			<div class="wrap acu-admin-page">
				<h1 class="screen-reader-text"><?php esc_html_e( 'Advance Canonical URL', 'advance-canonical-url' ); ?></h1>
				<div class="acu-page-header">
					<div class="acu-brand-mark" aria-hidden="true"><?php echo acu_get_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div>
						<div class="acu-page-title" aria-hidden="true"><?php esc_html_e( 'Advance Canonical URL', 'advance-canonical-url' ); ?></div>
						<p><?php echo esc_html( $page_description ); ?></p>
					</div>
					<span class="acu-status-badge"><span aria-hidden="true"></span><?php esc_html_e( 'Canonical output active', 'advance-canonical-url' ); ?></span>
				</div>

				<nav class="acu-tabs" aria-label="<?php esc_attr_e( 'Plugin pages', 'advance-canonical-url' ); ?>">
					<a class="acu-tab <?php echo 'settings' === $active_tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=advance_canonical_settings&tab=settings' ) ); ?>" <?php echo 'settings' === $active_tab ? 'aria-current="page"' : ''; ?>>
						<?php echo acu_get_icon( 'sliders' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'Settings', 'advance-canonical-url' ); ?></span>
					</a>
					<a class="acu-tab <?php echo 'more_plugins' === $active_tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=advance_canonical_settings&tab=more_plugins' ) ); ?>" <?php echo 'more_plugins' === $active_tab ? 'aria-current="page"' : ''; ?>>
						<?php echo acu_get_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'More Plugins', 'advance-canonical-url' ); ?></span>
					</a>
				</nav>

				<?php if ( 'settings' === $active_tab ) : ?>
				<div class="acu-settings-layout">
					<form id="acu_form" class="acu-settings-form" method="post" action="options.php">
						<?php settings_fields( 'acu_option_group' ); ?>

						<section class="acu-settings-section" aria-labelledby="acu-strategy-title">
							<div class="acu-section-heading">
								<span class="acu-section-icon" aria-hidden="true"><?php echo acu_get_icon( 'route' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<div>
									<h2 id="acu-strategy-title"><?php esc_html_e( 'Canonical strategy', 'advance-canonical-url' ); ?></h2>
									<p><?php esc_html_e( 'Select the level of control you need.', 'advance-canonical-url' ); ?></p>
								</div>
							</div>

							<div class="acu-choice-grid">
								<label class="acu-choice <?php echo $is_advanced ? '' : 'is-selected'; ?>">
									<input type="radio" name="acu_options[canonical_method]" value="basic" <?php checked( $options['canonical_method'], 'basic' ); ?> />
									<span class="acu-choice-icon acu-choice-icon-automatic"><?php echo acu_get_icon( 'wand-sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="acu-choice-copy">
										<strong><?php esc_html_e( 'Automatic', 'advance-canonical-url' ); ?></strong>
										<small><?php esc_html_e( 'Generate a canonical URL from each public URL.', 'advance-canonical-url' ); ?></small>
									</span>
									<span class="acu-choice-check" aria-hidden="true"><?php echo acu_get_icon( 'check-ring' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								</label>

								<label class="acu-choice <?php echo $is_advanced ? 'is-selected' : ''; ?>">
									<input type="radio" name="acu_options[canonical_method]" value="advance" <?php checked( $options['canonical_method'], 'advance' ); ?> />
									<span class="acu-choice-icon acu-choice-icon-custom"><?php echo acu_get_icon( 'sliders' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="acu-choice-copy">
										<strong><?php esc_html_e( 'Custom control', 'advance-canonical-url' ); ?></strong>
										<small><?php esc_html_e( 'Enable optional overrides in each content editor.', 'advance-canonical-url' ); ?></small>
									</span>
									<span class="acu-choice-check" aria-hidden="true"><?php echo acu_get_icon( 'check-ring' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								</label>
							</div>
						</section>

						<section class="acu-settings-section acu-query-section" aria-labelledby="acu-query-title">
							<div class="acu-section-heading">
								<span class="acu-section-icon" aria-hidden="true"><?php echo acu_get_icon( 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<div>
									<h2 id="acu-query-title"><?php esc_html_e( 'Query parameters', 'advance-canonical-url' ); ?></h2>
									<p><?php esc_html_e( 'Keep tracking and filter parameters out of canonical URLs.', 'advance-canonical-url' ); ?></p>
								</div>
							</div>

							<label class="acu-toggle-row" for="acu-query-strings">
								<span>
									<strong><?php esc_html_e( 'Remove query parameters', 'advance-canonical-url' ); ?></strong>
									<small><?php esc_html_e( 'Recommended for stable, duplicate-free canonical URLs.', 'advance-canonical-url' ); ?></small>
								</span>
								<input type="hidden" name="acu_options[query_strings]" value="no" />
								<input class="acu-toggle-input" type="checkbox" id="acu-query-strings" name="acu_options[query_strings]" value="yes" <?php checked( $options['query_strings'], 'yes' ); ?> />
								<span class="acu-toggle" aria-hidden="true"></span>
							</label>
						</section>

						<div class="acu-form-actions">
							<button type="submit" class="button button-primary acu-save-button">
								<span class="acu-save-icon" aria-hidden="true"><?php echo acu_get_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="acu-button-label"><?php esc_html_e( 'Save Settings', 'advance-canonical-url' ); ?></span>
							</button>
						</div>
					</form>

					<aside
						class="acu-policy-panel"
						aria-labelledby="acu-policy-title"
						data-method-basic="<?php esc_attr_e( 'Automatic', 'advance-canonical-url' ); ?>"
						data-method-advance="<?php esc_attr_e( 'Per-content overrides', 'advance-canonical-url' ); ?>"
						data-query-yes="<?php esc_attr_e( 'Removed', 'advance-canonical-url' ); ?>"
						data-query-no="<?php esc_attr_e( 'Preserved', 'advance-canonical-url' ); ?>"
					>
						<h2 id="acu-policy-title"><?php esc_html_e( 'Current policy', 'advance-canonical-url' ); ?></h2>
						<dl>
							<div>
								<dt><?php esc_html_e( 'Strategy', 'advance-canonical-url' ); ?></dt>
								<dd id="acu-policy-method"><?php echo esc_html( $method_label ); ?></dd>
							</div>
							<div>
								<dt><?php esc_html_e( 'Query parameters', 'advance-canonical-url' ); ?></dt>
								<dd id="acu-policy-query"><?php echo esc_html( $query_label ); ?></dd>
							</div>
							<div>
								<dt><?php esc_html_e( 'Fallback', 'advance-canonical-url' ); ?></dt>
								<dd><?php esc_html_e( 'Automatic URL', 'advance-canonical-url' ); ?></dd>
							</div>
						</dl>
						<p class="acu-policy-note"><?php echo acu_get_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Empty custom fields always fall back to the automatic canonical URL.', 'advance-canonical-url' ); ?></p>
					</aside>
				</div>
				<?php else : ?>
					<?php $this->acu_render_more_plugins(); ?>
				<?php endif; ?>

				<?php $this->acu_render_support_checkout(); ?>
			</div>
			<?php
		}

		/**
		 * Renders the developer's other WordPress.org plugins.
		 */
		private function acu_render_more_plugins() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = array(
				array(
					'name'        => __( 'Server Info', 'advance-canonical-url' ),
					'description' => __( 'Inspect server, PHP, database, caching, and WordPress diagnostics from one admin dashboard.', 'advance-canonical-url' ),
					'icon'        => 'server',
					'slug'        => 'server-info',
					'file'        => 'server-info/server-info.php',
				),
				array(
					'name'        => __( 'MetaViewer - Debug Meta Data', 'advance-canonical-url' ),
					'description' => __( 'Inspect post, user, and term metadata while debugging WordPress content and integrations.', 'advance-canonical-url' ),
					'icon'        => 'eye',
					'slug'        => 'metaviewer-debug-meta-data',
					'file'        => 'metaviewer-debug-meta-data/metaviewer.php',
				),
				array(
					'name'        => __( 'Randomize Password', 'advance-canonical-url' ),
					'description' => __( 'Generate strong randomized passwords for account creation and password reset workflows.', 'advance-canonical-url' ),
					'icon'        => 'key',
					'slug'        => 'randomize-password',
					'file'        => 'randomize-password/randomize-password.php',
				),
				array(
					'name'        => __( 'Fusion Pricing Tables', 'advance-canonical-url' ),
					'description' => __( 'Build flexible Elementor pricing tables with 15 ready-made skins, billing toggles, ribbons, and responsive controls.', 'advance-canonical-url' ),
					'icon'        => 'grid',
					'slug'        => 'fusion-pricing-tables',
					'file'        => 'fusion-pricing-tables/pricing-grid.php',
				),
			);
			?>
			<section class="acu-more-plugins" aria-labelledby="acu-more-plugins-title">
				<header class="acu-more-plugins-header">
					<h2 id="acu-more-plugins-title"><?php esc_html_e( 'More Plugins by Usman Ali Qureshi', 'advance-canonical-url' ); ?></h2>
					<p><?php esc_html_e( 'Small, focused tools built for practical WordPress administration and development.', 'advance-canonical-url' ); ?></p>
				</header>

				<div class="acu-plugin-grid">
					<?php foreach ( $plugins as $plugin ) : ?>
						<?php
						$is_installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin['file'] );
						$is_active    = $is_installed && is_plugin_active( $plugin['file'] );
						$details_url  = 'https://wordpress.org/plugins/' . $plugin['slug'] . '/';
						?>
						<article class="acu-plugin-card">
							<div class="acu-plugin-card-heading">
								<span class="acu-plugin-icon" aria-hidden="true"><?php echo acu_get_icon( $plugin['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<div>
									<h3><?php echo esc_html( $plugin['name'] ); ?></h3>
									<?php if ( $is_active ) : ?>
										<span class="acu-plugin-status is-active"><?php esc_html_e( 'Active', 'advance-canonical-url' ); ?></span>
									<?php elseif ( $is_installed ) : ?>
										<span class="acu-plugin-status"><?php esc_html_e( 'Installed', 'advance-canonical-url' ); ?></span>
									<?php else : ?>
										<span class="acu-plugin-status"><?php esc_html_e( 'Available', 'advance-canonical-url' ); ?></span>
									<?php endif; ?>
								</div>
							</div>

							<p><?php echo esc_html( $plugin['description'] ); ?></p>

							<div class="acu-plugin-actions">
								<?php if ( $is_active ) : ?>
									<span class="button disabled" aria-disabled="true"><?php esc_html_e( 'Active', 'advance-canonical-url' ); ?></span>
								<?php elseif ( $is_installed ) : ?>
									<?php $activate_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin['file'] ) ), 'activate-plugin_' . $plugin['file'] ); ?>
									<a class="button button-primary" href="<?php echo esc_url( $activate_url ); ?>"><?php esc_html_e( 'Activate', 'advance-canonical-url' ); ?></a>
								<?php else : ?>
									<?php $install_url = wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $plugin['slug'] ) ), 'install-plugin_' . $plugin['slug'] ); ?>
									<a class="button button-primary" href="<?php echo esc_url( $install_url ); ?>"><?php esc_html_e( 'Install Now', 'advance-canonical-url' ); ?></a>
								<?php endif; ?>

								<a class="acu-plugin-details" href="<?php echo esc_url( $details_url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php esc_html_e( 'View details', 'advance-canonical-url' ); ?>
									<?php echo acu_get_icon( 'external-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
		}

		/**
		 * Renders the compact supporter launcher and plan chooser.
		 */
		private function acu_render_support_checkout() {
			$plans = self::acu_get_support_plans();
			?>
			<button type="button" class="acu-support-launcher" data-acu-support-open aria-haspopup="dialog" aria-controls="acu-support-panel">
				<?php echo acu_get_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'Support', 'advance-canonical-url' ); ?></span>
			</button>

			<div id="acu-support-panel" class="acu-support-panel" hidden aria-hidden="true">
				<div class="acu-support-card" role="dialog" aria-modal="true" aria-labelledby="acu-support-title" aria-describedby="acu-support-description">
					<button type="button" class="acu-support-close" data-acu-support-close aria-label="<?php esc_attr_e( 'Close support options', 'advance-canonical-url' ); ?>">
						<?php echo acu_get_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>

					<div class="acu-support-header">
						<span class="acu-support-header-icon" aria-hidden="true"><?php echo acu_get_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<div>
							<h2 id="acu-support-title"><?php esc_html_e( 'Support Advance Canonical URL', 'advance-canonical-url' ); ?></h2>
							<p id="acu-support-description"><?php esc_html_e( 'Choose any one-time amount that feels right. Every feature stays free for everyone.', 'advance-canonical-url' ); ?></p>
						</div>
					</div>

					<div class="acu-support-plans">
						<?php foreach ( $plans as $plan ) : ?>
							<button
								type="button"
								class="acu-support-plan <?php echo ! empty( $plan['featured'] ) ? 'is-featured' : ''; ?>"
								data-acu-support-plan="<?php echo esc_attr( $plan['id'] ); ?>"
							>
								<span class="acu-support-plan-name"><?php echo esc_html( $plan['title'] ); ?></span>
								<span class="acu-support-plan-price"><?php echo esc_html( $plan['amount'] ); ?></span>
								<span class="acu-support-plan-description"><?php echo esc_html( $plan['description'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>

					<p id="acu-support-message" class="acu-support-message" aria-live="polite"></p>
				</div>
			</div>
			<?php
		}

		/**
		 * Sanitizes plugin settings.
		 *
		 * @param mixed $input Submitted settings.
		 * @return array
		 */
		public function acu_sanitize_and_validate( $input ) {
			$defaults = self::acu_default_options();
			$input    = is_array( $input ) ? $input : array();
			$method   = isset( $input['canonical_method'] ) ? sanitize_key( $input['canonical_method'] ) : $defaults['canonical_method'];
			$queries  = isset( $input['query_strings'] ) ? sanitize_key( $input['query_strings'] ) : $defaults['query_strings'];

			if ( ! in_array( $method, array( 'basic', 'advance' ), true ) ) {
				$method = $defaults['canonical_method'];
				add_settings_error( self::OPTION_NAME, 'acu_invalid_method', __( 'The canonical strategy was reset because the submitted value was invalid.', 'advance-canonical-url' ) );
			}

			if ( ! in_array( $queries, array( 'yes', 'no' ), true ) ) {
				$queries = $defaults['query_strings'];
				add_settings_error( self::OPTION_NAME, 'acu_invalid_queries', __( 'The query parameter setting was reset because the submitted value was invalid.', 'advance-canonical-url' ) );
			}

			return array(
				'canonical_method' => $method,
				'query_strings'    => $queries,
			);
		}

		/**
		 * Saves settings without reloading the admin page.
		 */
		public function acu_ajax_save_settings() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to change these settings.', 'advance-canonical-url' ) ), 403 );
			}

			check_ajax_referer( 'acu_save_settings', 'nonce' );

			$input   = isset( $_POST[ self::OPTION_NAME ] ) ? map_deep( wp_unslash( $_POST[ self::OPTION_NAME ] ), 'sanitize_text_field' ) : array();
			$options = $this->acu_sanitize_and_validate( $input );

			update_option( self::OPTION_NAME, $options );

			wp_send_json_success(
				array(
					'message' => __( 'Saved', 'advance-canonical-url' ),
					'options' => $options,
				)
			);
		}

		/**
		 * Builds a canonical URL for the current request.
		 *
		 * @param bool $remove_query Whether query parameters should be removed.
		 * @return string
		 */
		private function acu_get_automatic_url( $remove_query ) {
			if ( $remove_query && is_singular() && function_exists( 'wp_get_canonical_url' ) ) {
				$canonical = wp_get_canonical_url( get_queried_object_id() );
				if ( ! empty( $canonical ) ) {
					return $canonical;
				}
			}

			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
			$request_path = wp_parse_url( $request_uri, PHP_URL_PATH );
			$request_path = is_string( $request_path ) && '' !== $request_path ? $request_path : '/';
			$home_path    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
			$home_path    = is_string( $home_path ) ? trailingslashit( $home_path ) : '/';
			$relative     = ltrim( $request_path, '/' );
			$home_prefix  = ltrim( $home_path, '/' );

			if ( '' !== $home_prefix && 0 === strpos( $relative, $home_prefix ) ) {
				$relative = substr( $relative, strlen( $home_prefix ) );
			}

			$canonical = home_url( '/' . $relative );

			if ( ! $remove_query ) {
				$query = wp_parse_url( $request_uri, PHP_URL_QUERY );
				if ( is_string( $query ) && '' !== $query ) {
					$canonical .= '?' . $query;
				}
			}

			return $canonical;
		}

		/**
		 * Checks whether a URL is an absolute HTTP(S) URL.
		 *
		 * @param string $url URL to validate.
		 * @return bool
		 */
		public static function acu_is_valid_canonical_url( $url ) {
			$parts = wp_parse_url( $url );

			return is_array( $parts )
				&& ! empty( $parts['host'] )
				&& ! empty( $parts['scheme'] )
				&& in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true );
		}

		/**
		 * Prints the canonical link for the current request.
		 */
		public function acu_render_canonical() {
			if ( is_admin() || is_404() || is_search() ) {
				return;
			}

			$options      = self::acu_get_options();
			$remove_query = 'yes' === $options['query_strings'];
			$canonical    = $this->acu_get_automatic_url( $remove_query );
			$source       = 'automatic';

			if ( 'advance' === $options['canonical_method'] && is_singular() ) {
				$custom = get_post_meta( get_queried_object_id(), '_acu_can_url_value', true );
				if ( is_string( $custom ) && self::acu_is_valid_canonical_url( $custom ) ) {
					$canonical = $custom;
					$source    = 'custom';
				}
			}

			/**
			 * Filters the canonical URL before output.
			 *
			 * @param string $canonical Canonical URL.
			 * @param string $source    Either automatic or custom.
			 */
			$canonical = apply_filters( 'acu_canonical_url', $canonical, $source );

			if ( ! is_string( $canonical ) || ! self::acu_is_valid_canonical_url( $canonical ) ) {
				return;
			}

			echo "\n<!-- Advance Canonical URL (" . esc_html( ucfirst( $source ) ) . ") -->\n";
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
		}
	}
}
