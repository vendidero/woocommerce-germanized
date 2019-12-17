<?php
/**
 * Importer notice.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;

$dismiss_url = add_query_arg( 'notice', 'wc-gzd-hide-dhl-importer-notice', add_query_arg( 'nonce', wp_create_nonce( 'wc-gzd-hide-dhl-importer-notice' ) ) );
?>

<div id="message" class="updated woocommerce-gzd-message woocommerce-gzd-dhl-message">
    <h3><?php _ex( 'DHL built-in Integration', 'dhl', 'woocommerce-germanized' ); ?></h3>

    <p>
		<?php _ex( 'It seems like you are currently using the DHL for WooCommerce plugin. Germanized does now fully integrate DHL services and switching is as simple as can be. Check your advantages by using the DHL integration in Germanized and let Germanized import your current settings for you.', 'dhl', 'woocommerce-germanized' ); ?>
    </p>

    <ul>
        <li><?php _ex( 'No need to use an external plugin which might lead to incompatibilities', 'dhl', 'woocommerce-germanized' ); ?></li>
        <li><?php printf( _x( 'Many improved features such as automation, services per shipping method and %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://vendidero.de/dokument/dhl-labels-zu-sendungen-erstellen" target="_blank">' . _x( 'many more', 'dhl', 'woocommerce-germanized' ) . '</a>' ); ?></li>
        <li><?php _ex( 'Perfectly integrated in Germanized &ndash; easily create labels for shipments', 'dhl', 'woocommerce-germanized' ); ?></li>
    </ul>

    <p class="submit alignleft">
        <a class="button button-secondary" href="https://vendidero.de/dokument/dhl-labels-zu-sendungen-erstellen"
           target="_blank"><?php echo esc_html_x( 'Learn more', 'dhl', 'woocommerce-germanized' ); ?></a>
        <a class="button button-primary"
           href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-gzd-dhl-import', 'yes' ), 'woocommerce_gzd_dhl_import_nonce' ) ); ?>"><?php echo esc_html_x( 'Import settings and activate', 'dhl', 'woocommerce-germanized' ); ?></a>
    </p>

    <p class="alignright">
        <a href="<?php echo esc_url( $dismiss_url ); ?>"><?php _e( 'Hide this notice', 'woocommerce-germanized' ); ?></a>
    </p>

    <div class="clear"></div>
</div>