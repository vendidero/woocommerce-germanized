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
		}

		update_option( 'eu_owb_woocommerce_version', Package::get_version() );
		update_option( 'eu_owb_woocommerce_db_version', Package::get_version() );
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
				if ( isset( $setting['default'] ) && isset( $setting['id'] ) ) {
					wp_cache_delete( $setting['id'], 'options' );

					$autoload = isset( $setting['autoload'] ) ? (bool) $setting['autoload'] : true;
					add_option( $setting['id'], $setting['default'], '', ( $autoload ? 'yes' : 'no' ) );
				}
			}
		}
	}
}
