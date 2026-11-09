<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="wc-gzd-wizard-entry">
	<h1><?php esc_html_e( 'First Steps', 'woocommerce-germanized' ); ?></h1>
</div>

<div class="wc-gzd-wizard-inner-content inner-content-small">
	<p class="entry-desc"><?php esc_html_e( 'Congratulations! You are ready to go. You should now head over to the settings to configure Germanized in-deep.', 'woocommerce-germanized' ); ?></p>

	<fieldset>
		<div class="wc-gzd-wizard-list-item">
			<div class="list-item-content">
				<div class="list-item-left">
					<h3><?php esc_html_e( 'Resources', 'woocommerce-germanized' ); ?></h3>

					<ul class="wc-gzd-wizard-resources">
						<li><i class="dashicons dashicons-book"></i> <a href="https://vendidero.de/dokumentation/woocommerce-germanized" target="_blank"><?php esc_html_e( 'Knowledge Base', 'woocommerce-germanized' ); ?></a></li>
						<li><i class="dashicons dashicons-calendar-alt"></i> <a href="https://vendidero.de/blog" target="_blank"><?php esc_html_e( 'Stay tuned', 'woocommerce-germanized' ); ?></a></li>
						<li><i class="dashicons dashicons-welcome-learn-more"></i> <a href="https://wordpress.org/support/" target="_blank"><?php esc_html_e( 'Learn how to use WordPress', 'woocommerce-germanized' ); ?></a></li>
						<li><i class="dashicons dashicons-welcome-learn-more"></i> <a href="https://docs.woocommerce.com/" target="_blank"><?php esc_html_e( 'Learn how to use WooCommerce', 'woocommerce-germanized' ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>

		<?php if ( ! WC_germanized()->is_pro() ) : ?>
			<div class="wc-gzd-wizard-list-item">
				<div class="list-item-content">
					<div class="list-item-left">
						<h3><?php esc_html_e( 'Upgrade now', 'woocommerce-germanized' ); ?></h3>
						<p><?php esc_html_e( 'Want more features and professional support?', 'woocommerce-germanized' ); ?></p>
					</div>
				</div>

				<div class="list-item-footer">
					<a class="button button-secondary" href="https://vendidero.de/woocommerce-germanized" target="_blank"><?php esc_html_e( 'Discover professional version', 'woocommerce-germanized' ); ?></a>
				</div>
			</div>
		<?php endif; ?>
	</fieldset>

	<?php $wizard->output_links(); ?>
</div>
