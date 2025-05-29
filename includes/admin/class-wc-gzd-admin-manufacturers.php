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

		add_action( 'product_brand_add_form_fields', array( $this, 'add_brand_fields' ) );
		add_action( 'product_brand_edit_form_fields', array( $this, 'edit_brand_fields' ), 10 );

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
				'cols'  => 50,
				'rows'  => 5,
			),
			'formatted_eu_address' => array(
				'label' => __( 'EU responsible person', 'woocommerce-germanized' ),
				'type'  => 'textarea',
				'id'    => 'formatted_eu_address',
				'cols'  => 50,
				'rows'  => 5,
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
				if ( ! isset( $_POST[ $field['id'] ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					continue;
				}

				$field_value = isset( $_POST[ $field['id'] ] ) ? wp_unslash( $_POST[ $field['id'] ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( 'textarea' === $field['type'] ) {
					$field_value = wc_gzd_sanitize_html_text_field( $field_value );
				} else {
					$field_value = wc_clean( $field_value );
				}

				update_term_meta( $term_id, $field['id'], $field_value );
			}
		} elseif ( 'product_brand' === $taxonomy ) {
			if ( isset( $_POST['manufacturer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$term = isset( $_POST['manufacturer'] ) ? wc_clean( wp_unslash( $_POST['manufacturer'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( ! empty( $term ) ) {
					$term = wc_gzd_get_or_create_product_term_slug( $term, 'product_manufacturer' );
				}

				if ( ! empty( $term ) && ( $manufacturer = wc_gzd_get_manufacturer( $term ) ) ) {
					update_term_meta( $term_id, 'manufacturer', $manufacturer->get_slug() );
				} else {
					delete_term_meta( $term_id, 'manufacturer' );
				}
			}
		}
	}

	public function add_brand_fields() {
		?>
		<div class="form-field term-manufacturer-wrap" style="position: relative">
			<label for="tag-manufacturer"><?php echo esc_html__( 'Manufacturer', 'woocommerce-germanized' ); ?></label>
			<?php
			WC_Germanized_Meta_Box_Product_Data::instance()->manufacturer_select_field(
				array(
					'name'  => 'manufacturer',
					'id'    => 'tag-manufacturer',
					'term'  => false,
					'style' => 'width: 100%;',
				)
			);
			?>
		</div>
		<?php
	}

	public function edit_brand_fields( $term ) {
		$current_manufacturer_slug = get_term_meta( $term->term_id, 'manufacturer', true );
		$term                      = ! empty( $current_manufacturer_slug ) ? wc_gzd_get_manufacturer( $current_manufacturer_slug ) : false;
		?>
		<tr class="form-field term-select-manufacturer-wrap">
			<th scope="row" valign="top">
				<label for="manufacturer"><?php echo esc_html__( 'Manufacturer', 'woocommerce-germanized' ); ?></label>
			</th>
			<td>
				<input type="hidden" name="manufacturer" value="" />
				<?php
				WC_Germanized_Meta_Box_Product_Data::instance()->manufacturer_select_field(
					array(
						'name' => 'manufacturer',
						'id'   => 'manufacturer',
						'term' => $term,
					)
				);
				?>
			</td>
		</tr>
		<?php
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
			<tr class="form-field term-manufacturer-address-wrap">
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
