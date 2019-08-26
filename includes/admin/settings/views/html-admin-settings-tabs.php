<?php
/**
 * Admin View: Settings Tabs
 */
defined( 'ABSPATH' ) || exit;
?>

<h2 class="wc-gzd-setting-header">
	<?php _e( 'Germanized', 'woocommerce-germanized' ); ?>
</h2>

<p class="tab-description"><?php echo __( 'Adjust your Germanized settings.', 'woocommerce-germanized' ); ?></p>

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
                <td class="wc-gzd-setting-tab-enabled">
                    <?php if ( $tab->supports_disabling() ) : ?>
                        <a class="wc-gzd-settings-tab-toggle-<?php echo ( $tab->is_enabled() ? 'enabled' : 'disabled' ); ?>" href="#"><span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo ( $tab->is_enabled() ? 'enabled' : 'disabled' ); ?>"><?php echo esc_attr__( 'Yes', 'woocommerce-germanized' ); ?></span></a>
                    <?php else: ?>
                        <span class="status-enabled"><?php echo esc_attr__( 'Yes', 'woocommerce-germanized' ); ?></span>
                    <?php endif; ?>
                </td>
                <td class="wc-gzd-setting-tab-desc"><?php echo $tab->get_description(); ?></td>
                <td class="wc-gzd-setting-tab-actions"><a class="button button-secondary" href="<?php echo $tab->get_link(); ?>"><?php _e( 'Manage', 'woocommerce-germanized' ); ?></a></td>
            </tr>
        <?php endforeach; ?>
	</tbody>
</table>
