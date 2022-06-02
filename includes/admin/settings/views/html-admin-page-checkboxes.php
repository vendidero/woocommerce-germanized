<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<table class="wc-gzd-legal-checkboxes widefat">
	<thead>
	<tr>
		<th class="wc-gzd-legal-checkbox-sort"><?php echo wc_help_tip( __( 'Drag and drop to re-order checkboxes. This is the order being used for printing the fields.', 'woocommerce-germanized' ) ); ?></th>
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
			<a href="admin.php?page=wc-settings&amp;tab=germanized-checkboxes&amp;checkbox_id={{ data.id }}">{{
				data.admin_name }}</a>
			<div class="row-actions">
				<a href="admin.php?page=wc-settings&amp;tab=germanized-checkboxes&amp;checkbox_id={{ data.id }}"><?php esc_html_e( 'Edit', 'woocommerce-germanized' ); ?></a>
				<span class="sep">|</span> <a href="#" class="wc-gzd-legal-checkbox-delete"><?php esc_html_e( 'Delete', 'woocommerce-germanized' ); ?></a>
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
