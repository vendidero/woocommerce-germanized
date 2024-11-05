<?php
/**
 * Shipment label HTML for meta box.
 *
 * @var ShipmentLabel $label
 */
defined( 'ABSPATH' ) || exit;

use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Admin\Admin;
use Vendidero\Germanized\Shipments\Shipment;

/**
 * @var Shipment $shipment
 * @var \Vendidero\Germanized\Shipments\Labels\Label $label
 */

$actions = array();

if ( $label ) {
	if ( $label->get_file() ) {
		$actions['download'] = array(
			'url'     => $label->get_download_url(),
			'name'    => _x( 'Download label', 'shipments', 'woocommerce-germanized' ),
			'action'  => 'download_label',
			'classes' => 'download',
			'target'  => '_blank',
		);
	}

	$actions['delete'] = array(
		'url'               => '#',
		'classes'           => 'remove-shipment-label delete',
		'name'              => _x( 'Delete label', 'shipments', 'woocommerce-germanized' ),
		'action'            => 'delete_label',
		'target'            => 'self',
		'custom_attributes' => array(
			'data-shipment' => $shipment->get_id(),
		),
	);
} else {
	$actions['generate_label'] = array(
		'url'               => '#',
		'name'              => _x( 'Create label', 'shipments', 'woocommerce-germanized' ),
		'action'            => 'create_label',
		'classes'           => 'create-shipment-label has-shipment-modal create',
		'custom_attributes' => array(
			'id'              => 'wc-gzd-create-label-' . $shipment->get_id(),
			'data-id'         => 'wc-gzd-modal-create-shipment-label',
			'data-load-async' => true,
			'data-reference'  => $shipment->get_id(),
		),
	);
}

?>
<div class="wc-gzd-shipment-label wc-gzd-shipment-action-wrapper column col-auto column-spaced show-if show-if-provider show-if-provider-<?php echo esc_attr( $shipment->get_shipping_provider() ); ?>" data-shipment="<?php echo esc_attr( $shipment->get_id() ); ?>">
	<h4><?php printf( esc_html_x( '%s Label', 'shipments', 'woocommerce-germanized' ), esc_html( wc_gzd_get_shipping_provider_title( $shipment->get_shipping_provider() ) ) ); ?> <?php echo ( ( $shipment->has_label() && $shipment->get_tracking_id() ) ? wp_kses_post( Admin::get_shipment_tracking_html( $shipment ) ) : '' ); ?></h4>

	<div class="wc-gzd-shipment-label-content">
		<div class="shipment-label-actions shipment-inner-actions">
			<?php if ( $label ) : ?>
				<div class="shipment-label-actions-wrapper shipment-inner-actions-wrapper shipment-label-actions-download">
					<?php echo wc_gzd_render_shipment_action_buttons( $actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php
					/**
					 * Action that fires after the label download link of a shipment label was outputted.
					 *
					 * @param ShipmentLabel $label The label object.
					 * @param Shipment                 $shipment The shipment object.
					 *
					 * @since 3.0.6
					 * @package Vendidero/Germanized/Shipments
					 */
					do_action( 'woocommerce_gzd_shipment_label_admin_after_download', $label, $shipment );
					?>
				</div>
			<?php else : ?>
				<div class="shipment-label-actions-wrapper shipment-inner-actions-wrapper shipment-label-actions-create">
					<?php echo wc_gzd_render_shipment_action_buttons( $actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php include 'html-shipment-label-backbone.php'; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
