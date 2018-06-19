<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="wc-gzd-legal-checkboxes-heading">
	<?php _e( 'Checkboxes', 'woocommerce-germanized' ); ?>
	<a href="<?php echo apply_filters( 'woocommerce_gzd_admin_new_legal_checkbox_link', 'https://vendidero.de/woocommerce-germanized' ); ?>" class="page-title-action wc-gzd-disabled-button" target="<?php echo ( ! WC_germanized()->is_pro() ? '_blank' : '_self' ); ?>"><?php esc_html_e( 'Add checkbox', 'woocommerce-germanized' ); ?> <?php echo ( ! WC_germanized()->is_pro() ? '<span class="wc-gzd-premium-section-tab">pro</span>' : '' ); ?></a>
</h2>
<p><?php echo __( 'Legal checkboxes are being used to ask the customer for a certain permission or action (e.g. to accept terms & conditions) before the checkout or another form may be completed.', 'woocommerce-germanized' ); ?></p>
<table class="wc-gzd-legal-checkboxes widefat">
	<thead>
	<tr>
		<th class="wc-gzd-legal-checkbox-sort"><?php echo wc_gzd_help_tip( __( 'Drag and drop to re-order checkboxes. This is the order being used for printing the fields.', 'woocommerce-germanized' ) ); ?></th>
        <th class="wc-gzd-legal-checkbox-name"><?php esc_html_e( 'Name', 'woocommerce-germanized' ); ?></th>
        <th class="wc-gzd-legal-checkbox-desc"><?php esc_html_e( 'Description', 'woocommerce-germanized' ); ?></th>
        <th class="wc-gzd-legal-checkbox-enabled"><?php esc_html_e( 'Enabled', 'woocommerce-germanized' ); ?></th>
        <th class="wc-gzd-legal-checkbox-mandatory"><?php esc_html_e( 'Mandatory', 'woocommerce-germanized' ); ?></th>
		<th class="wc-gzd-legal-checkbox-locations"><?php esc_html_e( 'Location(s)', 'woocommerce-germanized' ); ?></th>
	</tr>
	</thead>
	<tbody class="wc-gzd-legal-checkbox-rows"></tbody>
</table>

<script type="text/html" id="tmpl-wc-gzd-legal-checkbox-row">
	<tr data-id="{{ data.id }}">
		<td width="1%" class="wc-gzd-legal-checkbox-sort"></td>
		<td class="wc-gzd-legal-checkbox-name">
			<a href="admin.php?page=wc-settings&amp;tab=germanized&amp;section=checkboxes&amp;checkbox_id={{ data.id }}">{{ data.admin_name }}</a>
			<div class="row-actions">
                <a href="admin.php?page=wc-settings&amp;tab=germanized&amp;section=checkboxes&amp;checkbox_id={{ data.id }}"><?php _e( 'Edit', 'woocommerce-germanized' ); ?></a> <span class="sep">|</span> <a href="#" class="wc-gzd-legal-checkbox-delete"><?php _e( 'Delete', 'woocommerce-germanized' ); ?></a>
			</div>
		</td>
        <td class="wc-gzd-legal-checkbox-desc">
            {{ data.admin_desc }}
        </td>
        <td class="wc-gzd-legal-checkbox-enabled">

        </td>
        <td class="wc-gzd-legal-checkbox-mandatory">

        </td>
        <td class="wc-gzd-legal-checkbox-locations">
            <ul></ul>
		</td>
	</tr>
</script>
