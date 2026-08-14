<?php
/**
 * Per-content canonical URL controls.
 *
 * @package advance-canonical-url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'advance_canonical_meta_box' ) ) {
	class advance_canonical_meta_box {

		/**
		 * Registers meta box hooks when custom control is enabled.
		 */
		public function __construct() {
			$options = advance_canonical_url::acu_get_options();

			if ( 'advance' !== $options['canonical_method'] ) {
				return;
			}

			add_action( 'admin_enqueue_scripts', array( $this, 'acu_admin_style' ) );
			add_action( 'add_meta_boxes', array( $this, 'acu_add_meta_box' ) );
			add_action( 'save_post', array( $this, 'acu_save_meta_box' ) );
		}

		/**
		 * Loads meta box styles on content editor screens.
		 *
		 * @param string $hook_suffix Current admin page hook.
		 */
		public function acu_admin_style( $hook_suffix ) {
			if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
				return;
			}

			$style_path    = ACU_PLUGIN_DIR . 'css/acu_admin.css';
			$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : ACU_VERSION;

			wp_enqueue_style( 'acu-admin-styles', ACU_PLUGIN_URL . 'css/acu_admin.css', array(), $style_version );
		}

		/**
		 * Adds the canonical URL box to public content types.
		 *
		 * @param string $post_type Current post type.
		 */
		public function acu_add_meta_box( $post_type ) {
			$post_types = get_post_types(
				array(
					'public'  => true,
					'show_ui' => true,
				),
				'names'
			);

			if ( ! in_array( $post_type, $post_types, true ) ) {
				return;
			}

			add_meta_box(
				'acu_canonical_meta_box',
				esc_html__( 'Canonical URL', 'advance-canonical-url' ),
				array( $this, 'acu_render_meta_box' ),
				$post_type,
				'advanced',
				'high'
			);
		}

		/**
		 * Saves a per-content canonical URL.
		 *
		 * @param int $post_id Post ID.
		 */
		public function acu_save_meta_box( $post_id ) {
			if ( ! isset( $_POST['acu_canonical_meta_box_nonce'], $_POST['acu_adv_can_url'] ) ) {
				return;
			}

			$nonce = sanitize_text_field( wp_unslash( $_POST['acu_canonical_meta_box_nonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'acu_inner_custom_box' ) ) {
				return;
			}

			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$url = trim( esc_url_raw( wp_unslash( $_POST['acu_adv_can_url'] ), array( 'http', 'https' ) ) );

			if ( '' === $url ) {
				delete_post_meta( $post_id, '_acu_can_url_value' );
				return;
			}

			if ( advance_canonical_url::acu_is_valid_canonical_url( $url ) ) {
				update_post_meta( $post_id, '_acu_can_url_value', $url );
			}
		}

		/**
		 * Renders the canonical URL meta box.
		 *
		 * @param WP_Post $post Current post.
		 */
		public function acu_render_meta_box( $post ) {
			wp_nonce_field( 'acu_inner_custom_box', 'acu_canonical_meta_box_nonce' );

			$value       = get_post_meta( $post->ID, '_acu_can_url_value', true );
			$default_url = get_permalink( $post );
			$has_custom  = is_string( $value ) && '' !== $value;
			?>
			<div class="acu-meta-box">
				<div class="acu-meta-status">
					<span class="acu-meta-status-icon" aria-hidden="true"><?php echo acu_get_icon( $has_custom ? 'link' : 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<div>
						<strong><?php echo $has_custom ? esc_html__( 'Custom canonical active', 'advance-canonical-url' ) : esc_html__( 'Using automatic canonical', 'advance-canonical-url' ); ?></strong>
						<span><?php esc_html_e( 'Search engines will receive one preferred URL for this content.', 'advance-canonical-url' ); ?></span>
					</div>
				</div>

				<div class="acu-default-url">
					<span><?php esc_html_e( 'Automatic URL', 'advance-canonical-url' ); ?></span>
					<code><?php echo esc_html( $default_url ); ?></code>
				</div>

				<div class="acu-custom-field">
					<label for="acu_adv_can_url"><?php esc_html_e( 'Custom canonical URL', 'advance-canonical-url' ); ?></label>
					<input type="url" id="acu_adv_can_url" name="acu_adv_can_url" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'https://example.com/preferred-page/', 'advance-canonical-url' ); ?>" inputmode="url" />
					<p><?php esc_html_e( 'Enter a complete HTTP or HTTPS URL. Clear the field to restore the automatic URL.', 'advance-canonical-url' ); ?></p>
				</div>
			</div>
			<?php
		}
	}
}
