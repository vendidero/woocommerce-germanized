<?php
/**
 * Single Product Shipping Time Info
 *
 * @author   Vendidero
 * @package  WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<?php if ( $available_shipping = WC()->shipping->load_shipping_methods() ) : ?>

	<?php foreach ( $available_shipping as $method ) : ?>

		<?php if ( $method->enabled == 'yes' ) : ?>
			
			<?php echo ( method_exists( $method, 'get_html_table_costs' ) ) ? $method->get_html_table_costs() : ''; ?>

		<?php endif ;?>

	<?php endforeach ;?>

<?php endif; ?>