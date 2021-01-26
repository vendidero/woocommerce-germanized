<?php
/**
 * Shipment label HTML for meta box.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;

$selected_services   = isset( $selected_services ) ? $selected_services : array();
$services            = \Vendidero\Germanized\DHL\Package::get_internetmarke_api()->get_product_list()->get_services_for_product( $product_id, $selected_services );
?>

<?php if ( ! empty( $services ) ) : ?>
	<p class="label"><?php _ex( 'Additional Services', 'dhl', 'woocommerce-germanized' ); ?></p>

    <div class="wc-gzd-deutsche-post-additional-service-list">
	    <?php foreach( $services as $service ) : ?>
		    <?php woocommerce_wp_checkbox( array(
			    'id'            => 'deutsche_post_label_additional_services_' . $service,
			    'name'          => 'deutsche_post_label_additional_services[]',
			    'wrapper_class' => 'form-field-checkbox',
			    'label'         => \Vendidero\Germanized\DHL\Package::get_internetmarke_api()->get_product_list()->get_additional_service_title( $service ),
			    'cbvalue'       => $service,
			    'value'         => in_array( $service, $selected_services ) ? $service : '',
		    ) ); ?>
	    <?php endforeach; ?>
    </div>
<?php endif; ?>
