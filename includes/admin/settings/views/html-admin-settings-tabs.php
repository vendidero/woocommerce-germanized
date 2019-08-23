<?php
/**
 * Admin View: Settings Tabs
 */
defined( 'ABSPATH' ) || exit;
?>

<h2 class="wc-gzd-setting-header">
	<?php _e( 'Germanized', 'woocommerce-germanized' ); ?>
</h2>

<p><?php echo __( 'Adjust your Germanized settings.', 'woocommerce-germanized' ); ?></p>

<table class="wc-gzd-setting-tabs widefat">
	<thead>
	<tr>
		<th class="wc-gzd-setting-tab-name"><?php esc_html_e( 'Name', 'woocommerce-germanized' ); ?></th>
        <th class="wc-gzd-setting-tab-enabled"><?php esc_html_e( 'Enabled', 'woocommerce-germanized' ); ?></th>
        <th class="wc-gzd-setting-tab-desc"><?php esc_html_e( 'Description', 'woocommerce-germanized' ); ?></th>
        <th class="wc-gzd-setting-tab-actions"></th>
	</tr>
	</thead>
	<tbody class="wc-gzd-setting-tab-rows">
        <?php foreach( $tabs as $tab_id => $tab ) : ?>
            <tr>
                <td class="wc-gzd-setting-tab-name"><a href="<?php echo $tab->get_link(); ?>" class="wc-gzd-setting-tab-link"><?php echo $tab->get_label(); ?></a></td>
                <td class="wc-gzd-setting-tab-enabled"><span class="status-enabled"><?php echo esc_attr__( 'Yes', 'woocommerce-germanized' ); ?></span></td>
                <td class="wc-gzd-setting-tab-desc"><?php echo $tab->get_description(); ?></td>
                <td class="wc-gzd-setting-tab-actions"><a class="button button-secondary" href="<?php echo $tab->get_link(); ?>"><?php _e( 'Adjust settings', 'woocommerce-germanized' ); ?></a></td>
            </tr>
        <?php endforeach; ?>
	</tbody>
</table>
