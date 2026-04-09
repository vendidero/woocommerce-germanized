<?php

namespace Vendidero\OrderWithdrawalButton;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {
	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '2.0.1';

	protected static $localized_scripts = array();

	/**
	 * Init the package
	 */
	public static function init() {
		if ( ! self::has_dependencies() ) {
			return;
		}

		self::init_hooks();
		self::includes();

		do_action( 'eu_owb_woocommerce_init' );
	}

	protected static function init_hooks() {
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_feature_compatibility' ) );

		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'init', array( __CLASS__, 'check_version' ), 10 );
		add_action( 'init', array( __CLASS__, 'register_plugin_links' ) );
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'filter_templates' ), 50, 3 );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'register_order_statuses' ) );
		add_action( 'init', array( __CLASS__, 'register_post_statuses' ) );
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'register_emails' ), 20 );
		add_action( 'init', array( __CLASS__, 'email_hooks' ), 10 );
		add_filter( 'woocommerce_email_styles', array( __CLASS__, 'email_styles' ), 20 );
		add_filter( 'woocommerce_template_directory', array( __CLASS__, 'set_woocommerce_template_dir' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( __CLASS__, 'order_withdrawal_request_details' ), 100, 1 );
		add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'admin_order_actions' ), 1500, 2 );
		add_filter( 'woocommerce_get_sections_advanced', array( __CLASS__, 'register_sections' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'register_settings' ), 10, 2 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'register_hidden_itemmeta' ), 10, 2 );
		add_action( 'woocommerce_after_order_itemmeta', array( __CLASS__, 'display_custom_itemmeta' ), 10, 3 );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'process_withdrawal_rejection' ), 45 );
		add_filter( 'woocommerce_menu_order_count', array( __CLASS__, 'menu_order_count' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'maybe_link_refund' ), 10, 2 );
		add_action( 'woocommerce_refund_deleted', array( __CLASS__, 'maybe_remove_refund' ), 10, 2 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_order_meta_box' ), 35 );

		add_action( 'init', array( __CLASS__, 'maybe_embed' ) );
	}

	public static function localize_printed_scripts() {
		$handles = array(
			'eu-owb-woocommerce',
		);

		$data = array(
			'eu-owb-woocommerce' => array(
				'name' => 'eu_owb_woocommerce_order_withdrawal_params',
				'data' => array(
					'wc_ajax_url' => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				),
			),
		);

		foreach ( $handles as $handle ) {
			if ( ! array_key_exists( $handle, $data ) ) {
				continue;
			}

			$params = $data[ $handle ];

			if ( ! in_array( $handle, self::$localized_scripts, true ) && wp_script_is( $handle ) ) {
				self::$localized_scripts[] = $handle;

				wp_localize_script( $handle, $params['name'], $params['data'] );
			}
		}
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
						$withdrawals = eu_owb_get_order_withdrawals( $order );

						if ( ! empty( $withdrawals ) ) : ?>
							<ul class="withdrawals">
							<?php
							foreach ( $withdrawals as $withdrawal ) :
								$delete_url = add_query_arg(
									array(
										'withdrawal_id' => $withdrawal['id'],
										'order_id'      => $order->get_id(),
									),
									wp_nonce_url( admin_url( 'admin-post.php?action=eu_owb_woocommerce_delete_withdrawal' ), 'eu_owb_woocommerce_delete_withdrawal' )
								);

								$item_list = array();
								foreach ( eu_owb_get_withdrawal_order_items( $order, $withdrawal ) as $item_data ) {
									$item_list[] = sprintf( _x( '%1$s x %2$s', 'item-quantity', 'woocommerce-germanized' ), wp_kses_post( $item_data['item']->get_name() ), esc_html( eu_owb_get_stock_amount( $item_data['quantity'] ) ) );
								}
								?>
								<li class="withdrawal withdrawal-<?php echo esc_attr( $withdrawal['status'] ); ?>">
									<div class="withdrawal-content">
										<p><?php echo wp_kses_post( sprintf( _x( '%1$s received on %2$s @ %3$s by <a href="mailto:%4$s">%5$s</a> %6$s', 'owb', 'woocommerce-germanized' ), ( 'yes' === $withdrawal['is_partial'] ) ? esc_html_x( 'Partial withdrawal', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'Full withdrawal', 'owb', 'woocommerce-germanized' ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $order, $withdrawal ) ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $order, $withdrawal ), get_option( 'time_format' ) ), eu_owb_get_order_withdrawal_email( $order, $withdrawal ), eu_owb_get_order_withdrawal_full_name( $order, $withdrawal, eu_owb_get_order_withdrawal_email( $order, $withdrawal ) ), self::get_withdrawal_email_verified_html( $order, $withdrawal ) ) ); ?></p>
										<p class="withdrawal-items"><?php echo implode( ', ', $item_list ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

										<?php if ( 'rejected' === $withdrawal['status'] && ! empty( $withdrawal['rejection_reason'] ) ) : ?>
											<?php echo wp_kses_post( wpautop( make_clickable( $withdrawal['rejection_reason'] ) ) ); ?>
										<?php endif; ?>
									</div>

									<p class="meta">
										<?php if ( 'confirmed' === $withdrawal['status'] && ! empty( $withdrawal['date_confirmed'] ) ) : ?>
											<abbr class="confirmed-date" title=""><?php echo esc_html( sprintf( _x( 'Confirmed %1$s at %2$s', 'owb', 'woocommerce-germanized' ), wc_format_datetime( eu_owb_get_order_withdrawal_date_confirmed( $order, $withdrawal ) ), wc_format_datetime( eu_owb_get_order_withdrawal_date_confirmed( $order, $withdrawal ), get_option( 'time_format' ) ) ) ); ?></abbr>
										<?php elseif ( 'rejected' === $withdrawal['status'] ) : ?>
											<abbr class="rejected-date" title=""><?php echo esc_html( sprintf( _x( 'Rejected %1$s at %2$s', 'owb', 'woocommerce-germanized' ), wc_format_datetime( eu_owb_get_order_withdrawal_date_rejected( $order, $withdrawal ) ), wc_format_datetime( eu_owb_get_order_withdrawal_date_rejected( $order, $withdrawal ), get_option( 'time_format' ) ) ) ); ?></abbr>
										<?php endif; ?>

										<a href="<?php echo esc_url( $delete_url ); ?>" class="delete_withdrawal eu-owb-woocommerce-needs-confirmation" data-confirm="<?php echo esc_attr_x( 'Do you really want to delete the withdrawal? This action cannot be undone.', 'owb', 'woocommerce-germanized' ); ?>" role="button"><?php echo esc_html_x( 'Delete withdrawal', 'owb', 'woocommerce-germanized' ); ?></a>
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

	public static function maybe_remove_refund( $refund_id, $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( eu_owb_order_has_confirmed_withdrawals( $order ) ) {
				foreach ( $order->get_items() as $item ) {
					if ( $item->meta_exists( '_withdrawn_quantity_refunds' ) ) {
						$refund_ids = array_filter( (array) $item->get_meta( '_withdrawn_quantity_refunds', true ) );

						if ( array_key_exists( $refund_id, $refund_ids ) ) {
							$refunded_qty  = eu_owb_get_stock_amount( $refund_ids[ $refund_id ] );
							$withdrawn_qty = eu_owb_get_stock_amount( $item->meta_exists( '_withdrawn_quantity', true ) ? $item->get_meta( '_withdrawn_quantity', true ) : 0 );
							$new_qty       = $withdrawn_qty + $refunded_qty;

							unset( $refund_ids[ $refund_id ] );

							if ( empty( $refund_ids ) ) {
								$item->delete_meta_data( '_withdrawn_quantity_refunds' );
							} else {
								$item->update_meta_data( '_withdrawn_quantity_refunds', $refund_ids );
							}

							if ( $new_qty <= 0 ) {
								$item->delete_meta_data( '_withdrawn_quantity' );
							} else {
								$item->update_meta_data( '_withdrawn_quantity', eu_owb_get_stock_amount( $new_qty ) );
							}

							$item->save();
						}
					}
				}
			}
		}
	}

	public static function maybe_link_refund( $order_id, $refund_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( eu_owb_order_has_confirmed_withdrawals( $order ) ) {
				if ( $refund = wc_get_order( $refund_id ) ) {
					foreach ( $refund->get_items() as $item ) {
						$original_item_id = absint( $item->get_meta( '_refunded_item_id' ) );
						$refunded_qty     = $item->get_quantity() * - 1;

						if ( $original_item = $order->get_item( $original_item_id ) ) {
							$withdrawn_qty = eu_owb_get_stock_amount( $original_item->meta_exists( '_withdrawn_quantity' ) ? $original_item->get_meta( '_withdrawn_quantity' ) : 0 );

							if ( $withdrawn_qty > 0 ) {
								$refunded_qty             = min( $refunded_qty, $withdrawn_qty );
								$withdrawn_qty            = $withdrawn_qty - $refunded_qty;
								$withdrawn_qty            = max( $withdrawn_qty, 0 );
								$refund_ids               = array_filter( (array) $original_item->get_meta( '_withdrawn_quantity_refunds' ) );
								$refund_ids[ $refund_id ] = $refunded_qty;

								if ( $withdrawn_qty <= 0 ) {
									$original_item->delete_meta_data( '_withdrawn_quantity' );
								} else {
									$original_item->update_meta_data( '_withdrawn_quantity', eu_owb_get_stock_amount( $withdrawn_qty ) );
								}

								$original_item->update_meta_data( '_withdrawn_quantity_refunds', $refund_ids );
								$original_item->save();
							}
						}
					}
				}
			}
		}
	}

	public static function maybe_embed() {
		if ( 'yes' === self::get_setting( 'enable_embed', 'yes' ) && eu_owb_has_public_withdrawal_page() ) {
			$footer_hook = self::get_theme_footer_hook();

			add_action( $footer_hook['hook'], array( __CLASS__, 'print_button' ), (int) $footer_hook['priority'] );
		}
	}

	public static function is_shop_request() {
		return apply_filters( 'eu_owb_woocommerce_is_shop_request', ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) );
	}

	public static function print_button() {
		if ( self::is_shop_request() ) {
			wc_get_template( 'global/order-withdrawal-button.php' );
		}
	}

	public static function register_plugin_links() {
		if ( self::is_standalone() && ! self::is_integration() ) {
			add_filter( 'plugin_action_links_' . plugin_basename( trailingslashit( self::get_path() ) . 'eu-order-withdrawal-button-for-woocommerce.php' ), array( __CLASS__, 'plugin_action_links' ) );
		}
	}

	public static function plugin_action_links( $links ) {
		return array_merge(
			array(
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=owb' ) ) . '">' . _x( 'Settings', 'owb', 'woocommerce-germanized' ) . '</a>',
			),
			$links
		);
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

	protected static function get_theme_footer_hook() {
		$custom_hooks = array(
			'astra'      => array(
				'hook'     => 'astra_footer',
				'priority' => 50,
			),
			'storefront' => array(
				'hook'     => 'storefront_footer',
				'priority' => 20,
			),
		);

		$theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : '';
		$hook  = array(
			'hook'     => 'wp_footer',
			'priority' => 5,
		);

		if ( array_key_exists( $theme->get_template(), $custom_hooks ) ) {
			$hook = wp_parse_args(
				$custom_hooks[ $theme->get_template() ],
				array(
					'hook'     => '',
					'priority' => 10,
				)
			);
		}

		return $hook;
	}

	public static function display_custom_itemmeta( $item_id, $item, $product ) {
		if ( $item->meta_exists( '_withdrawn_quantity' ) ) {
			$quantity_withdrawn = eu_owb_get_stock_amount( $item->get_meta( '_withdrawn_quantity', true ) );
			?>
			<mark class="withdrawal-quantity"><?php echo wp_kses_post( sprintf( _x( 'Withdrawn %1$sx', 'owb', 'woocommerce-germanized' ), $quantity_withdrawn ) ); ?></mark>
			<?php
		}

		if ( $item->meta_exists( '_withdrawal_request_quantity' ) ) {
			$quantity_withdrawn = eu_owb_get_stock_amount( $item->get_meta( '_withdrawal_request_quantity', true ) );
			?>
			<mark class="withdrawal-quantity withdrawal-requested"><?php echo wp_kses_post( sprintf( _x( 'Requested %1$sx', 'owb', 'woocommerce-germanized' ), $quantity_withdrawn ) ); ?></mark>
			<?php
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

	public static function admin_init() {
		add_filter( 'handle_bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'handle_order_bulk_actions' ), 10, 3 );
		add_filter( 'bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'register_order_bulk_actions' ), 10, 1 );

		add_filter( 'views_' . self::get_order_screen_id(), array( __CLASS__, 'register_unverified_withdrawal_view' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'legacy_unverified_withdrawal_requests_filter' ) );
		add_filter( 'woocommerce_order_query_args', array( __CLASS__, 'unverified_withdrawal_requests_filter' ), 10, 1 );

		// Order withdrawal request status
		add_filter( 'manage_' . ( 'shop_order' === self::get_order_screen_id() ? 'shop_order_posts' : self::get_order_screen_id() ) . '_columns', array( __CLASS__, 'register_withdrawal_request_column' ), 20 );
		add_action( 'manage_' . ( 'shop_order' === self::get_order_screen_id() ? 'shop_order_posts' : self::get_order_screen_id() ) . '_custom_column', array( __CLASS__, 'render_withdrawal_request_column' ), 20, 2 );
	}

	protected static function is_unverified_withdrawals_request() {
		$is_unverified = isset( $_GET['unverified_withdrawals'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $is_unverified;
	}

	protected static function is_withdrawal_status_request() {
		return self::is_unverified_withdrawals_request() || ( ! empty( $_GET['status'] ) && in_array( wc_clean( wp_unslash( $_GET['status'] ) ), array( 'wc-pending-wdraw' ), true ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public static function render_withdrawal_request_column( $column, $post_id ) {
		if ( 'withdrawal_request' === $column ) {
			if ( is_a( $post_id, 'WC_Order' ) ) {
				$the_order = $post_id;
			} else {
				global $the_order;

				if ( ! $the_order || $the_order->get_id() !== $post_id ) {
					$the_order = wc_get_order( $post_id );
				}
			}

			if ( is_a( $the_order, 'WC_Order' ) && ( $request = eu_owb_get_withdrawal_request( $the_order ) ) ) {
				echo '<mark data-tip="' . esc_attr( sprintf( _x( 'Received on %1$s at %2$s', 'owb', 'woocommerce-germanized' ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $the_order, $request ) ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $the_order, $request ), get_option( 'time_format' ) ) ) ) . '" class="order-status withdrawal-request-status withdrawal-request-' . esc_attr( 'no' === $request['is_partial'] ? 'full' : 'partial' ) . ' withdrawal-request-status-' . esc_attr( eu_owb_order_withdrawal_email_is_verified( $the_order, $request ) ? 'verified' : 'unverified' ) . ' tips"><span>' . ( ( 'yes' === $request['is_partial'] ) ? esc_html_x( 'Partial withdrawal request', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'Full withdrawal request', 'owb', 'woocommerce-germanized' ) ) . '</span></mark>';
				echo '<span class="description">' . wp_kses_post( sprintf( _x( 'By <a href="mailto:%1$s">%2$s</a> %3$s', 'owb', 'woocommerce-germanized' ), eu_owb_get_order_withdrawal_email( $the_order, $request ), eu_owb_get_order_withdrawal_full_name( $the_order, $request, eu_owb_get_order_withdrawal_email( $the_order, $request ) ), self::get_withdrawal_email_verified_html( $the_order, $request ) ) ) . '</span>';
			}
		}
	}

	protected static function get_withdrawal_email_verified_html( $order, $withdrawal ) {
		return ( eu_owb_order_withdrawal_email_is_verified( $order, $withdrawal ) ? '<span class="dashicons dashicons-yes-alt tips" data-tip="' . esc_attr( _x( 'E-mail address matches', 'owb', 'woocommerce-germanized' ) ) . '"></span>' : '<span class="dashicons dashicons-warning tips" data-tip="' . esc_attr( _x( 'E-mail address unknown', 'owb', 'woocommerce-germanized' ) ) . '"></span>' );
	}

	public static function register_withdrawal_request_column( $columns ) {
		if ( self::is_withdrawal_status_request() ) {
			$new_columns  = array();
			$added_column = false;

			foreach ( $columns as $column_name => $title ) {
				if ( ! $added_column && ( 'order_date' === $column_name || 'wc_actions' === $column_name ) ) {
					$new_columns['withdrawal_request'] = _x( 'Withdrawal request', 'owb', 'woocommerce-germanized' );
					$added_column                      = true;
				}

				$new_columns[ $column_name ] = $title;
			}

			if ( ! $added_column ) {
				$new_columns['withdrawal_request'] = _x( 'Withdrawal request', 'owb', 'woocommerce-germanized' );
			}

			return $new_columns;
		}

		return $columns;
	}

	public static function register_unverified_withdrawal_view( $views ) {
		if ( 'yes' !== self::get_setting( 'separately_store_unverified_withdrawal_requests', 'yes' ) ) {
			return $views;
		}

		$class_name                      = self::is_unverified_withdrawals_request() ? 'current' : '';
		$base_url                        = get_admin_url( null, 'admin.php?page=wc-orders&unverified_withdrawals=yes' );
		$views['unverified_withdrawals'] = '<a class="' . esc_attr( $class_name ) . '" href="' . esc_url( $base_url ) . '">' . _x( 'Unverified withdrawals', 'owb', 'woocommerce-germanized' ) . '</a>';

		return $views;
	}

	public static function unverified_withdrawal_requests_filter( $args ) {
		if ( self::is_unverified_withdrawals_request() && ! empty( $_GET['unverified_withdrawals'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $args['meta_query'] ) ) {
				$args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}
			$args['meta_query'][] = array(
				'key'     => '_withdrawal_request_is_verified',
				'value'   => 'no',
				'compare' => '=',
			);
		}

		return $args;
	}

	/**
	 * Filter the legacy orders list query.
	 *
	 * @param \WP_Query $query The WP_Query object.
	 */
	public static function legacy_unverified_withdrawal_requests_filter( $query ) {
		if (
			is_admin()
			&& $query->is_main_query()
			&& $query->get( 'post_type' ) === 'shop_order'
			&& self::is_unverified_withdrawals_request() && ! empty( $_GET['unverified_withdrawals'] ) // phpcs:ignore WordPress.Security.NonceVerification
		) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'     => '_withdrawal_request_is_verified',
						'value'   => 'no',
						'compare' => '=',
					),
				)
			);
		}

		return $query;
	}

	public static function register_settings( $settings, $section_id ) {
		if ( ! self::is_integration() && 'owb' === $section_id ) {
			$settings = Settings::get_settings();
		}

		return $settings;
	}

	public static function register_sections( $sections ) {
		if ( ! self::is_integration() ) {
			$sections['owb'] = _x( 'Withdrawals', 'owb-setting-section', 'woocommerce-germanized' );
		}

		return $sections;
	}

	public static function set_woocommerce_template_dir( $dir, $template ) {
		if ( file_exists( self::get_path() . '/templates/' . $template ) ) {
			return untrailingslashit( self::get_template_path() );
		}

		return $dir;
	}

	public static function has_email_improvements_enabled() {
		$is_enabled = false;

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			$is_enabled = \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'email_improvements' );
		}

		return $is_enabled;
	}

	public static function register_emails( $emails ) {
		$emails['EU_OWB_Email_Customer_Withdrawal_Request_Received']  = include self::get_path() . '/includes/emails/class-eu-owb-email-customer-withdrawal-request-received.php';
		$emails['EU_OWB_Email_Customer_Withdrawal_Request_Confirmed'] = include self::get_path() . '/includes/emails/class-eu-owb-email-customer-withdrawal-request-confirmed.php';
		$emails['EU_OWB_Email_Customer_Withdrawal_Request_Rejected']  = include self::get_path() . '/includes/emails/class-eu-owb-email-customer-withdrawal-request-rejected.php';
		$emails['EU_OWB_Email_New_Withdrawal_Request']                = include self::get_path() . '/includes/emails/class-eu-owb-email-new-withdrawal-request.php';
		$emails['EU_OWB_Email_Deleted_Withdrawal_Request']            = include self::get_path() . '/includes/emails/class-eu-owb-email-deleted-withdrawal-request.php';

		return $emails;
	}

	public static function email_hooks() {
		add_action( 'eu_owb_woocommerce_withdrawal_request_details', array( __CLASS__, 'withdrawal_email_edit_link' ), 10, 4 );
		add_action( 'eu_owb_woocommerce_withdrawal_request_details', array( __CLASS__, 'withdrawal_email_details' ), 20, 5 );
	}

	public static function email_styles( $styles ) {
		return $styles . '
			#body_content .email-withdrawal-details tbody tr:last-child td {
                border-bottom: 0;
                padding-bottom: 0;
            }
		';
	}

	public static function withdrawal_email_edit_link( $order, $sent_to_admin, $plain_text, $email ) {
		if ( is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Received' ) ) {
			$edit_withdrawal_link = eu_owb_get_edit_withdrawal_url( $order );

			if ( ! $request = eu_owb_get_withdrawal_request( $order ) ) {
				return;
			}

			$has_multiple_orders        = isset( $request['meta']['has_multiple_matching_orders'] ) ? wc_string_to_bool( $request['meta']['has_multiple_matching_orders'] ) : false;
			$embed_edit_withdrawal_link = apply_filters( 'eu_owb_woocommerce_email_embed_partial_withdrawal_link', ( ( ( 'no' === $request['is_partial'] && eu_owb_order_supports_partial_withdrawal( $order ) ) || $has_multiple_orders ) && ! empty( $edit_withdrawal_link ) && eu_owb_order_is_guest_withdrawal_request( $order ) ), $request, $order );

			if ( $embed_edit_withdrawal_link && eu_owb_order_withdrawal_email_is_verified( $order, $request ) ) {
				if ( $plain_text ) {
					wc_get_template(
						'emails/plain/email-withdrawal-edit-link.php',
						array(
							'order'                => $order,
							'sent_to_admin'        => $sent_to_admin,
							'plain_text'           => $plain_text,
							'withdrawal'           => $request,
							'email'                => $email,
							'edit_withdrawal_link' => $edit_withdrawal_link,
						)
					);
				} else {
					wc_get_template(
						'emails/email-withdrawal-edit-link.php',
						array(
							'order'                => $order,
							'sent_to_admin'        => $sent_to_admin,
							'plain_text'           => $plain_text,
							'withdrawal'           => $request,
							'email'                => $email,
							'edit_withdrawal_link' => $edit_withdrawal_link,
						)
					);
				}
			}
		}
	}

	public static function withdrawal_email_details( $order, $sent_to_admin, $plain_text, $email, $withdrawal ) {
		if ( $plain_text ) {
			wc_get_template(
				'emails/plain/email-withdrawal-details.php',
				array(
					'order'                 => $order,
					'sent_to_admin'         => $sent_to_admin,
					'plain_text'            => $plain_text,
					'email'                 => $email,
					'withdrawal'            => $withdrawal,
					'show_deleted_original' => is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Received' ) ? true : false,
					'hide_items'            => is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Confirmed' ) ? false : ! eu_owb_order_withdrawal_email_is_verified( $order, $withdrawal ),
				)
			);
		} else {
			wc_get_template(
				'emails/email-withdrawal-details.php',
				array(
					'order'                 => $order,
					'sent_to_admin'         => $sent_to_admin,
					'plain_text'            => $plain_text,
					'email'                 => $email,
					'withdrawal'            => $withdrawal,
					'show_deleted_original' => is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Received' ) ? true : false,
					'hide_items'            => is_a( $email, 'EU_OWB_Email_Customer_Withdrawal_Request_Confirmed' ) ? false : ! eu_owb_order_withdrawal_email_is_verified( $order, $withdrawal ),
				)
			);
		}
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
						$result = eu_owb_order_delete_withdrawal_request( $order );
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

			if ( self::is_hpos_enabled() ) {
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

	public static function register_order_bulk_actions( $actions ) {
		$actions['confirm_withdrawal_requests'] = _x( 'Confirm withdrawal requests', 'owb', 'woocommerce-germanized' );

		if ( self::is_unverified_withdrawals_request() ) {
			$actions['reject_withdrawal_requests'] = _x( 'Reject withdrawal requests', 'owb', 'woocommerce-germanized' );
			$actions['delete_withdrawal_requests'] = _x( 'Delete withdrawal requests', 'owb', 'woocommerce-germanized' );
		}

		return $actions;
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
		$other_screen_ids = array();

		return array_merge( self::get_order_screen_ids(), $other_screen_ids );
	}

	public static function register_scripts() {
		self::register_script( 'eu-owb-woocommerce', 'static/order-withdrawal.js', array( 'jquery', 'woocommerce' ) );
		wp_register_style( 'eu-owb-woocommerce-form', self::get_assets_url( 'static/form-styles.css' ), array(), self::get_version() );

		if ( function_exists( 'wc_post_content_has_shortcode' ) ) {
			if ( wc_post_content_has_shortcode( 'eu_owb_order_withdrawal_request_form' ) || apply_filters( 'eu_owb_woocommerce_page_has_form', false ) ) {
				wp_enqueue_style( 'eu-owb-woocommerce-form' );
				wp_enqueue_script( 'eu-owb-woocommerce' );
			}
		}
	}

	public static function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		wp_register_script( 'eu-owb-woocommerce-admin-order', self::get_assets_url( 'static/admin-order.js' ), array( 'jquery', 'woocommerce_admin' ), self::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		// Register admin styles.
		wp_register_style( 'eu-owb-woocommerce-admin-styles', self::get_assets_url( 'static/admin-styles.css' ), array( 'woocommerce_admin_styles' ), self::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_order_screen_ids(), true ) ) {
			wp_enqueue_style( 'eu-owb-woocommerce-admin-styles' );
			wp_enqueue_script( 'eu-owb-woocommerce-admin-order' );
		} elseif ( 'woocommerce_page_wc-settings' === $screen_id ) {
			wp_enqueue_style( 'eu-owb-woocommerce-admin-styles' );
		}
	}

	/**
	 * @param array $actions
	 * @param \WC_Order $order
	 *
	 * @return array
	 */
	public static function admin_order_actions( $actions, $order ) {
		if ( eu_owb_order_has_withdrawal_request( $order ) ) {
			$actions = array();
			$request = eu_owb_get_withdrawal_request( $order );

			$actions['confirm_withdrawal_request'] = array(
				'url'    => self::get_edit_withdrawal_url( $order->get_id() ),
				'name'   => _x( 'Confirm withdrawal request', 'owb', 'woocommerce-germanized' ),
				'action' => 'complete',
			);

			$actions['reject_withdrawal_request'] = array(
				'url'    => self::get_edit_withdrawal_url( $order->get_id(), 'reject' ),
				'name'   => _x( 'Reject withdrawal request', 'owb', 'woocommerce-germanized' ),
				'action' => 'reject',
			);

			if ( eu_owb_withdrawal_request_needs_manual_verification( $request ) ) {
				$actions['delete_withdrawal_request'] = array(
					'url'    => self::get_edit_withdrawal_url( $order->get_id(), 'delete' ),
					'name'   => _x( 'Delete withdrawal request', 'owb', 'woocommerce-germanized' ),
					'action' => 'delete',
				);
			}
		}

		return $actions;
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
			<h3><?php echo ( 'yes' === $request['is_partial'] ) ? esc_html_x( 'Partial withdrawal request', 'owb', 'woocommerce-germanized' ) : esc_html_x( 'Full withdrawal request', 'owb', 'woocommerce-germanized' ); ?></h3>

			<p><?php echo wp_kses_post( sprintf( _x( 'Received on %1$s @ %2$s by <a href="mailto:%3$s">%4$s</a> %5$s', 'owb', 'woocommerce-germanized' ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $order, $request ) ), wc_format_datetime( eu_owb_get_order_withdrawal_date_received( $order, $request ), get_option( 'time_format' ) ), eu_owb_get_order_withdrawal_email( $order, $request ), eu_owb_get_order_withdrawal_full_name( $order, $request, eu_owb_get_order_withdrawal_email( $order, $request ) ), self::get_withdrawal_email_verified_html( $order, $request ) ) ); ?></p>

			<div class="eu-owb-order-withdrawal-request-buttons">
				<a href="<?php echo esc_url( self::get_edit_withdrawal_url( $order->get_id() ) ); ?>" class="eu-owb-confirm-withdrawal-request button button-primary tips <?php echo esc_attr( $confirmation_needs_confirm ); ?>" data-confirm="<?php echo esc_attr_x( 'Are you sure to confirm the withdrawal request?', 'owb', 'woocommerce-germanized' ); ?>" data-tip="<?php echo esc_attr_x( 'Confirms the withdrawal request to the customer.', 'owb', 'woocommerce-germanized' ); ?>"><?php echo esc_html_x( 'Confirm withdrawal request', 'owb', 'woocommerce-germanized' ); ?></a>
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

	public static function get_edit_withdrawal_url( $order_id, $type = 'confirm', $args = array() ) {
		return esc_url_raw( wp_nonce_url( add_query_arg( $args, admin_url( "admin-ajax.php?action=eu_owb_woocommerce_{$type}_withdrawal_request&order_id={$order_id}" ) ), "eu_owb_woocommerce_{$type}_withdrawal_request" ) );
	}

	public static function register_post_statuses() {
		register_post_status(
			'wc-pending-wdraw',
			array(
				'label'                     => _x( 'Pending withdrawal', 'owb', 'woocommerce-germanized' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _nx_noop( 'Pending withdrawal (%s)', 'Pending withdrawals (%s)', 'owb', 'woocommerce-germanized' ),
			)
		);

		register_post_status(
			'wc-withdrawn',
			array(
				'label'                     => _x( 'Withdrawn', 'owb', 'woocommerce-germanized' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _nx_noop( 'Withdrawn (%s)', 'Withdrawn (%s)', 'owb', 'woocommerce-germanized' ),
			)
		);
	}

	public static function register_order_statuses( $statuses ) {
		/**
		 * Need to shorten wc-pending-withdrawal as it is too long for the custom order table limitation.
		 */
		$statuses['wc-pending-wdraw'] = _x( 'Pending withdrawal', 'owb', 'woocommerce-germanized' );
		$statuses['wc-withdrawn']     = _x( 'Withdrawn', 'owb', 'woocommerce-germanized' );

		return $statuses;
	}

	public static function register_shortcodes() {
		add_shortcode( 'eu_owb_order_withdrawal_request_form', array( __CLASS__, 'order_withdrawal_request_form' ) );

		/**
		 * Mark the return page as a Woo page to make sure default form styles work.
		 */
		add_filter(
			'is_woocommerce',
			function ( $is_woocommerce ) {
				if ( wc_post_content_has_shortcode( 'eu_owb_order_withdrawal_request_form' ) ) {
					$is_woocommerce = true;
				}

				return $is_woocommerce;
			}
		);
	}

	public static function order_withdrawal_request_form( $args = array() ) {
		$order_key             = isset( $_GET['order_key'] ) ? wc_clean( wp_unslash( $_GET['order_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id              = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$manually_select_items = isset( $_GET['manually_select_items'] ) ? wc_string_to_bool( wc_clean( wp_unslash( $_GET['manually_select_items'] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order                 = null;

		if ( ! empty( $order_id ) && ( $the_order = wc_get_order( $order_id ) ) ) {
			if ( $order_id === $the_order->get_id() && ! empty( $the_order->get_order_key() ) && hash_equals( $the_order->get_order_key(), $order_key ) ) {
				$order = $the_order;
			} elseif ( is_user_logged_in() && current_user_can( 'view_order', $the_order->get_id() ) ) {
				$order = $the_order;
			}
		}

		$defaults = array(
			'order'                 => $order,
			'order_key'             => $order_key,
			'manually_select_items' => $manually_select_items,
		);

		$args    = wp_parse_args( $args, $defaults );
		$notices = function_exists( 'wc_print_notices' ) ? wc_print_notices( true ) : '';
		$html    = '';

		// Output notices in case notices have not been outputted yet.
		if ( ! empty( $notices ) ) {
			$html .= '<div class="woocommerce">' . $notices . '</div>';
		}

		$html .= wc_get_template_html( 'forms/order-withdrawal-request.php', $args );

		return $html;
	}

	public static function is_integration() {
		return apply_filters( 'eu_owb_woocommerce_is_integration', false );
	}

	public static function is_hpos_enabled() {
		if ( ! is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) ) {
			return false;
		}

		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	public static function deactivate() {
		Install::deactivate();
	}

	public static function install() {
		self::init();

		if ( ! self::has_dependencies() ) {
			return;
		}

		Install::install();
	}

	public static function install_integration() {
		self::install();
	}

	public static function is_standalone() {
		return defined( 'EU_OWB_WC_IS_STANDALONE_PLUGIN' ) && EU_OWB_WC_IS_STANDALONE_PLUGIN;
	}

	public static function check_version() {
		if ( self::is_standalone() && self::has_dependencies() && ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'eu_owb_woocommerce_version' ) !== self::get_version() ) ) {
			Install::install();

			do_action( 'eu_owb_woocommerce_updated' );
		}
	}

	public static function log( $message, $type = 'info', $source = '' ) {
		/**
		 * Filter that allows adjusting whether to enable or disable
		 * logging for the shipments package
		 *
		 * @param boolean $enable_logging True if logging should be enabled. False otherwise.
		 */
		if ( ! apply_filters( 'eu_owb_woocommerce_enable_logging', false ) ) {
			return;
		}

		$logger = wc_get_logger();

		if ( ! $logger ) {
			return;
		}

		if ( ! is_callable( array( $logger, $type ) ) ) {
			$type = 'info';
		}

		$logger->{$type}( $message, array( 'source' => 'eu-owb-woocommerce' . ( ! empty( $source ) ? '-' . $source : '' ) ) );
	}

	public static function has_dependencies() {
		return class_exists( 'WooCommerce' );
	}

	private static function includes() {
		Ajax::init();

		include_once self::get_path() . '/includes/eu-owb-core-functions.php';
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_template_path() {
		return apply_filters( 'eu_owb_woocommerce_template_path', 'woocommerce/' );
	}

	/**
	 * Filter WooCommerce Templates to look into /templates before looking within theme folder
	 *
	 * @param string $template
	 * @param string $template_name
	 * @param string $template_path
	 *
	 * @return string
	 */
	public static function filter_templates( $template, $template_name, $template_path ) {
		$default_template_path = apply_filters( 'eu_owb_woocommerce_default_template_path', self::get_path() . '/templates/' . $template_name, $template_name );

		if ( file_exists( $default_template_path ) ) {
			$template_path = self::get_template_path();

			// Check for Theme overrides
			$theme_template = locate_template(
				apply_filters(
					'eu_owb_woocommerce_locate_theme_template_locations',
					array(
						trailingslashit( $template_path ) . $template_name,
					),
					$template_name
				)
			);

			if ( 'forms/order-withdrawal-request.php' === $template_name ) {
				wp_enqueue_style( 'eu-owb-woocommerce-form' );
				wp_enqueue_script( 'eu-owb-woocommerce' );
			}

			if ( ! $theme_template ) {
				$template = $default_template_path;
			} else {
				$template = $theme_template;
			}
		}

		return $template;
	}

	public static function declare_feature_compatibility() {
		if ( ! self::is_standalone() ) {
			return;
		}

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', trailingslashit( self::get_path() ) . 'eu-order-withdrawal-button-for-woocommerce.php', true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', trailingslashit( self::get_path() ) . 'eu-order-withdrawal-button-for-woocommerce.php', true );
		}
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path( $rel_path = '' ) {
		return trailingslashit( dirname( __DIR__ ) ) . $rel_path;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url( $rel_path = '' ) {
		return trailingslashit( plugins_url( '', __DIR__ ) ) . $rel_path;
	}

	public static function register_script( $handle, $path, $dep = array(), $ver = '', $in_footer = array( 'strategy' => 'defer' ) ) {
		global $wp_version;

		if ( version_compare( $wp_version, '6.3', '<' ) ) {
			$in_footer = true;
		}

		$ver = empty( $ver ) ? self::get_version() : $ver;

		wp_register_script(
			$handle,
			self::get_assets_url( $path ),
			$dep,
			$ver,
			$in_footer
		);
	}

	public static function get_assets_url( $script_or_style ) {
		$assets_url = self::get_url( 'build' );
		$is_debug   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		$is_style   = '.css' === substr( $script_or_style, -4 );
		$is_static  = strstr( $script_or_style, 'static/' );

		if ( $is_debug && $is_static && ! $is_style ) {
			$assets_url = self::get_url( 'assets/js' );
		}

		return trailingslashit( $assets_url ) . $script_or_style;
	}

	public static function enable_partial_withdrawals() {
		return wc_string_to_bool( self::get_setting( 'enable_partial_withdrawals', 'yes' ) );
	}

	public static function get_setting( $name, $default_value = false ) {
		$option_name = "eu_owb_woocommerce_{$name}";

		return get_option( $option_name, $default_value );
	}
}
