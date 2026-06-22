<?php

namespace Vendidero\OrderWithdrawalButton\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Vendidero\OrderWithdrawalButton\Package;
use Vendidero\OrderWithdrawalButton\WithdrawalOrder;

defined( 'ABSPATH' ) || exit;

class WithdrawalTable extends \WP_List_Table {

	protected $wp_post_type = null;

	protected $order_type = '';

	protected $has_filter = false;

	protected $request = array();

	protected $order_query_args = array();

	public function setup() {
		$this->order_type   = 'shop_order_withdraw';
		$this->wp_post_type = get_post_type_object( $this->order_type );

		add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );
		add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );
		add_filter( 'set_screen_option_edit_' . $this->order_type . '_per_page', array( $this, 'set_items_per_page' ), 10, 3 );
		add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 10, 2 );
		add_action( 'admin_footer', array( $this, 'enqueue_scripts' ) );
		add_action( 'parse_query', array( $this, 'setup_cpt_search' ) );

		$this->items_per_page();
		set_screen_options();

		add_action( 'manage_' . Admin::get_table_screen_id() . '_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Setup fallback search for CPT orders
	 *
	 * @param $wp
	 *
	 * @return void
	 */
	public function setup_cpt_search( $wp ) {
		if ( ! Package::is_hpos_enabled() ) {
			global $pagenow;

			if ( 'admin.php' !== $pagenow || ! isset( $wp->query_vars['post_type'] ) || $this->wp_post_type->name !== $wp->query_vars['post_type'] ) { // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
				return;
			}

			$post_ids = array();

			try {
				$data_store = \WC_Data_Store::load( 'order-withdrawal' );

				if ( is_callable( array( $data_store, 'search_orders' ) ) ) {
					$post_ids = isset( $_GET['s'] ) && ! empty( $wp->query_vars['s'] ) ? $data_store->search_orders( wc_clean( wp_unslash( $_GET['s'] ) ) ) : array(); // phpcs:ignore  WordPress.Security.NonceVerification.Recommended
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}

			if ( ! empty( $post_ids ) ) {
				// Remove "s" - we don't want to search order name.
				unset( $wp->query_vars['s'] );

				// so we know we're doing this.
				$wp->query_vars['shop_order_search'] = true;

				// Search by found posts.
				$wp->query_vars['post__in'] = array_merge( $post_ids, array( 0 ) );
			}
		}
	}

	/**
	 * Show confirmation message that order status changed for number of orders.
	 */
	public function bulk_action_notices() {
		if ( empty( $_REQUEST['bulk_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$order_statuses = Package::get_withdrawal_statuses();
		$number         = absint( isset( $_REQUEST['changed'] ) ? $_REQUEST['changed'] : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$bulk_action    = wc_clean( wp_unslash( $_REQUEST['bulk_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message        = '';

		// Check if any status changes happened.
		foreach ( $order_statuses as $slug => $name ) {
			if ( 'marked_' . str_replace( 'wc-owb-', '', $slug ) === $bulk_action ) { // WPCS: input var ok, CSRF ok.
				/* translators: %s: orders count */
				$message = sprintf( _nx( '%s withdrawal status changed.', '%s withdrawal statuses changed.', $number, 'owb', 'woocommerce-germanized' ), number_format_i18n( $number ) );
				break;
			}
		}

		switch ( $bulk_action ) {
			case 'trashed':
				/* translators: %s: orders count */
				$message = sprintf( _nx( '%s withdrawal moved to the Trash.', '%s withdrawals moved to the Trash.', $number, 'owb', 'woocommerce-germanized' ), number_format_i18n( $number ) );
				break;
			case 'untrashed':
				/* translators: %s: orders count */
				$message = sprintf( _nx( '%s withdrawal restored from the Trash.', '%s withdrawals restored from the Trash.', $number, 'owb', 'woocommerce-germanized' ), number_format_i18n( $number ) );
				break;
			case 'deleted':
				/* translators: %s: orders count */
				$message = sprintf( _nx( '%s withdrawal permanently deleted.', '%s withdrawals permanently deleted.', $number, 'owb', 'woocommerce-germanized' ), number_format_i18n( $number ) );
				break;
		}

		if ( ! empty( $message ) ) {
			echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Implements the "mark <status>" bulk action.
	 *
	 * @param array  $order_ids  The order IDs to change.
	 * @param string $new_status The new order status.
	 * @return int Number of orders modified.
	 */
	private function do_bulk_action_mark_orders( $order_ids, $new_status ) {
		$changed = 0;

		foreach ( $order_ids as $id ) {
			$order  = eu_owb_get_withdrawal( $id );
			$result = false;

			if ( ! $order ) {
				continue;
			}

			if ( 'confirmed' === $new_status ) {
				$result = eu_owb_order_confirm_withdrawal_request( $order );
			} elseif ( 'rejected' === $new_status && $order->has_status( 'requested' ) ) {
				$result = eu_owb_order_reject_withdrawal_request( $order );
			} else {
				$result = $order->update_status( $new_status, '', true );
			}

			if ( true === $result ) {
				++$changed;
			}
		}

		return $changed;
	}

	/**
	 * Handles bulk trashing of orders.
	 *
	 * @param int[] $ids Order IDs to be trashed.
	 * @param bool  $force_delete When set, the order will be completed deleted. Otherwise, it will be trashed.
	 *
	 * @return int Number of orders that were trashed.
	 */
	private function do_delete( $ids, $force_delete = false ) {
		$changed = 0;

		foreach ( $ids as $id ) {
			if ( $order = eu_owb_get_withdrawal( $id ) ) {
				$order->delete( $force_delete );
				$updated_order = eu_owb_get_withdrawal( $id );

				if ( ( $force_delete && false === $updated_order ) || ( ! $force_delete && $updated_order->get_status() === 'trash' ) ) {
					++$changed;
				}
			}
		}

		return $changed;
	}

	/**
	 * Handles bulk restoration of trashed orders.
	 *
	 * @param array $ids Order IDs to be restored to their previous status.
	 *
	 * @return int Number of orders that were restored from the trash.
	 */
	private function do_untrash( $ids ) {
		$changed = 0;

		foreach ( $ids as $id ) {
			if ( $order = eu_owb_get_withdrawal( $id ) ) {
				if ( $order->get_data_store()->untrash_withdrawal( $order ) ) {
					++$changed;
				}
			}
		}

		return $changed;
	}

	/**
	 * Specify the columns we wish to hide by default.
	 *
	 * @param array      $hidden Columns set to be hidden.
	 * @param \WP_Screen $screen Screen object.
	 *
	 * @return array
	 */
	public function default_hidden_columns( $hidden, $screen ) {
		if ( isset( $screen->id ) && Admin::get_table_screen_id() === $screen->id ) {
			$hidden = array_merge(
				$hidden,
				array()
			);
		}

		return $hidden;
	}

	/**
	 * Saves the items-per-page setting.
	 *
	 * @param mixed  $default The default value.
	 * @param string $option  The option being configured.
	 * @param int    $value   The submitted option value.
	 *
	 * @return mixed
	 */
	public function set_items_per_page( $default, $option, $value ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- backwards compat.
		return 'edit_' . $this->order_type . '_per_page' === $option ? absint( $value ) : $default;
	}

	public function display() {
		$post_type    = get_post_type_object( $this->order_type );
		$title        = esc_html( $post_type->labels->name );
		$search_label = '';

		if ( ! empty( $this->order_query_args['s'] ) ) {
			$search_label  = '<span class="subtitle">';
			$search_label .= sprintf(
			/* translators: %s: Search query. */
				_x( 'Search results for: %s', 'owb', 'woocommerce-germanized' ),
				'<strong>' . esc_html( $this->order_query_args['s'] ) . '</strong>'
			);
			$search_label .= '</span>';
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_kses_post(
			"
			<div class='wrap woocommerce_page_wc-orders'>
				<h1 class='wp-heading-inline'>{$title}</h1>
				{$search_label}
				<hr class='wp-header-end'>"
		);

		if ( $this->should_render_blank_state() ) {
			$this->render_blank_state();
			return;
		}

		$this->views();

		echo '<form id="eu-owb-withdrawals-filter" method="get" action="' . esc_url( get_admin_url( null, 'admin.php' ) ) . '">';
		echo '<input type="hidden" name="page" value="wc-owb-withdrawals" />';

		$state_params = array(
			'paged',
			'status',
		);

		foreach ( $state_params as $param ) {
			if ( ! isset( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				continue;
			}

			echo '<input type="hidden" name="' . esc_attr( $param ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) ) . '" >'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$this->search_box( esc_html_x( 'Search withdrawals', 'owb', 'woocommerce-germanized' ), 'withdrawals-search-input' );

		parent::display();
		echo '</form> </div>';
	}

	/**
	 * Renders advice in the event that no orders exist yet.
	 *
	 * @return void
	 */
	public function render_blank_state() {
		?>
		<div class="woocommerce-BlankState">
			<h2 class="woocommerce-BlankState-message">
				<?php echo esc_html_x( 'When you receive a new withdrawal, it will appear here.', 'owb', 'woocommerce-germanized' ); ?>
			</h2>
		</div>
		<?php
	}

	/**
	 * Checks whether the blank state should be rendered or not. This depends on whether there are others with a visible
	 * status.
	 *
	 * @return boolean TRUE when the blank state should be rendered, FALSE otherwise.
	 */
	private function should_render_blank_state() {
		return ( ! $this->has_filter ) && 0 === $this->count_orders_by_status( array_keys( $this->get_visible_statuses() ) );
	}

	private function get_visible_statuses() {
		return array_merge(
			Package::get_withdrawal_statuses(),
			array(
				'trash' => ( get_post_status_object( 'trash' ) )->label,
			)
		);
	}

	public function get_columns() {
		$columns = apply_filters(
			'eu_owb_woocommerce_withdrawals_list_table_columns',
			array(
				'cb'            => '<input type="checkbox" />',
				'withdrawal'    => esc_html_x( 'Withdrawal', 'owb', 'woocommerce-germanized' ),
				'order_number'  => esc_html_x( 'Order/Contract', 'owb', 'woocommerce-germanized' ),
				'order_date'    => esc_html_x( 'Date', 'owb', 'woocommerce-germanized' ),
				'order_address' => esc_html_x( 'Address', 'owb', 'woocommerce-germanized' ),
				'order_status'  => esc_html_x( 'Status', 'owb', 'woocommerce-germanized' ),
				'order_note'    => esc_html_x( 'Customer note', 'owb', 'woocommerce-germanized' ),
				'wc_actions'    => esc_html_x( 'Actions', 'owb', 'woocommerce-germanized' ),
			)
		);

		return $columns;
	}

	/**
	 * Defines the default sortable columns.
	 *
	 * @return string[]
	 */
	public function get_sortable_columns() {
		/**
		 * Filters the list of sortable columns.
		 *
		 * @param array $sortable_columns List of sortable columns.
		 *
		 * @since 7.3.0
		 */
		return apply_filters(
			'eu_owb_woocommerce_withdrawals_list_table_sortable_columns',
			array(
				'order_date' => 'date',
			)
		);
	}

	/**
	 * Checklist column, used for selecting items for processing by a bulk action.
	 *
	 * @param WithdrawalOrder $item The order object for the current row.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		if ( ! $this->wp_post_type || ! current_user_can( $this->wp_post_type->cap->edit_post, $item->get_id() ) ) {
			return;
		}

		ob_start();
		?>
		<input id="cb-select-<?php echo esc_attr( $item->get_id() ); ?>" type="checkbox" name="id[]" value="<?php echo esc_attr( $item->get_id() ); ?>" />
		<?php
		return ob_get_clean();
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		$limit = $this->get_items_per_page( 'edit_' . $this->order_type . '_per_page' );

		$this->order_query_args = array(
			'limit'    => $limit,
			'page'     => $this->get_pagenum(),
			'paginate' => true,
			'type'     => $this->order_type,
		);

		foreach ( array( 'status', 's', 'm', '_customer_user', 'search-filter' ) as $query_var ) {
			$this->request[ $query_var ] = sanitize_text_field( wp_unslash( isset( $_REQUEST[ $query_var ] ) ? $_REQUEST[ $query_var ] : '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$this->set_status_args();
		$this->set_order_args();
		$this->set_search_args();

		$order_query_args = (array) $this->order_query_args;

		// We must ensure the 'paginate' argument is set.
		$order_query_args['paginate'] = true;

		// Attempt to use cache if no additional query arguments are used.
		if ( empty( array_diff( array_keys( $this->order_query_args ), array( 'limit', 'page', 'paginate', 'type', 'status', 'orderby', 'order' ) ) ) ) {
			$this->order_query_args['no_found_rows'] = true;
			$order_query_args['no_found_rows']       = true;
		}

		$orders      = wc_get_orders( $order_query_args );
		$this->items = $orders->orders;

		$max_num_pages = $this->get_max_num_pages( $orders );

		// Check in case the user has attempted to page beyond the available range of orders.
		if ( 0 === $max_num_pages && $this->order_query_args['page'] > 1 ) {
			$count_query_args          = $order_query_args;
			$count_query_args['page']  = 1;
			$count_query_args['limit'] = 1;
			$order_count               = wc_get_orders( $count_query_args );
			$max_num_pages             = (int) ceil( $order_count->total / $order_query_args['limit'] );
		}

		$this->set_pagination_args(
			array(
				'total_items' => isset( $orders->total ) ? $orders->total : 0,
				'per_page'    => $limit,
				'total_pages' => $max_num_pages,
			)
		);
	}

	/**
	 * Implements order search.
	 */
	private function set_search_args(): void {
		$search_term = trim( sanitize_text_field( $this->request['s'] ) );
		$filter      = trim( sanitize_text_field( $this->request['search-filter'] ) );

		if ( ! empty( $filter ) ) {
			$this->order_query_args['search_filter'] = $filter;
		}

		if ( ! empty( $search_term ) ) {
			if ( empty( $this->order_query_args['search_filter'] ) ) {
				$this->order_query_args['search_filter'] = 'customers';
			}

			$this->order_query_args['s'] = $search_term;
			$this->has_filter            = true;

			/**
			 * Fix a HPOS bug that leads to search queries ignoring the order type.
			 */
			add_filter(
				'woocommerce_orders_table_query_clauses',
				function ( $clauses, $table_obj, $args ) {
					global $wpdb;

					$table_name       = $table_obj->get_table_name( 'orders' );
					$additional_query = $wpdb->prepare( "{$table_name}.type = %s", $this->order_type ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

					$clauses['where'] = str_replace( '1=1 AND (', "1=1 AND ({$additional_query} AND ", $clauses['where'] );

					return $clauses;
				},
				10,
				3
			);
		}
	}

	/**
	 * Get the max number of pages from orders or from cache.
	 *
	 * @param WithdrawalOrder[]|stdClass Number of pages and an array of order objects.
	 * @return int
	 */
	private function get_max_num_pages( &$orders ) {
		if ( ! isset( $this->order_query_args['no_found_rows'] ) || ! $this->order_query_args['no_found_rows'] ) {
			return $orders->max_num_pages;
		}

		$count         = $this->count_orders_by_status( $this->order_query_args['status'] );
		$limit         = $this->get_items_per_page( 'edit_' . $this->order_type . '_per_page' );
		$orders->total = $count;

		return ceil( $count / $limit );
	}

	/**
	 * Count orders by status.
	 *
	 * @param string|string[] $status The order status we are interested in.
	 *
	 * @return int
	 */
	private function count_orders_by_status( $status ) {
		$status = (array) $status;
		$counts = Admin::get_withdrawal_count();
		$count  = array_sum( array_intersect_key( $counts, array_flip( $status ) ) );

		return $count;
	}

	/**
	 * Updates the WC Order Query arguments as needed to support orderable columns.
	 */
	private function set_order_args() {
		$sortable  = $this->get_sortable_columns();
		$field     = sanitize_text_field( wp_unslash( isset( $_GET['orderby'] ) ? $_GET['orderby'] : '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$direction = strtoupper( sanitize_text_field( wp_unslash( isset( $_GET['order'] ) ? $_GET['order'] : '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $field, $sortable, true ) ) {
			$this->order_query_args['orderby'] = 'date';
			$this->order_query_args['order']   = 'DESC';
			return;
		}

		$this->order_query_args['orderby'] = $field;
		$this->order_query_args['order']   = in_array( $direction, array( 'ASC', 'DESC' ), true ) ? $direction : 'ASC';
	}

	/**
	 * Implements filtering of orders by status.
	 */
	private function set_status_args() {
		$status = array_filter( array_map( 'trim', (array) $this->request['status'] ) );

		if ( empty( $status ) || in_array( 'all', $status, true ) ) {
			$status = array_keys( Package::get_withdrawal_statuses() );
		} else {
			$this->has_filter = true;
		}

		$this->order_query_args['status'] = $status;
	}

	/**
	 * Sets up an items-per-page control.
	 */
	private function items_per_page() {
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'edit_' . $this->order_type . '_per_page',
			)
		);
	}

	/**
	 * Retrieves the list of bulk actions available for this table.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		$selected_status = isset( $this->order_query_args['status'] ) ? $this->order_query_args['status'] : false;

		if ( ! current_user_can( $this->wp_post_type->cap->edit_others_posts ) ) {
			return array();
		}

		if ( array( 'trash' ) === $selected_status ) {
			$actions = array(
				'untrash' => _x( 'Restore', 'owb', 'woocommerce-germanized' ),
				'delete'  => _x( 'Delete permanently', 'owb', 'woocommerce-germanized' ),
			);
		} else {
			$actions = array(
				'mark_confirmed' => _x( 'Confirm', 'owb', 'woocommerce-germanized' ),
				'mark_rejected'  => _x( 'Reject', 'owb', 'woocommerce-germanized' ),
				'trash'          => _x( 'Move to Trash', 'owb', 'woocommerce-germanized' ),
			);
		}

		return $actions;
	}

	/**
	 * Gets the current action selected from the bulk actions dropdown.
	 *
	 * @return string|false The action name. False if no action was selected.
	 */
	public function current_action() {
		if ( ! empty( $_REQUEST['delete_all'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 'delete_all';
		}

		return parent::current_action();
	}

	/**
	 * Handle bulk actions.
	 */
	public function handle_bulk_actions() {
		$action = $this->current_action();

		if ( ! $action || ! current_user_can( $this->wp_post_type->cap->edit_others_posts ) ) {
			return;
		}

		check_admin_referer( 'bulk-' . Admin::get_table_screen_id() );

		$redirect_to = remove_query_arg( array( 'deleted', 'ids' ), wp_get_referer() );
		$redirect_to = add_query_arg( 'paged', $this->get_pagenum(), $redirect_to );

		if ( 'delete_all' === $action ) {
			// Get all trashed orders.
			$ids = wc_get_orders(
				array(
					'type'   => $this->order_type,
					'status' => 'trash',
					'limit'  => -1,
					'return' => 'ids',
				)
			);

			$action = 'delete';
		} else {
			$ids = isset( $_REQUEST['id'] ) ? array_reverse( array_map( 'absint', (array) $_REQUEST['id'] ) ) : array();
		}

		if ( ! $ids ) {
			wp_safe_redirect( $redirect_to );
			exit;
		}

		$report_action  = '';
		$changed        = 0;
		$action_handled = true;

		if ( 'trash' === $action ) {
			$changed       = $this->do_delete( $ids );
			$report_action = 'trashed';
		} elseif ( 'delete' === $action ) {
			$changed       = $this->do_delete( $ids, true );
			$report_action = 'deleted';
		} elseif ( 'untrash' === $action ) {
			$changed       = $this->do_untrash( $ids );
			$report_action = 'untrashed';
		} elseif ( false !== strpos( $action, 'mark_' ) ) {
			$order_statuses = Package::get_withdrawal_statuses();
			$new_status     = substr( $action, 5 );
			$report_action  = 'marked_' . $new_status;

			if ( isset( $order_statuses[ 'wc-owb-' . $new_status ] ) ) {
				$changed = $this->do_bulk_action_mark_orders( $ids, $new_status );
			} else {
				$action_handled = false;
			}
		} else {
			$action_handled = false;
		}

		// Custom action.
		if ( ! $action_handled ) {
			$screen = get_current_screen()->id;

			/**
			 * This action is documented in /wp-admin/edit.php (it is a core WordPress hook).
			 *
			 * @since 7.2.0
			 *
			 * @param string $redirect_to The URL to redirect to after processing the bulk actions.
			 * @param string $action      The current bulk action.
			 * @param int[]  $ids         IDs for the orders to be processed.
			 */
			$custom_sendback = apply_filters( "handle_bulk_actions-{$screen}", $redirect_to, $action, $ids ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		if ( ! empty( $custom_sendback ) ) {
			$redirect_to = $custom_sendback;
		} elseif ( $changed ) {
			$redirect_to = add_query_arg(
				array(
					'bulk_action' => $report_action,
					'changed'     => $changed,
					'ids'         => implode( ',', $ids ),
				),
				$redirect_to
			);
		}

		wp_safe_redirect( $redirect_to );
		exit;
	}

	public function get_views() {
		$view_links  = array();
		$view_counts = array();
		$statuses    = $this->get_visible_statuses();
		$current     = ! empty( $this->request['status'] ) ? sanitize_text_field( $this->request['status'] ) : 'all';
		$all_count   = 0;

		foreach ( array_keys( $statuses ) as $slug ) {
			$total_in_status = $this->count_orders_by_status( $slug );

			if ( $total_in_status > 0 ) {
				$view_counts[ $slug ] = $total_in_status;
			}

			if ( ! in_array( $slug, array( 'auto-draft', 'trash' ), true ) ) {
				$all_count += $total_in_status;
			}
		}

		$view_links['all'] = $this->get_view_link( 'all', _x( 'All', 'owb', 'woocommerce-germanized' ), $all_count, '' === $current || 'all' === $current );

		foreach ( $view_counts as $slug => $count ) {
			$view_links[ $slug ] = $this->get_view_link( $slug, $statuses[ $slug ], $count, $slug === $current );
		}

		return $view_links;
	}

	/**
	 * Form a link to use in the list of table views.
	 *
	 * @param string $slug    Slug used to identify the view (usually the order status slug).
	 * @param string $name    Human-readable name of the view (usually the order status label).
	 * @param int    $count   Number of items in this view.
	 * @param bool   $current If this is the current view.
	 *
	 * @return string
	 */
	private function get_view_link( $slug, $name, $count, $current ) {
		$base_url = Package::get_withdrawals_url();
		$url      = esc_url( add_query_arg( 'status', $slug, $base_url ) );
		$name     = esc_html( $name );
		$count    = number_format_i18n( $count );
		$class    = $current ? 'class="current"' : '';

		return "<a href='$url' $class>$name <span class='count'>($count)</span></a>";
	}

	/**
	 * @param WithdrawalOrder $order
	 *
	 * @return void
	 */
	public function single_row( $order ) {
		$css_classes = array(
			'order-' . $order->get_id(),
			'type-' . $order->get_type(),
			'status-' . Package::maybe_remove_withdrawal_order_status_prefix( $order->get_status() ),
		);

		echo '<tr id="order-' . esc_attr( $order->get_id() ) . '" class="' . esc_attr( implode( ' ', $css_classes ) ) . '">';
		$this->single_row_columns( $order );
		echo '</tr>';
	}

	/**
	 * Render individual column.
	 *
	 * @param string   $column_id Column ID to render.
	 * @param WithdrawalOrder $order Order object.
	 */
	public function render_column( $column_id, $order ) {
		if ( ! $order ) {
			return;
		}

		if ( is_callable( array( $this, 'render_' . $column_id . '_column' ) ) ) {
			call_user_func( array( $this, 'render_' . $column_id . '_column' ), $order );
		}
	}

	/**
	 * Handles output for the default column.
	 *
	 * @param WithdrawalOrder $order       Current WooCommerce order object.
	 * @param string    $column_name Identifier for the custom column.
	 */
	public function column_default( $order, $column_name ) {
		do_action( 'eu_owb_woocommerce_withdrawal_order_list_table_custom_column', $column_name, $order );

		/**
		 * Fires for each custom column in the Custom Order Table in the administrative screen.
		 *
		 * @param string    $column_name Identifier for the custom column.
		 * @param WithdrawalOrder $order       Current WooCommerce order object.
		 *
		 * @since 7.0.0
		 */
		do_action( "manage_{$this->screen->id}_custom_column", $column_name, $order );
	}

	/**
	 * Renders the order number, customer name and provides a preview link.
	 *
	 * @param WithdrawalOrder $order The order object for the current row.
	 *
	 * @return void
	 */
	public function render_order_number_column( $order ) {
		$buyer = $order->get_formatted_full_name( $order->get_email() ) . Admin::get_withdrawal_email_verified_html( $order );

		echo $order->has_status( 'requested' ) ? '<a class="order-preview eu-owb-order-toggle-order-search" href="#"></a>' : '';

		if ( $order->has_parent() ) {
			echo '<a href="' . esc_url( OrderUtil::get_order_admin_edit_url( $order->get_parent_id() ) ) . '" class="order-view"><strong>#' . esc_attr( $order->get_order_number() ) . ' ' . wp_kses_post( $buyer ) . '</strong></a>';
		} else {
			echo '<strong>' . esc_attr( $order->get_order_number( 'admin' ) ? ( $order->get_order_number() . ' ' ) : '' ) . wp_kses_post( $buyer ) . '</strong>';
		}
		?>
		<?php if ( $order->has_status( 'requested' ) ) : ?>
			<div class="eu-owb-order-search-container eu-owb-order-inline-edit-wrapper inline-single-row hidden">
				<select class="eu-owb-order-search" name="inline_form_parent" id="parent_id_<?php echo esc_attr( $order->get_id() ); ?>" data-placeholder="<?php echo esc_attr_x( 'Search for an order', 'owb', 'woocommerce-germanized' ); ?>" data-allow_clear="true">
					<?php
					if ( $order->get_parent() ) :
						$order_string = sprintf(
							esc_html_x( 'Order #%s', 'owb', 'woocommerce-germanized' ),
							$order->get_order_number()
						);
						?>
						<option value="<?php echo esc_attr( $order->get_parent_id() ); ?>" selected="selected"><?php echo htmlspecialchars( wp_kses_post( $order_string ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></option>
					<?php endif; ?>
				</select>
				<button class="button button-primary eu-owb-order-withdrawal-order-save" href="#" data-save="parent_id" data-id="<?php echo esc_attr( $order->get_id() ); ?>"><span class="btn-text"><span class="dashicons dashicons-saved"></span></span></button>
			</div>
			<?php
		endif;

		// Used for showing date & status next to order number/buyer name on small screens.
		echo '<div class="order_date small-screen-only">';
		$this->render_order_date_column( $order );
		echo '</div>';
		echo '<div class="order_status small-screen-only">';
		$this->render_order_status_column( $order );
		echo '</div>';
	}

	/**
	 * Renders the order number, customer name and provides a preview link.
	 *
	 * @param WithdrawalOrder $order The order object for the current row.
	 *
	 * @return void
	 */
	public function render_order_note_column( $order ) {
		if ( $order->get_customer_note() ) {
			$customer_note = eu_owb_wptexturize_withdrawal_additional_information( $order->get_customer_note() );

			echo wp_kses( nl2br( $customer_note ), array( 'br' => array() ) );
		}
	}

	/**
	 * Renders the order number, customer name and provides a preview link.
	 *
	 * @param WithdrawalOrder $order The order object for the current row.
	 *
	 * @return void
	 */
	public function render_withdrawal_column( $order ) {
		echo '<mark class="order-status status-owb-type-' . esc_attr( $order->is_partial() ? 'partial' : 'full' ) . '"><span>' . sprintf( $order->is_partial() ? esc_html_x( 'Partial %s', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'Full %s', 'owb', 'woocommerce-germanized' ), esc_html( $order->get_id() ) ) . '</span></mark>';
	}

	/**
	 * Renders the order number, customer name and provides a preview link.
	 *
	 * @param WithdrawalOrder $order The order object for the current row.
	 *
	 * @return void
	 */
	public function render_order_address_column( $order ) {
		$address_fields = array(
			'formatted_full_name',
			'email',
		);

		$address = array();

		foreach ( $address_fields as $field ) {
			$getter = "get_{$field}";
			$value  = $order->$getter();

			if ( empty( $value ) ) {
				continue;
			}

			if ( 'email' === $field ) {
				$value = '<a href="' . esc_url( 'mailto:' . $value ) . '">' . esc_html( $value ) . '</a>';
			}

			$address[ $field ] = $value;
		}

		$formatted_address = implode( ', ', $address );

		echo wp_kses_post( $formatted_address );
	}

	/**
	 * Renders order actions.
	 *
	 * @param WithdrawalOrder $order The order object for the current row.
	 *
	 * @return void
	 */
	public function render_wc_actions_column( $order ) {
		echo '<p>';

		$actions = array();

		if ( $order->has_status( array( 'requested' ) ) ) {
			if ( $order->has_parent() ) {
				$actions['confirm_withdrawal_request'] = array(
					'url'    => Admin::get_edit_withdrawal_url( $order->get_id() ),
					'name'   => _x( 'Confirm withdrawal request', 'owb', 'woocommerce-germanized' ),
					'action' => 'complete',
				);
			}

			$actions['reject_withdrawal_request'] = array(
				'url'    => '#',
				'name'   => _x( 'Reject withdrawal request', 'owb', 'woocommerce-germanized' ),
				'action' => 'reject',
			);

			$actions['delete_withdrawal_request'] = array(
				'url'    => Admin::get_edit_withdrawal_url( $order->get_id(), 'delete' ),
				'name'   => _x( 'Delete withdrawal request', 'owb', 'woocommerce-germanized' ),
				'action' => 'delete',
			);
		}

		$actions = apply_filters( 'eu_owb_woocommerce_admin_withdrawal_order_actions', $actions, $order );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wc_render_action_buttons( $actions );

		echo '</p>';

		if ( $order->has_status( array( 'requested' ) ) ) {
			?>
			<div class="no-link eu-owb-withdrawal-reject-container hidden eu-owb-order-inline-edit-wrapper">
				<textarea class="eu-owb-withdrawal-reject-reason" name="inline_form_rejection_reason" id="rejection_reason_<?php echo esc_attr( $order->get_id() ); ?>" placeholder="<?php echo esc_attr_x( 'Reason', 'owb', 'woocommerce-germanized' ); ?>"></textarea>
				<button class="button button-primary eu-owb-order-withdrawal-order-save" href="#" data-save="rejection_reason" data-action="reject" data-id="<?php echo esc_attr( $order->get_id() ); ?>"><span class="btn-text"><span class="dashicons dashicons-saved"></span></span></button>
			</div>
			<?php
		}
	}

	/**
	 * Renders the order date.
	 *
	 * @param WithdrawalOrder $order The order object for the current row.
	 *
	 * @return void
	 */
	public function render_order_date_column( $order ) {
		$order_timestamp = $order->get_date_received() ? $order->get_date_received()->getTimestamp() : '';

		if ( ! $order_timestamp ) {
			echo '&ndash;';
			return;
		}

		// Check if the order was created within the last 24 hours, and not in the future.
		if ( $order_timestamp > strtotime( '-1 day', time() ) && $order_timestamp <= time() ) {
			$show_date = sprintf(
			/* translators: %s: human-readable time difference */
				_x( '%s ago', 'owb-human-readable-time-diff', 'woocommerce-germanized' ),
				human_time_diff( $order->get_date_received()->getTimestamp(), time() )
			);
		} else {
			$show_date = wc_format_datetime( $order->get_date_received() );
		}
		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $order->get_date_received()->date( 'c' ) ),
			esc_html( $order->get_date_received()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	public function enqueue_scripts() {
	}

	/**
	 * Renders the order status.
	 *
	 * @param WithdrawalOrder $order The order object for the current row.
	 *
	 * @return void
	 */
	public function render_order_status_column( $order ) {
		$status_name = Package::get_withdrawal_status_name( $order->get_status() );

		printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $order->get_status() ) ), esc_html( $status_name ) );
	}
}