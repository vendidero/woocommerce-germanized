<?php
/**
 * The Template for displaying shipping costs information.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/global/shipping-costs.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

?>
<?php if ( $available_shipping = WC()->shipping->load_shipping_methods() ) : ?>
	<?php foreach ( $available_shipping as $method ) : ?>
		<?php if ( 'yes' === $method->enabled ) : ?>
			<?php echo ( method_exists( $method, 'get_html_table_costs' ) ) ? wp_kses_post( $method->get_html_table_costs() ) : ''; ?>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php
endif;
