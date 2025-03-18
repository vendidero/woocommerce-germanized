window.shiptastic = window.shiptastic || {};
window.shiptastic.admin = window.shiptastic.admin || {};

( function( $, shipments ) {

    $.shipmentsShipment = function( shipmentId ) {

        /*
         * Variables accessible
         * in the class
         */
        this.vars = {
            $shipment    : false,
            params       : {},
            id           : '',
            isEditable   : true,
            needsItems   : true,
            addItemModal : false,
            modals       : [],
        };

        /*
         * Can access this.method
         * inside other methods using
         * root.method()
         */
        this.root = this;

        /*
         * Constructor
         */
        this.construct = function( shipmentId ) {
            this.vars.id        = shipmentId;
            this.vars.params    = shipments.admin.shipments.getParams();

            this.refreshDom();
        };

        this.refreshDom = function() {
            var self = this;

            self.vars.$shipment  = $( '#order-shipments-list' ).find( '#shipment-' + self.getId() );

            self.setNeedsItems( self.vars.$shipment.hasClass( 'needs-items' ) );
            self.setIsEditable( self.vars.$shipment.hasClass( 'is-editable' ) );
            self.onChangeProvider();

            $.each( self.vars.modals, function( i, modal ) {
                modal.destroy();
            } );

            self.vars.modals = [];
            $modals = $( '#shipment-' + self.vars.id + ' a.has-shipment-modal' ).wc_shiptastic_admin_shipment_modal();

            $modals.each( function() {
                self.vars.modals.push( $( this ).data( 'self' ) );
            } );

            $( '#shipment-' + self.vars.id + ' #shipment-items-' + self.vars.id ).off( '.wc-stc-shipment' );
            $( '#shipment-' + self.vars.id + ' #shipment-footer-' + self.vars.id ).off( '.wc-stc-shipment' );
            $( '#shipment-' + self.vars.id + ' #shipment-shipping-provider-' + self.vars.id ).off( '.wc-stc-shipment' );
            $( '#shipment-' + self.vars.id + ' #shipment-packaging-' + self.vars.id ).off( '.wc-stc-shipment' );
            $( '#shipment-' + self.vars.id + ' .wc-stc-shipment-label' ).off( '.wc-stc-shipment' );

            $( '#shipment-' + self.vars.id + ' #shipment-shipping-provider-' + self.vars.id ).on( 'change', self.onChangeProvider.bind( self ) );
            $( '#shipment-' + self.vars.id + ' #shipment-packaging-' + self.vars.id ).on( 'change', self.refreshDimensions.bind( self ) );

            $( '#shipment-' + self.vars.id + ' #shipment-items-' + self.vars.id )
                .on( 'change.wc-stc-shipment', '.item-quantity', self.onChangeQuantity.bind( self ) )
                .on( 'click.wc-stc-shipment', 'a.remove-shipment-item', self.onRemoveItem.bind( self ) )
                .on( 'wc_shiptastic_admin_shipment_modal_after_load_success.wc-stc-shipment', 'a.add-shipment-item', self.onLoadedItemsSuccess.bind( self ) )
                .on( 'wc_shiptastic_admin_shipment_modal_after_submit_success.wc-stc-shipment', 'a.add-shipment-item', self.onAddedItem.bind( self ) )
                .on( 'click.wc-stc-shipment', 'a.sync-shipment-items', self.onSyncItems.bind( self ) );

            $( '#shipment-' + self.vars.id + ' #shipment-footer-' + self.vars.id )
                .on( 'click.wc-stc-shipment', '.send-return-shipment-notification', self.onSendReturnNotification.bind( self ) )
                .on( 'click.wc-stc-shipment', '.confirm-return-shipment', self.onConfirmReturnRequest.bind( self ) );

            $( '#shipment-' + self.vars.id + ' .wc-stc-shipment-label' ).on( 'click.wc-stc-shipment', '.remove-shipment-label', self.onRemoveLabel.bind( self ) );
        };

        this.refreshDimensions = function() {
            var $shipment = this.getShipment(),
                $select   = $shipment.find( '#shipment-packaging-' + this.getId() ),
                $selected = $select.find( 'option:selected' );

            // No packaging selected - allow manual dimension control
            if ( '' === $selected.val() ) {
                $shipment.find( '#shipment-length-' + this.getId() ).removeClass( 'disabled' ).prop( 'disabled', false );
                $shipment.find( '#shipment-length-' + this.getId() ).val( '' );

                $shipment.find( '#shipment-width-' + this.getId() ).removeClass( 'disabled' ).prop( 'disabled', false );
                $shipment.find( '#shipment-width-' + this.getId() ).val( '' );

                $shipment.find( '#shipment-height-' + this.getId() ).removeClass( 'disabled' ).prop( 'disabled', false );
                $shipment.find( '#shipment-height-' + this.getId() ).val( '' );
            } else {
                $shipment.find( '#shipment-length-' + this.getId() ).addClass( 'disabled' ).prop( 'disabled', true );
                $shipment.find( '#shipment-length-' + this.getId() ).val( $selected.data( 'length' ) );

                $shipment.find( '#shipment-width-' + this.getId() ).addClass( 'disabled' ).prop( 'disabled', true );
                $shipment.find( '#shipment-width-' + this.getId() ).val( $selected.data( 'width' ) );

                $shipment.find( '#shipment-height-' + this.getId() ).addClass( 'disabled' ).prop( 'disabled', true );
                $shipment.find( '#shipment-height-' + this.getId() ).val( $selected.data( 'height' ) );
            }
        };

        this.blockPackaging = function() {
            this.getShipmentContent().find( '.wc-stc-shipment-packaging-wrapper' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        };

        this.unblockPackaging = function() {
            this.getShipmentContent().find( '.wc-stc-shipment-packaging-wrapper' ).unblock();
        };

        this.refreshPackaging = function() {
            var params = {
                'action'       : 'woocommerce_stc_refresh_shipment_packaging',
                'shipment_id'  : this.getId(),
                'security'     : shipments.admin.shipments.getParams().refresh_packaging_nonce
            };

            this.blockPackaging();
            shipments.admin.shipments.doAjax( params, this.unblockPackaging.bind( this ), this.unblockPackaging.bind( this ) );
        };

        this.onSendReturnNotification = function() {
            var params = {
                'action'       : 'woocommerce_stc_send_return_shipment_notification_email',
                'shipment_id'  : this.getId(),
                'security'     : shipments.admin.shipments.getParams().send_return_notification_nonce
            };

            this.block();
            shipments.admin.shipments.doAjax( params, this.unblock.bind( this ), this.unblock.bind( this ) );

            return false;
        };

        this.onConfirmReturnRequest = function() {
            var params = {
                'action'       : 'woocommerce_stc_confirm_return_request',
                'shipment_id'  : this.getId(),
                'security'     : shipments.admin.shipments.getParams().confirm_return_request_nonce
            };

            this.block();
            shipments.admin.shipments.doAjax( params, this.unblock.bind( this ), this.unblock.bind( this ) );

            return false;
        };

        this.onRemoveLabel = function() {
            var answer = window.confirm( shipments.admin.shipments.getParams().i18n_remove_label_notice );

            if ( answer ) {
                this.removeLabel();
            }

            return false;
        };

        this.removeLabel = function() {
            var params = {
                'action'       : 'woocommerce_stc_remove_shipment_label',
                'shipment_id'  : this.getId(),
                'security'     : shipments.admin.shipments.getParams().remove_label_nonce
            };

            this.block();
            shipments.admin.shipments.doAjax( params, this.unblock.bind( this ), this.unblock.bind( this ) );
        };

        this.onChangeProvider = function() {
            var $shipment = this.getShipment(),
                $select   = $shipment.find( '#shipment-shipping-provider-' + this.getId() ),
                $selected = $select.find( 'option:selected' );

            $shipment.find( '.show-if-provider' ).hide();

            if ( $selected.length > 0 && $selected.data( 'is-manual' ) && 'yes' === $selected.data( 'is-manual' ) ) {
                $shipment.find( '.show-if-provider-is-manual' ).show();
            }

            $shipment.find( '.show-if-provider-' + $select.val() ).show();
        };

        this.getShipment = function() {
            return this.vars.$shipment;
        };

        this.getShipmentContent = function() {
            return this.vars.$shipment.find( '> .shipment-content-wrapper > .shipment-content > .columns > div:not(.shipment-returns-data)' );
        };

        this.onChangeQuantity = function( e ) {
            var $quantity   = $( e.target ),
                $item       = $quantity.parents( '.shipment-item' ),
                itemId      = $item.data( 'id' ),
                newQuantity = $quantity.val();

            this.blockItems();

            var params = {
                'action'       : 'woocommerce_stc_limit_shipment_item_quantity',
                'shipment_id'  : this.getId(),
                'item_id'      : itemId,
                'quantity'     : newQuantity
            };

            shipments.admin.shipments.doAjax( params, this.onChangeQuantitySuccess.bind( this ) );
        };

        this.onChangeQuantitySuccess = function( data ) {
            var $item       = this.getShipment().find( '.shipment-item[data-id="' + data.item_id + '"]' ),
                currentQty  = $item.find( '.item-quantity' ).val(),
                maxQuantity = data.max_quantity;

            if ( currentQty > maxQuantity ) {
                $item.find( '.item-quantity' ).val( maxQuantity );
            } else if ( currentQty <= 0 ) {
                $item.find( '.item-quantity' ).val( 1 );
            }

            this.refreshDom();
            this.unblockItems();
        };

        this.setWeight = function( weight ) {
            this.getShipment().find( '#shipment-weight-' + this.getId() ).attr( 'placeholder', weight );
        };

        this.setLength = function( length ) {
            this.getShipment().find( '#shipment-length-' + this.getId() ).attr( 'placeholder', length );
        };

        this.setWidth = function( width ) {
            this.getShipment().find( '#shipment-width-' + this.getId() ).attr( 'placeholder', width );
        };

        this.setHeight = function( height ) {
            this.getShipment().find( '#shipment-height-' + this.getId() ).attr( 'placeholder', height );
        };

        this.setTotalWeight = function( weight ) {

        };

        this.setIsEditable = function( isEditable ) {
            var root = this;

            if ( typeof isEditable !== "boolean" ) {
                isEditable = true;
            }

            this.vars.isEditable = isEditable === true;

            if ( ! this.vars.isEditable ) {
                this.getShipment().removeClass( 'is-editable' );
                this.getShipment().addClass( 'is-locked' );

                // Disable inputs
                this.getShipmentContent().find( '.remove-shipment-item ' ).hide();
                this.getShipmentContent().find( '.shipment-item-actions' ).hide();
                this.getShipmentContent().find( ':input:not([type=hidden])' ).prop( 'disabled', true );

                $.each( this.vars.params.shipment_locked_excluded_fields, function( i, field ) {
                    root.getShipmentContent().find( ':input[name^=shipment_' + field + ']' ).prop( 'disabled', false );
                });

            } else {
                this.getShipment().addClass( 'is-editable' );
                this.getShipment().removeClass( 'is-locked' );

                // Disable inputs
                this.getShipmentContent().find( '.remove-shipment-item ' ).show();
                this.getShipmentContent().find( '.shipment-item-actions' ).show();
                this.getShipmentContent().find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
            }
        };

        this.setNeedsItems = function( needsItems ) {
            if ( typeof needsItems !== "boolean" ) {
                needsItems = true;
            }

            this.vars.needsItems = needsItems === true;

            if ( ! this.vars.needsItems ) {
                this.getShipment().removeClass( 'needs-items' );
            } else {
                this.getShipment().addClass( 'needs-items' );
            }
        };

        this.onSyncItems = function() {
            this.syncItems();

            return false;
        };

        this.syncItems = function() {
            this.blockItems();

            var params = {
                'action'     : 'woocommerce_stc_sync_shipment_items',
                'shipment_id': this.getId()
            };

            shipments.admin.shipments.doAjax( params, this.onSyncItemsSuccess.bind( this ), this.onSyncItemsError.bind( this ) );
        };

        this.onSyncItemsSuccess = function( data ) {
            this.unblockItems();
        };

        this.onSyncItemsError = function( data ) {
            this.unblockItems();
        };

        this.onAddItem = function( e, adminShipmentModal ) {
            adminShipmentModal.doAjax( {
                'action'       : 'woocommerce_stc_get_available_shipment_items',
                'reference_id' : adminShipmentModal.reference_id,
                'security'     : shipments.admin.shipments.getParams().edit_shipments_nonce
            }, this.onLoadedItemsSuccess.bind( this ) );

            return false;
        };

        this.onAddedItem = function( e, data ) {
            this.getShipmentContent().find( '.shipment-item-list' ).append( data.new_item );
            this.refreshDom();

            return false;
        };

        this.onLoadedItemsSuccess = function( e, data, adminShipmentModal ) {
            $select   = adminShipmentModal.$modal.find( 'select#wc-stc-shipment-add-items-select' );
            $quantity = adminShipmentModal.$modal.find( 'input#wc-stc-shipment-add-items-quantity' );

            $( document.body ).on( 'change', 'select#wc-stc-shipment-add-items-select', function() {
                var $selected = $( this ).find( 'option:selected' );

                $quantity.val( $selected.data( 'max-quantity' ) );
                $quantity.prop( 'max', $selected.data( 'max-quantity' ) );
            });

            $select.trigger( 'change' );
        };

        this.addItem = function( orderItemId, quantity ) {
            quantity = quantity || 1;

            this.blockItems();

            var params = {
                'action'           : 'woocommerce_stc_add_shipment_item',
                'shipment_id'      : this.getId(),
                'original_item_id' : orderItemId,
                'quantity'         : quantity
            };

            shipments.admin.shipments.doAjax( params, this.onAddItemSuccess.bind( this ), this.onAddItemError.bind( this ) );
        };

        this.addReturn = function( items ) {
            this.block();

            var params = {
                'action'       : 'woocommerce_stc_add_shipment_return',
                'shipment_id'  : this.getId()
            };

            $.extend( params, items );

            shipments.admin.shipments.doAjax( params, this.onAddReturnSuccess.bind( this ), this.onAddReturnError.bind( this ) );
        };

        this.onAddReturnSuccess = function( data ) {
            this.getShipment().find( '.shipment-return-list' ).append( data.new_shipment );

            this.refreshDom();
            shipments.admin.shipments.initShipments();

            this.unblock();
        };

        this.onAddReturnError = function( data ) {
            this.unblock();
        };

        this.onAddItemError = function( data ) {
            this.unblockItems();
        };

        this.onAddItemSuccess = function( data ) {
            this.refreshDom();
            this.unblockItems();
        };

        this.onRemoveItem = function( e ) {
            var $delete = $( e.target ),
                $item   = $delete.parents( '.shipment-item' ),
                itemId  = $item.data( 'id' );

            if ( $item.length > 0 ) {
                this.removeItem( itemId );
            }

            return false;
        };

        this.blockItems = function() {
            this.getShipmentContent().find( '.shipment-items' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        };

        this.block = function() {
            this.getShipment().block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        };

        this.unblockItems = function() {
            this.getShipmentContent().find( '.shipment-items' ).unblock();
        };

        this.unblock = function() {
            this.getShipment().unblock();
        };

        this.removeItem = function( itemId ) {
            var $item = this.getShipment().find( '.shipment-item[data-id="' + itemId + '"]' );

            var params = {
                'action'       : 'woocommerce_stc_remove_shipment_item',
                'shipment_id'  : this.getId(),
                'item_id'      : itemId
            };

            this.blockItems();

            shipments.admin.shipments.doAjax( params, this.onRemoveItemSuccess.bind( this ) );
        };

        this.onRemoveItemSuccess = function( data ) {
            var $item = this.getShipment().find( '.shipment-item[data-id="' + data['item_id'] + '"]' );

            if ( $item.length > 0 ) {
                $item.slideUp( 150, function() {
                    if ( $item.hasClass( 'shipment-item-is-parent' ) ) {
                        $children = $item.parents( '.shipment-item-list' ).find( '.shipment-item-parent-' + data['item_id'] );

                        $children.each( function( $child ) {
                            $( this ) .remove();
                        } );
                    }

                    $item.remove();
                });
            }

            this.unblockItems();
        };

        this.getId = function() {
            return this.vars.id;
        };

        /*
         * Pass options when class instantiated
         */
        this.construct( shipmentId );
    };

})( jQuery, window.shiptastic );
