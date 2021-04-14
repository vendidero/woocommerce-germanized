<?php

namespace Vendidero\Germanized\DHL\Admin;
use Vendidero\Germanized\DHL\Admin\Importer\DHL;
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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ), 30 );

		add_action( 'admin_init', array( __CLASS__, 'download_legacy_label' ) );

		// Legacy meta box
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_legacy_meta_box' ), 20 );

		// Template check
		add_filter( 'woocommerce_gzd_template_check', array( __CLASS__, 'add_template_check' ), 10, 1 );

	    // Product Options
		add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'product_options' ), 9 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product' ), 10, 1 );

		// Receiver ID options
        add_action( 'woocommerce_admin_field_dhl_receiver_ids', array( __CLASS__, 'output_receiver_ids_field' ), 10 );
        add_filter( 'woocommerce_admin_settings_sanitize_option', array( __CLASS__, 'save_receiver_ids' ), 10, 3 );

		add_action( 'admin_init', array( __CLASS__, 'refresh_im_data' ) );
		add_action( 'admin_notices', array( __CLASS__, 'refresh_im_notices' ) );

		Status::init();
	}

	public static function refresh_im_notices() {
	    if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['im-refresh-type'] ) ) {
	        ?>
            <div class="notice fade <?php echo ( isset( $_GET['success'] ) ? 'updated' : 'error' ); ?>"><p><?php echo ( isset( $_GET['success'] ) ? _x( 'Refreshed data successfully.', 'dhl', 'woocommerce-germanized' ) : sprintf( _x( 'Error while refreshing data. Please make sure that the Internetmarke API URL can be <a href="%s">accessed</a>.', 'dhl', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-status&tab=dhl' ) ) ); ?></p></div>
            <?php
        }
    }

	public static function refresh_im_data() {
	    if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['action'], $_GET['_wpnonce'] ) && 'wc-gzd-dhl-im-product-refresh' === $_GET['action'] ) {
	        if ( wp_verify_nonce( $_GET['_wpnonce'], 'wc-gzd-dhl-refresh-im-products' ) ) {
	            $result       = Package::get_internetmarke_api()->update_products();
	            $settings_url = add_query_arg( array( 'im-refresh-type' => 'products' ), Package::get_deutsche_post_shipping_provider()->get_edit_link( 'label' ) );

	            if ( is_wp_error( $result ) ) {
                    $settings_url = add_query_arg( array( 'error' => 1 ), $settings_url );
                } else {
		            $settings_url = add_query_arg( array( 'success' => 1 ), $settings_url );
                }

	            wp_safe_redirect( $settings_url );
	            exit();
            }
        } elseif ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['action'], $_GET['_wpnonce'] ) && 'wc-gzd-dhl-im-page-formats-refresh' === $_GET['action'] ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'wc-gzd-dhl-refresh-im-page-formats' ) ) {
				$result       = Package::get_internetmarke_api()->get_page_formats( true );
				$settings_url = add_query_arg( array( 'im-refresh-type' => 'formats' ), Package::get_deutsche_post_shipping_provider()->get_edit_link( 'label' ) );

				if ( is_wp_error( $result ) ) {
					$settings_url = add_query_arg( array( 'error' => 1 ), $settings_url );
				} else {
					$settings_url = add_query_arg( array( 'success' => 1 ), $settings_url );
				}

				wp_safe_redirect( $settings_url );
				exit();
			}
		}
    }

	public static function save_receiver_ids( $value, $option, $raw_value ) {
	    if ( ! isset( $option['type'] ) || 'dhl_receiver_ids' !== $option['type'] ) {
	        return $value;
	    }

		$receiver  = array();
	    $raw_value = is_array( $raw_value ) ? $raw_value : array();

		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification -- Nonce verification already handled in WC_Admin_Settings::save()
		if ( isset( $raw_value['receiver_id'], $raw_value['receiver_country'] ) ) {

			$receiver_ids    = wc_clean( wp_unslash( $raw_value['receiver_id'] ) );
			$countries       = wc_clean( wp_unslash( $raw_value['receiver_country'] ) );

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

		return $receiver;
    }

	public static function output_receiver_ids_field( $option ) {
		ob_start();

		$option_key   = isset( $option['id'] ) ? $option['id'] : 'dhl_receiver_ids';
		$receiver_ids = isset( $option['value'] ) ? $option['value'] : array();
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
                        foreach ( $receiver_ids as $receiver ) {
                            $i++;

                            echo '<tr class="receiver">
                                    <td><input type="text" value="' . esc_attr( wp_unslash( $receiver['id'] ) ) . '" name="' . $option_key . '[receiver_id][' . esc_attr( $i ) . ']" /></td>
                                    <td><input type="text" value="' . esc_attr( wp_unslash( $receiver['country'] ) ) . '" name="' . $option_key . '[receiver_country][' . esc_attr( $i ) . ']" /></td>
                                </tr>';
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
									<td><input type="text" name="<?php echo $option_key; ?>[receiver_id][' + size + ']" /></td>\
									<td><input type="text" name="<?php echo $option_key; ?>[receiver_country][' + size + ']" /></td>\
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

        woocommerce_wp_text_input( array( 'id' => '_dhl_hs_code', 'label' => _x( 'HS-Code (DHL)', 'dhl', 'woocommerce-germanized' ), 'desc_tip' => true, 'description' => _x( 'The HS Code is a number assigned to every possible commodity that can be imported or exported from any country.', 'dhl', 'woocommerce-germanized' ) ) );
        woocommerce_wp_select( array( 'options' => $countries, 'id' => '_dhl_manufacture_country', 'label' => _x( 'Country of manufacture (DHL)', 'dhl', 'woocommerce-germanized' ), 'desc_tip' => true, 'description' => _x( 'The country of manufacture is needed for customs of international shipping.', 'dhl', 'woocommerce-germanized' ) ) );
	}

    public static function save_product( $product ) {
	    $hs_code = isset( $_POST['_dhl_hs_code'] ) ? wc_clean( $_POST['_dhl_hs_code'] ) : '';
	    $country = isset( $_POST['_dhl_manufacture_country'] ) ? wc_clean( $_POST['_dhl_manufacture_country'] ) : '';

	    $dhl_product = wc_gzd_dhl_get_product( $product );
	    $dhl_product->set_hs_code( $hs_code );
	    $dhl_product->set_manufacture_country( $country );
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

		if ( ! DHL::is_plugin_enabled() && ( $post && 'shop_order' === $post->post_type && get_post_meta(  $post->ID, '_pr_shipment_dhl_label_tracking' ) ) ) {
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

	public static function download_legacy_label() {
		if( isset( $_GET['action'] ) && 'wc-gzd-dhl-download-legacy-label' === $_GET['action'] ) {
			if ( isset( $_GET['order_id'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'dhl-download-legacy-label' ) ) {
				$order_id = absint( $_GET['order_id'] );
				$args     = \Vendidero\Germanized\Shipments\Labels\DownloadHandler::parse_args( array(
					'force' => wc_string_to_bool( isset( $_GET['force'] ) ? wc_clean( $_GET['force'] ) : false )
				) );

				if ( current_user_can( 'edit_shop_orders' ) ) {
					if ( $order = wc_get_order( $order_id ) ) {
						$meta = (array) $order->get_meta( '_pr_shipment_dhl_label_tracking' );

						if ( ! empty( $meta ) ) {
							$path = $meta['label_path'];

							if ( file_exists( $path ) ) {
								$filename = basename( $path );

								\Vendidero\Germanized\Shipments\Labels\DownloadHandler::download( $path, $filename, $args['force'] );
							}
						}
					}
				}
			}
		}
	}

	public static function admin_scripts() {
		global $post;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wc-gzd-admin-dhl-internetmarke', Package::get_assets_url() . '/js/admin-internetmarke' . $suffix . '.js', array( 'jquery' ), Package::get_version() );
		wp_register_script( 'wc-gzd-admin-deutsche-post-label', Package::get_assets_url() . '/js/admin-deutsche-post-label' . $suffix . '.js', array( 'wc-gzd-admin-shipment-label-backbone' ), Package::get_version() );

		if ( wp_script_is( 'wc-gzd-admin-shipment-label-backbone', 'enqueued' ) ) {
			wp_enqueue_script( 'wc-gzd-admin-deutsche-post-label' );

			wp_localize_script(
				'wc-gzd-admin-deutsche-post-label',
				'wc_gzd_admin_deutsche_post_label_params',
				array(
					'refresh_label_preview_nonce' => wp_create_nonce( 'wc-gzd-dhl-refresh-deutsche-post-label-preview' ),
				)
			);
		}

		// Shipping zone methods
		if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['provider'] ) && 'deutsche_post' === $_GET['provider'] ) {
			wp_enqueue_script( 'wc-gzd-admin-dhl-internetmarke' );
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
