window.shiptastic = window.shiptastic || {};
window.shiptastic.admin = window.shiptastic.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.dhl_post_label = {
        params: {},

        init: function () {
            var self    = admin.dhl_post_label;
            self.params = wc_shiptastic_dhl_admin_deutsche_post_label_params;

            $( document.body ).on( 'wc_shiptastic_admin_shipment_modal_after_load_success', self.onLoadLabelModal )
        },

        onLoadLabelModal: function( e, data, modal ) {
            var self = admin.dhl_post_label;

            modal.$modal.off( 'change.stc-dp-fields' );
            modal.$modal.on( 'change.stc-dp-fields', '#wc-stc-shipment-label-admin-fields-deutsche_post #product_id', { adminShipmentModal: modal }, self.onChangeProduct );
            modal.$modal.on( 'change.stc-dp-fields', '#wc-stc-shipment-label-admin-fields-deutsche_post #product_id, #wc-stc-shipment-label-admin-fields-deutsche_post #wc-stc-shipment-label-wrapper-additional-services :input', { adminShipmentModal: modal }, self.onRefreshPreview );

            if ( modal.$modal.find( '#wc-stc-shipment-label-admin-fields-deutsche_post' ).length > 0 ) {
                var event = new $.Event( 'change' );

                event.data = {
                    'adminShipmentModal': modal
                }

                self.onRefreshPreview( event );
            }
        },

        getSelectedAdditionalServices: function() {
            return $( "#wc-stc-shipment-label-wrapper-additional-services :input:checked" ).map( function() {
                return $( this ).attr( 'name' ).replace( 'service_', '' );
            }).get();
        },

        onChangeProduct: function( event ) {
            // Reset services before submitting preview to prevent invalid services being passed.
            $( "#wc-stc-shipment-label-wrapper-additional-services :input:checked" ).prop( 'checked', false );
        },

        onRefreshPreview: function( event ) {
            var self     = admin.dhl_post_label,
                modal    = event.data.adminShipmentModal,
                params   = {};

            params['security']          = self.params.refresh_label_preview_nonce;
            params['product_id']        = self.getProductId();
            params['selected_services'] = self.getSelectedAdditionalServices();
            params['action']            = 'woocommerce_stc_dhl_refresh_deutsche_post_label_preview';

            modal.doAjax( params, self.onPreviewSuccess );
        },

        onPreviewSuccess: function( data ) {
            var self         = admin.dhl_post_label,
                $wrapper     = $( '.wc-stc-dhl-im-product-data .col-preview' ),
                $img_wrapper = $( '.wc-stc-dhl-im-product-data' ).find( '.image-preview' );

            if ( data.preview_url ) {
                $wrapper.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });

                if ( $img_wrapper.find( '.stamp-preview' ).length <= 0 ) {
                    $img_wrapper.append( '<img class="stamp-preview" style="display: none;" />' );
                }

                self.replaceProductData( data.preview_data );

                $img_wrapper.find( '.stamp-preview' ).prop('src', data.preview_url ).on( 'load', function() {
                    $wrapper.unblock();
                    $( this ).show();
                });
            } else {
                $img_wrapper.html( '' );
                self.replaceProductData( {} );
            }
        },

        getProductId: function() {
            return $( '#wc-stc-shipment-label-admin-fields-deutsche_post #product_id' ).val();
        },

        replaceProductData: function( productData ) {
            var self = admin.dhl_post_label,
                $wrapper = $( '.wc-stc-shipment-create-label' ).find( '.wc-stc-dhl-im-product-data' );

            $wrapper.find( '.data-placeholder' ).html( '' );

            $wrapper.find( '.data-placeholder' ).each( function() {
                var replaceKey = $( this ).data( 'replace' );

                if ( productData.hasOwnProperty( replaceKey ) ) {
                    $( this ).html( productData[ replaceKey ] );
                    $( this ).show();
                } else {
                    $( this ).hide();
                }
            } );
        }
    };

    $( document ).ready( function() {
        shiptastic.admin.dhl_post_label.init();
    });

})( jQuery, window.shiptastic.admin );