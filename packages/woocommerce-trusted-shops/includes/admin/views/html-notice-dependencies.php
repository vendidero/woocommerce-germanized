<?php
/**
 * Admin View: Generator Editor
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

$missing_count = 0;
$version_count = 0;

?>

<div id="message" class="error woocommerce-gzd-message wc-connect">
	
	<h3><?php echo _x( 'Dependencies Missing or Outdated', 'trusted-shops', 'woocommerce-germanized' );?></h3>

	<?php foreach ( $dependencies->plugins_required as $plugin => $data ) : ?>

		<?php if ( ! $dependencies->is_plugin_activated( $plugin ) ) : $missing_count++ ?>

			<?php if ( $missing_count == 1 ) : ?>

				<p><?php echo _x( 'To use WooCommerce Trusted Shops you may at first install the following plugins:', 'trusted-shops', 'woocommerce-germanized' ); ?></p>

			<?php endif; ?>

			<p><a class="button button-secondary" href="<?php echo admin_url( "plugin-install.php?tab=search&s=" . urlencode( $data[ 'name' ] ) ); ?>"><?php printf( _x( 'Install %s', 'trusted-shops', 'woocommerce-germanized' ), $data[ 'name' ] ); ?></a></p>

		<?php endif; ?>

		<?php if ( $dependencies->is_plugin_outdated( $plugin ) ) : $version_count ++ ?>

			<?php if ( $version_count == 1 ) : ?>

				<p><?php echo _x( 'To use WooCommerce Trusted Shops you may at first update the following plugins to a newer version:', 'trusted-shops', 'woocommerce-germanized' ); ?></p>

			<?php endif; ?>

			<p>- <?php printf( _x( '%s required in at least version %s', 'trusted-shops', 'woocommerce-germanized' ), $data[ 'name' ], '<strong>' . $data[ 'version' ] . '</strong>' ); ?></p>

		<?php endif; ?>

	<?php endforeach; ?>

	<?php if ( $version_count > 0 ) : ?>

		<p>
			<a class="button button-secondary" href="<?php echo admin_url( "update-core.php" ); ?>"><?php echo _x( 'Check for Updates', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
			<?php echo _x( 'or', 'trusted-shops', 'woocommerce-germanized' ); ?>
			<a class="" href="https://wordpress.org/plugins/woocommerce-trusted-shops/developers/" target="_blank"><?php echo _x( 'Install an older version', 'trusted-shops', 'woocommerce-germanized' ); ?></a>
		</p>

	<?php endif; ?>

</div>
