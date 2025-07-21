<?php
/**
 * Shipment label HTML for meta box.
 */
defined( 'ABSPATH' ) || exit;
?>

<script type="text/template" id="tmpl-wc-stc-modal-create-shipment-label-<?php echo esc_attr( $shipment->get_id() ); ?>" class="wc-stc-shipment-label-<?php echo esc_attr( $shipment->get_type() ); ?>">
	<div class="wc-backbone-modal wc-stc-admin-shipment-modal wc-stc-modal-create-shipment-label">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html_x( 'Create label', 'shipments', 'woocommerce-germanized' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text">Close modal panel</span>
					</button>
				</header>
				<article class="shiptastic-shipments shipments-create-label" data-shipment-type="<?php echo esc_attr( $shipment->get_type() ); ?>">
					<div class="notice-wrapper"></div>

					<form action="" method="post" class="wc-stc-create-shipment-label-form">
						<div class="wc-stc-shipment-create-label"></div>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Create', 'shipments', 'woocommerce-germanized' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
