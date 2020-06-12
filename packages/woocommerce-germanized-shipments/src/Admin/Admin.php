<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Package;
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

	    add_action( 'admin_init', array( __CLASS__, 'download_label' ) );

        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 35 );
        add_action( 'woocommerce_process_shop_order_meta', 'Vendidero\Germanized\Shipments\Admin\MetaBox::save', 60, 2 );

        add_action( 'admin_menu', array( __CLASS__, 'shipments_menu' ), 15 );
        add_action( 'load-woocommerce_page_wc-gzd-shipments', array( __CLASS__, 'setup_shipments_table' ), 0 );
	    add_action( 'load-woocommerce_page_wc-gzd-return-shipments', array( __CLASS__, 'setup_returns_table' ), 0 );
        add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );

        add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_table_view' ), 10 );

	    add_filter( 'handle_bulk_actions-edit-shop_order', array( __CLASS__, 'handle_order_bulk_actions' ), 10, 3 );
	    add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'define_order_bulk_actions' ), 10, 1 );

	    // Template check
	    add_filter( 'woocommerce_gzd_template_check', array( __CLASS__, 'add_template_check' ), 10, 1 );

	    // Return reason options
	    add_action( 'woocommerce_admin_field_shipment_return_reasons', array( __CLASS__, 'output_return_reasons_field' ) );
	    add_action( 'woocommerce_gzd_admin_settings_after_save_shipments', array( __CLASS__, 'save_return_reasons' ) );

	    // Menu count
	    add_action( 'admin_head', array( __CLASS__, 'menu_return_count' ) );
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
		    if ( apply_filters( 'woocommerce_gzd_shipments_include_requested_return_count_in_menu', true ) && current_user_can( 'manage_woocommerce' ) ) {
			    $return_count = wc_gzd_get_shipment_count( 'requested', 'return' );

			    if ( $return_count ) {
				    foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
					    if ( 0 === strpos( $menu_item[0], _x( 'Returns', 'shipments', 'woocommerce-germanized' ) ) ) {
						    $submenu['woocommerce'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . esc_attr( $return_count ) . '"><span class="requested-count">' . number_format_i18n( $return_count ) . '</span></span>'; // WPCS: override ok.
						    break;
					    }
				    }
			    }
		    }
	    }
    }

    public static function get_admin_shipment_item_columns( $shipment ) {
	    $item_columns = array(
		    'name' => array(
			    'title' => _x( 'Item', 'shipments', 'woocommerce-germanized' ),
			    'size'  => 6,
			    'order' => 5,
		    ),
		    'quantity' => array(
			    'title' => _x( 'Quantity', 'shipments', 'woocommerce-germanized' ),
			    'size'  => 3,
			    'order' => 10,
		    ),
		    'action' => array(
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

	    uasort ( $item_columns, array( __CLASS__, '_sort_shipment_item_columns' ) );

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

    public static function _sort_shipment_item_columns( $a, $b ) {
	    if ( $a['order'] == $b['order'] ) {
		    return 0;
	    }
	    return ( $a['order'] < $b['order'] ) ? -1 : 1;
    }

    public static function save_return_reasons() {
	    $reasons = array();

	    // phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification -- Nonce verification already handled in WC_Admin_Settings::save()
	    if ( isset( $_POST['shipment_return_reason'] ) ) {

		    $reasons_post = wc_clean( wp_unslash( $_POST['shipment_return_reason'] ) );
		    $order   = 0;

		    foreach( $reasons_post as $reason ) {
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
            <th scope="row" class="titledesc"><?php echo esc_html_x(  'Return reasons', 'shipments', 'woocommerce-germanized' ); ?></th>
            <td class="forminp" id="shipment_return_reasons">
                <div class="wc_input_table_wrapper">
                    <table class="widefat wc_input_table sortable" cellspacing="0">
                        <thead>
                        <tr>
                            <th class="sort">&nbsp;</th>
                            <th style="width: 10ch;"><?php echo esc_html_x(  'Reason code', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'The reason code is used to identify the reason.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
                            <th><?php echo esc_html_x(  'Reason', 'shipments', 'woocommerce-germanized' ); ?> <?php echo wc_help_tip( _x( 'Choose a reason text.', 'shipments', 'woocommerce-germanized' ) ); ?></th>
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
                            <th colspan="7"><a href="#" class="add button"><?php echo esc_html_x(  '+ Add reason', 'shipments', 'woocommerce-germanized' ); ?></a> <a href="#" class="remove_rows button"><?php echo esc_html_x( 'Remove selected reason(s)', 'shipments', 'woocommerce-germanized' ); ?></a></th>
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

		echo $html;
	}

	public static function download_label() {
		if ( isset( $_GET['action'] ) && 'wc-gzd-download-shipment-label' === $_GET['action'] ) {
			if ( isset( $_GET['shipment_id'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'download-shipment-label' ) ) {

				$shipment_id = absint( $_GET['shipment_id'] );
				$args       = wp_parse_args( $_GET, array(
					'force'  => 'no',
				) );

				if ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) {

				    if ( $shipment->has_label() ) {
				        $shipment->get_label()->download( $args );
                    }
                }
			}
		} elseif( isset( $_GET['action'] ) && 'wc-gzd-download-export-shipment-label' === $_GET['action'] ) {
			if ( wp_verify_nonce( $_REQUEST['_wpnonce'], 'download-export-shipment-label' ) ) {

				$args = wp_parse_args( $_GET, array(
					'force'  => 'no',
					'print'  => 'no',
				) );

				DownloadHandler::download_export( $args );
			}
		}
	}

	public static function add_template_check( $check ) {
		$check['shipments'] = array(
			'title'             => _x( 'Shipments', 'shipments', 'woocommerce-germanized' ),
			'path'              => Package::get_path() . '/templates',
			'template_path'     => $check['germanized']['template_path'],
			'outdated_help_url' => $check['germanized']['outdated_help_url'],
			'files'             => array(),
			'has_outdated'      => false,
		);

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
		    $redirect_to = add_query_arg(
			    array(
				    'post_type'   => 'shop_order',
				    'bulk_action' => $report_action,
				    'changed'     => $changed,
				    'ids'         => join( ',', $ids ),
			    ),
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

        if ( in_array( $option, array( 'woocommerce_page_wc_gzd_shipments_per_page', 'woocommerce_page_wc_gzd_return_shipments_per_page' ) ) ) {
            return absint( $value );
        }

        return $new_value;
    }

    public static function shipments_menu() {
        add_submenu_page( 'woocommerce', _x( 'Shipments', 'shipments', 'woocommerce-germanized' ), _x( 'Shipments', 'shipments', 'woocommerce-germanized' ), 'manage_woocommerce', 'wc-gzd-shipments', array( __CLASS__, 'shipments_page' ) );
	    add_submenu_page( 'woocommerce', _x( 'Returns', 'shipments', 'woocommerce-germanized' ), _x( 'Returns', 'shipments', 'woocommerce-germanized' ), 'manage_woocommerce', 'wc-gzd-return-shipments', array( __CLASS__, 'returns_page' ) );
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

	    $wp_list_table = $table;
	    $doaction      = $wp_list_table->current_action();

	    if ( $doaction ) {
		    check_admin_referer( 'bulk-shipments' );

		    $pagenum       = $wp_list_table->get_pagenum();
		    $parent_file   = $wp_list_table->get_main_page();
		    $sendback      = remove_query_arg( array( 'deleted', 'ids', 'changed', 'bulk_action' ), wp_get_referer() );

		    if ( ! $sendback ) {
			    $sendback = admin_url( $parent_file );
		    }

		    $sendback       = add_query_arg( 'paged', $pagenum, $sendback );
		    $shipment_ids   = array();

		    if ( isset( $_REQUEST['ids'] ) ) {
			    $shipment_ids = explode( ',', $_REQUEST['ids'] );
		    } elseif ( ! empty( $_REQUEST['shipment'] ) ) {
			    $shipment_ids = array_map( 'intval', $_REQUEST['shipment'] );
		    }

		    if ( ! empty( $shipment_ids ) ) {
			    $sendback = $wp_list_table->handle_bulk_actions( $doaction, $shipment_ids, $sendback );
		    }

		    $sendback = remove_query_arg( array( 'action', 'action2', '_status', 'bulk_edit', 'shipment' ), $sendback );

		    wp_redirect( $sendback );
		    exit();

	    } elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
		    wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
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
            <h1 class="wp-heading-inline"><?php echo _x( 'Shipments', 'shipments', 'woocommerce-germanized' ); ?></h1>
            <hr class="wp-header-end" />

            <?php
            $wp_list_table->output_notice();
            $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), $_SERVER['REQUEST_URI'] );
            ?>

            <?php $wp_list_table->views(); ?>

            <form id="posts-filter" method="get">

                <?php $wp_list_table->search_box( _x( 'Search shipments', 'shipments', 'woocommerce-germanized' ), 'shipment' ); ?>

                <input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( $_REQUEST['shipment_status'] ) : 'all'; ?>" />
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
            <h1 class="wp-heading-inline"><?php echo _x( 'Returns', 'shipments', 'woocommerce-germanized' ); ?></h1>
            <hr class="wp-header-end" />

			<?php
			$wp_list_table->output_notice();
			$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'updated', 'changed', 'deleted', 'trashed', 'untrashed' ), $_SERVER['REQUEST_URI'] );
			?>

			<?php $wp_list_table->views(); ?>

            <form id="posts-filter" method="get">

				<?php $wp_list_table->search_box( _x( 'Search returns', 'shipments', 'woocommerce-germanized' ), 'shipment' ); ?>

                <input type="hidden" name="shipment_status" class="shipment_status_page" value="<?php echo ! empty( $_REQUEST['shipment_status'] ) ? esc_attr( $_REQUEST['shipment_status'] ) : 'all'; ?>" />
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

        // Orders.
        foreach ( wc_get_order_types( 'order-meta-boxes' ) as $type ) {
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
        if ( in_array( $screen_id, self::get_screen_ids() ) ) {
            wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
        }

        if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'germanized-shipments' === $_GET['tab'] ) {
	        wp_enqueue_style( 'woocommerce_gzd_shipments_admin' );
        }
    }

    public static function admin_scripts() {
        global $post;

        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        $suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	    wp_register_script( 'wc-gzd-admin-shipment-label-backbone', Package::get_assets_url() . '/js/admin-shipment-label-backbone' . $suffix . '.js', array( 'jquery', 'woocommerce_admin', 'wc-backbone-modal' ), Package::get_version() );
	    wp_register_script( 'wc-gzd-admin-shipment', Package::get_assets_url() . '/js/admin-shipment' . $suffix . '.js', array( 'wc-gzd-admin-shipment-label-backbone' ), Package::get_version() );
        wp_register_script( 'wc-gzd-admin-shipments', Package::get_assets_url() . '/js/admin-shipments' . $suffix . '.js', array( 'wc-admin-order-meta-boxes', 'wc-gzd-admin-shipment' ), Package::get_version() );
        wp_register_script( 'wc-gzd-admin-shipments-table', Package::get_assets_url() . '/js/admin-shipments-table' . $suffix . '.js', array( 'woocommerce_admin', 'wc-gzd-admin-shipment-label-backbone' ), Package::get_version() );
	    wp_register_script( 'wc-gzd-admin-shipping-providers', Package::get_assets_url() . '/js/admin-shipping-providers' . $suffix . '.js', array( 'jquery' ), Package::get_version() );
	    wp_register_script( 'wc-gzd-admin-shipping-provider-method', Package::get_assets_url() . '/js/admin-shipping-provider-method' . $suffix . '.js', array( 'jquery' ), Package::get_version() );

        // Orders.
        if ( in_array( str_replace( 'edit-', '', $screen_id ), wc_get_order_types( 'order-meta-boxes' ) ) ) {

            wp_enqueue_script( 'wc-gzd-admin-shipments' );
            wp_enqueue_script( 'wc-gzd-admin-shipment' );

            wp_localize_script(
                'wc-gzd-admin-shipments',
                'wc_gzd_admin_shipments_params',
                array(
                    'ajax_url'                        => admin_url( 'admin-ajax.php' ),
                    'edit_shipments_nonce'            => wp_create_nonce( 'edit-shipments' ),
                    'order_id'                        => isset( $post->ID ) ? $post->ID : '',
                    'shipment_locked_excluded_fields' => array( 'status' ),
                    'i18n_remove_shipment_notice'     => _x( 'Do you really want to delete the shipment?', 'shipments', 'woocommerce-germanized' ),
                    'remove_label_nonce'              => wp_create_nonce( 'remove-shipment-label' ),
                    'edit_label_nonce'                => wp_create_nonce( 'edit-shipment-label' ),
                    'send_return_notification_nonce'  => wp_create_nonce( 'send-return-shipment-notification' ),
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

            foreach( self::get_bulk_action_handlers() as $handler ) {
                $bulk_actions[ sanitize_key( $handler->get_action() ) ] = array(
                    'title' => $handler->get_title(),
                    'nonce' => wp_create_nonce( $handler->get_nonce_name() ),
                );
            }

            wp_localize_script(
                'wc-gzd-admin-shipments-table',
                'wc_gzd_admin_shipments_table_params',
                array(
                    'ajax_url'            => admin_url( 'admin-ajax.php' ),
                    'search_orders_nonce' => wp_create_nonce( 'search-orders' ),
                    'bulk_actions'        => $bulk_actions,
                )
            );
        }

	    wp_localize_script(
		    'wc-gzd-admin-shipment-label-backbone',
		    'wc_gzd_admin_shipment_label_backbone_params',
		    array(
			    'ajax_url'                => admin_url( 'admin-ajax.php' ),
			    'create_label_form_nonce' => wp_create_nonce( 'create-shipment-label-form' ),
			    'create_label_nonce'      => wp_create_nonce( 'create-shipment-label' ),
		    )
	    );

	    // Shipping provider settings
	    if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'germanized-shipments' === $_GET['tab'] && isset( $_GET['section'] ) && 'provider' === $_GET['section'] ) {
		    wp_enqueue_script( 'wc-gzd-admin-shipping-providers' );

		    wp_localize_script(
			    'wc-gzd-admin-shipping-providers',
			    'wc_gzd_admin_shipping_providers_params',
			    array(
				    'ajax_url'                       => admin_url( 'admin-ajax.php' ),
				    'edit_shipping_providers_nonce'  => wp_create_nonce( 'edit-shipping-providers' ),
				    'remove_shipping_provider_nonce' => wp_create_nonce( 'remove-shipping-provider' ),
                    'i18n_remove_shipping_provider_notice' => _x( 'Do you really want to delete the shipping provider? Some of your existing shipments might be linked to that provider and might need adjustments.', 'shipments', 'woocommerce-germanized' ),
			    )
		    );
	    }

	    // Shipping provider method
	    if ( 'woocommerce_page_wc-settings' === $screen_id && isset( $_GET['tab'] ) && 'shipping' === $_GET['tab'] && ( isset( $_GET['zone_id'] ) || isset( $_GET['instance_id'] ) ) ) {
		    wp_enqueue_script( 'wc-gzd-admin-shipping-provider-method' );
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
	        $handlers = apply_filters( 'woocommerce_gzd_shipments_table_bulk_action_handlers', array(
		        'labels' => '\Vendidero\Germanized\Shipments\Admin\BulkLabel'
            ) );

	        foreach( $handlers as $key => $handler ) {
		        self::$bulk_handlers[ $key ] = new $handler();
	        }
        }

        return self::$bulk_handlers;
    }

    public static function get_bulk_action_handler( $action ) {
        $handlers = self::get_bulk_action_handlers();

        return array_key_exists( $action, $handlers ) ? $handlers[ $action ] : false;
    }

    public static function get_screen_ids() {

        $screen_ids = array(
            'woocommerce_page_wc-gzd-shipments',
            'woocommerce_page_wc-gzd-return-shipments',
        );

        foreach ( wc_get_order_types() as $type ) {
            $screen_ids[] = $type;
            $screen_ids[] = 'edit-' . $type;
        }

        return $screen_ids;
    }
}
