<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class        WC_GZD_Settings_Tab_Taxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Invoices extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Configure PDF invoices and packing slips.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Invoices & Packing Slips', 'woocommerce-germanized' ) . ' <span class="wc-gzd-pro wc-gzd-pro-outlined">' . __( 'pro', 'woocommerce-germanized' ) . '</span>';
	}

	public function get_name() {
		return 'invoices';
	}

	public function is_pro() {
		return true;
	}

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'invoice_options',
				'desc'  => '',
			),

			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_invoice_enable',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-invoices.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#accounting',
				'type'  => 'image',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'invoice_options',
			),
		);
	}

	protected function get_pro_content_html() {
		ob_start();
		?>
		<div class="wc-gzd-premium-overlay notice notice-warning inline">
			<h3><?php esc_html_e( 'Get Germanized Pro to unlock', 'woocommerce-germanized' ); ?></h3>
			<p><?php esc_html_e( 'Generate custom and professional PDF invoices, cancellations and packing slips automatically with Germanized Pro.', 'woocommerce-germanized' ); ?></p>
			<ul>
				<li>✓ <?php esc_html_e( 'Customize your documents with the built-in document editor', 'woocommerce-germanized' ); ?></li>
				<li>✓ <?php esc_html_e( 'Transfer your documents to your lexoffice and/or sevDesk account', 'woocommerce-germanized' ); ?></li>
				<li>✓ <?php esc_html_e( 'Export your documents as CSV and ZIP', 'woocommerce-germanized' ); ?></li>
			</ul>
			<p>
				<a class="button button-secondary" href="https://vendidero.de/woocommerce-germanized/features/#accounting" target="_blank"><?php esc_html_e( 'Learn more', 'woocommerce-germanized' ); ?></a>
				<a class="button button-primary wc-gzd-button" style="margin-left: 5px;" href="https://vendidero.de/woocommerce-germanized" target="_blank"><?php esc_html_e( 'Upgrade now', 'woocommerce-germanized' ); ?></a>
			</p>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	public function is_enabled() {
		return false;
	}
}
