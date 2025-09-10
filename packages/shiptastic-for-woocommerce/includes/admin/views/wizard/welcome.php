<?php
defined( 'ABSPATH' ) || exit;
?>

<div class="wc-shiptastic-wizard-entry">
	<h1><?php echo esc_html_x( 'Welcome! To get started, tell us from where you are shipping.', 'shipments', 'woocommerce-germanized' ); ?></h1>
</div>

<div class="wc-shiptastic-wizard-inner-content inner-content-small">
	<p class="entry-desc"><?php echo esc_html_x( 'This address may differ from your shop\'s base address in case you are shipping from another location.', 'shipments', 'woocommerce-germanized' ); ?></p>

	<div class="wc-shiptastic-wizard-config">
		<div class="wc-shiptastic-error-wrapper"></div>

		<table class="wc-shiptastic-wizard-settings form-table">
			<tbody>
				<?php WC_Admin_Settings::output_fields( \Vendidero\Shiptastic\Admin\Setup\Wizard::get_settings( 'welcome' ) ); ?>
			</tbody>
		</table>
	</div>

	<div class="wc-shiptastic-wizard-links">
		<button class="button button-primary button-submit" type="submit"><?php echo esc_attr_x( 'Continue', 'shipments-wizard', 'woocommerce-germanized' ); ?></button>
	</div>
</div>

