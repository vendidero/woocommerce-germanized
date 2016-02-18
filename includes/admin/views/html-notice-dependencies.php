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
	
	<h3><?php _e( 'Dependencies Missing or Outdated', 'woocommerce-germanized' );?></h3>

	<?php foreach ( $dependencies->plugins_required as $plugin => $data ) : ?>

		<?php if ( ! $dependencies->is_plugin_activated( $plugin ) ) : $missing_count++ ?>

			<?php if ( $missing_count == 1 ) : ?>

				<p><?php _e( 'To use WooCommerce Germanized you may at first install the following plugins:', 'woocommerce-germanized' ); ?></p>

			<?php endif; ?>

			<p><a class="button button-secondary" href="<?php echo admin_url( "plugin-install.php?tab=search&s=" . urlencode( $data[ 'name' ] ) ); ?>"><?php printf( __( 'Install %s', 'woocommerce-germanized' ), $data[ 'name' ] ); ?></a></p>

		<?php endif; ?>

		<?php if ( $dependencies->is_plugin_outdated( $plugin ) ) : $version_count ++ ?>

			<?php if ( $version_count == 1 ) : ?>

				<p><?php _e( 'To use WooCommerce Germanized you may at first update the following plugins to a newer version:', 'woocommerce-germanized' ); ?></p>

			<?php endif; ?>

			<p>- <?php printf( __( '%s required in at least version %s', 'woocommerce-germanized' ), $data[ 'name' ], '<strong>' . $data[ 'version' ] . '</strong>' ); ?></p>

		<?php endif; ?>

	<?php endforeach; ?>

	<?php if ( $version_count > 0 ) : ?>

		<p>
			<a class="button button-secondary" href="<?php echo admin_url( "update-core.php" ); ?>"><?php _e( 'Check for Updates', 'woocommerce-germanized' ); ?></a>
			<?php _e( 'or', 'woocommerce-germanized' ); ?>
			<a class="" href="https://wordpress.org/plugins/woocommerce-germanized/developers/" target="_blank"><?php _e( 'Install an older version', 'woocommerce-germanized' ); ?></a>
		</p>

	<?php endif; ?>

</div>