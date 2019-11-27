<?php

namespace Vendidero\Germanized\DHL;

defined( 'ABSPATH' ) || exit;

class Emails {

	public static function init() {
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'register_emails' ), 10 );

		// Change email template path if is germanized email template
		add_filter( 'woocommerce_template_directory', array( __CLASS__, 'set_woocommerce_template_dir' ), 10, 2 );
	}

	public static function set_woocommerce_template_dir( $dir, $template ) {
		if ( file_exists( Package::get_path() . '/templates/' . $template ) ) {
			return 'woocommerce-germanized';
		}

		return $dir;
	}

	public static function register_emails( $emails ) {
		$emails['WC_GZD_DHL_Email_Customer_Return_Shipment_Label'] = include Package::get_path() . '/includes/emails/class-wc-gzd-dhl-email-customer-return-shipment-label.php';

		return $emails;
	}
}
