<?php
/**
 * The Template for displaying available shipments for an order on the myaccount page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/myaccount/shipments.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Shipments/Templates/Emails/Plain
 * @version 1.1.3
 */
use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

/*
 * Action that fires befoure outputting a list of shipments belonging to a specific order on the customer account page.
 *
 * @param \Vendidero\Germanized\Shipments\Shipment[] $shipments The shipment instances.
 * @param WC_Order                                   $order The order instance.
 *
 * @since 3.0.0
 * @package Vendidero/Germanized/Shipments
 */
do_action( 'woocommerce_gzd_before_account_shipments', $shipments, $order ); ?>

<table class="woocommerce-shipments-table woocommerce-MyAccount-shipments woocommerce-MyAccount-<?php echo esc_attr( $type ); ?>-shipments shop_table shop_table_responsive my_account_shipments account-shipments-table">
	<thead>
	<tr>
		<?php foreach ( wc_gzd_get_account_shipments_columns( $type ) as $column_id => $column_name ) : ?>
			<th class="woocommerce-shipments-table__header woocommerce-shipments-table__header-<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
		<?php endforeach; ?>
	</tr>
	</thead>

	<tbody>
	<?php
	foreach ( $shipments as $shipment ) {
		$item_count = $shipment->get_item_count();
		?>
		<tr class="woocommerce-shipments-table__row woocommerce-shipments-table__row--status-<?php echo esc_attr( $shipment->get_status() ); ?> shipment">
			<?php foreach ( wc_gzd_get_account_shipments_columns( $shipment->get_type() ) as $column_id => $column_name ) : ?>
				<td class="woocommerce-shipments-table__cell woocommerce-shipments-table__cell-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
					<?php if ( has_action( 'woocommerce_gzd_my_account_order_shipments_column_' . $column_id ) ) : ?>
						<?php
						/*
						 * Action that fires befoure outputting a specific column in the shipments table view
						 * on the customer account page.
						 *
						 * The dynamic portion of the hook `$column_id` refers to the current column id being rendered
						 * e.g. shipment-number.
						 *
						 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment instance.
						 * @param WC_Order                                 $order The order instance.
						 *
						 * @since 3.0.0
						 * @package Vendidero/Germanized/Shipments
						 */
						do_action( 'woocommerce_gzd_my_account_shipments_column_' . $column_id, $shipment, $order );
						?>

					<?php elseif ( 'shipment-number' === $column_id ) : ?>
						<a href="<?php echo esc_url( $shipment->get_view_shipment_url() ); ?>">
							<?php echo esc_html( sprintf( _x( '%1$s #%2$s', 'shipment title', 'woocommerce-germanized' ), wc_gzd_get_shipment_label_title( $shipment->get_type() ), $shipment->get_shipment_number() ) ); ?>
						</a>

					<?php elseif ( 'shipment-date' === $column_id ) : ?>
						<time datetime="<?php echo esc_attr( $shipment->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $shipment->get_date_created() ) ); ?></time>

					<?php elseif ( 'shipment-status' === $column_id ) : ?>
						<?php echo esc_html( wc_gzd_get_shipment_status_name( $shipment->get_status() ) ); ?>

					<?php elseif ( 'shipment-tracking' === $column_id && $shipment->has_tracking() && ! $shipment->has_status( 'delivered' ) ) : ?>
						<a href="<?php echo esc_url( $shipment->get_tracking_url() ); ?>" target="_blank">
							<?php echo esc_html( _x( 'track now', 'shipments', 'woocommerce-germanized' ) ); ?>
						</a>

					<?php elseif ( 'shipment-actions' === $column_id ) : ?>
						<?php
						$actions = wc_gzd_get_account_shipments_actions( $shipment );

						if ( ! empty( $actions ) ) {
							foreach ( $actions as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
								echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button button ' . sanitize_html_class( $key ) . esc_attr( wc_gzd_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_gzd_wp_theme_get_element_class_name( 'button' ) : '' ) . '">' . esc_html( $action['name'] ) . '</a>';
							}
						}
						?>
					<?php endif; ?>
				</td>
			<?php endforeach; ?>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>

<?php
/**
 * This action is executed after listing all available shipments for an order
 * on the customer account page.
 *
 * @param Shipment[] $shipments Array of shipments.
 *
 * @since 3.0.0
 * @package Vendidero/Germanized/Shipments
 */
do_action( 'woocommerce_gzd_after_account_shipments', $shipments ); ?>
