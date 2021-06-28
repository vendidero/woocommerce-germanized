<?php

namespace Vendidero\OneStopShop;

use WC_DateTime;
use WP_List_Table;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 */
class ReportTable extends WP_List_Table {

	protected $query = null;

	protected $statuses = array();

	protected $counts = array();

	protected $notice = array();

	/**
	 * Constructor.
	 *
	 * @since 3.0.6
	 *
	 * @see WP_List_Table::__construct() for more information on default arguments.
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		add_filter( 'removable_query_args', array( $this, 'enable_query_removing' ) );
		add_filter( 'default_hidden_columns', array( $this, 'set_default_hidden_columns' ), 10, 2 );

		parent::__construct(
			array(
				'plural'   => _x( 'Reports', 'oss', 'woocommerce-germanized' ),
				'singular' => _x( 'Report', 'oss', 'woocommerce-germanized' ),
				'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
			)
		);
	}

	public function set_default_hidden_columns( $columns, $screen ) {
		if ( $this->screen->id === $screen->id ) {
			$columns = array_merge( $columns, $this->get_default_hidden_columns() );
		}

		return $columns;
	}

	protected function get_default_hidden_columns() {
		return array();
	}

	protected function get_hook_prefix() {
		return 'oss_woocommerce_admin_reports_table_';
	}

	public function enable_query_removing( $args ) {
		$args = array_merge( $args, array(
			'changed',
			'bulk_action'
		) );

		return $args;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of ids.
	 * @return string
	 */
	public function handle_bulk_actions( $action, $ids, $redirect_to ) {
		$ids         = array_reverse( wc_clean( $ids ) );
		$changed     = 0;

		if( 'delete' === $action ) {
			foreach ( $ids as $id ) {
				if ( $report = Package::get_report( $id ) ) {
					if ( $report->delete() ) {
						$changed++;
					}
				}
			}
		}

		$changed = apply_filters( "{$this->get_hook_prefix()}bulk_action", $changed, $action, $ids, $redirect_to, $this );

		if ( $changed ) {
			$redirect_to = add_query_arg(
				array(
					'changed'     => $changed,
					'ids'         => join( ',', $ids ),
					'bulk_action' => $action
				),
				$redirect_to
			);
		}

		return esc_url_raw( $redirect_to );
	}

	public function output_notices() {

	}

	/**
	 * Show confirmation message that order status changed for number of orders.
	 */
	public function set_bulk_notice() {
		$number      = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // WPCS: input var ok, CSRF ok.
		$bulk_action = isset( $_REQUEST['bulk_action'] ) ? wc_clean( wp_unslash( $_REQUEST['bulk_action'] ) ) : ''; // WPCS: input var ok, CSRF ok.

		if ( 'delete' === $bulk_action ) {
			$this->add_notice( sprintf( _nx( '%d report deleted.', '%d reports deleted.', $number, 'oss', 'woocommerce-germanized' ), number_format_i18n( $number ) ) );
		}

		do_action( "{$this->get_hook_prefix()}bulk_notice", $bulk_action, $this );
	}

	public function add_notice( $message, $type = 'success' ) {

	}

	/**
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( "manage_woocommerce" );
	}

	public function get_page_option() {
		return 'woocommerce_page_oss_reports_per_page';
	}

	public function get_reports( $args ) {
	    return Package::get_reports( $args );
    }

	/**
	 * @global array    $avail_post_stati
	 * @global WP_Query $wp_query
	 * @global int      $per_page
	 * @global string   $mode
	 */
	public function prepare_items() {
		global $per_page;

		$per_page        = $this->get_items_per_page( $this->get_page_option(), 10 );
		$per_page        = apply_filters( "{$this->get_hook_prefix()}edit_per_page", $per_page );
		$this->counts    = Package::get_report_counts();
		$paged           = $this->get_pagenum();
		$report_type     = isset( $_REQUEST['type'] ) ? wc_clean( $_REQUEST['type'] ) : '';
		$report_type     = in_array( $report_type, array_keys( Package::get_available_report_types( true ) ) ) ? $report_type : '';

		$args = array(
			'limit'            => $per_page,
			'paginate'         => true,
			'offset'           => ( $paged - 1 ) * $per_page,
			'count_total'      => true,
            'type'             => $report_type,
            'include_observer' => 'observer' === $report_type ? true : false,
		);

		$this->items = $this->get_reports( $args );

		$this->set_pagination_args(
			array(
				'total_items' => empty( $args['type'] ) ? array_sum( $this->counts ) : $this->counts[ $args['type'] ],
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 */
	public function no_items() {
		echo _x( 'No reports found', 'oss', 'woocommerce-germanized' );
	}

	/**
	 * Determine if the current view is the "All" view.
	 *
	 * @since 4.2.0
	 *
	 * @return bool Whether the current view is the "All" view.
	 */
	protected function is_base_request() {
		$vars = $_GET;
		unset( $vars['paged'] );

		if ( empty( $vars ) ) {
			return true;
		}

		return 1 === count( $vars );
	}

	/**
	 * @global array $locked_post_status This seems to be deprecated.
	 * @global array $avail_post_stati
	 * @return array
	 */
	protected function get_views() {
		$type_links        = array();
		$num_reports       = $this->counts;
		$total_reports     = array_sum( (array) $num_reports );
		$total_reports     = $total_reports - ( isset( $num_reports['observer'] ) ? $num_reports['observer'] : 0 );
		$class             = '';
		$all_args          = array();
		$include_observers = Package::enable_auto_observer();

		if ( empty( $class ) && ( $this->is_base_request() || isset( $_REQUEST['all_reports'] ) ) ) {
			$class = 'current';
		}

		$all_inner_html = sprintf(
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$total_reports, 'oss', 'woocommerce-germanized'
			),
			number_format_i18n( $total_reports )
		);

		$type_links['all'] = $this->get_edit_link( $all_args, $all_inner_html, $class );

		foreach ( Package::get_available_report_types( $include_observers ) as $type => $title ) {
			$class = '';

			if ( empty( $num_reports[ $type ] ) ) {
				continue;
			}

			if ( isset( $_REQUEST['type'] ) && $type === $_REQUEST['type'] ) {
				$class = 'current';
			}

			$type_args = array(
				'type' => $type,
			);

			$type_label = sprintf(
				translate_nooped_plural( _nx_noop( $title . ' <span class="count">(%s)</span>', $title . ' <span class="count">(%s)</span>', 'oss', 'woocommerce-germanized' ), $num_reports[ $type ] ),
				number_format_i18n( $num_reports[ $type ] )
			);

			$type_links[ $type ] = $this->get_edit_link( $type_args, $type_label, $class );
		}

		return $type_links;
	}

	/**
	 * Helper to create links to edit.php with params.
	 *
	 * @since 4.4.0
	 *
	 * @param string[] $args  Associative array of URL parameters for the link.
	 * @param string   $label Link text.
	 * @param string   $class Optional. Class attribute. Default empty string.
	 * @return string The formatted link string.
	 */
	protected function get_edit_link( $args, $label, $class = '' ) {
		$url = add_query_arg( $args, $this->get_main_page() );

		$class_html = $aria_current = '';
		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * @return string
	 */
	public function current_action() {
		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {
			return 'delete_all';
		}

		return parent::current_action();
	}

	/**
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
			<?php
			if ( 'top' === $which && ! is_singular() ) {
				ob_start();
				$this->render_filters();

				do_action( "{$this->get_hook_prefix()}filters", $which );

				$output = ob_get_clean();

				if ( ! empty( $output ) ) {
					echo $output;

					submit_button( _x( 'Filter', 'oss', 'woocommerce-germanized' ), '', 'filter_action', false, array( 'id' => 'oss-filter-submit' ) );
				}
			}
			?>
		</div>
		<?php
		do_action( 'manage_posts_extra_tablenav', $which );
	}

	protected function render_filters() {

	}

	/**
	 * @return array
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'posts', 'reports' );
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = array();

		$columns['cb']             = '<input type="checkbox" />';
		$columns['title']          = _x( 'Title', 'oss', 'woocommerce-germanized' );
		$columns['date_start']     = _x( 'Start', 'oss', 'woocommerce-germanized' );
		$columns['date_end']       = _x( 'End', 'oss', 'woocommerce-germanized' );
		$columns['net_total']      = _x( 'Net total', 'oss', 'woocommerce-germanized' );
		$columns['tax_total']      = _x( 'Tax total', 'oss', 'woocommerce-germanized' );
		$columns['status']         = _x( 'Status', 'oss', 'woocommerce-germanized' );
		$columns['actions']        = _x( 'Actions', 'oss', 'woocommerce-germanized' );

		$columns = apply_filters( "{$this->get_hook_prefix()}columns", $columns );

		return $columns;
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'date_start' => array( 'date_start', false ),
			'date_end'   => array( 'date_end', false ),
		);
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 4.3.0
	 *
	 * @return string Name of the default primary column, in this case, 'title'.
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}

	/**
	 * Handles the default column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Report $report The current shipment object.
	 * @param string $column_name The current column name.
	 */
	public function column_default( $report, $column_name ) {
		do_action( "{$this->get_hook_prefix()}custom_column", $column_name, $report );
	}

	public function get_main_page() {
		return 'admin.php?page=oss-reports';
	}

	/**
	 * Handles actions.
	 *
	 * @since 0.0.1
	 *
	 * @param Report $report The current report object.
	 */
	protected function column_actions( $report ) {
		do_action( "{$this->get_hook_prefix()}actions_start", $report );

		$actions = Admin::get_report_actions( $report );

        Admin::render_actions( $actions );

		do_action( "{$this->get_hook_prefix()}actions_end", $report );
	}

	public function column_cb( $report ) {
	    ?>
			<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $report->get_id() ); ?>">
				<?php printf( _x( 'Select %s', 'oss', 'woocommerce-germanized' ), $report->get_id() ); ?>
			</label>
			<input id="cb-select-<?php echo esc_attr( $report->get_id() ); ?>" type="checkbox" name="report[]" value="<?php echo esc_attr( $report->get_id() ); ?>" />
        <?php
	}

	/**
	 * @param Report $report
	 */
	public function column_title( $report ) {
		$title = $report->get_title();

        echo '<a href="' . esc_url( $report->get_url() ) . '">' . $title . '</a> ';
	}

	/**
	 * @param Report $report
	 */
	public function column_status( $report ) {
		$status = $report->get_status();

		return '<span class="oss-woo-status report-status-' . esc_attr( $status ) . '">' . esc_html( Package::get_report_status_title( $status ) ) . '</span>';
	}

	/**
	 * @param Report $report
	 */
	public function column_net_total( $report ) {
        return wc_price( $report->get_net_total() );
	}

	/**
	 * @param Report $report
	 */
	public function column_tax_total( $report ) {
		return wc_price( $report->get_tax_total() );
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Report $report
	 */
	public function column_date_start( $report ) {
		$show_date = $report->get_date_start()->date_i18n( apply_filters( "{$this->get_hook_prefix()}date_format", wc_date_format() ) );

		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $report->get_date_start()->date( 'c' ) ),
			esc_html( $report->get_date_start()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Report $report
	 */
	public function column_date_end( $report ) {
		$show_date = $report->get_date_end()->date_i18n( apply_filters( "{$this->get_hook_prefix()}date_format", wc_date_format() ) );

		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $report->get_date_end()->date( 'c' ) ),
			esc_html( $report->get_date_end()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 *
	 * @param Report $report
	 */
	public function single_row( $report ) {
		$GLOBALS['report'] = $report;
		$classes           = 'report report-' . $report->get_type();
		?>
		<tr id="report-<?php echo $report->get_id(); ?>" class="<?php echo esc_attr( $classes ); ?>">
			<?php $this->single_row_columns( $report ); ?>
		</tr>
		<?php
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array();
		$actions['delete'] = _x( 'Delete Permanently', 'oss', 'woocommerce-germanized' );

		return apply_filters( "{$this->get_hook_prefix()}bulk_actions", $actions );
	}
}
