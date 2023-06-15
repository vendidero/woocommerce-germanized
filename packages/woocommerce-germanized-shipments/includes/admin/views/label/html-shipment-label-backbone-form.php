<?php
/**
 * Shipment label HTML for meta box.
 * @var \Vendidero\Germanized\Shipments\Shipment $shipment
 * @var $settings
 * @var $shipment
 * @var $provider
 */
defined( 'ABSPATH' ) || exit;

$missing_div_closes = 0;
?>
<div class="wc-gzd-shipment-label-admin-fields" id="wc-gzd-shipment-label-admin-fields-<?php echo esc_attr( $provider->get_name() ); ?>">
	<?php \Vendidero\Germanized\Shipments\Admin\Settings::render_label_fields( $settings, $shipment, true ); ?>

	<input type="hidden" name="shipment_id" id="wc-gzd-shipment-label-admin-shipment-id" value="<?php echo esc_attr( $shipment->get_id() ); ?>" />
</div>
