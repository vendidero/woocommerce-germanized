<?php
/**
 * Shipment label HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;

use Vendidero\Germanized\DHL\Package;

$selected_data     = wc_gzd_dhl_get_deutsche_post_selected_default_product( $shipment, $dhl_order );
$im_products       = wc_gzd_dhl_get_deutsche_post_products( $shipment );
$product_id        = $selected_data['product_id'];
$product_code      = $selected_data['product_code'];
$selected_services = $selected_data['services'];
$is_wp_int         = false;

if ( ! empty( $product_code ) ) {
	$is_wp_int = Package::get_internetmarke_api()->is_warenpost_international( $product_code );
}
?>
<?php if ( empty( $im_products ) ) : ?>
    <style>
        .wc-backbone-modal-content footer {
            display: none !important;
        }
    </style>
    <div class="notice-wrapper">
        <div class="notice is-dismissible notice-warning">
            <p><?php printf( __( 'Sorry but none of your selected <a href="%s">Deutsche Post Products</a> is available for this shipment. Please verify your shipment data (e.g. weight) and try again.', 'dhl', 'woocommerce-germanized' ), admin_url( \Vendidero\Germanized\DHL\Admin\Settings::get_settings_url( 'internetmarke' ) ) ); ?></p>
        </div>
    </div>
<?php else: ?>
    <form action="" method="post" class="wc-gzd-create-shipment-label-form">
        <?php woocommerce_wp_select( array(
            'id'          		=> 'deutsche_post_label_dhl_product',
            'label'       		=> _x( 'Product', 'dhl', 'woocommerce-germanized' ),
            'description'		=> '',
            'options'			=> $im_products,
            'value'             => $product_code,
        ) ); ?>

        <div class="wc-gzd-shipment-im-additional-services">
            <?php include( Package::get_path() . '/includes/admin/views/html-deutsche-post-additional-services.php' ); ?>
        </div>

        <div class="wc-gzd-shipment-im-page-format" style="<?php echo ( $is_wp_int ? 'display: none;' : '' ); ?>">
            <?php woocommerce_wp_select( array(
                'id'          		=> 'deutsche_post_label_page_format',
                'label'       		=> _x( 'Page Format', 'dhl', 'woocommerce-germanized' ),
                'description'		=> '',
                'options'			=> Package::get_internetmarke_api()->get_page_format_list(),
                'value'             => isset( $selected_data['page_format'] ) ? $selected_data['page_format'] : '',
            ) ); ?>
        </div>
    </form>

    <div class="columns preview-columns wc-gzd-dhl-im-product-data">
        <div class="column col-4">
            <p class="wc-gzd-dhl-im-product-price wc-price data-placeholder hide-default" data-replace="price_formatted"></p>
        </div>
        <div class="column col-3 col-dimensions">
            <p class="wc-gzd-dhl-im-product-dimensions data-placeholder hide-default" data-replace="dimensions_formatted"></p>
        </div>
        <div class="column col-5 col-preview">
            <div class="image-preview"></div>
        </div>
        <div class="column col-12">
            <p class="wc-gzd-dhl-im-product-description data-placeholder hide-default" data-replace="description_formatted"></p>
            <p class="wc-gzd-dhl-im-product-information-text data-placeholder hide-default" data-replace="information_text_formatted"></p>
        </div>
    </div>
<?php endif; ?>
