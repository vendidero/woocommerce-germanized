<?php
/**
 * Admin View: Duplicate plugin notice
 */

if ( ! defined( 'ABSPATH' ) )
    exit;
?>

<div class="wc-gzd-ts-notice">
    <h3><?php echo _x( 'WPML Support', 'trusted-shops', 'woocommerce-germanized' ); ?></h3>
    <p>
        <?php if ( $is_default_language ) : ?>
            <?php echo _x( 'These settings serve as default settings for all your languages. To adjust the settings for a certain language, please switch your admin language through the WPML language switcher and adjust the corresponding settings.', 'trusted-shops', 'woocommerce-germanized' ); ?>
        <?php else : ?>
            <?php echo sprintf( _x( 'These settings apply for your %s shop. To adjust settings for another language, please switch your admin language through the WPML language switcher.', 'trusted-shops', 'woocommerce-germanized' ), '<strong>' . $current_language . '</strong>' ); ?>
        <?php endif; ?>
    </p>
</div>
