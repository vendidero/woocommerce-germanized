<?php
/**
 * Admin View: Generator Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$missing_count = 0;
$version_count = 0;

?>

<div id="message" class="error woocommerce-gzd-message wc-connect">
	<h3><?php echo esc_html_x( 'Dependencies Missing or Outdated', 'trusted-shops', 'woocommerce-germanized' ); ?></h3>
	<?php foreach ( $dependencies->plugins_required as $plugin => $data ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>

		<?php
		if ( ! $dependencies->is_plugin_activated( $plugin ) ) :
			$missing_count++;
			?>

			<?php if ( 1 === $missing_count ) : ?>

				<p><?php echo esc_html_x( 'To use WooCommerce Trusted Shops you may at first install the following plugins:', 'trusted-shops', 'woocommerce-germanized' ); ?></p>

			<?php endif; ?>

			<p><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=search&s=' . rawurlencode( $data['name'] ) ) ); ?>"><?php printf( esc_html_x( 'Install %s', 'trusted-shops', 'woocommerce-germanized' ), esc_html( $data['name'] ) ); ?></a></p>

		<?php endif; ?>

		<?php
		if ( $dependencies->is_plugin_outdated( $plugin ) ) :
			$version_count ++;
			?>
			<?php if ( 1 === $version_count ) : ?>
				<p><?php echo esc_html_x( 'To use WooCommerce Trusted Shops you may at first update the following plugins to a newer version:', 'trusted-shops', 'woocommerce-germanized' ); ?></p>
			<?php endif; ?>

			<p>- <?php printf( _x( '%1$s required in at least version %2$s', 'trusted-shops', 'woocommerce-germanized' ), esc_html( $data['name'] ), '<strong>' . esc_html( $data['version'] ) . '</strong>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

		<?php endif; ?>

	<?php endforeach; ?>

	<?php if ( $version_count > 0 ) : ?>

		<p>
			<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>"><?php echo esc_html_x( 'Check for Updates', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
			<?php echo esc_html_x( 'or', 'trusted-shops', 'woocommerce-germanized' ); ?>
			<a class="" href="https://wordpress.org/plugins/woocommerce-trusted-shops/developers/" target="_blank"><?php echo esc_html_x( 'Install an older version', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
		</p>

	<?php endif; ?>

</div>
