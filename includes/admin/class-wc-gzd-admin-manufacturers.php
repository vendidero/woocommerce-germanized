<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Admin_Manufacturers {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'product_manufacturer_add_form_fields', array( $this, 'add_fields' ) );
		add_action( 'product_manufacturer_edit_form_fields', array( $this, 'edit_fields' ), 10 );

		add_action( 'created_term', array( $this, 'save_fields' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_fields' ), 10, 3 );

		add_filter(
			'woocommerce_screen_ids',
			function ( $screen_ids ) {
				$screen_ids = array_merge(
					$screen_ids,
					array(
						'edit-product_manufacturer',
					)
				);

				return $screen_ids;
			}
		);
	}

	public static function get_fields() {
		return array(
			'formatted_address'    => array(
				'label' => __( 'Manufacturer Address', 'woocommerce-germanized' ),
				'type'  => 'textarea',
				'id'    => 'formatted_address',
			),
			'formatted_eu_address' => array(
				'label' => __( 'EU responsible person', 'woocommerce-germanized' ),
				'type'  => 'textarea',
				'id'    => 'formatted_eu_address',
			),
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
		if ( 'product_manufacturer' === $taxonomy ) {
			foreach ( self::get_fields() as $field_name => $field ) {
				$field_value = isset( $_POST[ $field['id'] ] ) ? wp_unslash( $_POST[ $field['id'] ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( 'textarea' === $field['type'] ) {
					$field_value = wc_gzd_sanitize_html_text_field( $field_value );
				} else {
					$field_value = wc_clean( $field_value );
				}

				update_term_meta( $term_id, $field['id'], $field_value );
			}
		}
	}

	public function add_fields() {
		foreach ( self::get_fields() as $field_name => $field ) :
			$field['value'] = '';
			?>
			<div class="form-field" style="position: relative">
				<?php if ( 'textarea' === $field['type'] ) : ?>
					<?php woocommerce_wp_textarea_input( $field ); ?>
				<?php else : ?>
				<?php endif; ?>
			</div>
			<?php
		endforeach;
	}

	/**
	 * Edit category thumbnail field.
	 *
	 * @param mixed $term Term (category) being edited.
	 */
	public function edit_fields( $term ) {
		$manufacturer = wc_gzd_get_manufacturer( $term );

		foreach ( self::get_fields() as $field_name => $field ) :
			$label          = $field['label'];
			$getter         = "get_{$field['id']}";
			$field['value'] = is_callable( array( $manufacturer, $getter ) ) ? $manufacturer->{ $getter }( 'edit' ) : '';
			$field['label'] = '';
			?>
			<tr class="form-field term-deposit-wrap">
				<th scope="row" valign="top">
					<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $label ); ?></label>
				</th>
				<td>
					<?php if ( 'textarea' === $field['type'] ) : ?>
						<?php woocommerce_wp_textarea_input( $field ); ?>
					<?php else : ?>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		endforeach;
	}
}

WC_GZD_Admin_Manufacturers::instance();
