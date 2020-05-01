<?php

namespace Vendidero\Germanized\DHL\Admin;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ReturnShipment;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ) );
		add_action( 'admin_init', array( __CLASS__, 'download_label' ) );

		// Legacy meta box
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_legacy_meta_box' ), 20 );

		// Label settings
		add_action( 'woocommerce_gzd_shipment_print_dhl_label_admin_fields', array( __CLASS__, 'label_fields' ), 10, 1 );
		add_action( 'woocommerce_gzd_return_shipment_print_dhl_label_admin_fields', array( __CLASS__, 'return_label_fields' ), 10, 1 );

		// Template check
		add_filter( 'woocommerce_gzd_template_check', array( __CLASS__, 'add_template_check' ), 10, 1 );

		// Check upload folder
        add_action( 'admin_notices', array( __CLASS__, 'check_upload_dir' ) );

	    // Product Options
		add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'product_options' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product' ), 10, 1 );

		// Reveiver ID options
        add_action( 'woocommerce_admin_field_dhl_receiver_ids', array( __CLASS__, 'output_receiver_ids_field' ) );
        add_action( 'woocommerce_gzd_admin_settings_after_save_dhl_labels', array( __CLASS__, 'save_receiver_ids' ) );
	}

	/**
     * Output label admin settings.
     *
	 * @param Shipment $p_shipment
	 */
	public static function label_fields( $p_shipment ) {
	    $shipment = $p_shipment;

		if ( ! $dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() ) ) {
			return;
		}

		$path = Package::get_path() . '/includes/admin/views/html-shipment-label-backbone-form.php';

		include $path;
    }

	/**
	 * Output label admin settings.
	 *
	 * @param ReturnShipment $p_shipment
	 */
	public static function return_label_fields( $p_shipment ) {
		$shipment = $p_shipment;

		if ( ! $dhl_order = wc_gzd_dhl_get_order( $shipment->get_order() ) ) {
			return;
		}

		$path = Package::get_path() . '/includes/admin/views/html-shipment-return-label-backbone-form.php';

		include $path;
	}

	public static function save_receiver_ids() {
		$receiver = array();

		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification -- Nonce verification already handled in WC_Admin_Settings::save()
		if ( isset( $_POST['receiver_id'] ) ) {

			$receiver_ids    = wc_clean( wp_unslash( $_POST['receiver_id'] ) );
			$countries       = wc_clean( wp_unslash( $_POST['receiver_country'] ) );

			foreach ( $receiver_ids as $i => $name ) {
				$country = isset( $countries[ $i ] ) ? substr( strtoupper( $countries[ $i ] ), 0, 2 ) : '';
				$slug    = sanitize_key( $receiver_ids[ $i ] . '_' . $country );

				$receiver[ $slug ] = array(
					'id'      => $receiver_ids[ $i ],
					'country' => $country,
                    'slug'    => $slug,
				);
			}
		}
		// phpcs:enable

		update_option( 'woocommerce_gzd_dhl_retoure_receiver_ids', $receiver );
    }

	public static function output_receiver_ids_field( $value ) {
		ob_start();
		?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php echo esc_html_x(  'Receiver Ids', 'dhl', 'woocommerce-germanized' ); ?></th>
            <td class="forminp" id="dhl_receiver_ids">
                <div class="wc_input_table_wrapper">
                    <table class="widefat wc_input_table sortable" cellspacing="0">
                        <input type="text" name="dhl_settings_hider" style="display: none" data-show_if_woocommerce_gzd_dhl_label_retoure_enable="" />
                        <thead>
                        <tr>
                            <th><?php echo esc_html_x(  'Receiver Id', 'dhl', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Find your Receiver Ids within your DHL contract data.', 'dhl', 'woocommerce-germanized' ) ); ?></th>
                            <th><?php echo esc_html_x(  'Country Code', 'dhl', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Leave empty to use the Receiver Id as fallback.', 'dhl', 'woocommerce-germanized' ) ); ?></th>
                        </tr>
                        </thead>
                        <tbody class="receiver_ids">
						<?php
						$i = -1;
						if ( Package::get_return_receivers() ) {
							foreach ( Package::get_return_receivers() as $receiver ) {
								$i++;

								echo '<tr class="receiver">
										<td><input type="text" value="' . esc_attr( wp_unslash( $receiver['id'] ) ) . '" name="receiver_id[' . esc_attr( $i ) . ']" /></td>
										<td><input type="text" value="' . esc_attr( wp_unslash( $receiver['country'] ) ) . '" name="receiver_country[' . esc_attr( $i ) . ']" /></td>
									</tr>';
							}
						}
						?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="7"><a href="#" class="add button"><?php echo esc_html_x(  '+ Add receiver', 'dhl', 'woocommerce-germanized' ); ?></a> <a href="#" class="remove_rows button"><?php echo esc_html_x( 'Remove selected receiver(s)', 'dhl', 'woocommerce-germanized' ); ?></a></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <script type="text/javascript">
                    jQuery(function() {
                        jQuery('#dhl_receiver_ids').on( 'click', 'a.add', function(){

                            var size = jQuery('#dhl_receiver_ids').find('tbody .receiver').length;

                            jQuery('<tr class="receiver">\
									<td><input type="text" name="receiver_id[' + size + ']" /></td>\
									<td><input type="text" name="receiver_country[' + size + ']" /></td>\
								</tr>').appendTo('#dhl_receiver_ids table tbody');

                            return false;
                        });
                    });
                </script>
            </td>
        </tr>
		<?php
		$html = ob_get_clean();

		echo $html;
    }

	public static function product_options() {
		global $post, $thepostid;

		$thepostid     = $post->ID;
		$_product      = wc_get_product( $thepostid );
		$dhl_product   = wc_gzd_dhl_get_product( $_product );

		$countries = WC()->countries->get_countries();
		$countries = array_merge( array( '0' => _x( 'Select a country', 'dhl', 'woocommerce-germanized' )  ), $countries );

		woocommerce_wp_text_input( array( 'id' => '_dhl_hs_code', 'label' => _x( 'Harmonized Tariff Schedule (DHL)', 'dhl', 'woocommerce-germanized' ), 'desc_tip' => true, 'description' => _x(  'This code is needed for customs of international shipping.', 'dhl', 'woocommerce-germanized' ) ) );
		woocommerce_wp_select( array( 'options' => $countries, 'id' => '_dhl_manufacture_country', 'label' => _x( 'Country of manufacture (DHL)', 'dhl', 'woocommerce-germanized' ), 'desc_tip' => true, 'description' => _x(  'The country of manufacture is needed for customs of international shipping.', 'dhl', 'woocommerce-germanized' ) ) );
	}

    public static function save_product( $product ) {
	    $hs_code = isset( $_POST['_dhl_hs_code'] ) ? wc_clean( $_POST['_dhl_hs_code'] ) : '';
	    $country = isset( $_POST['_dhl_manufacture_country'] ) ? wc_clean( $_POST['_dhl_manufacture_country'] ) : '';

	    $dhl_product = wc_gzd_dhl_get_product( $product );
	    $dhl_product->set_hs_code( $hs_code );
	    $dhl_product->set_manufacture_country( $country );
    }

	public static function check_upload_dir() {
		$dir     = Package::get_upload_dir();
		$path    = $dir['basedir'];
		$dirname = basename( $path );

		if ( @is_dir( $dir['basedir'] ) )
			return;
		?>
        <div class="error">
            <p><?php printf( _x( 'DHL label upload directory missing. Please manually create the folder %s and make sure that it is writeable.', 'dhl', 'woocommerce-germanized' ), '<i>wp-content/uploads/' . $dirname . '</i>' ); ?></p>
        </div>
		<?php
    }

	public static function add_template_check( $check ) {
		$check['dhl'] = array(
			'title'             => _x( 'DHL', 'dhl', 'woocommerce-germanized' ),
			'path'              => Package::get_path() . '/templates',
			'template_path'     => Package::get_template_path(),
			'outdated_help_url' => $check['germanized']['outdated_help_url'],
			'files'             => array(),
			'has_outdated'      => false,
		);

		return $check;
    }

	public static function add_legacy_meta_box() {
		global $post;

		if ( ! Importer::is_plugin_enabled() && ( $post && 'shop_order' === $post->post_type && get_post_meta(  $post->ID, '_pr_shipment_dhl_label_tracking' ) ) ) {
			add_meta_box( 'woocommerce-gzd-shipment-dhl-legacy-label', _x( 'DHL Label', 'dhl', 'woocommerce-germanized' ), array( __CLASS__, 'legacy_meta_box' ), 'shop_order', 'side', 'high' );
		}
	}

	public static function legacy_meta_box() {
		global $post;

		$order_id = $post->ID;
		$order    = wc_get_order( $order_id );
		$meta     = $order->get_meta( '_pr_shipment_dhl_label_tracking' );

		if ( ! empty( $meta ) ) {
			echo '<p>' . _x( 'This label has been generated by the DHL for WooCommerce Plugin and is shown for legacy purposes.', 'dhl', 'woocommerce-germanized' ) . '</p>';
			echo '<a class="button button-primary" target="_blank" href="' . self::get_legacy_label_download_url( $order_id ) . '">' . _x( 'Download label', 'dhl', 'woocommerce-germanized' ) . '</a>';
		}
	}

	public static function get_legacy_label_download_url( $order_id ) {
		$url = add_query_arg( array( 'action' => 'wc-gzd-dhl-download-legacy-label', 'order_id' => $order_id, 'force' => 'yes' ), wp_nonce_url( admin_url(), 'dhl-download-legacy-label' ) );

		return $url;
	}

	public static function download_label() {
		if( isset( $_GET['action'] ) && 'wc-gzd-dhl-download-legacy-label' === $_GET['action'] ) {
			if ( isset( $_GET['order_id'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'dhl-download-legacy-label' ) ) {

				$order_id = absint( $_GET['order_id'] );
				$args     = wp_parse_args( $_GET, array(
					'force'  => 'no',
					'print'  => 'no',
				) );

				DownloadHandler::download_legacy_label( $order_id, $args );
			}
		}
	}

	public static function admin_styles() {
		global $wp_scripts;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin styles.
		wp_register_style( 'woocommerce_gzd_dhl_admin', Package::get_assets_url() . '/css/admin' . $suffix . '.css', array( 'woocommerce_admin_styles' ), Package::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_screen_ids() ) ) {
			wp_enqueue_style( 'woocommerce_gzd_dhl_admin' );
		}

		// Shipping zone methods
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'shipping' === $_GET['tab'] && isset( $_GET['zone_id'] ) ) {
			wp_enqueue_style( 'woocommerce_gzd_dhl_admin' );
		}
	}

	protected static function get_table_screen_ids() {
	    return array(
            'woocommerce_page_wc-gzd-shipments',
            'woocommerce_page_wc-gzd-return-shipments'
        );
    }

	public static function get_screen_ids() {
		$screen_ids = self::get_table_screen_ids();

		foreach ( wc_get_order_types() as $type ) {
			$screen_ids[] = $type;
			$screen_ids[] = 'edit-' . $type;
		}

		return $screen_ids;
	}
}
