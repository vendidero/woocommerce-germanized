<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\Vendidero\OrderWithdrawalButton\Admin\Admin' ) ) {
	/**
	 * has_shortcode check will return false in case not yet registered (init:10).
	 */
	if ( doing_action( 'init' ) ) {
		add_action(
			'init',
			function () {
				$withdrawal_status = \Vendidero\OrderWithdrawalButton\Admin\Admin::get_current_withdrawal_page_status();

				if ( 'valid' !== $withdrawal_status['status'] ) {
					WC_GZD_Admin_Notices::instance()->activate_legal_news_note();
				}
			},
			9999
		);
	} else {
		$withdrawal_status = \Vendidero\OrderWithdrawalButton\Admin\Admin::get_current_withdrawal_page_status();

		if ( 'valid' !== $withdrawal_status['status'] ) {
			WC_GZD_Admin_Notices::instance()->activate_legal_news_note();
		}
	}
}
