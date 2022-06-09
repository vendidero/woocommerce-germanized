<?php
/**
 * Add extra profile fields for users in admin.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_GZD_Admin_Customer {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		// Admin profile
		add_action( 'show_user_profile', array( $this, 'profile_add_activation_field' ) );
		add_action( 'edit_user_profile', array( $this, 'profile_add_activation_field' ) );
		add_action( 'personal_options_update', array( $this, 'profile_save_activation_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'profile_save_activation_field' ) );

		if ( get_option( 'woocommerce_gzd_customer_activation' ) === 'yes' ) {
			add_filter( 'manage_users_columns', array( $this, 'add_user_column' ), 12, 1 );
			add_filter( 'manage_users_custom_column', array( $this, 'add_user_column_value' ), 12, 3 );
		}
	}

	public function add_user_column_value( $value, $column_name, $user_id ) {
		if ( 'woocommerce_doi' === $column_name ) {
			if ( WC_GZD_Customer_Helper::instance()->enable_double_opt_in_for_user( $user_id ) ) {
				if ( wc_gzd_is_customer_activated( $user_id ) ) {
					$value = '<span class="status-enabled">' . __( 'Yes', 'woocommerce-germanized' ) . '</span>';
				} else {
					$value = '<span class="status-disabled">' . __( 'No', 'woocommerce-germanized' ) . '</span>';
				}
			} else {
				$value = '<span>—</span>';
			}
		}

		return $value;
	}

	public function add_user_column( $columns ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$last_column = array_slice( $columns, -1, 1, true );
			array_pop( $columns );
			$columns['woocommerce_doi'] = __( 'DOI Confirmed?', 'woocommerce-germanized' );
			$columns                   += $last_column;
		}

		return $columns;
	}

	/**
	 * Adds customer activation option to profile
	 *
	 * @param object $user
	 */
	public function profile_add_activation_field( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! WC_GZD_Customer_Helper::instance()->enable_double_opt_in_for_user( $user ) || 'yes' !== get_option( 'woocommerce_gzd_customer_activation' ) ) {
			return;
		}
		if ( current_user_can( 'edit_user', $user->ID ) ) {
			?>
			<table class="form-table">
				<tbody>
				<tr>
					<th>
						<label for="woocommerce_activation"><?php esc_html_e( 'Double opt in', 'woocommerce-germanized' ); ?></label>
					</th>
					<td>
						<label for="woocommerce_activation">
							<input name="_woocommerce_activation" type="checkbox" id="_woocommerce_activation" value="1" <?php checked( wc_gzd_is_customer_activated( $user->ID ), 1 ); ?> <?php echo wc_gzd_is_customer_activated( $user->ID ) ? 'disabled="disabled"' : ''; ?> />
							<?php esc_html_e( 'Yes, customer opted in', 'woocommerce-germanized' ); ?>
						</label>
						<?php if ( ! wc_gzd_is_customer_activated( $user->ID ) ) : ?>
							<br/> <a class="wc-gzd-resend-activation-link button button-secondary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'gzd-resend-activation' => 'yes' ) ), 'resend-activation-link' ) ); ?>"><?php esc_html_e( 'Resend activation link', 'woocommerce-germanized' ); ?></a>
						<?php endif; ?>
					</td>
				</tr>
				</tbody>
			</table>
			<?php
		}
	}

	/**
	 * Delete activation key if user has been marked as opted in
	 *
	 * @param int $user_id
	 */
	public function profile_save_activation_field( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) ) {
			$user = get_userdata( $user_id );

			if ( isset( $_POST['_woocommerce_activation'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				delete_user_meta( $user_id, '_woocommerce_activation' );
			}
		}
	}

}

WC_GZD_Admin_Customer::instance();
