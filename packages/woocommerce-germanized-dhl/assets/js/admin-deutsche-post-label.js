window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.dhl_post_label = {

        params: {},

        init: function () {
            var self    = admin.dhl_post_label;
            self.params = wc_gzd_admin_deutsche_post_label_params;

            $( document ).on( 'change', '#deutsche_post_label_dhl_product, .wc-gzd-shipment-im-additional-services :input', self.onRefreshPreview );
            $( document.body ).on( 'wc_gzd_shipment_label_after_init', self.onInit );
        },

        onInit: function() {
            var self = admin.dhl_post_label;

            if ( $( '#deutsche_post_label_dhl_product' ).length > 0 ) {
                self.refreshProductData();
            }
        },

        getSelectedAdditionalServices: function() {
            var selectedIds = $( ".wc-gzd-shipment-im-additional-services :input:checked" ).map( function () {
                return $( this ).val();
            }).get();

            return selectedIds;
        },

        onRefreshPreview: function() {
            var self      = admin.dhl_post_label,
                backbone = germanized.admin.shipment_label_backbone.backbone,
                params   = {},
                $wrapper = $( '.wc-gzd-shipment-create-label' );

            params['security']          = self.params.refresh_label_preview_nonce;
            params['product_id']        = self.getProductId();
            params['selected_services'] = self.getSelectedAdditionalServices();
            params['action']            = 'woocommerce_gzd_dhl_refresh_deutsche_post_label_preview';

            backbone.doAjax( params, $wrapper, self.onPreviewSuccess );
        },

        onPreviewSuccess: function( data ) {
            var self         = admin.dhl_post_label,
                $wrapper     = $( '.wc-gzd-dhl-im-product-data .col-preview' ),
                $img_wrapper = $( '.wc-gzd-dhl-im-product-data' ).find( '.image-preview' );

            if ( data.is_wp_int ) {
                $wrapper.parents( '.wc-gzd-shipment-create-label' ).find( '.wc-gzd-shipment-im-page-format' ).hide();
            } else {
                $wrapper.parents( '.wc-gzd-shipment-create-label' ).find( '.wc-gzd-shipment-im-page-format' ).show();
            }

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

                $img_wrapper.find( '.stamp-preview' ).attr('src', data.preview_url ).load( function() {
                    $wrapper.unblock();
                    $( this ).show();
                });
            } else {
                $img_wrapper.html( '' );
            }
        },

        refreshProductData: function() {
            var self = admin.dhl_post_label;

            self.onRefreshPreview();
        },

        getProductId: function() {
            return $( '#deutsche_post_label_dhl_product' ).val();
        },

        replaceProductData: function( productData ) {
            var self = admin.dhl_post_label,
                $wrapper = $( '.wc-gzd-shipment-create-label' ).find( '.wc-gzd-dhl-im-product-data' );

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
        germanized.admin.dhl_post_label.init();
    });

})( jQuery, window.germanized.admin );