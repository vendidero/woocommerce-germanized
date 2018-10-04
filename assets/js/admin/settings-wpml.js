/* global  */
window.germanized = window.germanized || {};

( function( $, wp, germanized ) {

    germanized.settings_wpml = {
        params: {},

        init: function() {
            this.params = wc_gzd_settings_wpml_params;

            $.each( this.params.options, this.maybeUpdateDesc );
        },

        stripSlashes: function( str ) {
            str = str.replace(/\\'/g, '\'');
            str = str.replace(/\\"/g, '"');
            str = str.replace(/\\0/g, '\0');
            str = str.replace(/\\\\/g, '\\');
            return str;
        },

        maybeUpdateDesc: function( index, id ) {
            var $element = $( id ),
                self     = germanized.settings_wpml;

            if ( $element.length > 0 ) {
                if ( ! $element.data( 'wpml-notice' ) && $( id + '-attribute-container' ).length > 0 ) {
                    $element = $( id + '-attribute-container' );
                }

                if ( $element.data( 'wpml-notice' ) ) {
                    var str = $element.data( 'wpml-notice' );
                    str = self.stripSlashes( str );

                    $element.after( '<span class="wc-gzd-wpml-notice">' + str + '</span>' );
                }
            }
        }
    };

    $( document ).ready( function() {
        germanized.settings_wpml.init();
    });

})( jQuery, wp, window.germanized );
