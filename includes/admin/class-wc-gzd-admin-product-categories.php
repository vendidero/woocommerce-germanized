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

class WC_GZD_Admin_Product_Categories {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'product_cat_add_form_fields', array( $this, 'add_category_fields' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'edit_category_fields' ), 10 );

		add_action( 'created_term', array( $this, 'save_category_fields' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_category_fields' ), 10, 3 );
	}

	/**
	 * Save category fields
	 *
	 * @param mixed $term_id Term ID being saved.
	 * @param mixed $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( isset( $_POST['age_verification'] ) && 'product_cat' === $taxonomy ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST['age_verification'] ) && array_key_exists( absint( $_POST['age_verification'] ), wc_gzd_get_age_verification_min_ages() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				update_term_meta( $term_id, 'age_verification', absint( $_POST['age_verification'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} else {
				delete_term_meta( $term_id, 'age_verification' );
			}
		}
	}

	/**
	 * Category thumbnail fields.
	 */
	public function add_category_fields() {
		?>
		<div class="form-field term-age-verification-wrap">
			<label for="age_verification"><?php esc_html_e( 'Age Verification', 'woocommerce-germanized' ); ?></label>
			<select id="age_verification" name="age_verification" class="postform">
				<option value="" <?php selected( empty( $variation_data['_age_verification'] ), true ); ?>><?php esc_html_e( 'Same as Parent', 'woocommerce-germanized' ); ?></option>
				<?php foreach ( wc_gzd_get_age_verification_min_ages() as $key => $value ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Edit category thumbnail field.
	 *
	 * @param mixed $term Term (category) being edited.
	 */
	public function edit_category_fields( $term ) {
		$age_verification = get_term_meta( $term->term_id, 'age_verification', true );
		?>
		<tr class="form-field term-display-type-wrap">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Age Verification', 'woocommerce-germanized' ); ?></label></th>
			<td>
				<select id="age_verification" name="age_verification" class="postform">
					<option value="" <?php selected( empty( $age_verification ), true ); ?>><?php esc_html_e( 'None', 'woocommerce-germanized' ); ?></option>
					<?php foreach ( wc_gzd_get_age_verification_min_ages() as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $age_verification ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}
}

WC_GZD_Admin_Product_Categories::instance();
