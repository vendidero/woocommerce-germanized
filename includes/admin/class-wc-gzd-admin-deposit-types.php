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
			function ( $screen_ids ) {
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

				$is_packaging = wc_string_to_bool( isset( $_POST['deposit_is_packaging'] ) ? 'yes' : 'no' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				update_term_meta( $term_id, 'deposit_is_packaging', wc_bool_to_string( $is_packaging ) );

				if ( $is_packaging ) {
					$number_contents = absint( wp_unslash( isset( $_POST['deposit_packaging_number_contents'] ) ? $_POST['deposit_packaging_number_contents'] : 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

					update_term_meta( $term_id, 'deposit_packaging_number_contents', $number_contents );
					update_term_meta( $term_id, 'deposit_packaging', '' );
				} else {
					update_term_meta( $term_id, 'deposit_packaging_number_contents', 0 );

					$packaging_list = WC_germanized()->deposit_types->get_packaging_list(
						array(
							'exclude' => $term_id,
						)
					);

					$packaging = wc_clean( wp_unslash( isset( $_POST['deposit_packaging'] ) ? $_POST['deposit_packaging'] : '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

					if ( array_key_exists( $packaging, $packaging_list ) ) {
						update_term_meta( $term_id, 'deposit_packaging', $packaging );
					} else {
						update_term_meta( $term_id, 'deposit_packaging', '' );
					}
				}
			}

			if ( isset( $_POST['deposit_tax_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$tax_status = wc_clean( wp_unslash( $_POST['deposit_tax_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( array_key_exists( $tax_status, $this->get_deposit_tax_statuses() ) ) {
					update_term_meta( $term_id, 'deposit_tax_status', $tax_status );
				} else {
					update_term_meta( $term_id, 'deposit_tax_status', 'taxable' );
				}
			}
		}
	}

	public function add_fields() {
		$packaging_list = array_merge( array( '' => _x( 'None', 'deposit-packaging', 'woocommerce-germanized' ) ), WC_germanized()->deposit_types->get_packaging_list() );
		?>
		<div class="wc-gzd-admin-settings">
			<div class="form-field term-deposit-packaging-type-wrap" style="position: relative">
				<label for="deposit_is_packaging"><?php esc_html_e( 'Packaging?', 'woocommerce-germanized' ); ?></label>
				<fieldset>
					<label for="deposit_is_packaging">
						<input type="checkbox" name="deposit_is_packaging" id="deposit_is_packaging" value="yes" />
						<?php esc_html_e( 'This deposit is a packaging, e.g. beverage crate.', 'woocommerce-germanized' ); ?>
					</label>
				</fieldset>
			</div>
			<div class="form-field term-deposit-packaging-number-contents-wrap">
				<label for="deposit_packaging_number_contents"><?php esc_html_e( 'Number of contents', 'woocommerce-germanized' ); ?></label>
				<input data-show_if_deposit_is_packaging="yes" id="deposit_packaging_number_contents" style="max-width: 100px;" type="number" name="deposit_packaging_number_contents" />
			</div>
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
			<div class="form-field term-deposit-packaging-wrap">
				<label for="deposit_packaging"><?php esc_html_e( 'Packaging, e.g. crate', 'woocommerce-germanized' ); ?></label>
				<select id="deposit_packaging" name="deposit_packaging" data-show_if_deposit_is_packaging="no">
					<?php foreach ( $packaging_list as $slug => $title ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="form-field term-deposit-tax-status-wrap" style="position: relative">
				<label for="deposit_tax_status"><?php esc_html_e( 'Tax Status', 'woocommerce-germanized' ); ?></label>
				<select id="deposit_tax_status" name="deposit_tax_status">
					<?php foreach ( $this->get_deposit_tax_statuses() as $type => $title ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php
	}

	protected function get_deposit_tax_statuses() {
		return WC_germanized()->deposit_types->get_tax_statuses();
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
		$deposit         = wc_format_localized_price( get_term_meta( $term->term_id, 'deposit', true ) );
		$packaging_type  = get_term_meta( $term->term_id, 'deposit_packaging_type', true );
		$tax_status      = get_term_meta( $term->term_id, 'deposit_tax_status', true );
		$packaging       = get_term_meta( $term->term_id, 'deposit_packaging', true );
		$is_packaging    = get_term_meta( $term->term_id, 'deposit_is_packaging', true );
		$number_contents = get_term_meta( $term->term_id, 'deposit_packaging_number_contents', true );
		$packaging_list  = array_merge(
			array( '' => _x( 'None', 'deposit-packaging', 'woocommerce-germanized' ) ),
			WC_germanized()->deposit_types->get_packaging_list(
				array(
					'exclude' => $term->term_id,
				)
			)
		);
		?>
		</tbody>
		<tbody class="wc-gzd-admin-settings">
		<tr class="form-field term-deposit-is-packaging-wrap">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Packaging?', 'woocommerce-germanized' ); ?></label>
			</th>
			<td>
				<div style="position: relative">
					<fieldset>
						<label for="deposit_is_packaging">
							<input type="checkbox" name="deposit_is_packaging" id="deposit_is_packaging" value="yes" <?php checked( $is_packaging, 'yes' ); ?> />
							<?php esc_html_e( 'This deposit is a packaging, e.g. beverage crate.', 'woocommerce-germanized' ); ?>
						</label>
					</fieldset>
				</div>
			</td>
		</tr>
		<tr class="form-field term-deposit-packaging-number-contents-wrap">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Number of contents', 'woocommerce-germanized' ); ?></label>
			</th>
			<td>
				<div style="position: relative">
					<input data-show_if_deposit_is_packaging="yes" id="deposit_packaging_number_contents" style="max-width: 100px;" type="number" name="deposit_packaging_number_contents" value="<?php echo esc_attr( $number_contents ); ?>" />
				</div>
			</td>
		</tr>
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
		<tr class="form-field term-deposit-packaging-wrap">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Packaging, e.g. crate', 'woocommerce-germanized' ); ?></label>
			</th>
			<td>
				<div style="position: relative">
					<select id="deposit_packaging" name="deposit_packaging" data-show_if_deposit_is_packaging="no">
						<?php foreach ( $packaging_list as $slug => $title ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $packaging, $slug ); ?>><?php echo esc_html( $title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</td>
		</tr>
		<tr class="form-field term-deposit-tax-status-wrap">
			<th scope="row" valign="top">
				<label><?php esc_html_e( 'Tax Status', 'woocommerce-germanized' ); ?></label>
			</th>
			<td>
				<div style="position: relative">
					<select id="deposit_tax_status" name="deposit_tax_status">
						<?php foreach ( $this->get_deposit_tax_statuses() as $type => $title ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $tax_status, $type ); ?>><?php echo esc_html( $title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</td>
		</tr>
		<tbody>
		<?php
	}
}

WC_GZD_Admin_Deposit_Types::instance();
