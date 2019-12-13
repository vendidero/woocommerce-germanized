<?php
/**
 * Order shipments HTML for meta box.
 *
 * @package WooCommerce_Germanized/Shipments/Admin
 */

use Vendidero\Germanized\Shipments\Shipment;

defined( 'ABSPATH' ) || exit;

?>

<div class="shipment-content">
    <div class="columns">
	    <?php
	    /**
	     * Action that fires before the first column of a Shipment's meta box is being outputted.
	     *
	     * @param Shipment $shipment The shipment object.
	     *
	     * @since 3.0.0
         * @package Vendidero/Germanized/Shipments
	     */
        do_action( 'woocommerce_gzd_shipment_admin_before_columns', $shipment ); ?>

        <div class="column col-6">
            <p class="form-row">
                <label for="shipment-weight-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php printf( _x( 'Weight (%s)', 'shipments', 'woocommerce-germanized' ), $shipment->get_weight_unit() ); ?></label>
                <input type="text" class="wc_input_decimal" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_weight( 'edit' ) ) ); ?>" name="shipment_weight[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-weight-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_weight() ) ); ?>" />
            </p>

            <p class="form-row dimensions_field">
                <label for="shipment-length-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php printf( _x( 'Dimensions (%s)', 'shipments', 'woocommerce-germanized' ), $shipment->get_dimension_unit() ); ?><?php echo wc_help_tip( _x( 'LxWxH in decimal form.', 'shipments', 'woocommerce-germanized' ) ); ?></label>

                <span class="wrap">
                    <input type="text" size="6" class="wc_input_decimal" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_length( 'edit' ) ) ); ?>" name="shipment_length[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-length-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_length() ) ); ?>" />
                    <input type="text" size="6" class="wc_input_decimal" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_width( 'edit' ) ) ); ?>" name="shipment_width[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-width-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_width() ) ); ?>" />
                    <input type="text" size="6" class="wc_input_decimal" value="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_height( 'edit' ) ) ); ?>" name="shipment_height[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-height-<?php echo esc_attr( $shipment->get_id() ); ?>" placeholder="<?php echo esc_attr( wc_format_localized_decimal( $shipment->get_content_height() ) ); ?>" />
                </span>
            </p>

            <div class="columns">
	            <?php

                if ( $shipment->supports_label() && ( ( $label = $shipment->get_label() ) || $shipment->needs_label() ) ) :
                    include 'label/html-shipment-label.php';
                endif;
                ?>

	            <?php
	            /**
	             * Action that fires after the right column of a Shipment's meta box admin view.
	             *
	             * @param Shipment $shipment The shipment object.
	             *
	             * @since 3.0.0
                 * @package Vendidero/Germanized/Shipments
	             */
                do_action( 'woocommerce_gzd_shipments_meta_box_shipment_after_right_column', $shipment ); ?>
            </div>
        </div>

        <div class="column col-6">
            <p class="form-row">
                <label for="shipment-status-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo _x( 'Status', 'shipments', 'woocommerce-germanized' ); ?></label>
                <select class="shipment-status-select" id="shipment-status-<?php echo esc_attr( $shipment->get_id() ); ?>" name="shipment_status[<?php echo esc_attr( $shipment->get_id() ); ?>]">
				    <?php foreach( wc_gzd_get_shipment_selectable_statuses( $shipment->get_type() ) as $status => $title ) : ?>
                        <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, 'gzd-' . $shipment->get_status(), true ); ?>><?php echo $title; ?></option>
				    <?php endforeach; ?>
                </select>
            </p>

		    <?php if ( sizeof( $shipment->get_available_shipping_methods() ) > 1 ) : ?>
                <p class="form-row">
                    <label for="shipment-shipping-method-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo _x( 'Shipping method', 'shipments', 'woocommerce-germanized' ); ?></label>
                    <select class="shipment-shipping-method-select" id="shipment-shipping-method-<?php echo esc_attr( $shipment->get_id() ); ?>" name="shipment_shipping_method[<?php echo esc_attr( $shipment->get_id() ); ?>]">
					    <?php foreach( $shipment->get_available_shipping_methods() as $method => $title ) : ?>
                            <option value="<?php echo esc_attr( $method ); ?>" <?php selected( $method, $shipment->get_shipping_method(), true ); ?>><?php echo $title; ?></option>
					    <?php endforeach; ?>
                    </select>
                </p>
		    <?php endif; ?>

            <p class="form-row">
                <label for="shipment-shipping-provider-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo _x( 'Shipping provider', 'shipments', 'woocommerce-germanized' ); ?></label>
                <select class="shipment-shipping-provider-select" id="shipment-shipping-provider-<?php echo esc_attr( $shipment->get_id() ); ?>" name="shipment_shipping_provider[<?php echo esc_attr( $shipment->get_id() ); ?>]">
                    <?php foreach( wc_gzd_get_shipping_provider_select() as $provider => $title ) :
                        $provider_instance = wc_gzd_get_shipping_provider( $provider );
                        ?>
                        <option data-is-manual="<?php echo ( ( $provider_instance && $provider_instance->is_manual_integration() ) ? 'yes' : 'no' ); ?>" value="<?php echo esc_attr( $provider ); ?>" <?php selected( $provider, $shipment->get_shipping_provider(), true ); ?>><?php echo $title; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p class="form-row show-if show-if-provider show-if-provider-is-manual">
                <label for="shipment-tracking-id-<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo _x( 'Tracking Number', 'shipments', 'woocommerce-germanized' ); ?></label>
                <input type="text" value="<?php echo esc_attr( $shipment->get_tracking_id() ); ?>" name="shipment_tracking_id[<?php echo esc_attr( $shipment->get_id() ); ?>]" id="shipment-tracking-id-<?php echo esc_attr( $shipment->get_id() ); ?>" />
            </p>

            <div class="shipment-items" id="shipment-items-<?php echo esc_attr( $shipment->get_id() ); ?>">
                <div class="shipment-item-list-wrapper">
                    <div class="shipment-item-heading">
                        <div class="columns">
                            <div class="column col-7 shipment-item-name">
							    <?php echo _x( 'Item', 'shipments', 'woocommerce-germanized' ); ?>
                            </div>
                            <div class="column col-2 shipment-item-quantity">
							    <?php echo _x( 'Quantity', 'shipments', 'woocommerce-germanized' ); ?>
                            </div>
                            <div class="column col-3 shipment-item-action">
							    <?php echo _x( 'Actions', 'shipments', 'woocommerce-germanized' ); ?>
                            </div>
                        </div>
                    </div>

                    <div class="shipment-item-list">
					    <?php foreach( $shipment->get_items() as $item ) : ?>
						    <?php include 'html-order-shipment-item.php'; ?>
					    <?php endforeach; ?>
                    </div>
                </div>

                <div class="shipment-item-actions">
                    <div class="add-items">
                        <a class="add-shipment-item" href="#"><?php echo _x( 'Add item', 'shipments', 'woocommerce-germanized' ); ?></a>
                    </div>

                    <div class="sync-items">
                        <a class="sync-shipment-items" href="#"><?php echo _x( 'Sync items', 'shipments', 'woocommerce-germanized' ); ?></a>
					    <?php echo wc_help_tip( _x( 'Automatically adjust items and quantities based on order item data.', 'shipments', 'woocommerce-germanized' ) ); ?>
                    </div>

				    <?php
				    /**
				     * Action that fires in the item action container of a Shipment's meta box admin view.
				     *
				     * @param Shipment $shipment The shipment object.
				     *
				     * @since 3.0.0
                     * @package Vendidero/Germanized/Shipments
				     */
				    do_action( 'woocommerce_gzd_shipments_meta_box_shipment_item_actions', $shipment ); ?>
                </div>
            </div>

            <script type="text/template" id="tmpl-wc-gzd-modal-add-shipment-item-<?php echo esc_attr( $shipment->get_id() ); ?>">
                <div class="wc-backbone-modal">
                    <div class="wc-backbone-modal-content">
                        <section class="wc-backbone-modal-main" role="main">
                            <header class="wc-backbone-modal-header">
                                <h1><?php echo esc_html_x( 'Add Item', 'shipments', 'woocommerce-germanized' ); ?></h1>
                                <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                                    <span class="screen-reader-text">Close modal panel</span>
                                </button>
                            </header>
                            <article>
                                <form action="" method="post">
                                    <table class="widefat">
                                        <thead>
                                        <tr>
                                            <th><?php echo esc_html_x( 'Item', 'shipments', 'woocommerce-germanized' ); ?></th>
                                            <th><?php echo esc_html_x( 'Quantity', 'shipments', 'woocommerce-germanized' ); ?></th>
                                        </tr>
                                        </thead>
									    <?php
									    $row = '
									        <td><select id="wc-gzd-shipment-add-items-select" name="item_id"></select></td>
									        <td><input id="wc-gzd-shipment-add-items-quantity" type="number" step="1" min="0" max="9999" autocomplete="off" name="item_qty" placeholder="1" size="4" class="quantity" /></td>';
									    ?>
                                        <tbody data-row="<?php echo esc_attr( $row ); ?>">
                                        <tr>
										    <?php echo $row; // WPCS: XSS ok. ?>
                                        </tr>
                                        </tbody>
                                    </table>
                                </form>
                            </article>
                            <footer>
                                <div class="inner">
                                    <button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Add', 'shipments', 'woocommerce-germanized' ); ?></button>
                                </div>
                            </footer>
                        </section>
                    </div>
                </div>
                <div class="wc-backbone-modal-backdrop modal-close"></div>
            </script>

		    <?php
		    /**
		     * Action that fires after the left column of a Shipment's meta box admin view.
		     *
		     * @param Shipment $shipment The shipment object.
		     *
		     * @since 3.0.0
             * @package Vendidero/Germanized/Shipments
		     */
		    do_action( 'woocommerce_gzd_shipments_meta_box_shipment_after_left_column', $shipment ); ?>
        </div>

        <?php if ( 'simple' === $shipment->get_type() ) : ?>

            <script type="text/template" id="tmpl-wc-gzd-modal-add-shipment-return-<?php echo esc_attr( $shipment->get_id() ); ?>">
                <div class="wc-backbone-modal">
                    <div class="wc-backbone-modal-content">
                        <section class="wc-backbone-modal-main" role="main">
                            <header class="wc-backbone-modal-header">
                                <h1><?php echo esc_html_x( 'Add Return', 'shipments', 'woocommerce-germanized' ); ?></h1>
                                <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                                    <span class="screen-reader-text">Close modal panel</span>
                                </button>
                            </header>
                            <article>
                                <form action="" method="post">
                                    <table class="widefat">
                                        <thead>
                                        <tr>
                                            <th><?php echo esc_html_x( 'Item', 'shipments', 'woocommerce-germanized' ); ?></th>
                                            <th><?php echo esc_html_x( 'Quantity', 'shipments', 'woocommerce-germanized' ); ?></th>
                                        </tr>
                                        </thead>
                                        <tbody id="wc-gzd-return-shipment-items" data-row="<?php echo esc_attr( $row ); ?>"></tbody>
                                    </table>
                                </form>
                            </article>
                            <footer>
                                <div class="inner">
                                    <button id="btn-ok" class="button button-primary button-large"><?php echo esc_html_x( 'Add', 'shipments', 'woocommerce-germanized' ); ?></button>
                                </div>
                            </footer>
                        </section>
                    </div>
                </div>
                <div class="wc-backbone-modal-backdrop modal-close"></div>
            </script>

            <div class="column col-12 shipment-returns-data">
                <div class="shipment-returns">
                    <div class="shipment-return-list">
                        <?php
                        $global_shipment = $shipment;

                        foreach( $shipment->get_returns() as $return ) :
                            $shipment = $return;
                            ?>
                            <?php include 'html-order-shipment.php'; ?>
                        <?php endforeach;
                        $shipment = $global_shipment;
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php
        /**
         * Action that fires after the fields of a Shipment's meta box admin view have been rendered.
         *
         * @param Shipment $shipment The shipment object.
         *
         * @since 3.0.0
         * @package Vendidero/Germanized/Shipments
         */
        do_action( 'woocommerce_gzd_shipments_meta_box_shipment_after_fields', $shipment ); ?>

        <div class="column col-12 shipment-footer" id="shipment-footer-<?php echo esc_attr( $shipment->get_id() ); ?>">
	        <?php if ( 'simple' === $shipment->get_type() ) : ?>
                <a class="add-shipment-return" href="#" data-id="<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo _x( 'Add Return', 'shipments', 'woocommerce-germanized' ); ?></a>
	        <?php endif; ?>

            <?php if ( $shipment->is_editable() ) : ?>
                <a class="remove-shipment delete" href="#" data-id="<?php echo esc_attr( $shipment->get_id() ); ?>"><?php echo sprintf( _x( 'Delete %s', 'shipments', 'woocommerce-germanized' ), wc_gzd_get_shipment_label( $shipment->get_type() ) ); ?></a>
            <?php endif; ?>

	        <?php
	        /**
	         * Action that fires in the shipment action container of a Shipment's meta box admin view.
	         *
	         * @param Shipment $shipment The shipment object.
	         *
	         * @since 3.0.0
             * @package Vendidero/Germanized/Shipments
	         */
            do_action( 'woocommerce_gzd_shipments_meta_box_shipment_actions', $shipment ); ?>
        </div>
    </div>
</div>
