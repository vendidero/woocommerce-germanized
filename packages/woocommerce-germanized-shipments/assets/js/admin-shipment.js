window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    $.GermanizedShipment = function( shipmentId ) {

        /*
         * Variables accessible
         * in the class
         */
        this.vars = {
            $shipment    : false,
            params       : {},
            id           : '',
            isEditable   : true,
            needsItems   : true
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
            this.vars.params    = germanized.admin.shipments.getParams();

            this.refreshDom();

            $( document.body )
                .on( 'wc_backbone_modal_loaded', this.backbone.init.bind( this ) )
                .on( 'wc_backbone_modal_response', this.backbone.response.bind( this ) );
        };

        this.refreshDom = function() {
            this.vars.$shipment  = $( '#order-shipments-list' ).find( '#shipment-' + this.getId() );

            this.setNeedsItems( this.vars.$shipment.hasClass( 'needs-items' ) );
            this.setIsEditable( this.vars.$shipment.hasClass( 'is-editable' ) );
            this.onChangeProvider();

            $( '#shipment-' + this.vars.id + ' #shipment-items-' + this.vars.id ).off();
            $( '#shipment-' + this.vars.id + ' #shipment-footer-' + this.vars.id ).off();
            $( '#shipment-' + this.vars.id + ' #shipment-shipping-provider-' + this.vars.id ).off();
            $( '#shipment-' + this.vars.id + ' #shipment-packaging-' + this.vars.id ).off();
            $( '#shipment-' + this.vars.id + ' .wc-gzd-shipment-label' ).off();

            $( '#shipment-' + this.vars.id + ' #shipment-shipping-provider-' + this.vars.id ).on( 'change', this.onChangeProvider.bind( this ) );
            $( '#shipment-' + this.vars.id + ' #shipment-packaging-' + this.vars.id ).on( 'change', this.refreshDimensions.bind( this ) );

            $( '#shipment-' + this.vars.id + ' #shipment-items-' + this.vars.id )
                .on( 'change', '.item-quantity', this.onChangeQuantity.bind( this ) )
                .on( 'click', 'a.remove-shipment-item', this.onRemoveItem.bind( this ) )
                .on( 'click', 'a.add-shipment-item', this.onAddItem.bind( this ) )
                .on( 'click', 'a.sync-shipment-items', this.onSyncItems.bind( this ) );

            $( '#shipment-' + this.vars.id + ' #shipment-footer-' + this.vars.id )
                .on( 'click', '.send-return-shipment-notification', this.onSendReturnNotification.bind( this ) )
                .on( 'click', '.confirm-return-shipment', this.onConfirmReturnRequest.bind( this ) );

            $( '#shipment-' + this.vars.id + ' .wc-gzd-shipment-label' )
                .on( 'click', '.create-shipment-label:not(.disabled)', this.onCreateLabel.bind( this ) )
                .on( 'click', '.remove-shipment-label', this.onRemoveLabel.bind( this ) );
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
            this.getShipmentContent().find( '.wc-gzd-shipment-packaging-wrapper' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        };

        this.unblockPackaging = function() {
            this.getShipmentContent().find( '.wc-gzd-shipment-packaging-wrapper' ).unblock();
        };

        this.refreshPackaging = function() {
            var params = {
                'action'       : 'woocommerce_gzd_refresh_shipment_packaging',
                'shipment_id'  : this.getId(),
                'security'     : germanized.admin.shipments.getParams().refresh_packaging_nonce
            };

            this.blockPackaging();
            germanized.admin.shipments.doAjax( params, this.unblockPackaging.bind( this ), this.unblockPackaging.bind( this ) );
        };

        this.onSendReturnNotification = function() {
            var params = {
                'action'       : 'woocommerce_gzd_send_return_shipment_notification_email',
                'shipment_id'  : this.getId(),
                'security'     : germanized.admin.shipments.getParams().send_return_notification_nonce
            };

            this.block();
            germanized.admin.shipments.doAjax( params, this.unblock.bind( this ), this.unblock.bind( this ) );

            return false;
        };

        this.onConfirmReturnRequest = function() {
            var params = {
                'action'       : 'woocommerce_gzd_confirm_return_request',
                'shipment_id'  : this.getId(),
                'security'     : germanized.admin.shipments.getParams().confirm_return_request_nonce
            };

            this.block();
            germanized.admin.shipments.doAjax( params, this.unblock.bind( this ), this.unblock.bind( this ) );

            return false;
        };

        this.onRemoveLabel = function() {
            var answer = window.confirm( germanized.admin.shipments.getParams().i18n_remove_label_notice );

            if ( answer ) {
                this.removeLabel();
            }

            return false;
        };

        this.removeLabel = function() {
            var params = {
                'action'       : 'woocommerce_gzd_remove_shipment_label',
                'shipment_id'  : this.getId(),
                'security'     : germanized.admin.shipments.getParams().remove_label_nonce
            };

            this.block();
            germanized.admin.shipments.doAjax( params, this.unblock.bind( this ), this.unblock.bind( this ) );
        };

        this.onCreateLabel = function() {
            var $shipment = this.getShipment();

            $shipment.WCBackboneModal({
                template: 'wc-gzd-modal-create-shipment-label-' + this.getId()
            });

            return false;
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
                'action'       : 'woocommerce_gzd_limit_shipment_item_quantity',
                'shipment_id'  : this.getId(),
                'item_id'      : itemId,
                'quantity'     : newQuantity
            };

            germanized.admin.shipments.doAjax( params, this.onChangeQuantitySuccess.bind( this ) );
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
                'action'     : 'woocommerce_gzd_sync_shipment_items',
                'shipment_id': this.getId()
            };

            germanized.admin.shipments.doAjax( params, this.onSyncItemsSuccess.bind( this ), this.onSyncItemsError.bind( this ) );
        };

        this.onSyncItemsSuccess = function( data ) {
            this.unblockItems();
        };

        this.onSyncItemsError = function( data ) {
            this.unblockItems();
        };

        this.onAddItem = function() {

            this.getShipment().WCBackboneModal({
                template: 'wc-gzd-modal-add-shipment-item-' + this.getId()
            });

            return false;
        };

        this.addItem = function( orderItemId, quantity ) {
            quantity = quantity || 1;

            this.blockItems();

            var params = {
                'action'           : 'woocommerce_gzd_add_shipment_item',
                'shipment_id'      : this.getId(),
                'original_item_id' : orderItemId,
                'quantity'         : quantity
            };

            germanized.admin.shipments.doAjax( params, this.onAddItemSuccess.bind( this ), this.onAddItemError.bind( this ) );
        };

        this.addReturn = function( items ) {
            this.block();

            var params = {
                'action'       : 'woocommerce_gzd_add_shipment_return',
                'shipment_id'  : this.getId()
            };

            $.extend( params, items );

            germanized.admin.shipments.doAjax( params, this.onAddReturnSuccess.bind( this ), this.onAddReturnError.bind( this ) );
        };

        this.onAddReturnSuccess = function( data ) {
            this.getShipment().find( '.shipment-return-list' ).append( data.new_shipment );

            this.refreshDom();
            germanized.admin.shipments.initShipments();

            this.unblock();
        };

        this.onAddReturnError = function( data ) {
            this.unblock();
        };

        this.onAddItemError = function( data ) {
            this.unblockItems();
        };

        this.onAddItemSuccess = function( data ) {
            this.getShipmentContent().find( '.shipment-item-list' ).append( data.new_item );

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
                'action'       : 'woocommerce_gzd_remove_shipment_item',
                'shipment_id'  : this.getId(),
                'item_id'      : itemId
            };

            this.blockItems();

            germanized.admin.shipments.doAjax( params, this.onRemoveItemSuccess.bind( this ) );
        };

        this.onRemoveItemSuccess = function( data ) {
            var $item = this.getShipment().find( '.shipment-item[data-id="' + data['item_id'] + '"]' );

            if ( $item.length > 0 ) {
                $item.slideUp( 150, function() {
                    $( this ).remove();
                });
            }

            this.unblockItems();
        };

        this.getId = function() {
            return this.vars.id;
        };

        this.backbone = {

            onAddItemSuccess: function( data ) {
                $select   = $( 'select#wc-gzd-shipment-add-items-select' );
                $quantity = $( 'input#wc-gzd-shipment-add-items-quantity' );

                $quantity.val( 1 );

                $.each( data.items, function( id, item ) {
                    $select.append( '<option value="' + id + '">' + item.name + '</option>' );
                    $quantity.data( 'max-quantity-' + id, item.max_quantity );
                });

                $( '.wc-backbone-modal-content article' ).unblock();

                $( document.body ).on( 'change', 'input#wc-gzd-shipment-add-items-quantity', function() {
                    var item_id  = $select.val(),
                        quantity = $( this ).val();

                    if ( $quantity.data( 'max-quantity-' + item_id ) ) {
                        var maxQuantity = $quantity.data( 'max-quantity-' + item_id );

                        if ( quantity > maxQuantity ) {
                            $quantity.val( maxQuantity );
                        }
                    }
                });
            },

            init: function ( e, target ) {
                var id = this.getId();

                if ( ( 'wc-gzd-modal-add-shipment-item-' + id ) === target ) {

                    $( '.wc-backbone-modal-content article' ).block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });

                    germanized.admin.shipments.doAjax( {
                        'action'     : 'woocommerce_gzd_get_available_shipment_items',
                        'shipment_id': id
                    }, this.backbone.onAddItemSuccess.bind( this ) );

                    return false;
                }
            },

            response: function ( e, target, data ) {
                var id = this.getId();

                if ( ( 'wc-gzd-modal-add-shipment-item-' + id ) === target ) {
                    this.addItem( data.item_id, data.item_qty );
                }
            }
        };

        /*
         * Pass options when class instantiated
         */
        this.construct( shipmentId );
    };

})( jQuery, window.germanized.admin );
