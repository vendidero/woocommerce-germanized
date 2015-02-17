<?php
/**
 * Add extra profile fields for users in admin.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

class WC_GZD_Admin_Customer {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	public function __construct() {
		// Admin profile
		add_action( 'show_user_profile', array( $this, 'profile_add_activation_field' ) );
		add_action( 'edit_user_profile', array( $this, 'profile_add_activation_field' ) );
		add_action( 'personal_options_update', array( $this, 'profile_save_activation_field' ) );
		add_action( 'edit_user_profile_update', array( $this, 'profile_save_activation_field' ) );
	}

	/**
	 * Adds customer activation option to profile
	 *  
	 * @param  object $user 
	 */
	public function profile_add_activation_field( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! in_array( 'customer', $user->roles ) || get_option( 'woocommerce_gzd_customer_activation' ) != 'yes' )
			return;

		if ( current_user_can( 'edit_user', $user->ID ) ) {
			?>
				<table class="form-table">
					<tbody>
						<tr>
							<th><label for="woocommerce_activation"><?php _e( 'Double opt in', 'woocommerce-germanized' ); ?></label></th>
							<td>
								<label for="woocommerce_activation">
									<input name="_woocommerce_activation" type="checkbox" id="_woocommerce_activation" value="1" <?php checked( wc_gzd_is_customer_activated( $user->ID ), 1 ); ?> />
									<?php _e( 'Yes, customer opted in', 'woocommerce-germanized' ); ?>
								</label>
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
	 * @param  int $user_id 
	 */
	public function profile_save_activation_field( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) ) {
			$user = get_userdata( $user_id );
			if ( isset( $_POST[ '_woocommerce_activation' ] ) )
				delete_user_meta( $user_id, '_woocommerce_activation' );
		}
	}

	/**
	 * Check for customer that didn't activate their accounts within a couple of time and delete them
	 */
	public function account_cleanup() {
		if ( ! get_option( 'woocommerce_gzd_customer_cleanup_interval' ) || get_option( 'woocommerce_gzd_customer_cleanup_interval' ) == 0 )
			return;

		$user_query = new WP_User_Query(
			array( 'role' => 'Customer', 'meta_query' =>
				array(
					array(
						'key'     => '_woocommerce_activation',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				// Check date interval
				$registered = $user->data->user_registered;
				$date_diff = WC_germanized()->get_date_diff( $registered, date( 'Y-m-d' ) );
				if ( $date_diff[ 'd' ] >= (int) get_option( 'woocommerce_gzd_customer_cleanup_interval' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/user.php' );
					wp_delete_user( $user->ID );
				}
			}
		}
	}

}

WC_GZD_Admin_Customer::instance();