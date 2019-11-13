<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Package;
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
        add_action( 'woocommerce_process_shop_order_meta', 'Vendidero\Germanized\Shipments\Admin\MetaBox::save', 50, 2 );

        add_action( 'admin_menu', array( __CLASS__, 'shipments_menu' ), 15 );
        add_action( 'load-woocommerce_page_wc-gzd-shipments', array( __CLASS__, 'setup_shipments_table' ), 0 );
	    add_action( 'load-woocommerce_page_wc-gzd-return-shipments', array( __CLASS__, 'setup_returns_table' ), 0 );
        add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );

        add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_table_view' ), 10 );

	    add_filter( 'handle_bulk_actions-edit-shop_order', array( __CLASS__, 'handle_order_bulk_actions' ), 10, 3 );
	    add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'define_order_bulk_actions' ), 10, 1 );

	    // Template check
	    add_filter( 'woocommerce_gzd_template_check', array( __CLASS__, 'add_template_check' ), 10, 1 );
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
				    Automation::create_shipments( $id );
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
    }

    public static function admin_scripts() {
        global $post;

        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';
        $suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_register_script( 'wc-gzd-admin-shipment', Package::get_assets_url() . '/js/admin-shipment' . $suffix . '.js', array( 'jquery' ), Package::get_version() );
        wp_register_script( 'wc-gzd-admin-shipments', Package::get_assets_url() . '/js/admin-shipments' . $suffix . '.js', array( 'wc-admin-order-meta-boxes', 'wc-gzd-admin-shipment' ), Package::get_version() );
        wp_register_script( 'wc-gzd-admin-shipments-table', Package::get_assets_url() . '/js/admin-shipments-table' . $suffix . '.js', array( 'woocommerce_admin' ), Package::get_version() );

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
	        $handlers = apply_filters( 'woocommerce_gzd_shipments_table_bulk_action_handlers', array() );

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
            'woocommerce_page_wc-gzd-return-shipments'
        );

        foreach ( wc_get_order_types() as $type ) {
            $screen_ids[] = $type;
            $screen_ids[] = 'edit-' . $type;
        }

        return $screen_ids;
    }
}
