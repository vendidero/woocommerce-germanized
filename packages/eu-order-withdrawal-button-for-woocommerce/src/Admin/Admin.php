<?php

namespace Vendidero\OrderWithdrawalButton\Admin;

use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Vendidero\OrderWithdrawalButton\Package;
use Vendidero\OrderWithdrawalButton\Settings;
use Vendidero\OrderWithdrawalButton\WithdrawalOrder;

defined( 'ABSPATH' ) || exit;

class Admin {

	protected static $order_item_map = array();

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_action( 'admin_init', array( __CLASS__, 'on_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'register_screen_ids' ), 10 );

		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( __CLASS__, 'order_withdrawal_request_details' ), 100, 1 );
		add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'admin_order_actions' ), 1500, 2 );
		add_action( 'woocommerce_admin_order_actions_end', array( __CLASS__, 'inline_order_actions' ) );
		add_filter( 'woocommerce_get_sections_advanced', array( __CLASS__, 'register_sections' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'register_settings' ), 10, 2 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'register_hidden_itemmeta' ), 10, 2 );
		add_action( 'woocommerce_after_order_itemmeta', array( __CLASS__, 'display_custom_itemmeta' ), 10, 3 );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'process_withdrawal_rejection' ), 45 );
		add_filter( 'woocommerce_menu_order_count', array( __CLASS__, 'menu_order_count' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_order_meta_box' ), 35 );

		add_action( 'woocommerce_system_status_report', array( __CLASS__, 'status_report' ) );
	}

	public static function status_report() {
		$template_info = self::get_template_info();
		?>
		<table class="wc_status_table widefat" id="status-table-shiptastic" cellspacing="0">
			<thead>
			<tr>
				<th colspan="3" data-export-label="Order Withdrawal Button for WooCommerce"><h2><?php echo esc_html_x( 'EU Order Withdrawal Button for WooCommerce', 'owb', 'woocommerce-germanized' ); ?></h2></th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td data-export-label="Order withdrawal Button for WooCommerce Database Version"><?php echo esc_html_x( 'Database Version', 'owb', 'woocommerce-germanized' ); ?></td>
				<td class="help">&nbsp</td>
				<td><?php echo esc_html( get_option( 'eu_owb_woocommerce_db_version' ) ); ?></td>
			</tr>
			<tr>
				<td data-export-label="Order withdrawal Button for WooCommerce Overrides"><?php echo esc_html_x( 'Overrides', 'owb', 'woocommerce-germanized' ); ?></td>
				<td class="help">&nbsp;</td>
				<td>
					<?php if ( ! empty( $template_info['files'] ) ) : ?>
						<?php foreach ( $template_info['files'] as $file ) : ?>
							<?php printf( '<code>%s</code>', esc_html( str_replace( WP_CONTENT_DIR . '/themes/', '', $file['theme_file'] ) ) ); ?>
							<?php if ( $file['outdated'] ) : ?>
								<?php printf( esc_html_x( 'Version %1$s is out of date. The core version %2$s is available at: %3$s', 'owb', 'woocommerce-germanized' ), '<span class="red" style="color:red">' . esc_html( $file['theme_version'] ) . '</span>', esc_html( $file['core_version'] ), '<code>' . esc_html( str_replace( WP_PLUGIN_DIR, '', $file['core_file'] ) ) . '</code>' ); ?>
							<?php endif; ?>
							<br/>
						<?php endforeach; ?>
					<?php else : ?>
						&ndash;
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( true === $template_info['has_outdated_templates'] ) : ?>
				<tr>
					<td data-export-label="Order withdrawal Button for WooCommerce Outdated Templates"><?php echo esc_html_x( 'Outdated templates', 'owb', 'woocommerce-germanized' ); ?>:</td>
					<td class="help">&nbsp;</td>
					<td>
						<mark class="error">
							<span class="dashicons dashicons-warning"></span>
						</mark>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	public static function get_template_info() {
		$core_path     = Package::get_path( 'templates' );
		$files         = \WC_Admin_Status::scan_template_files( $core_path );
		$template_path = Package::get_template_path();
		$template_data = array(
			'files'                  => array(),
			'has_outdated_templates' => false,
		);

		foreach ( $files as $file ) {
			if ( '.DS_Store' === $file ) {
				continue;
			}

			$theme_file = false;

			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/' . $template_path . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $template_path . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $template_path . $file ) ) {
				$theme_file = get_template_directory() . '/' . $template_path . $file;
			}

			if ( false !== $theme_file ) {
				$core_version  = \WC_Admin_Status::get_file_version( trailingslashit( $core_path ) . $file );
				$theme_version = \WC_Admin_Status::get_file_version( $theme_file );

				if ( ! $theme_version ) {
					$theme_version = '1.0';
				}

				$file_data = array(
					'core_file'     => trailingslashit( $core_path ) . $file,
					'template'      => $file,
					'theme_file'    => $theme_file,
					'theme_version' => $theme_version,
					'core_version'  => $core_version,
					'outdated'      => false,
				);

				if ( $core_version && $theme_version && version_compare( $theme_version, $core_version, '<' ) ) {
					$file_data['outdated']                   = true;
					$template_data['has_outdated_templates'] = true;
				}

				$template_data['files'][] = $file_data;
			}
		}

		return $template_data;
	}

	public static function inline_order_actions( $order ) {
		if ( $request = eu_owb_get_withdrawal_request( $order ) ) {
			?>
			<div class="eu-owb-withdrawal-reject-container hidden eu-owb-order-inline-edit-wrapper no-link">
				<textarea class="eu-owb-withdrawal-reject-reason" name="rejection_reason" id="rejection_reason_<?php echo esc_attr( $request->get_id() ); ?>" placeholder="<?php echo esc_attr_x( 'Reason', 'owb', 'woocommerce-germanized' ); ?>"></textarea>
				<button class="button button-primary eu-owb-order-withdrawal-order-save" href="#" data-save="rejection_reason" data-action="reject" data-id="<?php echo esc_attr( $request->get_id() ); ?>"><span class="btn-text"><span class="dashicons dashicons-saved"></span></span></button>
			</div>
			<?php
		}
	}

	public static function register_screen_ids( $screen_ids ) {
		$screen_ids = array_merge( $screen_ids, self::get_core_screen_ids() );

		return $screen_ids;
	}

	public static function get_core_screen_ids() {
		$screen_ids = array(
			self::get_table_screen_id(),
		);

		return $screen_ids;
	}

	public static function set_screen_option( $new_value, $option, $value ) {
		if ( in_array( $option, array( 'woocommerce_page_wc_stc_shipments_per_page', 'woocommerce_page_wc_stc_return_shipments_per_page' ), true ) ) {
			return absint( $value );
		}

		return $new_value;
	}

	public static function can_view_woocommerce_menu_item() {
		return current_user_can( 'edit_others_shop_orders' );
	}

	public static function register_menu() {
		global $submenu;

		$show_in_menu = apply_filters( 'eu_owb_woocommerce_show_withdrawals_in_menu', ! empty( $_GET['page'] ) && 'wc-owb-withdrawals' === wc_clean( wp_unslash( $_GET['page'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $show_in_menu ) {
			return;
		}

		$order_type = 'shop_order_withdraw';
		$post_type  = get_post_type_object( $order_type );

		add_submenu_page(
			self::can_view_woocommerce_menu_item() ? 'woocommerce' : 'admin.php',
			$post_type->labels->name,
			$post_type->labels->menu_name,
			$post_type->cap->edit_posts,
			'wc-owb-withdrawals',
			array( __CLASS__, 'output_table_view' )
		);

		if ( isset( $submenu['woocommerce'] ) ) {
			// Add count if user has access.
			if ( current_user_can( 'edit_others_shop_orders' ) ) {
				$withdrawal_count = self::get_withdrawal_count( 'requested' );

				if ( $withdrawal_count ) {
					foreach ( $submenu['woocommerce'] as $key => $menu_item ) {
						if ( 0 === strpos( $menu_item[0], $post_type->labels->name ) ) {
							$submenu['woocommerce'][ $key ][0] .= ' <span class="menu-counter count-' . esc_attr( $withdrawal_count ) . '"><span class="processing-count">' . number_format_i18n( $withdrawal_count ) . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							break;
						}
					}
				}
			}
		}

		add_action( 'load-' . self::get_table_screen_id(), array( __CLASS__, 'setup_table_view' ) );
	}

	public static function setup_table_view() {
		global $wp_list_table;

		$order_table = new WithdrawalTable();

		$wp_list_table = $order_table; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_list_table->setup();

		if ( $wp_list_table->current_action() ) {
			$wp_list_table->handle_bulk_actions();
		}

		$current_url  = esc_url_raw( wp_unslash( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ) );
		$stripped_url = remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), $current_url );

		if ( $stripped_url !== $current_url ) {
			wp_safe_redirect( $stripped_url );
			exit;
		}
	}

	/**
	 * Count withdrawals by status - need to apply our own logic here as we do not
	 * register our custom order statues via wc_get_order_statuses.
	 *
	 * @return array|integer
	 */
	public static function get_withdrawal_count( $target_status = '' ) {
		$order_type    = 'shop_order_withdraw';
		$counts        = OrderUtil::get_count_for_type( $order_type );
		$core_statuses = array( 'wc-owb-requested', 'wc-owb-confirmed', 'wc-owb-rejected' );
		$needs_reset   = false;

		foreach ( $core_statuses as $status ) {
			if ( ! isset( $counts[ $status ] ) ) {
				$counts[ $status ] = 0;
				$needs_reset       = true;
			}
		}

		if ( $needs_reset ) {
			$order_count_cache = new OrderCountCache();
			$order_count_cache->set_multiple( $order_type, $counts );
		}

		if ( ! empty( $target_status ) ) {
			$target_status = Package::maybe_prefix_withdrawal_order_status( $target_status );

			return array_key_exists( $target_status, $counts ) ? $counts[ $target_status ] : 0;
		}

		return $counts;
	}

	public static function get_table_screen_id() {
		$page_suffix = 'wc-owb-withdrawals';
		$page_name   = ( self::can_view_woocommerce_menu_item() ? 'woocommerce_page_' : 'admin_page_' ) . $page_suffix;

		return $page_name;
	}

	public static function output_table_view() {
		global $wp_list_table;

		$wp_list_table->prepare_items();
		$wp_list_table->display();
	}

	public static function add_order_meta_box() {
		$order_type_screen_ids = array_merge( wc_get_order_types( 'order-meta-boxes' ), array( self::get_order_screen_id() ) );

		// Orders.
		foreach ( $order_type_screen_ids as $type ) {
			add_meta_box(
				'eu-owb-order-withdrawals',
				_x( 'Withdrawals', 'owb', 'woocommerce-germanized' ),
				function ( $post ) {
					global $theorder;

					if ( is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'init_theorder_object' ) ) ) {
						\Automattic\WooCommerce\Utilities\OrderUtil::init_theorder_object( $post );
					} else {
						global $post, $thepostid;

						if ( ! is_object( $theorder ) ) {
							if ( ! is_int( $thepostid ) ) {
								$thepostid = $post->ID;
							}

							$theorder = wc_get_order( $thepostid );
						}
					}

					if ( $theorder ) {
						$order       = $theorder;
						$withdrawals = eu_owb_get_order_withdrawals( $order, array( 'status' => array( 'confirmed', 'rejected' ) ) );

						if ( ! empty( $withdrawals ) ) :
							?>
							<ul class="withdrawals">
								<?php
								foreach ( $withdrawals as $withdrawal ) :
									$item_list = array();
									foreach ( $withdrawal->get_items() as $item ) {
										$item_list[] = sprintf( _x( '%1$s x %2$s', 'item-quantity', 'woocommerce-germanized' ), wp_kses_post( $item->get_name() ), esc_html( $item->get_quantity() ) );
									}
									?>
									<li class="withdrawal withdrawal-<?php echo esc_attr( Package::maybe_remove_withdrawal_order_status_prefix( $withdrawal->get_status() ) ); ?>">
										<div class="withdrawal-content">
											<p><?php echo wp_kses_post( sprintf( _x( '%1$s received on %2$s @ %3$s by <a href="mailto:%4$s">%5$s</a> %6$s', 'owb', 'woocommerce-germanized' ), ( $withdrawal->is_partial() ) ? esc_html_x( 'Partial withdrawal', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'Full withdrawal', 'owb', 'woocommerce-germanized' ), wc_format_datetime( $withdrawal->get_date_received() ), wc_format_datetime( $withdrawal->get_date_received(), get_option( 'time_format' ) ), $withdrawal->get_email(), $withdrawal->get_formatted_full_name( $withdrawal->get_email() ), self::get_withdrawal_email_verified_html( $withdrawal ) ) ); ?></p>
											<p class="withdrawal-items"><?php echo implode( ', ', $item_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

											<?php if ( $withdrawal->has_status( 'rejected' ) && ! empty( $withdrawal->get_rejection_reason() ) ) : ?>
												<?php echo wp_kses_post( wpautop( make_clickable( $withdrawal->get_rejection_reason() ) ) ); ?>
											<?php endif; ?>
										</div>

										<p class="meta">
											<?php if ( $withdrawal->has_status( 'confirmed' ) && $withdrawal->get_date_confirmed() ) : ?>
												<abbr class="confirmed-date" title=""><?php echo esc_html( sprintf( _x( 'Confirmed %1$s at %2$s', 'owb', 'woocommerce-germanized' ), wc_format_datetime( $withdrawal->get_date_confirmed() ), wc_format_datetime( $withdrawal->get_date_confirmed(), get_option( 'time_format' ) ) ) ); ?></abbr>
											<?php elseif ( $withdrawal->has_status( 'confirmed' ) && $withdrawal->get_date_rejected() ) : ?>
												<abbr class="rejected-date" title=""><?php echo esc_html( sprintf( _x( 'Rejected %1$s at %2$s', 'owb', 'woocommerce-germanized' ), wc_format_datetime( $withdrawal->get_date_rejected() ), wc_format_datetime( $withdrawal->get_date_rejected(), get_option( 'time_format' ) ) ) ); ?></abbr>
											<?php endif; ?>

											<a href="<?php echo esc_url( self::get_edit_withdrawal_url( $withdrawal->get_id(), 'delete' ) ); ?>" class="delete_withdrawal" role="button"><?php echo esc_html_x( 'Delete withdrawal', 'owb', 'woocommerce-germanized' ); ?></a>
										</p>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php
						endif;
					}
				},
				$type,
				'side',
				'default'
			);
		}
	}

	public static function register_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		wp_register_script( 'eu-owb-woocommerce-admin-order', Package::get_assets_url( 'static/admin-order.js' ), array( 'jquery', 'woocommerce_admin' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		wp_localize_script(
			'eu-owb-woocommerce-admin-order',
			'eu_owb_woocommerce_admin_order_params',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'search_orders_nonce' => wp_create_nonce( 'eu_owb_woocommerce_search_orders' ),
				'save_order_nonce'    => wp_create_nonce( 'eu_owb_woocommerce_save_order' ),
			)
		);

		// Register admin styles.
		wp_register_style( 'eu-owb-woocommerce-admin-styles', Package::get_assets_url( 'static/admin-styles.css' ), array( 'woocommerce_admin_styles' ), Package::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_order_screen_ids(), true ) ) {
			wp_enqueue_style( 'eu-owb-woocommerce-admin-styles' );
			wp_enqueue_script( 'eu-owb-woocommerce-admin-order' );
		} elseif ( 'woocommerce_page_wc-settings' === $screen_id ) {
			wp_enqueue_style( 'eu-owb-woocommerce-admin-styles' );
		}
	}

	public static function menu_order_count( $count ) {
		$count += wc_orders_count( 'pending-wdraw' );

		return $count;
	}

	public static function process_withdrawal_rejection( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( isset( $_POST['reject_withdrawal_request'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$reason = isset( $_POST['eu_owb_reject_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['eu_owb_reject_reason'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

				eu_owb_order_reject_withdrawal_request( $order, $reason );
			}
		}
	}

	protected static function get_order_item_map( $order_id ) {
		if ( ! array_key_exists( $order_id, self::$order_item_map ) ) {
			self::$order_item_map[ $order_id ] = array();

			foreach ( eu_owb_get_order_withdrawals( $order_id ) as $withdrawal ) {
				foreach ( $withdrawal->get_items() as $item ) {
					if ( ! isset( self::$order_item_map[ $order_id ][ $item->get_parent_id() ] ) ) {
						self::$order_item_map[ $order_id ][ $item->get_parent_id() ] = array(
							'requested' => 0,
							'rejected'  => 0,
							'confirmed' => 0,
						);
					}

					$status = Package::maybe_remove_withdrawal_order_status_prefix( $withdrawal->get_status() );

					if ( array_key_exists( $status, self::$order_item_map[ $order_id ][ $item->get_parent_id() ] ) ) {
						self::$order_item_map[ $order_id ][ $item->get_parent_id() ][ $status ] += $item->get_quantity();
					}
				}
			}
		}

		return self::$order_item_map[ $order_id ];
	}

	public static function display_custom_itemmeta( $item_id, $item, $product ) {
		$item_map = self::get_order_item_map( $item->get_order_id() );

		if ( array_key_exists( $item_id, $item_map ) ) {
			if ( $item_map[ $item_id ]['confirmed'] > 0 ) {
				?>
				<mark class="withdrawal-quantity"><?php echo wp_kses_post( sprintf( _x( 'Withdrawn %1$sx', 'owb', 'woocommerce-germanized' ), $item_map[ $item_id ]['confirmed'] ) ); ?></mark>
				<?php
			}

			if ( $item_map[ $item_id ]['requested'] > 0 ) {
				?>
				<mark class="withdrawal-quantity withdrawal-requested"><?php echo wp_kses_post( sprintf( _x( 'Requested %1$sx', 'owb', 'woocommerce-germanized' ), $item_map[ $item_id ]['requested'] ) ); ?></mark>
				<?php
			}
		}
	}

	public static function register_hidden_itemmeta( $hidden_meta ) {
		return array_merge(
			$hidden_meta,
			array(
				'_withdrawn_quantity',
				'_withdrawn_quantity_refunded',
				'_withdrawn_quantity_refund_ids',
				'_withdrawn_quantities',
				'_withdrawal_request_quantity',
			)
		);
	}

	/**
	 * @param WithdrawalOrder $withdrawal
	 *
	 * @return string
	 */
	public static function get_withdrawal_email_verified_html( $withdrawal ) {
		return ( $withdrawal->has_verified_email() ? '<span class="eu-owb-woocommerce-verified-status is-verified dashicons dashicons-yes-alt tips" data-tip="' . esc_attr( _x( 'E-mail address matches', 'owb', 'woocommerce-germanized' ) ) . '"></span>' : '<span class="eu-owb-woocommerce-verified-status is-unverified dashicons dashicons-warning tips" data-tip="' . esc_attr( _x( 'E-mail address unknown', 'owb', 'woocommerce-germanized' ) ) . '"></span>' );
	}

	public static function register_order_bulk_actions( $actions ) {
		$actions['confirm_withdrawal_requests'] = _x( 'Confirm withdrawal requests', 'owb', 'woocommerce-germanized' );
		$actions['reject_withdrawal_requests']  = _x( 'Reject withdrawal requests', 'owb', 'woocommerce-germanized' );
		$actions['delete_withdrawal_requests']  = _x( 'Delete withdrawal requests', 'owb', 'woocommerce-germanized' );

		return $actions;
	}

	public static function handle_order_bulk_actions( $redirect_to, $action, $ids ) {
		$ids           = apply_filters( 'woocommerce_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'order' );
		$changed       = 0;
		$report_action = '';

		if ( in_array( $action, array( 'confirm_withdrawal_requests', 'reject_withdrawal_requests', 'delete_withdrawal_requests' ), true ) ) {
			foreach ( $ids as $id ) {
				$order         = wc_get_order( $id );
				$report_action = $action;

				if ( $order && eu_owb_order_has_withdrawal_request( $order ) ) {
					if ( 'confirm_withdrawal_requests' === $action ) {
						$result = eu_owb_order_confirm_withdrawal_request( $order );
					} elseif ( 'reject_withdrawal_requests' === $action ) {
						$result = eu_owb_order_reject_withdrawal_request( $order );
					} elseif ( 'delete_withdrawal_requests' === $action ) {
						$result = eu_owb_order_delete_withdrawal_request( $order, false, false );
					}

					if ( $result ) {
						++$changed;
					}
				}
			}
		}

		if ( $changed ) {
			$redirect_query_args = array(
				'post_type'   => 'shop_order',
				'bulk_action' => $report_action,
				'changed'     => $changed,
				'ids'         => join( ',', $ids ),
				'status'      => 'wc-withdrawn',
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

	/**
	 * @param array $actions
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
	public static function admin_order_actions( $actions, $order ) {
		if ( $request = eu_owb_get_withdrawal_request( $order ) ) {
			$actions = array();

			$actions['confirm_withdrawal_request'] = array(
				'url'    => self::get_edit_withdrawal_url( $request->get_id() ),
				'name'   => _x( 'Confirm withdrawal request', 'owb', 'woocommerce-germanized' ),
				'action' => 'complete',
			);

			$actions['reject_withdrawal_request'] = array(
				'url'    => self::get_edit_withdrawal_url( $request->get_id(), 'reject' ),
				'name'   => _x( 'Reject withdrawal request', 'owb', 'woocommerce-germanized' ),
				'action' => 'reject',
			);

			$actions['delete_withdrawal_request'] = array(
				'url'    => self::get_edit_withdrawal_url( $request->get_id(), 'delete' ),
				'name'   => _x( 'Delete withdrawal request', 'owb', 'woocommerce-germanized' ),
				'action' => 'delete',
			);
		}

		return $actions;
	}

	public static function get_edit_withdrawal_url( $order_id, $type = 'confirm', $args = array() ) {
		return esc_url_raw( wp_nonce_url( add_query_arg( $args, admin_url( "admin-ajax.php?action=eu_owb_woocommerce_{$type}_withdrawal&order_id={$order_id}" ) ), "eu_owb_woocommerce_{$type}_withdrawal" ) );
	}

	public static function order_withdrawal_request_details( $order ) {
		if ( ! eu_owb_order_has_withdrawal_request( $order ) ) {
			return;
		}

		$request                    = eu_owb_get_withdrawal_request( $order );
		$confirmation_needs_confirm = apply_filters( 'eu_owb_woocommerce_withdrawal_confirmation_needs_confirm', true ) ? 'eu-owb-woocommerce-needs-confirmation' : '';
		$rejection_needs_confirm    = apply_filters( 'eu_owb_woocommerce_withdrawal_rejection_needs_confirm', true ) ? 'eu-owb-woocommerce-needs-confirmation' : '';
		?>
		<div class="eu-owb-order-withdrawal-request">
			<h3><?php echo ( $request->is_partial() ) ? esc_html_x( 'Partial withdrawal request', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'Full withdrawal request', 'owb', 'woocommerce-germanized' ); ?></h3>

			<p><?php echo wp_kses_post( sprintf( _x( 'Received on %1$s @ %2$s by <a href="mailto:%3$s">%4$s</a> %5$s', 'owb', 'woocommerce-germanized' ), wc_format_datetime( $request->get_date_received() ), wc_format_datetime( $request->get_date_received(), get_option( 'time_format' ) ), $request->get_email(), $request->get_formatted_full_name( $request->get_email() ), self::get_withdrawal_email_verified_html( $request ) ) ); ?></p>

			<div class="eu-owb-order-withdrawal-request-buttons">
				<a href="<?php echo esc_url( self::get_edit_withdrawal_url( $request->get_id() ) ); ?>" class="eu-owb-confirm-withdrawal-request button button-primary tips <?php echo esc_attr( $confirmation_needs_confirm ); ?>" data-confirm="<?php echo esc_attr_x( 'Are you sure to confirm the withdrawal request?', 'owb', 'woocommerce-germanized' ); ?>" data-tip="<?php echo esc_attr_x( 'Confirms the withdrawal request to the customer.', 'owb', 'woocommerce-germanized' ); ?>"><?php echo esc_html_x( 'Confirm withdrawal request', 'owb', 'woocommerce-germanized' ); ?></a>
				<a href="#" class="eu-owb-reject-withdrawal-request-start tips" data-tip="<?php echo esc_attr_x( 'Reject the withdrawal request by providing a reason for the rejection.', 'owb', 'woocommerce-germanized' ); ?>"><?php echo esc_html_x( 'Reject request', 'owb', 'woocommerce-germanized' ); ?></a>
			</div>
			<div class="eu-owb-reject-withdrawal-request-form hidden">
				<p class="form-field form-field-wide">
					<label for="eu_owb_reject_reason"><?php echo esc_html_x( 'Reason', 'owb', 'woocommerce-germanized' ); ?>:</label>
					<textarea rows="5" cols="40" name="eu_owb_reject_reason" tabindex="6" id="eu_owb_reject_reason" placeholder="<?php echo esc_attr_x( 'Describe why you\'ve rejected the withdrawal request.', 'owb', 'woocommerce-germanized' ); ?>"></textarea>
				</p>

				<input type="hidden" name="eu_owb_reject_order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" />
				<?php wp_nonce_field( 'eu_owb_reject_withdrawal_request', 'eu_owb_reject_withdrawal_request_nonce' ); ?>

				<p>
					<button type="submit" class="button button-primary <?php echo esc_attr( $rejection_needs_confirm ); ?>" data-confirm="<?php echo esc_attr_x( 'Are you sure to reject the withdrawal request?', 'owb', 'woocommerce-germanized' ); ?>" id="eu-owb-reject-withdrawal-request-submit" name="reject_withdrawal_request" value="<?php echo esc_attr_x( 'Reject withdrawal request', 'owb', 'woocommerce-germanized' ); ?>"><?php echo esc_html_x( 'Reject withdrawal request', 'owb', 'woocommerce-germanized' ); ?></button>
				</p>
			</div>
		</div>
		<?php
	}

	public static function register_settings( $settings, $section_id ) {
		if ( ! Package::is_integration() && 'owb' === $section_id ) {
			$settings = Settings::get_settings();
		}

		return $settings;
	}

	public static function register_sections( $sections ) {
		if ( ! Package::is_integration() ) {
			$sections['owb'] = _x( 'Withdrawals', 'owb-setting-section', 'woocommerce-germanized' );
		}

		return $sections;
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
		$screen_ids   = array_merge( $screen_ids, self::get_core_screen_ids() );

		return array_filter( $screen_ids );
	}

	public static function get_screen_ids() {
		$other_screen_ids = array();

		return array_merge( self::get_order_screen_ids(), $other_screen_ids );
	}

	public static function on_init() {
		add_filter( 'handle_bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'handle_order_bulk_actions' ), 10, 3 );
		add_filter( 'bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'register_order_bulk_actions' ), 10, 1 );

		add_filter( 'views_' . self::get_order_screen_id(), array( __CLASS__, 'register_withdrawal_view' ) );
		add_filter( 'views_edit-' . self::get_order_screen_id(), array( __CLASS__, 'register_withdrawal_view' ) );
	}

	public static function register_withdrawal_view( $views ) {
		$base_url             = Package::get_withdrawals_url();
		$requested            = self::get_withdrawal_count( 'requested' );
		$views['withdrawals'] = '<a class="" href="' . esc_url( $base_url ) . '">' . _x( 'Withdrawals', 'owb', 'woocommerce-germanized' ) . ' <span class="count">(' . esc_html( $requested ) . ')</span></a>';

		return $views;
	}
}