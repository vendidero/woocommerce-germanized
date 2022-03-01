<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Admin_Deposit_Types {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'product_deposit_type_add_form_fields', array( $this, 'add_fields' ) );
		add_action( 'product_deposit_type_edit_form_fields', array( $this, 'edit_fields' ), 10 );

		add_action( 'created_term', array( $this, 'save_fields' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_fields' ), 10, 3 );

        add_filter( 'woocommerce_screen_ids', function( $screen_ids ) {
            $screen_ids = array_merge( $screen_ids, array(
                'edit-product_deposit_type',
            ) );

            return $screen_ids;
        } );
	}

	/**
	 * Save category fields
	 *
	 * @param mixed $term_id Term ID being saved.
	 * @param mixed $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function save_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( isset( $_POST['deposit'] ) && 'product_deposit_type' === $taxonomy ) {
            update_term_meta( $term_id, 'deposit', wc_format_decimal( $_POST['deposit'] ) );
		}
	}

	public function add_fields() {
		?>
        <div class="form-field term-deposit-wrap" style="position: relative">
            <label for="deposit"><?php esc_html_e( 'Deposit', 'woocommerce-germanized' ); ?> (<?php echo get_woocommerce_currency_symbol(); ?>)</label>
            <input id="deposit" style="max-width: 100px;" type="text"  name="deposit" class="wc_input_price" value="" />
        </div>
		<?php
	}

	/**
	 * Edit category thumbnail field.
	 *
	 * @param mixed $term Term (category) being edited.
	 */
	public function edit_fields( $term ) {
		$deposit = wc_format_localized_price( get_term_meta( $term->term_id, 'deposit', true ) );
		?>
        <tr class="form-field term-deposit-wrap">
            <th scope="row" valign="top">
                <label><?php esc_html_e( 'Deposit', 'woocommerce-germanized' ); ?> (<?php echo get_woocommerce_currency_symbol(); ?>)</label>
            </th>
            <td>
                <div style="position: relative">
                    <input id="deposit" style="max-width: 100px;" type="text" name="deposit" class="wc_input_price" value="<?php echo esc_attr( $deposit ); ?>" />
                </div>
            </td>
        </tr>
		<?php
	}
}

WC_GZD_Admin_Deposit_Types::instance();