<?php
/**
 * Shipment label HTML for meta box.
 * @var \Vendidero\Shiptastic\Shipment $shipment
 * @var $settings
 * @var $shipment
 * @var $provider
 */
defined( 'ABSPATH' ) || exit;

$missing_div_closes = 0;
?>
<div class="wc-stc-shipment-label-admin-fields" id="wc-stc-shipment-label-admin-fields-<?php echo esc_attr( $provider->get_name() ); ?>">
	<?php \Vendidero\Shiptastic\Admin\Settings::render_label_fields( $settings, $shipment, true ); ?>

	<input type="hidden" name="shipment_id" id="wc-stc-shipment-label-admin-shipment-id" value="<?php echo esc_attr( $shipment->get_id() ); ?>" />
</div>
