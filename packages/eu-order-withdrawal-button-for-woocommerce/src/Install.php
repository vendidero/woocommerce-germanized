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

	private static function generate_in_query_sql( $values ) {
		global $wpdb;

		$in_query = array();

		foreach ( $values as $value ) {
			$in_query[] = $wpdb->prepare( "'%s'", $value ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedSimplePlaceholder
		}

		return '(' . implode( ',', $in_query ) . ')';
	}

	private static function build_legacy_query( $date_created_after = 0 ) {
		global $wpdb;

		$sql = '';

		if ( Package::is_hpos_enabled() ) {
			$orders_table_name = \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_orders_table_name();
			$meta_table_name   = \Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_meta_table_name();
			$order_status_in   = self::generate_in_query_sql( array_keys( wc_get_order_statuses() ) );

			$joins = array(
				"INNER JOIN {$meta_table_name} AS mt0 ON {$orders_table_name}.id = mt0.order_id AND (mt0.meta_key = '_withdrawals' OR mt0.meta_key = '_withdrawal_request')",
			);

			$join_sql       = implode( ' ', $joins );
			$where_date_sql = '';

			if ( ! empty( $date_created_after ) ) {
				$datetime       = new \WC_DateTime( "@{$date_created_after}", new \DateTimeZone( 'UTC' ) );
				$where_date_sql = $wpdb->prepare( " AND ({$orders_table_name}.date_created_gmt >= %s)", $datetime->date( 'Y-m-d H:i:s' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}

			// @codingStandardsIgnoreStart
			$sql = $wpdb->prepare(
				"
			SELECT {$orders_table_name}.id as order_id FROM {$orders_table_name}  
			$join_sql
			WHERE 1=1 
				AND ( {$orders_table_name}.type = 'shop_order' ) AND ( {$orders_table_name}.status IN {$order_status_in} ) {$where_date_sql}
			GROUP BY {$orders_table_name}.id 
			ORDER BY {$orders_table_name}.date_created_gmt ASC 
			LIMIT %d, %d",
				0,
				10
			);
			// @codingStandardsIgnoreEnd
		} else {
			$joins = array(
				"INNER JOIN {$wpdb->postmeta} AS mt0 ON {$wpdb->posts}.ID = mt0.post_id AND (mt0.meta_key = '_withdrawals' OR mt0.meta_key = '_withdrawal_request')",
			);

			$join_sql       = implode( ' ', $joins );
			$post_status_in = self::generate_in_query_sql( array_keys( wc_get_order_statuses() ) );
			$where_date_sql = '';

			if ( ! empty( $date_created_after ) ) {
				$datetime       = new \WC_DateTime( "@{$date_created_after}", new \DateTimeZone( 'UTC' ) );
				$where_date_sql = $wpdb->prepare( " AND ({$wpdb->posts}.post_date_gmt >= %s)", $datetime->date( 'Y-m-d H:i:s' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}

			// @codingStandardsIgnoreStart
			$sql = $wpdb->prepare(
				"
			SELECT {$wpdb->posts}.ID as order_id FROM {$wpdb->posts}  
			$join_sql
			WHERE 1=1 
				AND ( {$wpdb->posts}.post_type = 'shop_order' ) AND ( {$wpdb->posts}.post_status IN {$post_status_in} ) {$where_date_sql}
			GROUP BY {$wpdb->posts}.ID 
			ORDER BY {$wpdb->posts}.post_date_gmt ASC 
			LIMIT %d, %d",
				0,
				10
			);
			// @codingStandardsIgnoreEnd
		}

		return $sql;
	}

	public static function legacy_withdrawal_query( $date_created_after = 0 ) {
		global $wpdb;

		$wpdb->hide_errors();

		$query = self::build_legacy_query( $date_created_after );

		$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$orders  = array();

		foreach ( $results as $result ) {
			if ( $order = wc_get_order( $result->order_id ) ) {
				$orders[] = $order;
			}
		}

		return $orders;
	}

	public static function migrate_withdrawals() {
		if ( $queue = WC()->queue() ) {
			$queue->schedule_single(
				time() + 60,
				'eu_owb_migrate_withdrawals',
				array( 'date_created_after' => 0 ),
				'eu_order_withdrawal_button'
			);
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
