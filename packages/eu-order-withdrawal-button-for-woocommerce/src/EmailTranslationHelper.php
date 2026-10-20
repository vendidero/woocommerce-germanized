<?php

namespace Vendidero\OrderWithdrawalButton;

defined( 'ABSPATH' ) || exit;

trait EmailTranslationHelper {

	public function setup_email_locale( $lang = false ) {
		if ( apply_filters( 'eu_owb_woocommerce_allow_switching_email_locale', true ) ) {
			do_action( 'eu_owb_woocommerce_switch_email_locale', $this, $lang );
		}
	}

	public function restore_email_locale() {
		if ( apply_filters( 'eu_owb_woocommerce_allow_restoring_email_locale', true ) ) {
			do_action( 'eu_owb_woocommerce_restore_email_locale', $this );
		}
	}
}
