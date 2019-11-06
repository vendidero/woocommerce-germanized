<?php
/**
 * Shipment label HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;

use Vendidero\Germanized\DHL\Package;
?>

<div class="wc-gzd-shipment-dhl-label column column-spaced col-12" data-label="<?php echo ( $dhl_label ? esc_attr( $dhl_label->get_id() ) : '' ); ?>">
    <h4><?php _ex(  'DHL Label', 'dhl', 'woocommerce-germanized' ); ?> <?php echo ( $dhl_label ? '<a class="shipment-tracking-number" href="' . $dhl_label->get_tracking_url() . '" target="_blank">' . $dhl_label->get_number() . '</a>' : '' ); ?></h4>

    <div class="wc-gzd-shipment-dhl-label-content">
        <div class="shipment-dhl-label-actions">
	        <?php if ( $dhl_label ) : ?>
                <div class="shipment-dhl-label-actions-wrapper shipment-dhl-label-actions-download">

                    <a class="button button-secondary download-shipment-label" href="<?php echo $dhl_label->get_download_url(); ?>" target="_blank"><?php _ex(  'Download', 'dhl', 'woocommerce-germanized' ); ?></a>

                    <?php if ( 'return' === $dhl_label->get_type() ) : ?>
                        <a class="send-shipment-label email" href="#" data-label="<?php echo esc_attr( $dhl_label->get_id() ); ?>"><?php _ex(  'Send to customer', 'dhl', 'woocommerce-germanized' ); ?></a>
                    <?php endif; ?>

                    <a class="remove-shipment-label delete" data-label="<?php echo esc_attr( $dhl_label->get_id() ); ?>" href="#"><?php _ex(  'Delete', 'dhl', 'woocommerce-germanized' ); ?></a>
                </div>
	        <?php else: ?>
                <div class="shipment-dhl-label-actions-wrapper shipment-dhl-label-actions-create">
                    <a class="button button-secondary create-shipment-label" href="#" title="<?php _ex(  'Create new DHL label', 'dhl', 'woocommerce-germanized' ); ?>"><?php _ex( 'Create label', 'dhl', 'woocommerce-germanized' ); ?></a>
                    <?php include( 'html-shipment-label-backbone.php' ); ?>
                </div>
	        <?php endif; ?>
        </div>
    </div>
</div>
