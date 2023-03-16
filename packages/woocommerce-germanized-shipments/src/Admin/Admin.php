<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Packaging\ReportHelper;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\Automation;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

	protected static $bulk_handlers = null;

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 35 );
		add_action( 'woocommerce_process_shop_order_meta', 'Vendidero\Germanized\Shipments\Admin\MetaBox::save', 60, 2 );

		add_action( 'admin_menu', array( __CLASS__, 'shipments_menu' ), 15 );
		add_action( 'load-woocommerce_page_wc-gzd-shipments', array( __CLASS__, 'setup_shipments_table' ), 0 );
		add_action( 'load-woocommerce_page_wc-gzd-return-shipments', array( __CLASS__, 'setup_returns_table' ), 0 );

		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_woocommerce_page_wc_gzd_shipments_per_page', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_woocommerce_page_wc_gzd_return_shipments_per_page', array( __CLASS__, 'set_screen_option' ), 10, 3 );

		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_table_view' ), 10 );

		// Template check
		add_filter( 'woocommerce_gzd_template_check', array( __CLASS__, 'add_template_check' ), 10, 1 );

		// Return reason options
		add_action( 'woocommerce_admin_field_shipment_return_reasons', array( __CLASS__, 'output_return_reasons_field' ) );
		add_action( 'woocommerce_gzd_admin_settings_after_save_shipments', array( __CLASS__, 'save_return_reasons' ), 10, 2 );

		// Packaging options
		add_action( 'woocommerce_admin_field_packaging_list', array( __CLASS__, 'output_packaging_list' ) );
		add_action( 'woocommerce_gzd_admin_settings_after_save_shipments_packaging', array( __CLASS__, 'save_packaging_list' ), 10 );

		add_action( 'woocommerce_admin_field_packaging_reports', array( __CLASS__, 'output_packaging_reports' ) );

		// Menu count
		add_action( 'admin_head', array( __CLASS__, 'menu_return_count' ) );

		// Check upload folder
		add_action( 'admin_notices', array( __CLASS__, 'check_upload_dir' ) );

		// Register endpoints within settings
		add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'register_endpoint_settings' ), 20, 2 );

		// Product Options
		add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'product_options' ), 9 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product' ), 10, 1 );

		// Observe base country setting
		add_action( 'woocommerce_settings_save_general', array( __CLASS__, 'observe_base_country_setting' ), 100 );

		add_action(
			'admin_init',
			function() {
				// Order shipping status
				add_filter( 'manage_' . ( 'shop_order' === self::get_order_screen_id() ? 'shop_order_posts' : self::get_order_screen_id() ) . '_columns', array( __CLASS__, 'register_order_shipping_status_column' ), 20 );
				add_action( 'manage_' . ( 'shop_order' === self::get_order_screen_id() ? 'shop_order_posts' : self::get_order_screen_id() ) . '_custom_column', array( __CLASS__, 'render_order_columns' ), 20, 2 );

				add_filter( 'handle_bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'handle_order_bulk_actions' ), 10, 3 );
				add_filter( 'bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'define_order_bulk_actions' ), 10, 1 );
			}
		);
	}

	public static function render_order_columns( $column, $post_id ) {
		if ( 'shipping_status' === $column ) {
			global $the_order;

			if ( ! $the_order || $the_order->get_id() !== $post_id ) {
				$the_order = wc_get_order( $post_id );
			}

			if ( $shipment_order = wc_gzd_get_shipment_order( $the_order ) ) {
				$shipping_status = $shipment_order->get_shipping_status();
				$status_html     = '<span class="order-shipping-status status-' . esc_attr( $shipping_status ) . '">' . esc_html( wc_gzd_get_shipment_order_shipping_status_name( $shipping_status ) ) . '</span>';

				if ( in_array( $shipping_status, array( 'shipped', 'partially-shipped' ), true ) && $shipment_order->get_shipments() ) {
					echo '<a target="_blank" href="' . esc_url( add_query_arg( array( 'order_id' => $post_id ), admin_url( 'admin.php?page=wc-gzd-shipments' ) ) ) . '">' . wp_kses_post( $status_html ) . '</a>';
				} else {
					echo wp_kses_post( $status_html );
				}
			}
		}
	}

	public static function register_order_shipping_status_column( $columns ) {
		$new_columns  = array();
		$added_column = false;

		foreach ( $columns as $column_name => $title ) {
			if ( ! $added_column && ( 'shipping_address' === $column_name || 'wc_actions' === $column_name ) ) {
				$new_columns['shipping_status'] = _x( 'Shipping Status', 'shipments-order-column-name', 'woocommerce-germanized' );
				$added_column                   = true;
			}

			$new_columns[ $column_name ] = $title;
		}

		if ( ! $added_column ) {
			$new_columns['shipping_status'] = _x( 'Shipping Status', 'shipments-order-column-name', 'woocommerce-germanized' );
		}

		return $new_columns;
	}

	/**
	 * In case the shipper/return country is set to AF (or DE with missing state) due to a bug in Woo, make sure
	 * to automatically adjust it to the right value in case the base country option is being saved.
	 *
	 * @return void
	 */
	public static function observe_base_country_setting() {
		if ( isset( $_POST['woocommerce_default_country'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$new_base_country = wc_format_country_state_string( get_option( 'woocommerce_default_country' ) );

			if ( 'AF' !== $new_base_country['country'] ) {
				$shipper_country = wc_format_country_state_string( get_option( 'woocommerce_gzd_shipments_shipper_address_country' ) );
				$return_country  = wc_format_country_state_string( get_option( 'woocommerce_gzd_shipments_return_address_country' ) );

				if ( 'AF' === $shipper_country['country'] || ( 'DE' === $new_base_country['country'] && 'DE' === $shipper_country['country'] && empty( $shipper_country['state'] ) && ! empty( $new_base_country['state'] ) ) ) {
					update_option( 'woocommerce_gzd_shipments_shipper_address_country', get_option( 'woocommerce_default_country' ) );
				}

				if ( 'AF' === $return_country['country'] || ( 'DE' === $new_base_country['country'] && 'DE' === $return_country['country'] && empty( $return_country['state'] ) && ! empty( $return_country['state'] ) ) ) {
					update_option( 'woocommerce_gzd_shipments_return_address_country', get_option( 'woocommerce_default_country' ) );
				}
			}
		}
	}

	public static function product_options() {
		global $post, $thepostid, $product_object;

		$_product          = wc_get_product( $product_object );
		$shipments_product = wc_gzd_shipments_get_product( $_product );

		$countries = WC()->countries->get_countries();
		$countries = array_merge( array( '0' => _x( 'Select a country', 'shipments', 'woocommerce-germanized' ) ), $countries );

		woocommerce_wp_text_input(
			array(
				'id'          => '_hs_code',
				'label'       => _x( 'HS-Code (Customs)', 'shipments', 'woocommerce-germanized' ),
				'desc_tip'    => true,
				'description' => _x( 'The HS Code is a number assigned to every possible commodity that can be imported or exported from any country.', 'shipments', 'woocommerce-germanized' ),
				'value'       => $shipments_product->get_hs_code( 'edit' ),
			)
		);

		woocommerce_wp_select(
			array(
				'options'     => $countries,
				'id'          => '_manufacture_country',
				'label'       => _x( 'Country of manufacture (Customs)', 'shipments', 'woocommerce-germanized' ),
				'desc_tip'    => true,
				'description' => _x( 'The country of manufacture is needed for customs of international shipping.', 'shipments', 'woocommerce-germanized' ),
				'value'       => $shipments_product->get_manufacture_country( 'edit' ),
			)
		);

		do_action( 'woocommerce_gzd_shipments_product_options', $shipments_product );
	}

	/**
	 * @param \WC_Product $product
	 */
	public static function save_product( $product ) {
		$hs_code = isset( $_POST['_hs_code'] ) ? wc_clean( wp_unslash( $_POST['_hs_code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$country = isset( $_POST['_manufacture_country'] ) ? wc_clean( wp_unslash( $_POST['_manufacture_country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$shipments_product = wc_gzd_shipments_get_product( $product );
		$shipments_product->set_hs_code( $hs_code );
		$shipments_product->set_manufacture_country( $country );

		/**
		 * Remove legacy data upon saving in case it is not transmitted (e.g. DHL standalone plugin).
		 */
		if ( apply_filters( 'woocommerce_gzd_shipments_remove_legacy_customs_meta', isset( $_POST['_dhl_hs_code'] ) ? false : true, $product ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$product->delete_meta_data( '_dhl_hs_code' );
			$product->delete_meta_data( '_dhl_manufacture_country' );
		}

		do_action( 'woocommerce_gzd_shipments_save_product_options', $shipments_product );
	}

	public static function check_upload_dir() {
		$dir     = Package::get_upload_dir();
		$path    = $dir['basedir'];
		$dirname = basename( $path );

		if ( @is_dir( $dir['basedir'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return;
		}
		?>
		<div class="error">
			<p><?php printf( esc_html_x( 'Shipments upload directory missing. Please manually create the folder %s and make sure that it is writeable.', 'shipments', 'woocommerce-germanized' ), '<i>wp-content/uploads/' . esc_html( $dirname ) . '</i>' ); ?></p>
		</div>
		<?php
	}

	private static function get_setting_key_by_id( $settings, $id, $type = '' ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				if ( isset( $value['id'] ) && $value['id'] === $id ) {
					if ( ! empty( $type ) && $type !== $value['type'] ) {
						continue;
					}
					return $key;
				}
			}
		}

		return false;
	}

	protected static function add_settings_after( $settings, $id, $insert = array(), $type = '' ) {
		$key = self::get_setting_key_by_id( $settings, $id, $type );

		if ( is_numeric( $key ) ) {
			$key ++;
			$settings = array_merge( array_merge( array_slice( $settings, 0, $key, true ), $insert ), array_slice( $settings, $key, count( $settings ) - 1, true ) );
		} else {
			$settings += $insert;
		}

		return $settings;
	}

	public static function register_endpoint_settings( $settings, $current_section ) {
		if ( '' === $current_section ) {
			$endpoints = array(
				array(
					'title'    => _x( 'View Shipments', 'shipments', 'woocommerce-germanized' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; View shipments" page.', 'shipments', 'woocommerce-germanized' ),
					'id'       => 'woocommerce_gzd_shipments_view_shipments_endpoint',
					'type'     => 'text',
					'default'  => 'view-shipments',
					'desc_tip' => true,
				),
				array(
					'title'    => _x( 'View shipment', 'shipments', 'woocommerce-germanized' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; View shipment" page.', 'shipments', 'woocommerce-germanized' ),
					'id'       => 'woocommerce_gzd_shipments_view_shipment_endpoint',
					'type'     => 'text',
					'default'  => 'view-shipment',
					'desc_tip' => true,
				),
				array(
					'title'    => _x( 'Add Return Shipment', 'shipments', 'woocommerce-germanized' ),
					'desc'     => _x( 'Endpoint for the "My account &rarr; Add return shipment" page.', 'shipments', 'woocommerce-germanized' ),
					'id'       => 'woocommerce_gzd_shipments_add_return_shipment_endpoint',
					'type'     => 'text',
					'default'  => 'add-return-shipment',
					'desc_tip' => true,
				),
			);

			$settings = self::add_settings_after( $settings, 'woocommerce_myaccount_downloads_endpoint', $endpoints );
		}

		return $settings;
	}

	public static function menu_return_count() {
		global $submenu;

		if ( isset( $submenu['woocommerce'] ) ) {

			/**
			 * Filter to adjust whether to include requested return count in admin menu or not.
			 *
			 * @param boolean $show_count Whether to show count or not.
			 *
			 * @since 3.1.3
			 * @package Vendidero/Germanized/Shipments
			 */
			if ( apply_filters( 'woocommerce_gzd_shipments_include_requested_return_count_in_menu', true ) && current_user_can( 'edit_others_shop_orders' ) ) {
				$return_count = wc_gzd_get_shipment_count( 'requested', 'return' );

				if ( $return_count ) {
					foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
						if ( 0 === strpos( $menu_item[0], _x( 'Returns', 'shipments', 'woocommerce-germanized' ) ) ) {
							$submenu['woocommerce'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . esc_attr( $return_count ) . '"><span class="requested-count">' . number_format_i18n( $return_count ) . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							break;
						}
					}
				}
			}
		}
	}

	public static function get_admin_shipment_item_columns( $shipment ) {
		$item_columns = array(
			'name'     => array(
				'title' => _x( 'Item', 'shipments', 'woocommerce-germanized' ),
				'size'  => 6,
				'order' => 5,
			),
			'quantity' => array(
				'title' => _x( 'Quantity', 'shipments', 'woocommerce-germanized' ),
				'size'  => 3,
				'order' => 10,
			),
			'action'   => array(
				'title' => _x( 'Actions', 'shipments', 'woocommerce-germanized' ),
				'size'  => 3,
				'order' => 15,
			),
		);

		if ( 'return' === $shipment->get_type() ) {
			$item_columns['return_reason'] = array(
				'title' => _x( 'Reason', 'shipments', 'woocommerce-germanized' ),
				'size'  => 3,
				'order' => 7,
			);

			$item_columns['name']['size']     = 5;
			$item_columns['quantity']['size'] = 2;
			$item_columns['action']['size']   = 2;
		}

		uasort( $item_columns, array( __CLASS__, 'sort_shipment_item_columns' ) );

		/**
		 * Filter to adjust shipment item columns shown in admin view.
		 *
		 * @param array    $item_columns The columns available.
		 * @param Shipment $shipment The shipment.
		 *
		 * @since 3.1.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( 'woocommerce_gzd_shipments_meta_box_shipment_item_columns', $item_columns, $shipment );
	}

	protected static function sort_shipment_item_columns( $a, $b ) {
		if ( $a['order'] === $b['order'] ) {
			return 0;
		}

		return ( $a['order'] < $b['order'] ) ? -1 : 1;
	}

	public static function save_packaging_list() {
		$current_key_list         = array();
		$packaging_ids_after_save = array();

		foreach ( wc_gzd_get_packaging_list() as $pack ) {
			$current_key_list[] = $pack->get_id();
		}

		if ( isset( $_POST['packaging'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$packaging_post  = wc_clean( wp_unslash( $_POST['packaging'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order           = 0;
			$available_types = array_keys( wc_gzd_get_packaging_types() );

			foreach ( $packaging_post as $packaging ) {
				$packaging     = wc_clean( $packaging );
				$packaging_id  = isset( $packaging['packaging_id'] ) ? absint( $packaging['packaging_id'] ) : 0;
				$packaging_obj = wc_gzd_get_packaging( $packaging_id );

				if ( $packaging_obj ) {
					$packaging_obj->set_props(
						array(
							'type'               => ! in_array( $packaging['type'], $available_types, true ) ? 'cardboard' : $packaging['type'],
							'weight'             => empty( $packaging['weight'] ) ? 0 : $packaging['weight'],
							'description'        => empty( $packaging['description'] ) ? '' : $packaging['description'],
							'length'             => empty( $packaging['length'] ) ? 0 : $packaging['length'],
							'width'              => empty( $packaging['width'] ) ? 0 : $packaging['width'],
							'height'             => empty( $packaging['height'] ) ? 0 : $packaging['height'],
							'max_content_weight' => empty( $packaging['max_content_weight'] ) ? 0 : $packaging['max_content_weight'],
							'order'              => ++$order,
						)
					);

					if ( empty( $packaging_obj->get_description() ) ) {
						if ( $packaging_obj->get_id() > 0 ) {
							$packaging_obj->delete( true );
							continue;
						} else {
							continue;
						}
					}

					$packaging_obj->save();
					$packaging_ids_after_save[] = $packaging_obj->get_id();
				}
			}
		}

		$to_delete = array_diff( $current_key_list, $packaging_ids_after_save );

		if ( ! empty( $to_delete ) ) {
			foreach ( $to_delete as $delete_id ) {
				if ( $packaging = wc_gzd_get_packaging( $delete_id ) ) {
					$packaging->delete( true );
				}
			}
		}
	}

	public static function save_return_reasons( $tab, $current_section ) {
		if ( '' !== $current_section ) {
			return;
		}

		$reasons = array();

		if ( isset( $_POST['shipment_return_reason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$reasons_post = wc_clean( wp_unslash( $_POST['shipment_return_reason'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order        = 0;

			foreach ( $reasons_post as $reason ) {
				$code        = isset( $reason['code'] ) ? $reason['code'] : '';
				$reason_text = isset( $reason['reason'] ) ? $reason['reason'] : '';

				if ( empty( $code ) ) {
					$code = sanitize_title( $reason_text );
				}

				if ( ! empty( $reason_text ) ) {
					$reasons[] = array(
						'order'  => ++$order,
						'code'   => $code,
						'reason' => $reason_text,
					);
				}
			}
		}
		// phpcs:enable

		update_option( 'woocommerce_gzd_shipments_return_reasons', $reasons );
	}

	public static function output_return_reasons_field( $value ) {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html_x( 'Return reasons', 'shipments', 'woocommerce-germanized' ); ?></th>
			<td class="forminp" id="shipment_return_reasons">
				<div class="wc_input_table_wrapper">
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th style="width: 10ch;"><?php echo esc_html_x( 'Reason code', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'The reason code is used to identify the reason.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
							<th><?php echo esc_html_x( 'Reason', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Choose a reason text.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
						</tr>
						</thead>
						<tbody class="shipment_return_reasons">
						<?php
						$i = -1;
						foreach ( wc_gzd_get_return_shipment_reasons() as $reason ) {
							$i++;

							echo '<tr class="reason">
                                    <td class="sort"></td>
                                    <td style="width: 10ch;"><input type="text" value="' . esc_attr( wp_unslash( $reason->get_code() ) ) . '" name="shipment_return_reason[' . esc_attr( $i ) . '][code]" /></td>
                                    <td><input type="text" value="' . esc_attr( wp_unslash( $reason->get_reason() ) ) . '" name="shipment_return_reason[' . esc_attr( $i ) . '][reason]" /></td>
                                </tr>';
						}
						?>
						</tbody>
						<tfoot>
						<tr>
							<th colspan="7"><a href="#" class="add button"><?php echo esc_html_x( '+ Add reason', 'shipments', 'woocommerce-germanized' ); ?></a> <a href="#" class="remove_rows button"><?php echo esc_html_x( 'Remove selected reason(s)', 'shipments', 'woocommerce-germanized' ); ?></a></th>
						</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#shipment_return_reasons').on( 'click', 'a.add', function(){

							var size = jQuery('#shipment_return_reasons').find('tbody .reason').length;

							jQuery('<tr class="reason">\
									<td class="sort"></td>\
									<td style="width: 10ch;"><input type="text" name="shipment_return_reason[' + size + '][code]" /></td>\
									<td><input type="text" name="shipment_return_reason[' + size + '][reason]" /></td>\
								</tr>').appendTo('#shipment_return_reasons table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function output_packaging_reports( $value ) {
		$reports = ReportHelper::get_reports();
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><label for="wc_gzd_shipments_create_packaging_report_year"><?php echo esc_html_x( 'Packaging Reports', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Generate summary reports which contain information about the amount of packaging material used for your shipments.', 'shipments', 'woocommerce-germanized' ) ); ?></label></th>
			<td class="forminp" id="packaging_reports_wrapper">
				<style>
					.wc-gzd-shipments-create-packaging-report {
						margin-bottom: 15px;
						padding: 0;
					}
					.wc-gzd-shipments-create-packaging-report select {
						width: auto !important;
						min-width: 120px;
					}
					.wc-gzd-shipments-create-packaging-report button.button {
						height: 34px;
						margin-left: 10px;
					}
					table.packaging_reports_table thead th {
						padding: 10px;
					}
					table.packaging_reports_table tbody td {
						padding: 15px 10px;
					}

					table.packaging_reports_table tbody td .packaging-report-status {
						margin-left: 5px;
					}
				</style>
				<div class="wc-gzd-shipments-create-packaging-report submit">
					<select name="report_year" id="wc_gzd_shipments_create_packaging_report_year">
						<?php
						foreach ( array_reverse( range( (int) date( 'Y' ) - 2, (int) date( 'Y' ) ) ) as $year ) : // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							$start_day = date( 'Y-m-d', strtotime( $year . '-01-01' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							?>
							<option value="<?php echo esc_html( $start_day ); ?>"><?php echo esc_html( $year ); ?></option>
						<?php endforeach; ?>
					</select>

					<button class="button" type="submit" name="save" value="create_report"><?php echo esc_html_x( 'Create report', 'shipments', 'woocommerce-germanized' ); ?></button>
				</div>

				<?php if ( ! empty( $reports ) ) : ?>
					<table class="widefat packaging_reports_table" cellspacing="0">
						<thead>
						<tr>
							<th style="width: 30ch;"><?php echo esc_html_x( 'Report', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 20ch;"><?php echo esc_html_x( 'Start', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 20ch;"><?php echo esc_html_x( 'End', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Total weight', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Count', 'shipments', 'woocommerce-germanized' ); ?></th>
						</tr>
						</thead>
						<tbody class="">
							<?php foreach ( $reports as $report ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( $report->get_url() ); ?>" target="_blank"><?php echo esc_html( $report->get_title() ); ?></a> <span class="packaging-report-status status-<?php echo esc_attr( $report->get_status() ); ?>"><?php echo esc_html( ReportHelper::get_report_status_title( $report->get_status() ) ); ?></span></td>
									<td>
										<?php
										$show_date = $report->get_date_start()->date_i18n( wc_date_format() );

										printf(
											'<time datetime="%1$s" title="%2$s">%3$s</time>',
											esc_attr( $report->get_date_start()->date( 'c' ) ),
											esc_html( $report->get_date_start()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
											esc_html( $show_date )
										);
										?>
									</td>
									<td>
										<?php
										$show_date = $report->get_date_end()->date_i18n( wc_date_format() );

										printf(
											'<time datetime="%1$s" title="%2$s">%3$s</time>',
											esc_attr( $report->get_date_end()->date( 'c' ) ),
											esc_html( $report->get_date_end()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
											esc_html( $show_date )
										);
										?>
									</td>
									<td>
										<?php echo esc_html( wc_gzd_format_shipment_weight( $report->get_total_weight(), wc_gzd_get_packaging_weight_unit() ) ); ?>
									</td>
									<td>
										<?php echo esc_html( $report->get_total_count() ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function output_packaging_list( $value ) {
		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html_x( 'Available Packaging', 'shipments', 'woocommerce-germanized' ); ?></th>
			<td class="forminp" id="packaging_list_wrapper">
				<div class="wc_input_table_wrapper">
					<style>
						tbody.packaging_list tr td {
							padding: .5em;
						}
						tbody.packaging_list select {
							width: 100% !important;
						}
						tbody.packaging_list .input-inner-wrap {
							clear: both;
						}
						tbody.packaging_list .input-inner-wrap input.wc_input_decimal {
							width: 33% !important;
							min-width: auto !important;
							float: left !important;
						}
					</style>
					<table class="widefat wc_input_table sortable" cellspacing="0">
						<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th style="width: 20ch;"><?php echo esc_html_x( 'Description', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'A description to help you identify the packaging.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
							<th style="width: 15ch;"><?php echo esc_html_x( 'Type', 'shipments', 'woocommerce-germanized' ); ?></th>
							<th style="width: 5ch;"><?php echo sprintf( esc_html_x( 'Weight (%s)', 'shipments', 'woocommerce-germanized' ), esc_html( wc_gzd_get_packaging_weight_unit() ) ); ?> <?php echo wc_help_tip( _x( 'The weight of the packaging.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
							<th style="width: 15ch;"><?php echo sprintf( esc_html_x( 'Dimensions (LxWxH, %s)', 'shipments', 'woocommerce-germanized' ), esc_html( wc_gzd_get_packaging_dimension_unit() ) ); ?></th>
							<th style="width: 5ch;"><?php echo esc_html_x( 'Max weight (kg)', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'The maximum weight this packaging can hold. Leave empty to not restrict maximum weight.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
						</tr>
						</thead>
						<tbody class="packaging_list">
						<?php
						$count = 0;
						foreach ( wc_gzd_get_packaging_list() as $packaging ) :
							?>
							<tr class="packaging">
								<td class="sort"></td>
								<td style="width: 20ch;">
									<input type="text" name="packaging[<?php echo esc_attr( $count ); ?>][description]" value="<?php echo esc_attr( wp_unslash( $packaging->get_description() ) ); ?>" />
									<input type="hidden" name="packaging[<?php echo esc_attr( $count ); ?>][packaging_id]" value="<?php echo esc_attr( $packaging->get_id() ); ?>" />
								</td>
								<td style="width: 15ch;">
									<select name="packaging[<?php echo esc_attr( $count ); ?>][type]">
										<?php foreach ( wc_gzd_get_packaging_types() as $type => $type_title ) : ?>
											<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $packaging->get_type(), $type ); ?>><?php echo esc_attr( $type_title ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td style="width: 5ch;">
									<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][weight]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_weight() ) ); ?>" placeholder="0" />
								</td>
								<td style="width: 15ch;">
									<span class="input-inner-wrap">
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][length]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_length() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Length', 'shipments', 'woocommerce-germanized' ) ); ?>" />
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][width]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_width() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Width', 'shipments', 'woocommerce-germanized' ) ); ?>" />
										<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][height]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_height() ) ); ?>" placeholder="<?php echo esc_attr( _x( 'Height', 'shipments', 'woocommerce-germanized' ) ); ?>" />
									</span>
								</td>
								<td style="width: 5ch;">
									<input class="wc_input_decimal" type="text" name="packaging[<?php echo esc_attr( $count ); ?>][max_content_weight]" value="<?php echo esc_attr( wc_format_localized_decimal( $packaging->get_max_content_weight() ) ); ?>" placeholder="0" />
								</td>
							</tr>
							<?php
							$count++;
						endforeach;
						?>
						</tbody>
						<tfoot>
						<tr>
							<th colspan="7"><a href="#" class="add button"><?php echo esc_html_x( '+ Add packaging', 'shipments', 'woocommerce-germanized' ); ?></a> <a href="#" class="remove_rows button"><?php echo esc_html_x( 'Remove selected packaging', 'shipments', 'woocommerce-germanized' ); ?></a></th>
						</tr>
						</tfoot>
					</table>
				</div>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#packaging_list_wrapper').on( 'click', 'a.add', function(){

							var size = jQuery('#packaging_list_wrapper').find('tbody .packaging').length;

							jQuery('<tr class="packaging">\
									<td class="sort"></td>\
									<td style="width: 10ch;"><input type="text" name="packaging[' + size + '][description]" value="" /></td>\
									<td style="width: 10ch;">\
										<select name="packaging[' + size + '][type]">\
											<?php
											foreach ( wc_gzd_get_packaging_types() as $type => $type_title ) :
												?>
												\
												<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_attr( $type_title ); ?></option>\
											<?php endforeach; ?>\
										</select>\
									</td>\
									<td style="width: 5ch;">\
										<input class="wc_input_decimal" type="text" name="packaging[' + size + '][weight]" placeholder="0" />\
									</td>\
									<td style="width: 15ch;">\
										<span class="input-inner-wrap">\
											<input class="wc_input_decimal" type="text" name="packaging[' + size + '][length]" value="" placeholder="<?php echo esc_attr( _x( 'Length', 'shipments', 'woocommerce-germanized' ) ); ?>" />\
											<input class="wc_input_decimal" type="text" name="packaging[' + size + '][width]" value="" placeholder="<?php echo esc_attr( _x( 'Width', 'shipments', 'woocommerce-germanized' ) ); ?>" />\
											<input class="wc_input_decimal" type="text" name="packaging[' + size + '][height]" value="" placeholder="<?php echo esc_attr( _x( 'Height', 'shipments', 'woocommerce-germanized' ) ); ?>" />\
										</span>\
									</td>\
									<td style="width: 5ch;">\
										<input class="wc_input_decimal" type="text" name="packaging[' + size + '][max_content_weight]" placeholder="0" />\
									</td>\
								</tr>').appendTo('#packaging_list_wrapper table tbody');

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function add_template_check( $check ) {
		$check['germanized']['path'][] = Package::get_path() . '/templates';

		return $check;
	}

	public static function add_table_view( $screen_ids ) {
		$screen_ids[] = 'woocommerce_page_wc-gzd-shipments';
		$screen_ids[] = 'woocommerce_page_wc-gzd-return-shipments';

		return $screen_ids;
	}

	public static function handle_order_bulk_actions( $redirect_to, $action, $ids ) {
		$ids           = apply_filters( 'woocommerce_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'order' );
		$changed       = 0;
		$report_action = '';

		if ( 'gzd_create_shipments' === $action ) {
			foreach ( $ids as $id ) {
				$order         = wc_get_order( $id );
				$report_action = 'gzd_created_shipments';

				if ( $order ) {
					Automation::create_shipments( $id, false );
					$changed++;
				}
			}
		}

		if ( $changed ) {
			$redirect_query_args = array(
				'post_type'   => 'shop_order',
				'bulk_action' => $report_action,
				'changed'     => $changed,
				'ids'         => join( ',', $ids ),
			);

			if ( Package::is_hpos_enabled() ) {
				unset( $redirect_query_args['post_type'] );
				$redirect_query_args['page'] = 'wc-orders';
			}

			$redirect_to = add_query_arg(
				$redirect_query_args,
				$redirect_to
			);

			return esc_url_raw( $redirect_to );
		} else {
			return $redirect_to;
		}
	}

	public static function define_order_bulk_actions( $actions ) {
		$actions['gzd_create_shipments'] = _x( 'Create shipments', 'shipments', 'woocommerce-germanized' );

		return $actions;
	}

	public static function set_screen_option( $new_value, $option, $value ) {

		if ( in_array( $option, array( 'woocommerce_page_wc_gzd_shipments_per_page', 'woocommerce_page_wc_gzd_return_shipments_per_page' ), true ) ) {
			return absint( $value );
		}

		return $new_value;
	}

	public static function shipments_menu() {
		add_submenu_page( 'woocommerce', _x( 'Shipments', 'shipments', 'woocommerce-germanized' ), _x( 'Shipments', 'shipments', 'woocommerce-germanized' ), 'edit_others_shop_orders', 'wc-gzd-shipments', array( __CLASS__, 'shipments_page' ) );
		add_submenu_page( 'woocommerce', _x( 'Returns', 'shipments', 'woocommerce-germanized' ), _x( 'Returns', 'shipments', 'woocommerce-germanized' ), 'edit_others_shop_orders', 'wc-gzd-return-shipments', array( __CLASS__, 'returns_page' ) );
	}

	/**
	 * @param Shipment $shipment
	 */
	public static function get_shipment_tracking_html( $shipment ) {
		$tracking_html = '';

		if ( $tracking_id = $shipment->get_tracking_id() ) {

			if ( $tracking_url = $shipment->get_tracking_url() ) {
				$tracking_html = '<a class="shipment-tracking-number" href="' . esc_url( $tracking_url ) . '" target="_blank">' . $tracking_id . '</a>';
			} else {
				$tracking_html = '<span class="shipment-tracking-number">' . $tracking_id . '</span>';
			}
		}

		return $tracking_html;
	}

	/**
	 * @param Table $table
	 */
	protected static function setup_table( $table ) {
		global $wp_list_table;

		$wp_list_table = $table; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$doaction      = $wp_list_table->current_action();

		if ( $doaction ) {
			check_admin_referer( 'bulk-shipments' );

			$pagenum     = $wp_list_table->get_pagenum();
			$parent_file = $wp_list_table->get_main_page();
			$sendback    = remove_query_arg( array( 'deleted', 'ids', 'changed', 'bulk_action' ), wp_get_referer() );

			if ( ! $sendback ) {
				$sendback = admin_url( $parent_file );
			}

			$sendback     = add_query_arg( 'paged', $pagenum, $sendback );
			$shipment_ids = array();

			if ( isset( $_REQUEST['ids'] ) ) {
				$shipment_ids = array_map( 'absint', explode( ',', wp_unslash( $_REQUEST['ids'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} elseif ( ! empty( $_REQUEST['shipment'] ) ) {
				$shipment_ids = array_map( 'absint', wp_unslash( $_REQUEST['shipment'] ) );
			}

			if ( ! empty( $shipment_ids ) ) {
				$sendback = $wp_list_table->handle_bulk_actions( $doaction, $shipment_ids, $sendback );
			}

			$sendback = remove_query_arg( array( 'action', 'action2', '_status', 'bulk_edit', 'shipment' ), $sendback );

			wp_safe_redirect( esc_url_raw( $sendback ) );
			exit();

		} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			wp_safe_redirect( esc_url_raw( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			exit;
		}

		$wp_list_table->set_bulk_notice();
		$wp_list_table->prepare_items();

		add_screen_option( 'per_page' );
	}

	public static function setup_shipments_table() {
		$table = new Table();

		self::setup_table( $table );
	}

	public static function setup_returns_table() {
		$table = new ReturnTable( array( 'type' => 'return' ) );

		self::setup_table( $table );
	}

	public static function shipments_page() {
		global $wp_list_table;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Shipments', 'shipments', 'woocommerce-germanized' ); ?></h1>
			<hr class="wp-header-end" />

			<?php
			$wp_list_table->output_notice();
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=wc-gzd-shipments' ) ) );
			?>

			<?php $wp_list_table->views(); ?>

			<form id="posts-filter" method="get">

				<?php $wp_list_table->search_box( _x( 'Search shipments', 'shipments', 'woocommerce-germanized' ), 'shipment' ); ?>

				<input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( wc_clean( wp_unslash( $_REQUEST['shipment_status'] ) ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
				<input type="hidden" name="shipment_type" class="shipment_type" value="simple" />

				<input type="hidden" name="type" class="type_page" value="shipment" />
				<input type="hidden" name="page" value="wc-gzd-shipments" />

				<?php $wp_list_table->display(); ?>
			</form>

			<div id="ajax-response"></div>
			<br class="clear" />
		</div>
		<?php
	}

	public static function returns_page() {
		global $wp_list_table;

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Returns', 'shipments', 'woocommerce-germanized' ); ?></h1>
			<hr class="wp-header-end" />

			<?php
			$wp_list_table->output_notice();
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url( 'admin.php?page=wc-gzd-shipments' ) ) );
			?>

			<?php $wp_list_table->views(); ?>

			<form id="posts-filter" method="get">

				<?php $wp_list_table->search_box( _x( 'Search returns', 'shipments', 'woocommerce-germanized' ), 'shipment' ); ?>

				<input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( wc_clean( wp_unslash( $_REQUEST['shipment_status'] ) ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" />
				<input type="hidden" name="shipment_type" class="shipment_type" value="return" />

				<input type="hidden" name="type" class="type_page" value="shipment" />
				<input type="hidden" name="page" value="wc-gzd-return-shipments" />

				<?php $wp_list_table->display(); ?>
			</form>

			<div id="ajax-response"></div>
			<br class="clear" />
		</div>
		<?php
	}

	public static function add_meta_boxes() {
		$order_type_screen_ids = array_merge( wc_get_order_types( 'order-meta-boxes' ), array( self::get_order_screen_id() ) );

		// Orders.
		foreach ( $order_type_screen_ids as $type ) {
			add_meta_box( 'woocommerce-gzd-order-shipments', _x( 'Shipments', 'shipments', 'woocommerce-germanized' ), array( MetaBox::class, 'output' ), $type, 'normal', 'high' );
		}
	}

	public static function admin_styles() {
		global $wp_scripts;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin styles.
		wp_register_style( 'woocommerce_gzd_shipments_admin', Package::get_assets_url() . '/css/admin' . $suffix . '.css', array( 'woocommerce_admin_styles' ), Package::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_screen_ids(), true ) ) {
			wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
		}

		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && in_array( $_GET['tab'], array( 'germanized-shipments', 'germanized-shipping_provider' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
		}

		// Shipping zone methods
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'shipping' === $_GET['tab'] && ( isset( $_GET['zone_id'] ) || isset( $_GET['instance_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
		}
	}

	public static function admin_scripts() {
		global $post, $theorder;

		$screen               = get_current_screen();
		$screen_id            = $screen ? $screen->id : '';
		$suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$post_id              = isset( $post->ID ) ? $post->ID : '';
		$order_or_post_object = $post;

		if ( ( $theorder instanceof \WC_Order ) && self::is_order_meta_box_screen( $screen_id ) ) {
			$order_or_post_object = $theorder;
		}

		wp_register_script( 'wc-gzd-admin-shipment-label-backbone', Package::get_assets_url() . '/js/admin-shipment-label-backbone' . $suffix . '.js', array( 'jquery', 'woocommerce_admin', 'wc-backbone-modal' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipment', Package::get_assets_url() . '/js/admin-shipment' . $suffix . '.js', array( 'wc-gzd-admin-shipment-label-backbone' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipments', Package::get_assets_url() . '/js/admin-shipments' . $suffix . '.js', array( 'wc-admin-order-meta-boxes', 'wc-gzd-admin-shipment' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipments-table', Package::get_assets_url() . '/js/admin-shipments-table' . $suffix . '.js', array( 'woocommerce_admin', 'wc-gzd-admin-shipment-label-backbone' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipping-providers', Package::get_assets_url() . '/js/admin-shipping-providers' . $suffix . '.js', array( 'jquery', 'jquery-ui-sortable' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'wc-gzd-admin-shipping-provider-method', Package::get_assets_url() . '/js/admin-shipping-provider-method' . $suffix . '.js', array( 'jquery' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		// Orders.
		if ( self::is_order_meta_box_screen( $screen_id ) ) {
			wp_enqueue_script( 'wc-gzd-admin-shipments' );
			wp_enqueue_script( 'wc-gzd-admin-shipment' );

			$order_order_post_id = $post_id;

			if ( self::is_order_meta_box_screen( $screen_id ) && isset( $order_or_post_object ) && is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'get_post_or_order_id' ) ) ) {
				$order_order_post_id = \Automattic\WooCommerce\Utilities\OrderUtil::get_post_or_order_id( $order_or_post_object );
			}

			wp_localize_script(
				'wc-gzd-admin-shipments',
				'wc_gzd_admin_shipments_params',
				array(
					'ajax_url'                        => admin_url( 'admin-ajax.php' ),
					'edit_shipments_nonce'            => wp_create_nonce( 'edit-shipments' ),
					'order_id'                        => $order_order_post_id,
					'shipment_locked_excluded_fields' => array( 'status' ),
					'i18n_remove_shipment_notice'     => _x( 'Do you really want to delete the shipment?', 'shipments', 'woocommerce-germanized' ),
					'remove_label_nonce'              => wp_create_nonce( 'remove-shipment-label' ),
					'edit_label_nonce'                => wp_create_nonce( 'edit-shipment-label' ),
					'send_return_notification_nonce'  => wp_create_nonce( 'send-return-shipment-notification' ),
					'refresh_packaging_nonce'         => wp_create_nonce( 'refresh-shipment-packaging' ),
					'confirm_return_request_nonce'    => wp_create_nonce( 'confirm-return-request' ),
					'i18n_remove_label_notice'        => _x( 'Do you really want to delete the label?', 'shipments', 'woocommerce-germanized' ),
					'i18n_create_label_enabled'       => _x( 'Create new label', 'shipments', 'woocommerce-germanized' ),
					'i18n_create_label_disabled'      => _x( 'Please save the shipment before creating a new label', 'shipments', 'woocommerce-germanized' ),
				)
			);
		}

		// Table
		if ( 'woocommerce_page_wc-gzd-shipments' === $screen_id || 'woocommerce_page_wc-gzd-return-shipments' === $screen_id ) {
			wp_enqueue_script( 'wc-gzd-admin-shipments-table' );

			$bulk_actions = array();

			foreach ( self::get_bulk_action_handlers() as $handler ) {
				$bulk_actions[ sanitize_key( $handler->get_action() ) ] = array(
					'title' => $handler->get_title(),
					'nonce' => wp_create_nonce( $handler->get_nonce_name() ),
				);
			}

			wp_localize_script(
				'wc-gzd-admin-shipments-table',
				'wc_gzd_admin_shipments_table_params',
				array(
					'ajax_url'                       => admin_url( 'admin-ajax.php' ),
					'search_orders_nonce'            => wp_create_nonce( 'search-orders' ),
					'search_shipping_provider_nonce' => wp_create_nonce( 'search-shipping-provider' ),
					'bulk_actions'                   => $bulk_actions,
				)
			);
		}

		wp_localize_script(
			'wc-gzd-admin-shipment-label-backbone',
			'wc_gzd_admin_shipment_label_backbone_params',
			array(
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'i18n_modal_close'        => _x( 'Close', 'shipments-close-modal', 'woocommerce-germanized' ),
				'create_label_form_nonce' => wp_create_nonce( 'create-shipment-label-form' ),
				'create_label_nonce'      => wp_create_nonce( 'create-shipment-label' ),
			)
		);

		// Shipping provider settings
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'germanized-shipping_provider' === $_GET['tab'] && empty( $_GET['provider'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_enqueue_script( 'wc-gzd-admin-shipping-providers' );

			wp_localize_script(
				'wc-gzd-admin-shipping-providers',
				'wc_gzd_admin_shipping_providers_params',
				array(
					'ajax_url'                             => admin_url( 'admin-ajax.php' ),
					'edit_shipping_providers_nonce'        => wp_create_nonce( 'edit-shipping-providers' ),
					'remove_shipping_provider_nonce'       => wp_create_nonce( 'remove-shipping-provider' ),
					'sort_shipping_provider_nonce'         => wp_create_nonce( 'sort-shipping-provider' ),
					'i18n_remove_shipping_provider_notice' => _x( 'Do you really want to delete the shipping provider? Some of your existing shipments might be linked to that provider and might need adjustments.', 'shipments', 'woocommerce-germanized' ),
				)
			);
		}

		// Shipping provider method
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'shipping' === $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$excluded_sections = array( 'classes' ) + Package::get_excluded_methods();

			/**
			 * Older third-party shipping methods may not support instance-settings and will have their settings
			 * output in a separate section under Settings > Shipping.
			 */
			if ( ( isset( $_GET['zone_id'] ) || isset( $_GET['instance_id'] ) ) || ( isset( $_GET['section'] ) && ! in_array( $_GET['section'], $excluded_sections, true ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_script( 'wc-gzd-admin-shipping-provider-method' );
				$providers = array_filter( array_keys( wc_gzd_get_shipping_provider_select() ) );

				wp_localize_script(
					'wc-gzd-admin-shipping-provider-method',
					'wc_gzd_admin_shipping_provider_method_params',
					array(
						'shipping_providers' => $providers,
					)
				);
			}
		}
	}

	/**
	 * @return BulkActionHandler[] $handler
	 */
	public static function get_bulk_action_handlers() {
		if ( is_null( self::$bulk_handlers ) ) {
			self::$bulk_handlers = array();

			/**
			 * Filter to register new BulkActionHandler for certain Shipment bulk actions.
			 *
			 * @param array $handlers Array containing key => classname.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$handlers = apply_filters(
				'woocommerce_gzd_shipments_table_bulk_action_handlers',
				array(
					'labels' => '\Vendidero\Germanized\Shipments\Admin\BulkLabel',
				)
			);

			foreach ( $handlers as $key => $handler ) {
				self::$bulk_handlers[ $key ] = new $handler();
			}
		}

		return self::$bulk_handlers;
	}

	public static function get_bulk_action_handler( $action ) {
		$handlers = self::get_bulk_action_handlers();

		return array_key_exists( $action, $handlers ) ? $handlers[ $action ] : false;
	}

	/**
	 * Helper function to determine whether the current screen is an order edit screen.
	 *
	 * @param string $screen_id Screen ID.
	 *
	 * @return bool Whether the current screen is an order edit screen.
	 */
	protected static function is_order_meta_box_screen( $screen_id ) {
		return in_array( str_replace( 'edit-', '', $screen_id ), self::get_order_screen_ids(), true );
	}

	public static function get_order_screen_id() {
		return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	}

	protected static function get_order_screen_ids() {
		$screen_ids = array();

		foreach ( wc_get_order_types() as $type ) {
			$screen_ids[] = $type;
			$screen_ids[] = 'edit-' . $type;
		}

		$screen_ids[] = self::get_order_screen_id();

		return array_filter( $screen_ids );
	}

	public static function get_screen_ids() {
		$screen_ids = array(
			'woocommerce_page_wc-gzd-shipments',
			'woocommerce_page_wc-gzd-return-shipments',
		);

		return array_merge( $screen_ids, self::get_order_screen_ids() );
	}
}
