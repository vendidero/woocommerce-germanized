<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShipmentItem;
use WC_DateTime;
use WP_List_Table;
use Vendidero\Germanized\Shipments\ShipmentQuery;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 * @package Vendidero/Germanized/Shipments\Admin
 */
class Table extends WP_List_Table {

	protected $query = null;

	protected $stati = array();

	protected $counts = array();

	protected $notice = array();

	protected $shipment_type = '';

	/**
	 * Constructor.
	 *
	 * @since 3.0.6
	 *
	 * @see WP_List_Table::__construct() for more information on default arguments.
	 *
	 * @global WP_Post_Type $post_type_object
	 * @global wpdb         $wpdb
	 *
	 * @param array $args An associative array of arguments.
	 */
	public function __construct( $args = array() ) {
		add_filter( 'removable_query_args', array( $this, 'enable_query_removing' ) );
		add_filter( 'default_hidden_columns', array( $this, 'set_default_hidden_columns' ), 10, 2 );

		$args = wp_parse_args(
			$args,
			array(
				'type' => 'simple',
			)
		);

		$this->shipment_type = $args['type'];

		parent::__construct(
			array(
				'plural' => 'shipments',
				'screen' => isset( $args['screen'] ) ? $args['screen'] : null,
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
		return array(
			'weight',
			'dimensions',
			'packaging',
		);
	}

	public function enable_query_removing( $args ) {
		$args = array_merge(
			$args,
			array(
				'changed',
				'bulk_action',
			)
		);

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
		$ids     = array_reverse( array_map( 'absint', $ids ) );
		$changed = 0;

		if ( false !== strpos( $action, 'mark_' ) ) {

			$shipment_statuses = wc_gzd_get_shipment_statuses();
			$new_status        = substr( $action, 5 ); // Get the status name from action.

			// Sanity check: bail out if this is actually not a status, or is not a registered status.
			if ( isset( $shipment_statuses[ 'gzd-' . $new_status ] ) ) {

				foreach ( $ids as $id ) {

					if ( $shipment = wc_gzd_get_shipment( $id ) ) {
						$shipment->update_status( $new_status, true );

						/**
						 * Action that fires after a shipment bulk status update has been processed.
						 *
						 * @param integer $shipment_id The shipment id.
						 * @param string  $new_status The new shipment status.
						 *
						 * @since 3.0.0
						 * @package Vendidero/Germanized/Shipments
						 */
						do_action( 'woocommerce_gzd_shipment_edit_status', $id, $new_status );
						$changed++;
					}
				}
			}
		} elseif ( 'delete' === $action ) {
			foreach ( $ids as $id ) {
				if ( $shipment = wc_gzd_get_shipment( $id ) ) {
					$shipment->delete( true );
					$changed++;
				}
			}
		} elseif ( 'confirm_requests' === $action ) {
			foreach ( $ids as $id ) {
				if ( $shipment = wc_gzd_get_shipment( $id ) ) {
					if ( 'return' === $shipment->get_type() ) {
						if ( $shipment->is_customer_requested() && $shipment->has_status( 'requested' ) ) {
							if ( $shipment->confirm_customer_request() ) {
								$changed++;
							}
						}
					}
				}
			}
		}

		/**
		 * Filter to decide whether a Shipment has changed during bulk action or not.
		 *
		 * @param boolean                                     $changed Whether the Shipment has changed or not.
		 * @param string                                      $action The bulk action
		 * @param string                                      $redirect_to The redirect URL.
		 * @param Table $table The table instance.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$changed = apply_filters( 'woocommerce_gzd_shipments_bulk_action', $changed, $action, $ids, $redirect_to, $this );

		if ( $changed ) {
			$redirect_to = add_query_arg(
				array(
					'changed'     => $changed,
					'ids'         => join( ',', $ids ),
					'bulk_action' => $action,
				),
				$redirect_to
			);
		}

		return esc_url_raw( $redirect_to );
	}

	public function output_notice() {

		if ( ! empty( $this->notice ) ) {
			$type = isset( $this->notice['type'] ) ? $this->notice['type'] : 'success';

			echo '<div id="message" class="' . esc_attr( 'success' === $type ? 'updated' : $type ) . ' notice is-dismissible">' . wp_kses_post( wpautop( $this->notice['message'] ) ) . ' <button type="button" class="notice-dismiss"></button></div>';
		}

		$this->notice = array();
	}

	/**
	 * Show confirmation message that order status changed for number of orders.
	 */
	public function set_bulk_notice() {

		$number      = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$bulk_action = isset( $_REQUEST['bulk_action'] ) ? wc_clean( wp_unslash( $_REQUEST['bulk_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'delete' === $bulk_action ) {

			$this->set_notice( sprintf( _nx( '%d shipment deleted.', '%d shipments deleted.', $number, 'shipments', 'woocommerce-germanized' ), number_format_i18n( $number ) ) );

		} elseif ( strpos( $bulk_action, 'mark_' ) !== false ) {

			$shipment_statuses = wc_gzd_get_shipment_statuses();

			// Check if any status changes happened.
			foreach ( $shipment_statuses as $slug => $name ) {

				if ( 'mark_' . str_replace( 'gzd-', '', $slug ) === $bulk_action ) { // WPCS: input var ok, CSRF ok.
					$this->set_notice( sprintf( _nx( '%d shipment status changed.', '%d shipment statuses changed.', $number, 'shipments', 'woocommerce-germanized' ), number_format_i18n( $number ) ) );
					break;
				}
			}
		}

		/**
		 * Action that fires after bulk updating shipments. Action might be usefull to add
		 * custom notices after custom bulk actions have been applied.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_bulk_notice
		 *
		 * @param string $bulk_action The bulk action.
		 * @param Table  $shipment_table The table object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "{$this->get_hook_prefix()}bulk_notice", $bulk_action, $this );
	}

	public function set_notice( $message, $type = 'success' ) {
		$this->notice = array(
			'message' => $message,
			'type'    => $type,
		);
	}

	protected function get_stati() {
		return $this->stati;
	}

	/**
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( 'edit_shop_orders' );
	}

	public function get_page_option() {
		return 'woocommerce_page_wc_gzd_shipments_per_page';
	}

	/**
	 * @global array     $avail_post_stati
	 * @global WP_Query $wp_query
	 * @global int       $per_page
	 * @global string    $mode
	 */
	public function prepare_items() {
		global $per_page;

		$per_page = $this->get_items_per_page( $this->get_page_option(), 10 ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		/**
		 * Filter to adjust Shipment's table items per page.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_edit_per_page
		 *
		 * @param integer $per_page Number of Shipments per page.
		 * @param string  $type The type in this case shipment.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$per_page     = apply_filters( "{$this->get_hook_prefix()}edit_per_page", $per_page, 'shipment' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->stati  = wc_gzd_get_shipment_statuses();
		$this->counts = wc_gzd_get_shipment_counts( $this->shipment_type );
		$paged        = $this->get_pagenum();

		$args = array(
			'limit'       => $per_page,
			'paginate'    => true,
			'offset'      => ( $paged - 1 ) * $per_page,
			'count_total' => true,
			'type'        => $this->shipment_type,
		);

		if ( isset( $_REQUEST['shipment_status'] ) && in_array( $_REQUEST['shipment_status'], array_keys( $this->stati ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['status'] = wc_clean( wp_unslash( $_REQUEST['shipment_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( isset( $_REQUEST['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'weight' === $_REQUEST['orderby'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$args['orderby'] = 'weight';
			} else {
				$args['orderby'] = wc_clean( wp_unslash( $_REQUEST['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		if ( isset( $_REQUEST['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['order'] = 'asc' === $_REQUEST['order'] ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( isset( $_REQUEST['parent_id'] ) && ! empty( $_REQUEST['parent_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['parent_id'] = absint( $_REQUEST['parent_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( isset( $_REQUEST['order_id'] ) && ! empty( $_REQUEST['order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['order_id'] = absint( $_REQUEST['order_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( isset( $_REQUEST['shipping_provider'] ) && ! empty( $_REQUEST['shipping_provider'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['shipping_provider'] = wc_clean( wp_unslash( $_REQUEST['shipping_provider'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( isset( $_REQUEST['m'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$m    = wc_clean( wp_unslash( $_REQUEST['m'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$year = substr( $m, 0, 4 );

			if ( ! empty( $year ) ) {
				$month = '';
				$day   = '';

				if ( strlen( $m ) > 5 ) {
					$month = substr( $m, 4, 2 );
				}

				if ( strlen( $m ) > 7 ) {
					$day = substr( $m, 6, 2 );
				}

				$datetime = new WC_DateTime();
				$datetime->setDate( $year, 1, 1 );

				if ( ! empty( $month ) ) {
					$datetime->setDate( $year, $month, 1 );
				}

				if ( ! empty( $day ) ) {
					$datetime->setDate( $year, $month, $day );
				}

				$next_month = clone $datetime;
				$next_month->modify( '+ 1 month' );
				// Make sure to not include next month first day
				$next_month->modify( '-1 day' );

				$args['date_created'] = $datetime->format( 'Y-m-d' ) . '...' . $next_month->format( 'Y-m-d' );
			}
		}

		if ( isset( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = wc_clean( wp_unslash( $_REQUEST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! is_numeric( $search ) ) {
				$search = '*' . $search . '*';
			}

			$args['search'] = $search;
		}

		// Query the user IDs for this page
		$this->query = new ShipmentQuery( apply_filters( "{$this->get_hook_prefix()}query_args", $args, $this ) );
		$this->items = $this->query->get_shipments();

		$this->set_pagination_args(
			array(
				'total_items' => $this->query->get_total(),
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 */
	public function no_items() {
		echo esc_html_x( 'No shipments found', 'shipments', 'woocommerce-germanized' );
	}

	/**
	 * Determine if the current view is the "All" view.
	 *
	 * @since 4.2.0
	 *
	 * @return bool Whether the current view is the "All" view.
	 */
	protected function is_base_request() {
		$vars = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

		$status_links    = array();
		$num_shipments   = $this->counts;
		$total_shipments = array_sum( (array) $num_shipments );
		$class           = '';
		$all_args        = array();

		if ( empty( $class ) && ( $this->is_base_request() || isset( $_REQUEST['all_shipments'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$class = 'current';
		}

		$all_inner_html = sprintf(
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$total_shipments,
				'shipments',
				'woocommerce-germanized-shipments'
			),
			number_format_i18n( $total_shipments )
		);

		$status_links['all'] = $this->get_edit_link( $all_args, $all_inner_html, $class );

		foreach ( wc_gzd_get_shipment_statuses() as $status => $title ) {
			$class = '';

			if ( ! in_array( $status, array_keys( $this->stati ), true ) || empty( $num_shipments[ $status ] ) ) {
				continue;
			}

			if ( isset( $_REQUEST['shipment_status'] ) && $status === $_REQUEST['shipment_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$class = 'current';
			}

			$status_args = array(
				'shipment_status' => $status,
			);

			$status_label = sprintf(
				translate_nooped_plural( _nx_noop( ( $title . ' <span class="count">(%s)</span>' ), ( $title . ' <span class="count">(%s)</span>' ), 'shipments', 'woocommerce-germanized' ), $num_shipments[ $status ] ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle,WordPress.WP.I18n.NonSingularStringLiteralPlural
				number_format_i18n( $num_shipments[ $status ] )
			);

			$status_links[ $status ] = $this->get_edit_link( $status_args, $status_label, $class );
		}

		return $status_links;
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
		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 'delete_all';
		}

		return parent::current_action();
	}

	/**
	 * Display a monthly dropdown for filtering items
	 *
	 * @since 3.0.6
	 *
	 * @global wpdb      $wpdb
	 * @global WP_Locale $wp_locale
	 *
	 * @param string $post_type
	 */
	protected function months_dropdown( $type ) {
		global $wpdb, $wp_locale;

		$extra_checks = "AND shipment_status != 'auto-draft'";

		if ( isset( $_GET['shipment_status'] ) && 'all' !== $_GET['shipment_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$extra_checks = $wpdb->prepare( ' AND shipment_status = %s', wc_clean( wp_unslash( $_GET['shipment_status'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$months      = $wpdb->get_results( "SELECT DISTINCT YEAR( shipment_date_created ) AS year, MONTH( shipment_date_created ) AS month FROM $wpdb->gzd_shipments WHERE 1=1 $extra_checks ORDER BY shipment_date_created DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$month_count = count( $months );

		if ( ! $month_count || ( 1 === $month_count && 0 === $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? absint( wp_unslash( $_GET['m'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<label for="filter-by-date" class="screen-reader-text"><?php echo esc_html_x( 'Filter by date', 'shipments', 'woocommerce-germanized' ); ?></label>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value="0"><?php echo esc_html_x( 'All dates', 'shipments', 'woocommerce-germanized' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 === $arc_row->year ) {
					continue;
				}

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				printf(
					"<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: 1: month name, 2: 4-digit year */
					esc_html( sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year ) ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.0.6
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		$finished    = ( isset( $_GET['bulk_action_handling'] ) && 'finished' === $_GET['bulk_action_handling'] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$bulk_action = ( isset( $_GET['current_bulk_action'] ) ) ? wc_clean( wp_unslash( $_GET['current_bulk_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'top' === $which ) {
			?>
			<div class="bulk-action-wrapper">
				<h4 class="bulk-title"><?php echo esc_html_x( 'Processing bulk actions...', 'shipments', 'woocommerce-germanized' ); ?></h4>
				<div class="bulk-notice-wrapper"></div>
				<progress class="woocommerce-shimpents-bulk-progress" max="100" value="0"></progress>
			</div>
			<?php
			if ( $finished && ( $handler = Admin::get_bulk_action_handler( $bulk_action ) ) ) :
				$errors  = $handler->get_notices( 'error' );
				$info    = $handler->get_notices( 'info' );
				$success = $handler->get_notices( 'success' );
				?>
				<?php if ( ! empty( $errors ) ) : ?>
					<?php foreach ( $errors as $notice ) : ?>
						<div class="error">
							<p><?php echo wp_kses_post( $notice ); ?></p>
						</div>
					<?php endforeach; ?>

					<?php $handler->admin_after_error(); ?>
				<?php elseif ( $success ) : ?>
					<?php if ( ! empty( $info ) ) : ?>
						<?php foreach ( $info as $notice ) : ?>
							<div class="notice">
								<p><?php echo wp_kses_post( $notice ); ?></p>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>

					<div class="updated">
						<p><?php echo wp_kses_post( $handler->get_success_message() ); ?></p>
					</div>
				<?php endif; ?>

				<?php
				$handler->admin_handled();
				/**
				 * Action that fires after a certain bulk action result has been rendered.
				 *
				 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
				 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
				 * `$bulk_action` refers to the bulk action handled.
				 *
				 * Example hook name: woocommerce_gzd_return_shipments_table_mark_processing_handled
				 *
				 * @param BulkActionHandler $bulk_action_handler The bulk action handler.
				 * @param string            $bulk_action The bulk action.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( "{$this->get_hook_prefix()}bulk_action_{$bulk_action}_handled", $handler, $bulk_action );
				?>

				<?php $handler->reset(); ?>
			<?php endif; ?>
			<?php
		}

		parent::display_tablenav( $which );
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

				$this->months_dropdown( 'shipment' );
				$this->order_filter();
				$this->shipping_provider_filter();

				/**
				 * Action that fires after outputting Shipments table view filters.
				 * Might be used to add custom filters to the Shipments table view.
				 *
				 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
				 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
				 *
				 * Example hook name: woocommerce_gzd_return_shipments_table_filters
				 *
				 * @param string $which top or bottom.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/Shipments
				 */
				do_action( "{$this->get_hook_prefix()}filters", $which );

				$output = ob_get_clean();

				if ( ! empty( $output ) ) {
					echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

					submit_button( _x( 'Filter', 'shipments', 'woocommerce-germanized' ), '', 'filter_action', false, array( 'id' => 'shipment-query-submit' ) );
				}
			}
			?>
		</div>
		<?php
		do_action( 'manage_posts_extra_tablenav', $which );
	}

	protected function shipping_provider_filter() {
		$shipping_provider = '';
		$provider_string   = '';

		if ( ! empty( $_GET['shipping_provider'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$shipping_provider = wc_clean( wp_unslash( $_GET['shipping_provider'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $provider = wc_gzd_get_shipping_provider( $shipping_provider ) ) {
				$provider_string = $provider->get_title();
			}
		}
		?>
		<select class="wc-gzd-shipping-provider-search" name="shipping_provider" data-placeholder="<?php echo esc_attr_x( 'Filter by shipping provider', 'shipments', 'woocommerce-germanized' ); ?>" data-allow_clear="true">
			<option value="<?php echo esc_attr( $shipping_provider ); ?>" selected="selected"><?php echo htmlspecialchars( wp_kses_post( $provider_string ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><option>
		</select>
		<?php
	}

	protected function order_filter() {
		$order_id     = '';
		$order_string = '';

		if ( ! empty( $_GET['order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_id     = absint( $_GET['order_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order_string = sprintf(
				esc_html_x( 'Order #%s', 'shipments', 'woocommerce-germanized' ),
				$order_id
			);
		}
		?>
		<select class="wc-gzd-order-search" name="order_id" data-placeholder="<?php echo esc_attr_x( 'Filter by order', 'shipments', 'woocommerce-germanized' ); ?>" data-allow_clear="true">
			<option value="<?php echo esc_attr( $order_id ); ?>" selected="selected"><?php echo htmlspecialchars( wp_kses_post( $order_string ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><option>
		</select>
		<?php
	}

	/**
	 * @return array
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'posts', 'shipments' );
	}

	protected function get_custom_columns() {
		$columns = array();

		$columns['cb']         = '<input type="checkbox" />';
		$columns['title']      = _x( 'Title', 'shipments', 'woocommerce-germanized' );
		$columns['date']       = _x( 'Date', 'shipments', 'woocommerce-germanized' );
		$columns['status']     = _x( 'Status', 'shipments', 'woocommerce-germanized' );
		$columns['items']      = _x( 'Items', 'shipments', 'woocommerce-germanized' );
		$columns['address']    = _x( 'Address', 'shipments', 'woocommerce-germanized' );
		$columns['packaging']  = _x( 'Packaging', 'shipments', 'woocommerce-germanized' );
		$columns['weight']     = _x( 'Weight', 'shipments', 'woocommerce-germanized' );
		$columns['dimensions'] = _x( 'Dimensions', 'shipments', 'woocommerce-germanized' );
		$columns['order']      = _x( 'Order', 'shipments', 'woocommerce-germanized' );
		$columns['actions']    = _x( 'Actions', 'shipments', 'woocommerce-germanized' );

		return $columns;
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns = $this->get_custom_columns();

		/**
		 * Filters the columns displayed in the Shipments list table.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_edit_per_page
		 *
		 * @param string[] $columns An associative array of column headings.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$columns = apply_filters( "{$this->get_hook_prefix()}columns", $columns );

		return $columns;
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return apply_filters(
			"{$this->get_hook_prefix()}sortable_columns",
			array(
				'date'   => array( 'date_created', false ),
				'weight' => 'weight',
				'order'  => 'order_id',
			),
			$this
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
	 * @param Shipment $shipment The current shipment object.
	 * @param string          $column_name The current column name.
	 */
	public function column_default( $shipment, $column_name ) {

		/**
		 * Fires in each custom column in the Shipments list table.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_filters
		 *
		 * @param string  $column_name The name of the column to display.
		 * @param integer $shipment_id The current shipment id.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "{$this->get_hook_prefix()}custom_column", $column_name, $shipment->get_id() );
	}

	public function get_main_page() {
		return 'admin.php?page=wc-gzd-shipments';
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_title( $shipment ) {
		$title = sprintf( _x( '%1$s #%2$s', 'shipment title', 'woocommerce-germanized' ), wc_gzd_get_shipment_label_title( $shipment->get_type() ), $shipment->get_shipment_number() );

		if ( $order = $shipment->get_order() ) {
			echo '<a href="' . esc_url( $shipment->get_edit_shipment_url() ) . '">' . wp_kses_post( $title ) . '</a> ';
		} else {
			echo wp_kses_post( $title ) . ' ';
		}

		echo '<p class="shipment-title-meta">';

		if ( $packaging = $shipment->get_packaging() ) {
			echo '<span class="shipment-packaging">' . wp_kses_post( $packaging->get_description() ) . '</span> ';
		}

		$provider = $shipment->get_shipping_provider();

		if ( ! empty( $provider ) ) {
			echo '<span class="shipment-shipping-provider">' . sprintf( esc_html_x( 'via %s', 'shipments', 'woocommerce-germanized' ), wp_kses_post( wc_gzd_get_shipping_provider_title( $provider ) ) ) . '</span> ';
		}

		if ( $tracking_id = $shipment->get_tracking_id() ) {
			if ( $shipment->has_tracking() && ( $tracking_url = $shipment->get_tracking_url() ) ) {
				echo '<a class="shipment-tracking-id" target="_blank" href="' . esc_url( $tracking_url ) . '">' . esc_html( $tracking_id ) . '</a>';
			} else {
				echo '<span class="shipment-tracking-id">' . esc_html( $tracking_id ) . '</span>';
			}
		}

		echo '</p>';
	}

	protected function get_custom_actions( $shipment, $actions ) {
		return $actions;
	}

	/**
	 * Handles shipment actions.
	 *
	 * @since 0.0.1
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	protected function column_actions( $shipment ) {
		echo '<p>';

		/**
		 * Action that fires before table actions are outputted for a Shipment.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_actions_start
		 *
		 * @param Shipment $shipment The shipment object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "{$this->get_hook_prefix()}actions_start", $shipment );

		$actions = array();

		if ( $shipment->has_status( array( 'draft' ) ) ) {
			$actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzd_update_shipment_status&status=processing&shipment_id=' . $shipment->get_id() ), 'update-shipment-status' ),
				'name'   => _x( 'Processing', 'shipments', 'woocommerce-germanized' ),
				'action' => 'processing',
			);
		}

		if ( $shipment->has_status( array( 'draft', 'processing' ) ) ) {
			$actions['shipped'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzd_update_shipment_status&status=shipped&shipment_id=' . $shipment->get_id() ), 'update-shipment-status' ),
				'name'   => _x( 'Shipped', 'shipments', 'woocommerce-germanized' ),
				'action' => 'shipped',
			);
		}

		if ( $shipment->supports_label() ) {

			if ( $label = $shipment->get_label() ) {

				$actions['download_label'] = array(
					'url'    => $label->get_download_url(),
					'name'   => _x( 'Download label', 'shipments', 'woocommerce-germanized' ),
					'action' => 'download-label download',
					'target' => '_blank',
				);

			} elseif ( $shipment->needs_label() ) {

				$actions['generate_label'] = array(
					'url'    => '#',
					'name'   => _x( 'Generate label', 'shipments', 'woocommerce-germanized' ),
					'action' => 'generate-label generate',
				);

				include Package::get_path() . '/includes/admin/views/label/html-shipment-label-backbone.php';
			}
		}

		$actions = $this->get_custom_actions( $shipment, $actions );

		/**
		 * Filters the actions available for Shipments table list column.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_actions
		 *
		 * @param array    $actions The registered Shipment actions.
		 * @param Shipment $shipment The shipment object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		$actions = apply_filters( "{$this->get_hook_prefix()}actions", $actions, $shipment );

		echo wc_gzd_render_shipment_action_buttons( $actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		/**
		 * Action that fires after table actions are outputted for a Shipment.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_actions_end
		 *
		 * @param Shipment $shipment The shipment object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		do_action( "{$this->get_hook_prefix()}actions_end", $shipment );

		echo '</p>';
	}

	public function column_cb( $shipment ) {
		if ( current_user_can( 'edit_shop_orders' ) ) :
			?>
			<label class="screen-reader-text" for="cb-select-<?php echo esc_attr( $shipment->get_id() ); ?>">
				<?php printf( esc_html_x( 'Select %s', 'shipments', 'woocommerce-germanized' ), esc_html( $shipment->get_id() ) ); ?>
			</label>
			<input id="cb-select-<?php echo esc_attr( $shipment->get_id() ); ?>" type="checkbox" name="shipment[]" value="<?php echo esc_attr( $shipment->get_id() ); ?>" />
			<?php
		endif;
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_items( $shipment ) {
		?>
		<table class="wc-gzd-shipments-preview">
			<tbody>
			<?php foreach ( $shipment->get_items() as $item ) : ?>
				<tr class="wc-gzd-shipment-item-preview wc-gzd-shipment-item-preview-<?php echo esc_attr( $item->get_id() ); ?>">
					<td class="wc-gzd-shipment-item-column-name">
						<?php if ( $product = $item->get_product() ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id() ) ); ?>"><?php echo wp_kses_post( $item->get_name() ); ?></a>
						<?php else : ?>
							<?php echo wp_kses_post( $item->get_name() ); ?>
						<?php endif; ?>

						<?php echo ( $item->get_sku() ? '<br/><small>' . esc_html_x( 'SKU:', 'shipments', 'woocommerce-germanized' ) . ' ' . esc_html( $item->get_sku() ) . '</small>' : '' ); ?>

						<?php
						/**
						 * Action that fires after outputting the shipment item data in admin table view.
						 *
						 * @param integer                                      $item_id The shipment item id.
						 * @param ShipmentItem $shipment_item The shipment item instance.
						 * @param Shipment $shipment The shipment instance.
						 *
						 * @since 3.0.6
						 * @package Vendidero/Germanized/Shipments
						 */
						do_action( "{$this->get_hook_prefix()}item_after_name", $item->get_id(), $item, $shipment );
						?>
					</td>
					<td class="wc-gzd-shipment-item-column-quantity">
						<?php echo esc_html( $item->get_quantity() ); ?>x
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_address( $shipment ) {
		$address = $shipment->get_formatted_address();

		if ( $address ) {
			echo '<a target="_blank" href="' . esc_url( $shipment->get_address_map_url( $shipment->get_address() ) ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) . '</a>';
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_status( $shipment ) {
		echo '<span class="shipment-status shipment-type-' . esc_attr( $shipment->get_type() ) . '-status status-' . esc_attr( $shipment->get_status() ) . '">' . esc_html( wc_gzd_get_shipment_status_name( $shipment->get_status() ) ) . '</span>';
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_weight( $shipment ) {
		echo wc_gzd_format_shipment_weight( $shipment->get_total_weight(), $shipment->get_weight_unit() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_packaging( $shipment ) {
		if ( $packaging = $shipment->get_packaging() ) {
			echo wp_kses_post( $packaging->get_description() );
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_dimensions( $shipment ) {
		echo wc_gzd_format_shipment_dimensions( $shipment->get_dimensions(), $shipment->get_dimension_unit() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_date( $shipment ) {
		$shipment_timestamp = $shipment->get_date_created() ? $shipment->get_date_created()->getTimestamp() : '';

		if ( ! $shipment_timestamp ) {
			echo '&ndash;';
			return;
		}

		// Check if the order was created within the last 24 hours, and not in the future.
		if ( $shipment_timestamp > strtotime( '-1 day', time() ) && $shipment_timestamp <= time() ) {
			$show_date = sprintf(
			/* translators: %s: human-readable time difference */
				_x( '%s ago', '%s = human-readable time difference', 'woocommerce-germanized' ),
				human_time_diff( $shipment->get_date_created()->getTimestamp(), time() )
			);
		} else {
			/**
			 * Filter to adjust the Shipment date format in table view.
			 *
			 * @param string $format The date format.
			 *
			 * @since 3.0.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$show_date = $shipment->get_date_created()->date_i18n( apply_filters( 'woocommerce_gzd_admin_shipment_date_format', _x( 'M j, Y', 'shipments', 'woocommerce-germanized' ) ) );
		}

		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $shipment->get_date_created()->date( 'c' ) ),
			esc_html( $shipment->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param Shipment $shipment The current shipment object.
	 */
	public function column_order( $shipment ) {
		if ( ( $order = $shipment->get_order() ) && is_callable( array( $order, 'get_edit_order_url' ) ) ) {
			echo '<a href="' . esc_url( $order->get_edit_order_url() ) . '">' . esc_html( $order->get_order_number() ) . '</a>';
		} else {
			echo esc_html( $shipment->get_order_id() );
		}
	}

	/**
	 *
	 * @param int|WC_GZD_Shipment $shipment
	 */
	public function single_row( $shipment ) {
		$GLOBALS['shipment'] = $shipment;
		$classes             = 'shipment shipment-status-' . $shipment->get_status();
		?>
		<tr id="shipment-<?php echo esc_attr( $shipment->get_id() ); ?>" class="<?php echo esc_attr( $classes ); ?>">
			<?php $this->single_row_columns( $shipment ); ?>
		</tr>
		<?php
	}

	protected function get_custom_bulk_actions( $actions ) {
		return $actions;
	}

	protected function get_hook_prefix() {
		$suffix = ( 'simple' === $this->shipment_type ? '' : '_' . $this->shipment_type );

		return "woocommerce_gzd{$suffix}_shipments_table_";
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array();

		if ( current_user_can( 'delete_shop_orders' ) ) {
			$actions['delete'] = _x( 'Delete Permanently', 'shipments', 'woocommerce-germanized' );
		}

		$actions['mark_processing'] = _x( 'Change status to processing', 'shipments', 'woocommerce-germanized' );
		$actions['mark_shipped']    = _x( 'Change status to shipped', 'shipments', 'woocommerce-germanized' );
		$actions['mark_delivered']  = _x( 'Change status to delivered', 'shipments', 'woocommerce-germanized' );
		$actions['labels']          = _x( 'Generate and download labels', 'shipments', 'woocommerce-germanized' );

		$actions = $this->get_custom_bulk_actions( $actions );

		/**
		 * Filter to register addtional bulk actions for shipments.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type e.g. return. In case of simple shipments the type is omitted.
		 *
		 * Example hook name: woocommerce_gzd_return_shipments_table_bulk_actions
		 *
		 * @param array $actions Array containing key => value pairs.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( "{$this->get_hook_prefix()}bulk_actions", $actions );
	}

}
