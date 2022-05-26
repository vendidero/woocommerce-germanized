<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Admin_Note_Legal_News extends WC_GZD_Admin_Note {

	public function get_name() {
		return 'legal_news';
	}

	public function is_disabled() {
		$is_disabled = parent::is_disabled();

		if ( ! $is_disabled && 'yes' === get_option( '_wc_gzd_has_legal_news' ) && current_user_can( 'manage_woocommerce' ) ) {
			$is_disabled = false;
		} else {
			$is_disabled = true;
		}

		return $is_disabled;
	}

	public function dismiss( $and_note = true ) {
		parent::dismiss( $and_note );

		delete_option( '_wc_gzd_has_legal_news' );
	}

	public function get_title() {
		return __( 'Attention: New Regulations', 'woocommerce-germanized' );
	}

	public function get_content() {
		return sprintf( __( 'As of 2022-05-28, a new <a href="%1$s">price indication regulation</a> will apply. In addition, the <a href="%2$s">Omnibus-Directive</a> goes into effect. From now on, you must provide information about the authenticity of customer reviews. Germanized added a <a href="%3$s">new information page</a> for you, which you should use to provide information on the authenticity of reviews. Additionally, Germanized inserts notices when showing ratings.', 'woocommerce-germanized' ), 'https://www.it-recht-kanzlei.de/preisangabenverordnung-2022-wichtige-aenderungen.html', 'https://www.haendlerbund.de/de/news/aktuelles/rechtliches/4145-omnibus-rezensionen-gekennzeichnet', wc_gzd_get_page_permalink( 'review_authenticity' ) );
	}

	public function get_actions() {
		return array(
			array(
				'url'        => admin_url( 'admin.php?page=wc-settings&tab=germanized-general&section=shop' ),
				'title'      => __( 'Manage review authenticity settings', 'woocommerce-germanized' ),
				'target'     => '_self',
				'is_primary' => true,
			),
			array(
				'url'        => 'https://vendidero.de/dokument/echtheit-von-bewertungen-kennzeichnen',
				'title'      => __( 'Learn more', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
		);
	}
}
