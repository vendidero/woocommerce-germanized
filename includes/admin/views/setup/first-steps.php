<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<h1><?php _e( 'First Steps', 'woocommerce-germanized' ); ?></h1>

<p class="headliner"><?php _e( 'Congratulations! You are ready to go. You should now head over to the settings to configure Germanized in-deep.', 'woocommerce-germanized' ); ?></p>

<div class="wc-gzd-setup-grid">
    <div class="wc-gzd-setup-grid-item">
        <h3><?php _e( 'Resources', 'woocommerce-germanized' ); ?></h3>

        <ul class="more">
            <li><i class="dashicons dashicons-book"></i> <a
                        href="https://vendidero.de/dokumentation/woocommerce-germanized"
                        target="_blank"><?php _e( 'Knowledge Base', 'woocommerce-germanized' ); ?></a></li>
            <li><i class="dashicons dashicons-calendar-alt"></i> <a href="https://vendidero.de/blog"
                                                                    target="_blank"><?php _e( 'Stay tuned', 'woocommerce-germanized' ); ?></a>
            </li>
            <li><i class="dashicons dashicons-welcome-learn-more"></i> <a href="https://wordpress.org/support/"
                                                                          target="_blank"><?php _e( 'Learn how to use WordPress', 'woocommerce-germanized' ); ?></a>
            </li>
            <li><i class="dashicons dashicons-welcome-learn-more"></i> <a href="https://docs.woocommerce.com/"
                                                                          target="_blank"><?php _e( 'Learn how to use WooCommerce', 'woocommerce-germanized' ); ?></a>
            </li>
        </ul>
    </div>

	<?php if ( ! WC_germanized()->is_pro() ) : ?>
        <div class="wc-gzd-setup-grid-item">
            <h3><?php _e( 'Upgrade now', 'woocommerce-germanized' ); ?></h3>
            <p><?php _e( 'Want more features and premium support?', 'woocommerce-germanized' ); ?></p>

            <a class="button wc-gzd-button" href="https://vendidero.de/woocommerce-germanized"
               target="_blank"><?php _e( 'Discover professional version', 'woocommerce-germanized' ); ?></a>
        </div>
	<?php endif; ?>
</div>
