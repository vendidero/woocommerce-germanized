<?php

namespace Vendidero\OrderWithdrawalButton;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
		$current_version = get_option( 'eu_owb_woocommerce_version', null );

		self::create_default_options();

		if ( ! $current_version ) {
			self::maybe_create_page();

			/**
			 * Flush the order count cache to prevent undefined index errors
			 * after introducing new order statuses.
			 */
			if ( class_exists( '\Automattic\WooCommerce\Caches\OrderCountCache' ) ) {
				$order_count_cache = new \Automattic\WooCommerce\Caches\OrderCountCache();
				$order_count_cache->flush();
			}
		}

		if ( ! is_null( $current_version ) && version_compare( $current_version, '2.1.0', '<' ) ) {
			self::migrate_withdrawals();
		}

		update_option( 'eu_owb_woocommerce_version', Package::get_version() );
		update_option( 'eu_owb_woocommerce_db_version', Package::get_version() );
	}

	public static function legacy_withdrawal_query( $date_created_after = 0 ) {
		$custom_query_cpt_cb = function ( $query, $query_vars ) {
			if ( ! empty( $query_vars['has_withdrawal'] ) ) {
				$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_withdrawal_request',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_withdrawals',
						'compare' => 'EXISTS',
					),
				);

				unset( $query_vars['has_withdrawal'] );
			}

			return $query;
		};

		$custom_query_hpos_cb = function ( $query_vars ) {
			if ( ! empty( $query_vars['has_withdrawal'] ) ) {
				$query_vars['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_withdrawal_request',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_withdrawals',
						'compare' => 'EXISTS',
					),
				);

				unset( $query_vars['has_withdrawal'] );
			}

			return $query_vars;
		};

		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', $custom_query_cpt_cb, 10, 2 );
		add_filter( 'woocommerce_orders_table_datastore_get_orders_query', $custom_query_hpos_cb, 10 );

		$query_args = array(
			'limit'          => 10,
			'has_withdrawal' => true,
			'type'           => 'shop_order',
			'order'          => 'ASC',
			'orderby'        => 'date_created',
		);

		if ( ! empty( $date_created_after ) ) {
			$query_args['date_created'] = '>=' . $date_created_after;
		}

		$orders = wc_get_orders( $query_args );

		return $orders;
	}

	public static function migrate_withdrawals() {
		$orders = self::legacy_withdrawal_query( 0 );

		if ( ! empty( $orders ) ) {
			if ( $queue = WC()->queue() ) {
				$queue->schedule_single(
					time() + 10,
					'eu_owb_migrate_withdrawals',
					array( 'date_created_after' => 0 ),
					'eu_order_withdrawal_button'
				);
			}
		}
	}

	public static function maybe_create_page() {
		if ( ! function_exists( 'wc_create_page' ) ) {
			include_once WC()->plugin_path() . '/includes/admin/wc-admin-functions.php';
		}

		$page = array(
			'key'     => 'withdraw_from_contract',
			'name'    => _x( 'withdraw-from-contract', 'owb-page-slug', 'woocommerce-germanized' ),
			'title'   => _x( 'Withdraw from contract', 'owb-page-title', 'woocommerce-germanized' ),
			'content' => '[eu_owb_order_withdrawal_request_form]',
			'status'  => 'draft',
		);

		$page_id = wc_create_page( esc_sql( $page['name'] ), "woocommerce_{$page['key']}_page_id", $page['title'], '', ( ! empty( $page['parent'] ) ? wc_get_page_id( $page['parent'] ) : 0 ), $page['status'] );

		if ( $page_id && ! empty( $page['content'] ) ) {
			self::update_page_content( $page_id, $page['content'] );
		}
	}

	protected static function update_page_content( $page_id, $content, $append = true ) {
		$page = get_post( $page_id );

		if ( $page ) {
			$is_shortcode    = preg_match( '/^\[[a-z]+(?:_[a-z]+)*]$/m', $content ) > 0;
			$current_content = $append ? $page->post_content . "\n" : '';
			$new_content     = $current_content . wp_kses_post( $content );

			if ( function_exists( 'has_blocks' ) && has_blocks( $page_id ) ) {
				if ( $is_shortcode ) {
					$new_content = $current_content . "<!-- wp:shortcode -->\n" . ' ' . esc_html( $content ) . ' ' . "\n  <!-- /wp:shortcode -->";
				} else {
					$new_content = $current_content . wp_kses_post( $content );
				}
			}

			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => apply_filters( 'eu_owb_woocommerce_update_page_content', $new_content, $page_id, $content, $page->post_content, $append, $is_shortcode ),
				)
			);
		}
	}

	public static function deactivate() {}

	protected static function create_default_options() {
		foreach ( Settings::get_sections() as $section ) {
			foreach ( Settings::get_settings( $section ) as $setting ) {
				$setting = wp_parse_args(
					$setting,
					array(
						'id'           => '',
						'default'      => null,
						'skip_install' => false,
						'autoload'     => true,
					)
				);

				if ( $setting['default'] && ! empty( $setting['id'] ) && ! $setting['skip_install'] ) {
					wp_cache_delete( $setting['id'], 'options' );

					$autoload = (bool) $setting['autoload'];
					add_option( $setting['id'], $setting['default'], '', ( $autoload ? 'yes' : 'no' ) );
				}
			}
		}
	}
}
