<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Admin_Note_Blocks extends WC_GZD_Admin_Note {

	public function get_name() {
		return 'blocks';
	}

	public function is_disabled() {
		$is_disabled = parent::is_disabled();

		if ( ! $is_disabled && current_user_can( 'manage_woocommerce' ) && 'yes' === get_option( '_wc_gzd_maybe_needs_block_update' ) ) {
			$is_disabled = false;
		} else {
			$is_disabled = true;
		}

		return $is_disabled;
	}

	public function dismiss( $and_note = true ) {
		parent::dismiss( $and_note );

		delete_option( '_wc_gzd_maybe_needs_block_update' );
	}

	public function get_title() {
		return __( 'Checkout Block & Full-Site-Editing', 'woocommerce-germanized' );
	}

	public function get_content() {
		return sprintf( __( 'Starting with 3.14, Germanized officially supports the new WooCommerce blocks. The checkout block will be automatically adapted to the regulations of the button solution. Furthermore you may need to make sure that additional blocks supplied by Germanized are placed correctly, e.g. the checkboxes block. If your current theme supports full-site-editing you might as well check your single product template and use the price label blocks Germanized registers, to embed price labels such as the unit price, tax notice etc.', 'woocommerce-germanized' ), 'https://www.it-recht-kanzlei.de/preisangabenverordnung-2022-wichtige-aenderungen.html', 'https://www.haendlerbund.de/de/news/aktuelles/rechtliches/4145-omnibus-rezensionen-gekennzeichnet', wc_gzd_get_page_permalink( 'review_authenticity' ) );
	}

	public function get_actions() {
		$actions = array();

		if ( wc_gzd_has_checkout_block() ) {
			$actions[] = array(
				'url'        => 'https://vendidero.de/dokument/umsetzung-der-button-loesung-im-woocommerce-checkout#checkout-block',
				'title'      => __( 'Adjust Checkout Block', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => true,
			);
		}

		if ( wc_gzd_current_theme_is_fse_theme() ) {
			$actions[] = array(
				'url'        => 'https://vendidero.de/dokument/preisauszeichnungen-anpassen#full-site-editing-mit-gutenberg',
				'title'      => __( 'Adjust Single Product template', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => count( $actions ) > 0 ? false : true,
			);
		}

		return $actions;
	}
}
