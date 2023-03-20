window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.shipment_label_backbone = {

        params: {},

        init: function () {
            var self    = germanized.admin.shipment_label_backbone;
            self.params = wc_gzd_admin_shipment_label_backbone_params;

            $( document )
                .on( 'click', '.germanized-create-label .show-further-services', self.onExpandServices )
                .on( 'click', '.germanized-create-label .show-fewer-services', self.onHideServices )
                .on( 'click', '.germanized-create-label .notice .notice-dismiss', self.onRemoveNotice )
                .on( 'change', '.germanized-create-label #product_id', self.onChangeProductId )
                .on( 'change', '.germanized-create-label :input:visible[id]', self.onChangeField );

            $( document.body )
                .on( 'wc_backbone_modal_loaded', self.backbone.init )
                .on( 'wc_backbone_modal_response', self.backbone.response );
        },

        parseFieldId: function( fieldId ) {
            return fieldId.replace( '[', '_' ).replace( ']', '' );
        },

        onChangeField: function() {
            var self     = germanized.admin.shipment_label_backbone,
                $wrapper = $( '.germanized-create-label' ),
                fieldId  = self.parseFieldId( $( this ).attr( 'id' ) ),
                val      = $( this ).val();

            /**
             * Show or hide a wrapper based on checkbox status
             */
            if ( $( this ).hasClass( 'show-if-trigger' ) ) {
                var $show = $wrapper.find( $( this ).data( 'show-if' ) );

                if ( $show.length > 0 ) {
                    if ( $( this ) .is( ':checked' ) ) {
                        $show.show();
                    } else {
                        $show.hide();
                    }

                    $( document.body ).trigger( 'wc_gzd_shipment_label_show_if' );
                }
            } else {
                $wrapper.find( ':input[data-show-if-' + fieldId + ']' ).parents( '.form-field' ).hide();

                if ( $( this ).is( ':checkbox' ) ) {
                    if ( $( this ).is( ':checked' ) ) {
                        $wrapper.find( ':input[data-show-if-' + fieldId + ']' ).parents( '.form-field' ).show();
                    }
                } else {
                    $wrapper.find( ':input[data-show-if-' + fieldId + '*="' + val + '"]' ).parents( '.form-field' ).show();
                }
            }
        },

        onChangeProductId: function() {
            var self = germanized.admin.shipment_label_backbone;

            self.showOrHideByProduct( $( this ).val() );
        },

        showOrHideByProduct: function( productId ) {
            var $wrapper  = $( '.germanized-create-label' ),
                $fields   = $wrapper.find( 'p.form-field :input[data-products-supported]' );

            $fields.each( function() {
                var $field    = $( this ),
                    isHidden  = true,
                    supported = $field.data( 'products-supported' ).split( ',' );

                if ( $.inArray( productId, supported ) !== -1 ) {
                    isHidden = false;
                }

                if ( isHidden ) {
                    $field.parents( '.form-field' ).hide();
                } else {
                    $field.parents( '.form-field' ).show();
                }
            } );
        },

        onRemoveNotice: function() {
            $( this ).parents( '.notice' ).slideUp( 150, function() {
                $( this ).remove();
            });
        },

        onExpandServices: function() {
            var $wrapper  = $( this ).parents( '.germanized-create-label' ).find( '.show-if-further-services' ),
                $trigger  = $( this ).parents( '.show-services-trigger' );

            $wrapper.show();
            $wrapper.find( ':input:visible' ).trigger( 'change' );

            $trigger.find( '.show-further-services' ).hide();
            $trigger.find( '.show-fewer-services' ).show();

            return false;
        },

        onHideServices: function() {
            var $wrapper  = $( this ).parents( '.germanized-create-label' ).find( '.show-if-further-services' ),
                $trigger  = $( this ).parents( '.show-services-trigger' );

            $wrapper.hide();

            $trigger.find( '.show-further-services' ).show();
            $trigger.find( '.show-fewer-services' ).hide();

            return false;
        },

        backbone: {

            getShipmentId: function( target ) {
                return target.replace( /^\D+/g, '' );
            },

            init: function( e, target ) {
                if ( target.indexOf( 'wc-gzd-modal-create-shipment-label' ) !== -1 ) {
                    var self         = germanized.admin.shipment_label_backbone.backbone,
                        backbone     = germanized.admin.shipment_label_backbone,
                        $modal       = $( '.germanized-create-label' ).parents( '.wc-backbone-modal-content' ),
                        shipmentId   = self.getShipmentId( target ),
                        params       = {
                            'action'     : 'woocommerce_gzd_create_shipment_label_form',
                            'shipment_id': shipmentId,
                            'security'   : backbone.params.create_label_form_nonce
                        };

                    self.doAjax( params, $modal, self.onInitForm );
                }
            },

            onAjaxSuccess: function( data ) {

            },

            onAjaxError: function( data ) {

            },

            doAjax: function( params, $wrapper, cSuccess, cError  ) {
                var self     = germanized.admin.shipment_label_backbone.backbone,
                    backbone = germanized.admin.shipment_label_backbone,
                    $content = $wrapper.find( '.germanized-create-label' );

                cSuccess = cSuccess || self.onAjaxSuccess;
                cError   = cError || self.onAjaxError;

                if ( ! params.hasOwnProperty( 'shipment_id' ) ) {
                    params['shipment_id'] = $( '#wc-gzd-shipment-label-admin-shipment-id' ).val();
                }

                $wrapper.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });

                $wrapper.find( '.notice-wrapper' ).empty();

                $.ajax({
                    type: "POST",
                    url:  backbone.params.ajax_url,
                    data: params,
                    success: function( data ) {
                        if ( data.success ) {
                            if ( data.fragments ) {
                                $.each( data.fragments, function ( key, value ) {
                                    $( key ).replaceWith( value );
                                });
                            }
                            $wrapper.unblock();
                            cSuccess.apply( $content, [ data ] );
                        } else {
                            $wrapper.unblock();
                            cError.apply( $content, [ data ] );

                            self.printNotices( $content, data.messages );

                            // ScrollTo top of modal
                            $content.animate({
                                scrollTop: 0
                            }, 500 );
                        }
                    },
                    error: function( data ) {},
                    dataType: 'json'
                });
            },

            onInitForm: function( data ) {
                var self       = germanized.admin.shipment_label_backbone.backbone,
                    shipmentId = data['shipment_id'],
                    $modal     = $( '.germanized-create-label' );

                $( document.body ).trigger( 'wc-enhanced-select-init' );
                $( document.body ).trigger( 'wc-init-datepickers' );
                $( document.body ).trigger( 'init_tooltips' );
                $( document.body ).trigger( 'wc_gzd_shipment_label_after_init' );

                $modal.find( ':input:visible' ).trigger( 'change' );

                $modal.parents( '.wc-backbone-modal' ).on( 'click', '#btn-ok', { 'shipmentId': shipmentId }, self.onSubmit );
                $modal.parents( '.wc-backbone-modal' ).on( 'touchstart', '#btn-ok', { 'shipmentId': shipmentId }, self.onSubmit );
                $modal.parents( '.wc-backbone-modal' ).on( 'keydown', { 'shipmentId': shipmentId }, self.onKeyDown );
            },

            getFormData: function( $form ) {
                var data = {}
                    hideService = false;

                /**
                 * Service data should always be transmitted, even though not shown
                 */
                if ( ! $form.find( '.show-if-further-services' ).is( ':visible' ) ) {
                    $form.find( '.show-if-further-services' ).show();
                    hideService = true;
                }

                /**
                 * Do only transmit data of visible label fields
                 */
                $.each( $form.find( ':input:visible' ).serializeArray(), function( index, item ) {
                    if ( item.name.indexOf( '[]' ) !== -1 ) {
                        item.name = item.name.replace( '[]', '' );
                        data[ item.name ] = $.makeArray( data[ item.name ] );
                        data[ item.name ].push( item.value );
                    } else {
                        data[ item.name ] = item.value;
                    }
                });

                if ( hideService ) {
                    $form.find( '.show-if-further-services' ).hide();
                }

                return data;
            },

            printNotices: function( $wrapper, messages ) {
                var self = germanized.admin.shipment_label_backbone.backbone;

                $.each( messages, function ( type, typeMessages ) {
                    $.each( typeMessages, function ( i, message ) {
                        self.addNotice( message, ( 'soft' === type ? 'warning' : type ), $wrapper );
                    });
                });
            },

            onSubmitSuccess: function( data ) {
                var self       = germanized.admin.shipment_label_backbone.backbone,
                    $modal     = $( this ).parents( '.wc-backbone-modal-content' ),
                    shipmentId = data['shipment_id'],
                    $content   = $modal.find( '.germanized-create-label' )

                if ( data.messages.hasOwnProperty( 'error' ) || data.messages.hasOwnProperty( 'soft' ) ) {
                    self.printNotices( $content, data.messages );
                    $modal.find( 'footer' ).find( '#btn-ok' ).addClass( 'modal-close' ).attr( 'id', 'btn-close' ).text( germanized.admin.shipment_label_backbone.params.i18n_modal_close );
                } else {
                    $modal.find( '.modal-close' ).trigger( 'click' );
                }

                if ( $( 'div#shipment-' + shipmentId ).length > 0 ) {
                    germanized.admin.shipments.initShipment( shipmentId );
                }
            },

            onKeyDown: function( e ) {
                var self   = germanized.admin.shipment_label_backbone.backbone,
                    button = e.keyCode || e.which;

                // Enter key
                if ( 13 === button && ! ( e.target.tagName && ( e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea' ) ) ) {
                    self.onSubmit.apply( $( this ).find( 'button#btn-ok' ), [ e ] );
                }
            },

            onSubmit: function( e ) {
                var self       = germanized.admin.shipment_label_backbone.backbone,
                    backbone   = germanized.admin.shipment_label_backbone,
                    shipmentId = e.data.shipmentId,
                    $modal     = $( this ).parents( '.wc-backbone-modal-content' ),
                    $content   = $modal.find( '.germanized-create-label' ),
                    $form      = $content.find( 'form' ),
                    params     = self.getFormData( $form );

                params['security']    = backbone.params.create_label_nonce;
                params['shipment_id'] = shipmentId;
                params['action']      = 'woocommerce_gzd_create_shipment_label';

                self.doAjax( params, $modal, self.onSubmitSuccess );

                e.preventDefault();
                e.stopPropagation();
            },

            addNotice: function( message, noticeType, $wrapper ) {
                $wrapper.find( '.notice-wrapper' ).append( '<div class="notice is-dismissible notice-' + noticeType +'"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
            },

            response: function( e, target, data ) {
                if ( target.indexOf( 'wc-gzd-modal-create-shipment-label' ) !== -1 ) {}
            }
        }
    };

    $( document ).ready( function() {
        germanized.admin.shipment_label_backbone.init();
    });

})( jQuery, window.germanized.admin );
