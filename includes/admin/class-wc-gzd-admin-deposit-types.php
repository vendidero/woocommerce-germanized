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

		add_filter(
			'woocommerce_screen_ids',
			function( $screen_ids ) {
				$screen_ids = array_merge(
					$screen_ids,
					array(
						'edit-product_deposit_type',
					)
				);

				return $screen_ids;
			}
		);
	}

	/**
	 * Save category fields
	 *
	 * @param mixed $term_id Term ID being saved.
	 * @param mixed $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function save_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( 'product_deposit_type' === $taxonomy ) {
			if ( isset( $_POST['deposit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				update_term_meta( $term_id, 'deposit', wc_format_decimal( wp_unslash( $_POST['deposit'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			}

			if ( isset( $_POST['deposit_packaging_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$packaging_type = wc_clean( wp_unslash( $_POST['deposit_packaging_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( array_key_exists( $packaging_type, WC_germanized()->deposit_types->get_packaging_types() ) ) {
					update_term_meta( $term_id, 'deposit_packaging_type', $packaging_type );
				} else {
					delete_term_meta( $term_id, 'deposit_packaging_type' );
				}
			}
		}
	}

	public function add_fields() {
		?>
		<div class="form-field term-deposit-wrap" style="position: relative">
			<label for="deposit"><?php esc_html_e( 'Deposit', 'woocommerce-germanized' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
			<input id="deposit" style="max-width: 100px;" type="text"  name="deposit" class="wc_input_price" value="" />
		</div>
		<div class="form-field term-deposit-packaging-type-wrap" style="position: relative">
			<label for="deposit_packaging_type"><?php esc_html_e( 'Packaging Type', 'woocommerce-germanized' ); ?></label>
			<select id="deposit_packaging_type" name="deposit_packaging_type">
				<?php foreach ( $this->get_deposit_packaging_types() as $type => $title ) : ?>
					<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	protected function get_deposit_packaging_types() {
		return array_merge( array( '' => _x( 'None', 'deposit-packaging-type', 'woocommerce-germanized' ) ), WC_germanized()->deposit_types->get_packaging_types() );
	}

	/**
	 * Edit category thumbnail field.
	 *
	 * @param mixed $term Term (category) being edited.
	 */
	public function edit_fields( $term ) {
		$deposit        = wc_format_localized_price( get_term_meta( $term->term_id, 'deposit', true ) );
		$packaging_type = get_term_meta( $term->term_id, 'deposit_packaging_type', true );
		?>
		<tr class="form-field term-deposit-wrap">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Deposit', 'woocommerce-germanized' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
			</th>
			<td>
				<div style="position: relative">
					<input id="deposit" style="max-width: 100px;" type="text" name="deposit" class="wc_input_price" value="<?php echo esc_attr( $deposit ); ?>" />
				</div>
			</td>
		</tr>
		<tr class="form-field term-deposit-packaging-type-wrap">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Packaging Type', 'woocommerce-germanized' ); ?></label>
			</th>
			<td>
				<div style="position: relative">
					<select id="deposit_packaging_type" name="deposit_packaging_type">
						<?php foreach ( $this->get_deposit_packaging_types() as $type => $title ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $packaging_type, $type ); ?>><?php echo esc_html( $title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</td>
		</tr>
		<?php
	}
}

WC_GZD_Admin_Deposit_Types::instance();
