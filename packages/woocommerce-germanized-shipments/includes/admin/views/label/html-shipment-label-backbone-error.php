<?php
/**
 * Shipment label HTML for meta box.
 * @var \Vendidero\Germanized\Shipments\Shipment $shipment
 * @var WP_Error $error
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wc-gzd-shipment-label-admin-errors" id="wc-gzd-shipment-label-admin-errors-<?php echo esc_attr( $provider->get_name() ); ?>">
	<style>
		.wc-backbone-modal-content footer {
			display: none !important;
		}
	</style>
	<div class="notice-wrapper">
		<?php foreach ( $error->get_error_messages() as $message ) : ?>
			<div class="notice is-dismissible notice-warning">
				<?php echo wp_kses_post( wpautop( $message ) ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
