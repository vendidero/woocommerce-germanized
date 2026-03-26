<?php
/**
 * Customer withdrawal request confirmed email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-withdrawal-request-confirmed.php.
 *
 * HOWEVER, on occasion EU OWB will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Vendidero/OrderWithdrawalButton/Templates
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = \Vendidero\OrderWithdrawalButton\Package::has_email_improvements_enabled();

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html_x( 'Hi %s,', 'owb', 'woocommerce-germanized' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html_x( 'Hi,', 'owb', 'woocommerce-germanized' ) );
}
?>
</p>

<p><?php printf( esc_html_x( 'We’ve wanted to let you know that your withdrawal request has been reviewed and is hereby confirmed. We will inform you shortly about the next steps.', 'owb', 'woocommerce-germanized' ), esc_html( $order->get_order_number() ) ); ?></p>

<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

do_action( 'eu_owb_woocommerce_withdrawal_request_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'eu_owb_woocommerce_withdrawal_request_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
