<?php
/**
 * Customer Trusted Shops Review Notification
 *
 * @author Vendidero
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo "= " . $email_heading . " =\n\n";

echo sprintf( _x( 'Dear %s %s,', 'trusted-shops', 'woocommerce-germanized' ), wc_gzd_get_crud_data( $order, 'billing_first_name' ), wc_gzd_get_crud_data( $order, 'billing_last_name' ) ) . "\n\n";

echo sprintf( _x( 'You have recently shopped at %s. Thank you! We would be glad if you spent some time to write a review about your order. To do so please follow follow the link.', 'trusted-shops', 'woocommerce-germanized' ), get_bloginfo( 'name' ) ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo WC_Germanized()->trusted_shops->get_new_review_link( wc_gzd_get_crud_data( $order, 'billing_email' ), wc_gzd_get_crud_data( $order, 'id' ) ) . "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );