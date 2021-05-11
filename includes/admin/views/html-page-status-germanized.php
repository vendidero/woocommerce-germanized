<?php
/**
 * Admin View: Page - Germanized Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="updated woocommerce-message">
    <p><?php _e( 'Please copy and paste this information in your ticket when contacting support:', 'woocommerce-germanized' ); ?> </p>
    <p class="submit"><a href="#"
                         class="button-primary debug-report"><?php _e( 'Get System Report', 'woocommerce-germanized' ); ?></a>
        <div id="debug-report">
            <textarea readonly="readonly"></textarea>
    <p class="submit">
        <button id="copy-for-support" class="button-primary" href="#"
                data-tip="<?php _e( 'Copied!', 'woocommerce-germanized' ); ?>"><?php _e( 'Copy for Support', 'woocommerce-germanized' ); ?></button>
    </p>
</div>
</div>
<br/>
<table class="wc_status_table widefat" cellspacing="0" id="status">
    <thead>
    <tr>
        <th colspan="3" data-export-label="Germanized"><?php _e( 'Germanized', 'woocommerce-germanized' ); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-export-label="WC GZD Version"><?php _e( 'Version', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
        <td><?php echo esc_html( WC_germanized()->version ); ?></td>
    </tr>
    <tr>
        <td data-export-label="WC GZD Database Version"><?php _e( 'Database Version', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
        <td><?php echo esc_html( get_option( 'woocommerce_gzd_db_version' ) ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Small Business"><?php _e( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
        <td><?php echo ( wc_gzd_is_small_business() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">' . '&ndash;' . '</mark>' ); ?></td>
    </tr>
    <tr>
        <td data-export-label="PHP Sodium"><?php _e( 'PHP Sodium Extension', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
        <td><?php echo ( class_exists( 'WC_GZD_Secret_Box_Helper' ) && defined( 'SODIUM_LIBRARY_VERSION' ) ? SODIUM_LIBRARY_VERSION : '<mark class="no">' . '&ndash;' . '</mark>' ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Default Delivery Time"><?php _e( 'Default Delivery Time', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
		<?php
		$term_id = get_option( 'woocommerce_gzd_default_delivery_time' );
		$term    = false;

		if ( $term_id ) {
			$term = get_term_by( 'id', $term_id, 'product_delivery_time' );
		}
		?>
        <td><?php echo $term ? $term->name : '<mark class="no">' . '&ndash;' . '</mark>' . ( $term_id && ! $term ? ' [' . __( 'Term doesn’t exist', 'woocommerce-germanized' ) . ']' : '' ); ?></td>
    </tr>
    <tr>
        <td data-export-label="Checkout Fallback"><?php _e( 'Fallback Mode', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
        <td><?php echo 'yes' === get_option( 'woocommerce_gzd_display_checkout_fallback' ) ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">' . '&ndash;' . '</mark>'; ?></td>
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
        <th colspan="3" data-export-label="Taxes"><?php _e( 'Taxes', 'woocommerce-germanized' ); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td data-export-label="Split Tax"><?php _e( 'Split-tax', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
        <td><?php echo 'yes' === get_option( 'woocommerce_gzd_shipping_tax' ) ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">' . '&ndash;' . '</mark>'; ?></td>
    </tr>
    <tr>
        <td data-export-label="Split Tax"><?php _e( 'Additional costs include taxes', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
        <td><?php echo wc_gzd_additional_costs_include_tax() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">' . '&ndash;' . '</mark>'; ?></td>
    </tr>
    <tr>
        <td data-export-label="Virtual VAT"><?php _e( 'Virtual VAT', 'woocommerce-germanized' ); ?>:</td>
        <td class="help">&nbsp;</td>
        <td><?php echo 'yes' === get_option( 'woocommerce_gzd_enable_virtual_vat' ) ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">' . '&ndash;' . '</mark>'; ?></td>
    </tr>
    <tr>
        <td data-export-label="Tax Rate Name Collision"><?php _e( 'Tax Rate Name Collision', 'woocommerce-germanized' ); ?>
            :
        </td>
        <td class="help"><?php echo wc_help_tip( esc_attr( __( 'Make sure, that different tax rates do not have the same names. WooCommerce will then merge these rates within checkout into one line.', 'woocommerce-germanized' ) ) ); ?></td>
        <td>
			<?php

			global $wpdb;

			$tax_classes = WC_Tax::get_tax_classes();
			$tax_rate_names = array();
			$collisions = array();

			foreach ( $tax_classes as $class ) {

				$rates = $wpdb->get_results( $wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
							WHERE tax_rate_class = %s
							ORDER BY tax_rate_order
							",
					sanitize_title( $class )
				) );

				foreach ( $rates as $rate ) {
					if ( ! isset( $tax_rate_names[ $rate->tax_rate_name ] ) ) {
						$tax_rate_names[ $rate->tax_rate_name ] = $rate;
					} elseif ( $tax_rate_names[ $rate->tax_rate_name ]->tax_rate_class != $rate->tax_rate_class ) {
						array_push( $collisions, array( $tax_rate_names[ $rate->tax_rate_name ], $rate ) );
					}

				}

			}

			if ( ! empty( $collisions ) ) {
				$c_count = 0;
				foreach ( $collisions as $collision ) {
					echo ( $c_count ++ > 0 ? ' | ' : '' ) . $collision[0]->tax_rate_id . ' (' . $collision[0]->tax_rate_class . ') && ' . $collision[1]->tax_rate_id . ' (' . $collision[1]->tax_rate_class . ')';
				}
			} else {
				echo '<mark class="no">' . '&ndash;' . '</mark>';
			}

			?>
        </td>
    </tr>
    <tr>
        <td data-export-label="VAT Table Check"><?php _e( 'VAT Table Check', 'woocommerce-germanized' ); ?>:</td>
        <td class="help"><?php echo wc_help_tip( esc_attr( __( 'Checks whether all WooCommerce tax relevant tables have been added.', 'woocommerce-germanized' ) ) ); ?></td>
        <td><?php echo WC_GZD_Admin_Status::tax_tables_exist() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">' . '&ndash;' . '</mark>' . ' [' . sprintf( __( 'Missing: %s', 'woocommerce-germanized' ), implode( ', ', WC_GZD_Admin_Status::get_missing_tax_tables() ) ) . ']'; ?></td>
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
        <th colspan="3" data-export-label="Legal Pages"><?php _e( 'Legal Pages', 'woocommerce-germanized' ); ?></th>
    </tr>
    </thead>
    <tbody>
	<?php foreach ( WC_GZD_Admin_Status::get_legal_pages() as $option => $page ) : ?>

        <tr>
            <td data-export-label="<?php echo $page['title']; ?>"><?php echo $page['title']; ?></td>
            <td class="help">&nbsp;</td>
            <td><?php echo $page['id'] && get_post( $page['id'] ) ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">' . '&ndash;' . '</mark>' . ( $page['id'] && ! get_post( $page['id'] ) ? ' [' . __( 'Page doesn’t exist', 'woocommerce-germanized' ) . ']' : '' ); ?></td>
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
        <th colspan="3" data-export-label="Compatibility"><?php _e( 'Compatibility', 'woocommerce-germanized' ); ?></th>
    </tr>
    </thead>
    <tbody class="tools">

	<?php foreach ( WC_germanized()->compatibilities as $c => $comp ) :

		if ( ! $comp->is_activated() ) {
			continue;
		}

		$version_data = $comp->get_version_data();
		?>

        <tr>
            <td data-export-label="<?php esc_attr( $comp->get_name() ); ?>"><?php echo $comp->get_name(); ?></td>
            <td class="help"><?php echo wc_help_tip( esc_attr( sprintf( __( 'Checks whether compatibility options for %s are being applied.', 'woocommerce-germanized' ), $comp->get_name() ) ) ); ?></td>
            <td>
				<?php echo( $comp->is_applicable() ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">' . '&ndash;' . '</mark>' ); ?>
				<?php echo( ! $comp->is_supported() ? sprintf( __( 'Version %s not supported, supporting version %s - %s', 'woocommerce-germanized' ), $version_data['version'], $version_data['requires_at_least'], $version_data['tested_up_to'] ) : '' ); ?>
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
        <th colspan="3"><?php _e( 'Tools', 'woocommerce-germanized' ); ?></th>
    </tr>
    </thead>
    <tbody class="tools">
    <tr>
        <td><?php _e( 'German Formal', 'woocommerce-germanized' ); ?></td>
        <td class="help"><?php echo wc_help_tip( esc_attr( __( 'This option will install and activate German formal as your WordPress and WooCommerce language.', 'woocommerce-germanized' ) ) ); ?></td>
        <td>
            <a href="<?php echo wp_nonce_url( add_query_arg( array( 'install-language' => 'de_DE_formal' ) ), 'wc-gzd-install-language' ); ?>"
               class="button button-secondary"><?php _e( 'Install de_DE_formal', 'woocommerce-germanized' ); ?></a></td>
    </tr>
    <tr>
        <td><?php _e( 'Text Options', 'woocommerce-germanized' ); ?></td>
        <td class="help"><?php echo wc_help_tip( esc_attr( __( 'This option removes custom Germanized text options (e.g. Pay-Button-Text) and installs default options. You may use this options to reinstall text options e.g. after a language switch.', 'woocommerce-germanized' ) ) ); ?></td>
        <td>
            <a href="<?php echo wp_nonce_url( add_query_arg( array( 'delete-text-options' => true ) ), 'wc-gzd-delete-text-options' ); ?>"
               class="button button-secondary"><?php _e( 'Delete text options', 'woocommerce-germanized' ); ?></a></td>
    </tr>
    <tr>
        <td><?php _e( 'Renew EU VAT Rates', 'woocommerce-germanized' ); ?></td>
        <td class="help"><?php echo wc_help_tip( esc_attr( __( 'Insert VAT rates for EU countries based on your current OSS participation status. This option deletes all current rates before inserting.', 'woocommerce-germanized' ) ) ); ?></td>
        <td>
            <a href="<?php echo wp_nonce_url( add_query_arg( array( 'insert-vat-rates' => true ) ), 'wc-gzd-insert-vat-rates' ); ?>"
               class="button button-secondary"><?php _e( 'Renew VAT Rates', 'woocommerce-germanized' ); ?></a></td>
    </tr>
    <tr>
        <td><?php _e( 'Disable notices', 'woocommerce-germanized' ); ?></td>
        <td class="help"><?php echo wc_help_tip( esc_attr( __( 'Germanized might ask you to leave a review or notices you of using a possibly unsupported theme. If you want to disable these notices, check this option.', 'woocommerce-germanized' ) ) ); ?></td>
        <td><a href="<?php echo wp_nonce_url( add_query_arg( array( 'check-notices' => true ) ), 'wc-gzd-notices' ); ?>"
               class="button button-secondary"><?php echo 'yes' === get_option( 'woocommerce_gzd_disable_notices' ) ? __( 'Enable notices', 'woocommerce-germanized' ) : __( 'Disable notices', 'woocommerce-germanized' ); ?></a>
        </td>
    </tr>
    <?php if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) && ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) : ?>
    <tr>
        <td><?php _e( 'Encryption Key', 'woocommerce-germanized' ); ?></td>
        <td class="help"></td>
        <td><?php echo WC_GZD_Secret_Box_Helper::get_encryption_key_notice(); ?>
            <?php if ( WC_GZD_Secret_Box_Helper::supports_auto_insert() ) : ?>
                <a class="button button-primary" href="<?php echo wp_nonce_url( add_query_arg( array( 'insert-encryption-key' => true ) ), 'wc-gzd-insert-encryption-key' ); ?>"><?php _e( 'Auto insert', 'woocommerce-germanized' ); ?></a>
            <?php endif; ?>
            <a class="button button-secondary" href="https://vendidero.de/dokument/verschluesselung-sensibler-daten" target="_blank"><?php _e( 'Learn more', 'woocommerce-germanized' ); ?></a>
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
        <th colspan="3" data-export-label="Templates"><?php _e( 'Templates', 'woocommerce-germanized' ); ?></th>
    </tr>
    </thead>
    <tbody>
	<?php foreach ( WC_GZD_Admin::instance()->get_template_version_check_result() as $plugin => $data ) : ?>
        <tr>
            <td data-export-label="Overrides"><?php _e( 'Overrides', 'woocommerce-germanized' ); ?>
                :<br/><strong><?php echo esc_html( $data['title'] ); ?></strong></td>
            <td class="help">&nbsp</td>
            <td>
				<?php if ( ! empty( $data['files'] ) ) : ?>
					<?php foreach ( $data['files'] as $file ) : ?>
						<?php printf( '<code>%s</code>', str_replace( WP_CONTENT_DIR . '/themes/', '', $file['theme_file'] ) ); ?>

						<?php if ( $file['outdated'] ) : ?>
							<?php printf( __( 'Version %s is out of date. The core version is %s.', 'woocommerce-germanized' ), '<span class="red" style="color:red">' . $file['theme_version'] . '</span>', $file['core_version'] ); ?>
						<?php endif; ?>

                        <br/>
					<?php endforeach; ?>

					<?php if ( $data['has_outdated'] ) : ?>
                        <br/><a href="<?php echo esc_url( $data['outdated_help_url'] ); ?>"
                                target="_blank"><?php _e( 'Learn how to update outdated templates', 'woocommerce-germanized' ) ?></a>
					<?php endif; ?>
				<?php else: ?>
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
                    var the_value = jQuery.trim(jQuery(this).find('td:eq(2)').text());
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