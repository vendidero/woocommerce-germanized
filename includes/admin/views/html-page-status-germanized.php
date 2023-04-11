<?php
/**
 * Admin View: Page - Germanized Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="updated woocommerce-message">
	<p><?php esc_html_e( 'Please copy and paste this information in your ticket when contacting support:', 'woocommerce-germanized' ); ?> </p>
	<p class="submit"><a href="#" class="button-primary debug-report"><?php esc_html_e( 'Get System Report', 'woocommerce-germanized' ); ?></a>
		<div id="debug-report">
			<textarea readonly="readonly"></textarea>
	<p class="submit">
		<button id="copy-for-support" class="button-primary" href="#" data-tip="<?php esc_html_e( 'Copied!', 'woocommerce-germanized' ); ?>"><?php esc_html_e( 'Copy for Support', 'woocommerce-germanized' ); ?></button>
	</p>
</div>
</div>
<br/>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Germanized"><?php esc_html_e( 'Germanized', 'woocommerce-germanized' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td data-export-label="WC GZD Version"><?php esc_html_e( 'Version', 'woocommerce-germanized' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<td><?php echo esc_html( WC_germanized()->version ); ?></td>
	</tr>
	<tr>
		<td data-export-label="WC GZD Database Version"><?php esc_html_e( 'Database Version', 'woocommerce-germanized' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<td><?php echo esc_html( get_option( 'woocommerce_gzd_db_version' ) ); ?></td>
	</tr>
	<tr>
		<td data-export-label="Small Business"><?php esc_html_e( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<td><?php echo ( wc_gzd_is_small_business() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>' ); ?></td>
	</tr>
	<tr>
		<td data-export-label="PHP Sodium"><?php esc_html_e( 'PHP Sodium Extension', 'woocommerce-germanized' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<td><?php echo ( class_exists( 'WC_GZD_Secret_Box_Helper' ) && defined( 'SODIUM_LIBRARY_VERSION' ) ? esc_html( SODIUM_LIBRARY_VERSION ) : '<mark class="no">&ndash;</mark>' ); ?></td>
	</tr>
	<tr>
		<td data-export-label="Default Delivery Time"><?php esc_html_e( 'Default Delivery Time', 'woocommerce-germanized' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<?php
		$term_slug = get_option( 'woocommerce_gzd_default_delivery_time' );
		$term_obj  = false;

		if ( $term_slug ) {
			$term_obj = WC_germanized()->delivery_times->get_term_object( $term_slug );
		}
		?>
		<td><?php echo ( ( $term_obj ) ? esc_html( $term_obj->name ) : '<mark class="no">&ndash;</mark>' ) . ( ( $term_slug && ! $term_obj ) ? ' [' . esc_html__( 'Term doesn’t exist', 'woocommerce-germanized' ) . ']' : '' ); ?></td>
	</tr>
	<tr>
		<td data-export-label="Checkout Fallback"><?php esc_html_e( 'Fallback Mode', 'woocommerce-germanized' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<td><?php echo ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_fallback' ) ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>' ); ?></td>
	</tr>
	<?php

	/**
	 * After Germanized status page output.
	 *
	 * Fires after Germanized has rendered it's status page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_gzd_status_after_germanized' );
	?>
	</tbody>
</table>

<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Taxes"><?php esc_html_e( 'Taxes', 'woocommerce-germanized' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td data-export-label="Additional costs tax calculation mode"><?php esc_html_e( 'Additional costs tax calculation mode', 'woocommerce-germanized' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<td><?php echo esc_html( wc_gzd_get_additional_costs_tax_calculation_mode() ); ?><?php echo esc_html( ( wc_gzd_calculate_additional_costs_taxes_based_on_main_service() ? ' (' . wc_gzd_additional_costs_taxes_detect_main_service_by() . ')' : '' ) ); ?></td>
	</tr>
	<tr>
		<td data-export-label="Additional costs include taxes"><?php esc_html_e( 'Additional costs include taxes', 'woocommerce-germanized' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<td><?php echo ( wc_gzd_additional_costs_include_tax() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>' ); ?></td>
	</tr>
	<tr>
		<td data-export-label="VAT Table Check"><?php esc_html_e( 'VAT Table Check', 'woocommerce-germanized' ); ?>:</td>
		<td class="help"><?php echo wc_help_tip( esc_attr( esc_html__( 'Checks whether all WooCommerce tax relevant tables have been added.', 'woocommerce-germanized' ) ) ); ?></td>
		<td><?php echo ( WC_GZD_Admin_Status::tax_tables_exist() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark> [' . sprintf( esc_html__( 'Missing: %s', 'woocommerce-germanized' ), esc_html( implode( ', ', WC_GZD_Admin_Status::get_missing_tax_tables() ) ) ) . ']' ); ?></td>
	</tr>
	<?php

	/**
	 * After VAT status.
	 *
	 * Fires after Germanized has rendered the VAT status section.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_gzd_status_after_vat' );
	?>
	</tbody>
</table>

<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Legal Pages"><?php esc_html_e( 'Legal Pages', 'woocommerce-germanized' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( WC_GZD_Admin_Status::get_legal_pages() as $option_name => $page_data ) : ?>
		<tr>
			<td data-export-label="<?php echo esc_attr( $page_data['title'] ); ?>"><?php echo esc_html( $page_data['title'] ); ?></td>
			<td class="help">&nbsp;</td>
			<td><?php echo ( $page_data['id'] && get_post( $page_data['id'] ) ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>' . ( $page_data['id'] && ! get_post( $page_data['id'] ) ? ' [' . esc_html__( 'Page doesn’t exist', 'woocommerce-germanized' ) . ']' : '' ) ); ?></td>
		</tr>
	<?php endforeach; ?>
	<?php
	/**
	 * After legal pages section.
	 *
	 * Fires after the legal pages section within the Germanized status page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_gzd_status_after_legal_pages' );
	?>
	</tbody>
</table>

<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Compatibility"><?php esc_html_e( 'Compatibility', 'woocommerce-germanized' ); ?></th>
	</tr>
	</thead>
	<tbody class="tools">

	<?php
	foreach ( WC_germanized()->compatibilities as $c => $comp ) :

		if ( ! $comp->is_activated() ) {
			continue;
		}

		$version_data = $comp->get_version_data();
		?>

		<tr>
			<td data-export-label="<?php esc_attr( $comp->get_name() ); ?>"><?php echo esc_html( $comp->get_name() ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_attr( sprintf( esc_html__( 'Checks whether compatibility options for %s are being applied.', 'woocommerce-germanized' ), $comp->get_name() ) ) ); ?></td>
			<td>
				<?php echo ( $comp->is_applicable() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>' ); ?>
				<?php echo ( ! $comp->is_supported() ? sprintf( esc_html__( 'Version %1$s not supported, supporting version %2$s - %3$s', 'woocommerce-germanized' ), esc_html( $version_data['version'] ), esc_html( $version_data['requires_at_least'] ), esc_html( $version_data['tested_up_to'] ) ) : '' ); ?>
			</td>
		</tr>

	<?php endforeach; ?>

	<?php
	/**
	 * After compatibility section.
	 *
	 * Fires after the compatibility section within the Germanized status page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_gzd_status_after_compatibility' );
	?>
	</tbody>
</table>

<table class="wc_status_table widefat" cellspacing="0">
	<thead>
	<tr>
		<th colspan="3"><?php esc_html_e( 'Tools', 'woocommerce-germanized' ); ?></th>
	</tr>
	</thead>
	<tbody class="tools">
	<tr>
		<td><?php esc_html_e( 'German Formal', 'woocommerce-germanized' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_attr( __( 'This option will install and activate German formal as your WordPress and WooCommerce language.', 'woocommerce-germanized' ) ) ); ?></td>
		<td>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wc-gzd-check-language_install' => 'de_DE_formal' ) ), 'wc-gzd-check-language_install' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Install de_DE_formal', 'woocommerce-germanized' ); ?></a></td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Text Options', 'woocommerce-germanized' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_attr( __( 'This option removes custom Germanized text options (e.g. Pay-Button-Text) and installs default options. You may use this options to reinstall text options e.g. after a language switch.', 'woocommerce-germanized' ) ) ); ?></td>
		<td>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wc-gzd-check-text_options_deletion' => true ) ), 'wc-gzd-check-text_options_deletion' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Delete text options', 'woocommerce-germanized' ); ?></a>
		</td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Renew EU VAT Rates', 'woocommerce-germanized' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_attr( __( 'Insert VAT rates for EU countries based on your current OSS participation status. This option deletes all current rates before inserting.', 'woocommerce-germanized' ) ) ); ?></td>
		<td>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wc-gzd-check-insert_vat_rates' => true ) ), 'wc-gzd-check-insert_vat_rates' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Renew VAT Rates', 'woocommerce-germanized' ); ?></a>
		</td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Disable notices', 'woocommerce-germanized' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_attr( __( 'Germanized might ask you to leave a review or notices you of using a possibly unsupported theme. If you want to disable these notices, check this option.', 'woocommerce-germanized' ) ) ); ?></td>
		<td>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wc-gzd-check-disable_notices' => true ) ), 'wc-gzd-check-disable_notices' ) ); ?>" class="button button-secondary"><?php echo ( 'yes' === get_option( 'woocommerce_gzd_disable_notices' ) ? esc_html__( 'Enable notices', 'woocommerce-germanized' ) : esc_html__( 'Disable notices', 'woocommerce-germanized' ) ); ?></a>
		</td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Extended debug mode', 'woocommerce-germanized' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_attr( __( 'Enable/disable extended debug mode via log files. Check your logs via WooCommerce > Status > Logs.', 'woocommerce-germanized' ) ) ); ?></td>
		<td>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wc-gzd-check-enable_debug_mode' => true ) ), 'wc-gzd-check-enable_debug_mode' ) ); ?>" class="button button-secondary"><?php echo ( 'yes' === get_option( 'woocommerce_gzd_extended_debug_mode' ) ? esc_html__( 'Disable debug mode', 'woocommerce-germanized' ) : esc_html__( 'Enable debug mode', 'woocommerce-germanized' ) ); ?></a>
		</td>
	</tr>
	<tr>
		<td><?php esc_html_e( 'Food Options', 'woocommerce-germanized' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_attr( __( 'Enable/disable product food options.', 'woocommerce-germanized' ) ) ); ?></td>
		<td>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wc-gzd-check-disable_food_options' => true ) ), 'wc-gzd-check-disable_food_options' ) ); ?>" class="button button-secondary"><?php echo ( 'yes' === get_option( 'woocommerce_gzd_disable_food_options' ) ? esc_html__( 'Enable food options', 'woocommerce-germanized' ) : esc_html__( 'Disable food options', 'woocommerce-germanized' ) ); ?></a>
		</td>
	</tr>
	<?php if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) && ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) : ?>
	<tr>
		<td><?php esc_html_e( 'Encryption Key', 'woocommerce-germanized' ); ?></td>
		<td class="help"></td>
		<td><?php echo wp_kses_post( WC_GZD_Secret_Box_Helper::get_encryption_key_notice() ); ?>
			<?php if ( WC_GZD_Secret_Box_Helper::supports_auto_insert() ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'wc-gzd-check-encryption_key_insert' => true ) ), 'wc-gzd-check-encryption_key_insert' ) ); ?>"><?php esc_html_e( 'Auto insert', 'woocommerce-germanized' ); ?></a>
			<?php endif; ?>
			<a class="button button-secondary" href="https://vendidero.de/dokument/verschluesselung-sensibler-daten" target="_blank"><?php esc_html_e( 'Learn more', 'woocommerce-germanized' ); ?></a>
		</td>
	</tr>
	<?php endif; ?>
	<?php
	/**
	 * After tools section.
	 *
	 * Fires after the tools section within the Germanized status page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_gzd_status_after_tools' );
	?>
	</tbody>
</table>

<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Templates"><?php esc_html_e( 'Templates', 'woocommerce-germanized' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( WC_GZD_Admin::instance()->get_template_version_check_result() as $plugin_name => $data ) : ?>
		<tr>
			<td data-export-label="Overrides"><?php esc_html_e( 'Overrides', 'woocommerce-germanized' ); ?>:<br/><strong><?php echo esc_html( $data['title'] ); ?></strong></td>
			<td class="help">&nbsp</td>
			<td>
				<?php if ( ! empty( $data['files'] ) ) : ?>
					<?php foreach ( $data['files'] as $file ) : ?>
						<?php printf( '<code>%s</code>', esc_html( str_replace( WP_CONTENT_DIR . '/themes/', '', $file['theme_file'] ) ) ); ?>

						<?php if ( $file['outdated'] ) : ?>
							<?php printf( esc_html__( 'Version %1$s is out of date. The core version %2$s is available at: %3$s', 'woocommerce-germanized' ), '<span class="red" style="color:red">' . esc_html( $file['theme_version'] ) . '</span>', esc_html( $file['core_version'] ), '<code>' . esc_html( str_replace( WP_PLUGIN_DIR, '', $file['core_file'] ) ) . '</code>' ); ?>
						<?php endif; ?>

						<br/>
					<?php endforeach; ?>

					<?php if ( $data['has_outdated'] ) : ?>
						<br/><a href="<?php echo esc_url( $data['outdated_help_url'] ); ?>" target="_blank"><?php esc_html_e( 'Learn how to update outdated templates', 'woocommerce-germanized' ); ?></a>
					<?php endif; ?>
				<?php else : ?>
					&ndash;
				<?php endif; ?>
			</td>
		</tr>

	<?php endforeach; ?>

	<?php
	/**
	 * After templates section.
	 *
	 * Fires after the templates section within the Germanized status page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'woocommerce_gzd_status_after_templates' );
	?>
	</tbody>
</table>

<script type="text/javascript">

	jQuery('a.help_tip').click(function () {
		return false;
	});

	jQuery('a.debug-report').click(function () {

		var report = '';

		jQuery('#status thead, #status tbody').each(function () {

			if (jQuery(this).is('thead')) {

				var label = jQuery(this).find('th:eq(0)').data('export-label') || jQuery(this).text();
				report = report + "\n### " + jQuery.trim(label) + " ###\n\n";

			} else {

				jQuery('tr', jQuery(this)).each(function () {

					var label = jQuery(this).find('td:eq(0)').data('export-label') || jQuery(this).find('td:eq(0)').text();
					var the_name = jQuery.trim(label).replace(/(<([^>]+)>)/ig, ''); // Remove HTML

					var $value_html = jQuery( this ).find( 'td:eq(2)' ).clone();
					$value_html.find( '.private' ).remove();
					$value_html.find( '.dashicons-yes' ).replaceWith( '&#10004;' );
					$value_html.find( '.dashicons-no-alt, .dashicons-warning' ).replaceWith( '&#10060;' );

					// Format value
					var the_value   = $value_html.text().trim();
					var value_array = the_value.split(', ');

					if (value_array.length > 1) {

						// If value have a list of plugins ','
						// Split to add new line
						var output = '';
						var temp_line = '';
						jQuery.each(value_array, function (key, line) {
							temp_line = temp_line + line + '\n';
						});

						the_value = temp_line;
					}

					report = report + '' + the_name + ': ' + the_value + "\n";
				});

			}
		});

		try {
			jQuery("#debug-report").slideDown();
			jQuery("#debug-report textarea").val(report).focus().select();
			jQuery(this).fadeOut();
			return false;
		} catch (e) {
			console.log(e);
		}

		return false;
	});

	jQuery(document).ready(function ($) {
		$('#copy-for-support').tipTip({
			'attribute': 'data-tip',
			'activation': 'click',
			'fadeIn': 50,
			'fadeOut': 50,
			'delay': 0
		});

		$('body').on('copy', '#copy-for-support', function (e) {
			e.clipboardData.clearData();
			e.clipboardData.setData('text/plain', $('#debug-report textarea').val());
			e.preventDefault();
		});

	});

</script>
