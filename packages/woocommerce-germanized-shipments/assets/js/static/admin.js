window.shipments = window.shipments || {};
window.shipments.admin = window.shipments.admin || {};

( function( $, shipments ) {
    shipments.admin.shipments_admin = {
        params: {},

        init: function() {
            var self = shipments.admin.shipments_admin;
            self.params = wc_gzd_shipments_admin_params;

            $( document ).on( 'click', 'a.woocommerce-gzd-shipments-input-toggle-trigger', this.onInputToggleClick );
        },

        onInputToggleClick: function() {
            var $toggle   = $( this ).find( 'span.woocommerce-gzd-shipments-input-toggle' ),
                $row      = $toggle.parents( 'fieldset' ),
                $checkbox = $row.find( 'input[type=checkbox]' ).length > 0 ? $row.find( 'input[type=checkbox]' ) : $toggle.parent().nextAll( 'input[type=checkbox]:first' ),
                $enabled  = $toggle.hasClass( 'woocommerce-input-toggle--enabled' );

            $toggle.removeClass( 'woocommerce-input-toggle--enabled' );
            $toggle.removeClass( 'woocommerce-input-toggle--disabled' );

            if ( $enabled ) {
                $checkbox.prop( 'checked', false );
                $toggle.addClass( 'woocommerce-input-toggle--disabled' );
            } else {
                $checkbox.prop( 'checked', true );
                $toggle.addClass( 'woocommerce-input-toggle--enabled' );
            }

            $checkbox.trigger( 'change' );

            return false;
        }
    };

    $( document ).ready( function() {
        shipments.admin.shipments_admin.init();
    });

})( jQuery, window.shipments );