<?php
/**
 * Plugin Name: Advance Canonical URL
 * Plugin URI: https://github.com/usmanaliqureshi/advance-canonical-url
 * Description: Control site-wide canonical URLs and optionally override them for individual posts, pages, and public post types.
 * Version: 1.1.1
 * Requires at least: 5.2
 * Requires PHP: 7.3
 * Author: Usman Ali Qureshi
 * Author URI: https://www.usmanaliqureshi.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advance-canonical-url
 * Domain Path: /languages/
 *
 * @package advance-canonical-url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ACU_VERSION', '1.1.1' );
define( 'ACU_PLUGIN_FILE', __FILE__ );
define( 'ACU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ACU_FREEMIUS_PRODUCT_ID', '36939' );
define( 'ACU_FREEMIUS_SUPPORTER_PLAN_ID', '61250' );
define( 'ACU_FREEMIUS_BACKER_PLAN_ID', '61251' );
define( 'ACU_FREEMIUS_SPONSOR_PLAN_ID', '61252' );
define( 'ACU_FREEMIUS_AGENCY_SPONSOR_PLAN_ID', '61253' );
define( 'ACU_FREEMIUS_PUBLIC_KEY', 'pk_2b3550d957f326485b7ccf5a88779' );

/**
 * Returns a sanitized Lucide-style interface icon.
 *
 * @param string $name  Icon name.
 * @param string $class Optional CSS class.
 * @return string
 */
function acu_get_icon( $name, $class = '' ) {
	$icons = array(
		'link'          => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
		'route'         => '<circle cx="6" cy="19" r="3"/><path d="M9 19h6.5a3.5 3.5 0 0 0 0-7h-8a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/>',
		'sparkles'      => '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/><path d="M5 3v4M3 5h4M19 17v4M17 19h4"/>',
		'wand-sparkles' => '<path d="m15 4-1 1"/><path d="m15 16-1 1"/><path d="m19 10-1 1"/><path d="m19 22-1 1"/><path d="m23 6-1 1"/><path d="m3 21 9-9"/><path d="m6 3 1 1"/><path d="m6 15 1 1"/><path d="m10 7 1 1"/>',
		'sliders'       => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
		'filter'        => '<path d="M3 6h18M7 12h10M10 18h4"/>',
		'check'         => '<path d="m5 12 4 4L19 6"/>',
		'check-ring'    => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
		'info'          => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
		'heart'         => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"/>',
		'x'             => '<path d="M18 6 6 18M6 6l12 12"/>',
		'grid'          => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
		'server'        => '<rect width="18" height="8" x="3" y="3" rx="2"/><rect width="18" height="8" x="3" y="13" rx="2"/><path d="M7 7h.01M7 17h.01"/>',
		'eye'           => '<path d="M2.06 12.35a1 1 0 0 1 0-.7C3.73 7.6 7.68 5 12 5c4.32 0 8.27 2.6 9.94 6.65a1 1 0 0 1 0 .7C20.27 16.4 16.32 19 12 19c-4.32 0-8.27-2.6-9.94-6.65Z"/><circle cx="12" cy="12" r="3"/>',
		'key'           => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.8 12.2 8.4-8.4M15 8l2 2M18 5l2 2"/>',
		'external-link' => '<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$svg = sprintf(
		'<svg class="acu-icon %1$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		esc_attr( $class ),
		$icons[ $name ]
	);

	return wp_kses(
		$svg,
		array(
			'svg'    => array(
				'class'           => true,
				'viewbox'         => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'aria-hidden'     => true,
				'focusable'       => true,
			),
			'path'   => array( 'd' => true ),
			'circle' => array(
				'cx' => true,
				'cy' => true,
				'r'  => true,
			),
			'rect'   => array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
			),
		)
	);
}

if ( ! function_exists( 'acu_fs' ) ) {
	/**
	 * Returns the Freemius SDK instance.
	 *
	 * @return Freemius
	 */
	function acu_fs() {
		global $acu_fs;

		if ( ! isset( $acu_fs ) ) {
			require_once ACU_PLUGIN_DIR . 'vendor/freemius/start.php';

			$acu_fs = fs_dynamic_init(
				array(
					'id'                  => ACU_FREEMIUS_PRODUCT_ID,
					'slug'                => 'advance-canonical-url',
					'type'                => 'plugin',
					'public_key'          => ACU_FREEMIUS_PUBLIC_KEY,
					'is_premium'          => false,
					'has_premium_version' => false,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => true,
					'menu'                => array(
						'slug'    => 'advance_canonical_settings',
						'account' => false,
						'contact' => false,
						'support' => false,
						'pricing' => false,
						'parent'  => array(
							'slug' => 'options-general.php',
						),
					),
				)
			);
		}

		return $acu_fs;
	}

	acu_fs();
	acu_fs()->add_filter( 'is_pricing_page_visible', '__return_false' );
	do_action( 'acu_fs_loaded' );
}

require_once ACU_PLUGIN_DIR . 'includes/advance-canonical-url.php';
require_once ACU_PLUGIN_DIR . 'includes/canonical-meta-box.php';

register_activation_hook( ACU_PLUGIN_FILE, array( 'advance_canonical_url', 'acu_activation' ) );

/**
 * Starts the plugin after all plugins are available.
 */
function acu_run_plugin() {
	new advance_canonical_url();
	new advance_canonical_meta_box();
}
add_action( 'plugins_loaded', 'acu_run_plugin' );
