<?php
defined( 'ABSPATH' ) || exit;
?>

<div class="wc-shiptastic-wizard-entry">
	<h1><?php echo esc_html_x( 'Ready to ship!', 'shipments', 'woocommerce-germanized' ); ?></h1>
</div>

<div class="wc-shiptastic-wizard-inner-content inner-content-small">
	<p class="entry-desc"><?php echo esc_html_x( 'Thank you for taking the time to configure Shiptastic. You may find some additional resources below or get back to work.', 'shipments', 'woocommerce-germanized' ); ?></p>

	<fieldset>
		<div class="wc-shiptastic-wizard-list-item">
			<div class="list-item-content">
				<div class="list-item-left">
					<h3><?php echo esc_html_x( 'How shipments work', 'shipments', 'woocommerce-germanized' ); ?></h3>
					<p><?php echo esc_html_x( 'Learn how Shiptastic leverages shipments to enable complex shipping scenarios and save valuable time.', 'shipments', 'woocommerce-germanized' ); ?></p>
				</div>
			</div>

			<div class="list-item-footer">
				<a class="button button-secondary" href="<?php echo esc_url( _x( 'https://vendidero.com/doc/shiptastic/manage-shipments', 'shipments-admin-link', 'woocommerce-germanized' ) ); ?>" target="_blank"><?php echo esc_html_x( 'Learn more', 'shipments', 'woocommerce-germanized' ); ?></a>
			</div>
		</div>

		<div class="wc-shiptastic-wizard-list-item">
			<div class="list-item-content">
				<div class="list-item-left">
					<h3><?php echo esc_html_x( 'Setup rule-based shipping costs', 'shipments', 'woocommerce-germanized' ); ?></h3>
					<p><?php echo esc_html_x( 'Shiptastic offers rule-based shipping methods for each enabled shipping service provider.', 'shipments', 'woocommerce-germanized' ); ?></p>
				</div>
			</div>

			<div class="list-item-footer">
				<a href="<?php echo esc_url( _x( 'https://vendidero.com/doc/shiptastic/manage-shipping-rules', 'shipments-admin-link', 'woocommerce-germanized' ) ); ?>" target="_blank"><?php echo esc_html_x( 'Learn more', 'shipments', 'woocommerce-germanized' ); ?></a>
				<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><?php echo esc_html_x( 'Get started', 'shipments', 'woocommerce-germanized' ); ?></a>
			</div>
		</div>
	</fieldset>

	<div class="wc-shiptastic-wizard-links">
		<a class="button button-primary" href="<?php echo esc_url( 'index.php' ); ?>"><?php echo esc_attr_x( 'Back to work', 'shipments-wizard', 'woocommerce-germanized' ); ?></a>
	</div>
</div>
