<?php
/**
 * Shipment label HTML for meta box.
 * @var \Vendidero\Shiptastic\Shipment $shipment
 * @var WP_Error $error
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wc-stc-shipment-label-admin-errors" id="wc-stc-shipment-label-admin-errors-<?php echo esc_attr( $provider->get_name() ); ?>">
	<div class="notice-wrapper">
		<?php foreach ( $error->get_error_messages() as $message ) : ?>
			<div class="notice is-dismissible notice-warning">
				<?php echo wp_kses_post( wpautop( $message ) ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
