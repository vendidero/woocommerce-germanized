<?php
/**
 * Trusted Shops Trustbadge
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<script type="text/javascript">
    /**
     * Modul: WooCommerce Germanized
     */
	<?php echo WC_germanized()->trusted_shops->get_trustbadge_code(); ?>
</script>