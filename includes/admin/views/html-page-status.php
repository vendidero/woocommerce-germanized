<?php
/**
 * Admin View: Page - Status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : 'status';
?>
<div class="wrap woocommerce">
	<div class="icon32 icon32-woocommerce-status" id="icon-woocommerce"><br /></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php
			$tabs = apply_filters( 'woocommerce_gzd_admin_status_tabs', array(
				'status' 	 => __( 'System Status', 'woocommerce-germanized' ),
				'tools'  	 => __( 'Tools', 'woocommerce-germanized' ),
				'logs'   	 => __( 'Logs', 'woocommerce-germanized' ),
				'germanized' => __( 'Germanized', 'woocommerce-germanized' ),
			) );
			foreach ( $tabs as $name => $label ) {
				echo '<a href="' . admin_url( 'admin.php?page=wc-status&tab=' . $name ) . '" class="nav-tab ';
				if ( $current_tab == $name ) echo 'nav-tab-active';
				echo '">' . $label . '</a>';
			}
		?>
	</h2><br/>
	<?php
		switch ( $current_tab ) {
			case "tools" :
				WC_GZD_Admin_Status::status_tools();
			break;
			case "logs" :
				WC_GZD_Admin_Status::status_logs();
			break;
			case "germanized" :
				WC_GZD_Admin_Status::germanized();
			break;
			case "status": 
			case "":
				WC_GZD_Admin_Status::status_report();
			break;
			default :
				WC_GZD_Admin_Status::status_default( $current_tab );
			break;
		}
	?>
</div>